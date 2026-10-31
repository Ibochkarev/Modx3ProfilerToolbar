<?php
/**
 * Main profiler orchestrator: start/stop, event tracking, register profilers.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Service;

class ProfilerService
{
    /** @var \modX */
    protected $modx;

    /** @var bool */
    protected $enabled = false;
    /** @var string */
    protected $cliOutput = 'console';
    /** @var string */
    protected $cliOutputFile;
    /** @var string */
    protected $cliOutputFormat = 'json';

    /** @var int */
    protected $samplingRate = 100;

    /** @var string|null */
    protected $requestId;

    /** @var float */
    protected $startTime;

    /** @var float */
    protected $ttfbTime;

    /** @var int */
    protected $startMemory;

    /** @var array */
    protected $events = [];

    /** @var array Tag metrics grouped by hash (from parser wrapper) */
    protected $tags = [];

    /** @var array Component traces grouped by hash (from SnippetProcessWrapper / ChunkProcessWrapper) */
    protected $componentTraces = [];

    /** @var array Temporary storage for snippet/chunk start times */
    public $_snippetStarts = [];

    /** @var array */
    protected $collectors = [];

    public function __construct($modx)
    {
        $this->modx = $modx;
        $this->enabled = (bool) $modx->getOption('modx3profilertoolbar.enabled', null, false);
        $this->samplingRate = (int) $modx->getOption('modx3profilertoolbar.sampling_rate', null, 100);
        $this->cliOutput = $modx->getOption('modx3profilertoolbar.cli_output', null, 'console');
        $this->cliOutputFile = $modx->getOption('modx3profilertoolbar.cli_output_file', null, '');
        $this->cliOutputFormat = $modx->getOption('modx3profilertoolbar.cli_output_format', null, 'json');
    }

    public function shouldProfile(): bool
    {
        if (!$this->enabled) {
            return false;
        }
        if ($this->samplingRate < 100) {
            if (mt_rand(1, 100) > $this->samplingRate) {
                return false;
            }
        }
        if (php_sapi_name() === 'cli' && !$this->modx->getOption('modx3profilertoolbar.cli_enabled', null, false)) {
            return false;
        }
        return true;
    }

    public function start(): void
    {
        if (!$this->shouldProfile()) {
            return;
        }
        $this->requestId = $this->generateRequestId();
        $this->startTime = microtime(true);
        $this->ttfbTime = $this->startTime; // TTFB: time to first byte (approximated as profiler start)
        $this->startMemory = memory_get_usage(true);
        $this->registerProfilers();
        $this->trackEvent('OnMODXInit', 'start');
    }

    protected function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function registerProfilers(): void
    {
        // SQL profiler через PDO
        $sqlProfiler = new \Modx3ProfilerToolbar\Profiler\SQLProfiler($this->modx, $this);
        $sqlProfiler->register();
        $this->collectors['sql'] = $sqlProfiler;

        // Регистрируем пустые collectors для структуры данных
        $this->collectors['snippet'] = new \Modx3ProfilerToolbar\Profiler\SnippetProfiler($this->modx, $this);
        $this->collectors['plugin'] = new \Modx3ProfilerToolbar\Profiler\PluginProfiler($this->modx, $this);
        $this->collectors['chunk'] = new \Modx3ProfilerToolbar\Profiler\ChunkProfiler($this->modx, $this);
    }

    public function trackEvent(string $name, string $phase = 'complete', array $data = []): void
    {
        $time = microtime(true);
        $memory = memory_get_usage(true);

        if ($phase === 'start') {
            $this->events[$name] = [
                'name' => $name,
                'start' => $time,
                'start_memory' => $memory,
                'data' => $data,
            ];
        } elseif ($phase === 'end' && isset($this->events[$name])) {
            $this->events[$name]['end'] = $time;
            $this->events[$name]['end_memory'] = $memory;
            $this->events[$name]['duration'] = $time - $this->events[$name]['start'];
            $this->events[$name]['memory_delta'] = $memory - $this->events[$name]['start_memory'];
        } else {
            $this->events[$name . '_' . count($this->events)] = [
                'name' => $name,
                'start' => $time,
                'end' => $time,
                'duration' => 0,
                'data' => $data,
            ];
        }
    }

    public function stop(): void
    {
        if (!$this->requestId) {
            return;
        }

        $endTime = microtime(true);

        // Calculate TTFB (time from profiler start to first tracked event / response preparation)
        $ttfb = $this->ttfbTime - $this->startTime;

        // 1) Завершённые вызовы: из $parser->tags (pdoTools пишет туда при втором вызове debugParser)
        if ($this->modx->parser instanceof \debugPdoParser && !empty($this->modx->parser->tags)) {
            foreach ($this->modx->parser->tags as $hash => $tagData) {
                $this->trackTag($tagData['tag'] ?? '', [
                    'type' => $this->detectTagTypeFromString($tagData['tag'] ?? ''),
                    'cached' => false,
                    'parse_time' => (float) ($tagData['parse_time'] ?? 0),
                    'queries' => (int) ($tagData['queries'] ?? 0),
                    'query_time' => (float) ($tagData['query_time'] ?? $tagData['queries_time'] ?? 0),
                    'attempts' => (int) ($tagData['attempts'] ?? 1),
                ]);
            }
        }

        // 2) Незавершённые вызовы: из CoreTools::$tags (первый вызов был, второй не произошёл)
        $this->collectCoreToolsTags($endTime);

        $endMemory = memory_get_usage(true);

        $collectorService = new CollectorService($this->modx, $this);
        $traceData = $collectorService->collect($this->requestId, [
            'start_time' => $this->startTime,
            'end_time' => $endTime,
            'ttfb' => $ttfb,
            'start_memory' => $this->startMemory,
            'end_memory' => $endMemory,
            'events' => $this->events,
            'collectors' => $this->collectors,
        ]);

        $analyzerService = new AnalyzerService($this->modx, $this);
        $analysis = $analyzerService->analyze($traceData);
        $traceData['advisor'] = $analysis['advisor'];
        $traceData['performance_score'] = $analysis['score'];

        // CLI output
        if (php_sapi_name() === 'cli') {
            $this->outputCliTrace($traceData);
            return;
        }

        // Web toolbar injection
        if ($this->modx->context->key === 'web') {
            $toolbarService = new ToolbarService($this->modx, $this);
            $toolbarService->inject($this->requestId, $traceData);
        }

        // Reset profilers for long-running scripts (CLI, workers)
        if (php_sapi_name() === 'cli' && isset($this->collectors['sql'])) {
            $this->collectors['sql']->reset();
        }
    }

    /**
     * Output profiling data when running in CLI mode.
     */
    protected function outputCliTrace(array $traceData): void
    {
        $output = '';
        switch ($this->cliOutputFormat) {
            case 'text':
                $output = $this->formatTraceAsText($traceData);
                break;
            case 'csv':
                $output = $this->formatTraceAsCsv($traceData);
                break;
            case 'json':
            default:
                $output = json_encode($traceData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                break;
        }

        if ($this->cliOutput === 'file' && !empty($this->cliOutputFile)) {
            // Resolve to an absolute path inside MODX core_path for safety
            $corePath = rtrim((string) $this->modx->getOption('core_path'), '/');
            $filePath = $this->cliOutputFile;
            // If path is not absolute, prefix core_path
            if (!preg_match('#^[/\\]#', $filePath)) {
                $filePath = $corePath . '/' . ltrim($filePath, '/');
            }
            // Normalize and prevent directory traversal
            $realDir = realpath(dirname($filePath));
            if ($realDir === false || strpos($realDir, $corePath) !== 0) {
                // Fallback to core_path/logs if validation fails
                $fallbackDir = $corePath . '/components/modx3profilertoolbar/logs';
                @mkdir($fallbackDir, 0755, true);
                $filePath = $fallbackDir . '/' . basename($filePath);
            } else {
                if (!is_dir($realDir)) {
                    @mkdir($realDir, 0755, true);
                }
            }
            file_put_contents($filePath, $output . PHP_EOL);
        } else {
            // default to console output
            echo $output . PHP_EOL;
        }
    }

    /**
     * Helper to format trace data as plain text.
     */
    protected function formatTraceAsText(array $traceData): string
    {
        $lines = [];
        $lines[] = '--- Modx3ProfilerToolbar CLI Trace ---';
        $lines[] = 'Request ID: ' . ($traceData['request_id'] ?? $this->requestId);
        $lines[] = 'Context: ' . ($traceData['context'] ?? 'unknown');
        $lines[] = 'URL: ' . ($traceData['url'] ?? 'N/A');
        $lines[] = 'Method: ' . ($traceData['method'] ?? 'N/A');
        $lines[] = '';
        
        // Metrics
        $metrics = $traceData['metrics'] ?? [];
        $lines[] = 'Metrics:';
        $lines[] = '  Total Time:    ' . round(($metrics['total_time'] ?? 0) * 1000, 2) . ' ms';
        $lines[] = '  PHP Time:      ' . round(($metrics['php_time'] ?? 0) * 1000, 2) . ' ms';
        $lines[] = '  SQL Time:      ' . round(($metrics['sql_time'] ?? 0) * 1000, 2) . ' ms';
        $lines[] = '  Memory Peak:   ' . $this->formatBytes((int) ($metrics['memory_peak'] ?? 0));
        $lines[] = '  SQL Count:     ' . ($traceData['sql_count'] ?? 0);
        $lines[] = '  Snippet Count: ' . ($traceData['snippet_count'] ?? 0);
        $lines[] = '  Chunk Count:   ' . ($traceData['chunk_count'] ?? 0);
        $lines[] = '';
        
        // Performance score
        if (isset($traceData['performance_score'])) {
            $score = $traceData['performance_score'];
            $status = $score >= 80 ? 'Excellent' : ($score >= 50 ? 'Fair' : 'Critical');
            $lines[] = "Performance Score: $score / 100 ($status)";
            $lines[] = '';
        }
        
        // Advisor
        if (!empty($traceData['advisor'])) {
            $lines[] = 'Advisor:';
            foreach ($traceData['advisor'] as $item) {
                $lines[] = '  - ' . ($item['message'] ?? $item);
            }
            $lines[] = '';
        }
        
        // Events
        if (!empty($traceData['events'])) {
            $lines[] = 'Events (' . count($traceData['events']) . '):';
            foreach ($traceData['events'] as $event) {
                $name = $event['name'] ?? 'unknown';
                $duration = (float) ($event['duration'] ?? 0);
                $type = isset($event['type']) ? '[' . $event['type'] . '] ' : '';
                $cached = isset($event['cached']) && $event['cached'] ? ' [cached]' : '';
                $lines[] = "  $type$name$cached: " . round($duration * 1000, 2) . ' ms';
            }
            $lines[] = '';
        }
        
        // SQL
        if (!empty($traceData['sql'])) {
            $lines[] = 'SQL Queries (' . count($traceData['sql']) . '):';
            foreach ($traceData['sql'] as $i => $q) {
                $time = (float) ($q['time'] ?? 0);
                $slow = $time * 1000 > (float) $this->modx->getOption('modx3profilertoolbar.slow_query_ms', null, 100) ? ' [slow]' : '';
                $lines[] = '  #' . ($i + 1) . ' (' . ($time * 1000) . ' ms)' . $slow . ': ' . $q['query'];
            }
        }
        
        return implode(PHP_EOL, $lines);
    }

    /**
     * Helper to format trace data as CSV.
     */
    protected function formatTraceAsCsv(array $traceData): string
    {
        $csv = "Name,Type,Duration(ms),Cached\n";
        if (!empty($traceData['events'])) {
            foreach ($traceData['events'] as $event) {
                $name = $this->escapeCsvField($event['name'] ?? '');
                $type = $event['type'] ?? 'event';
                $duration = round((float) ($event['duration'] ?? 0) * 1000, 2);
                $cached = $event['cached'] ?? '';
                $csv .= "$name,$type,$duration,$cached\n";
            }
        }
        if (!empty($traceData['sql'])) {
            foreach ($traceData['sql'] as $q) {
                $query = $this->escapeCsvField($q['query'] ?? '');
                $time = round((float) ($q['time'] ?? 0) * 1000, 2);
                $csv .= "$query,sql,$time,\n";
            }
        }
        return $csv;
    }

    /**
     * Escape a field for CSV output (RFC 4180).
     */
    protected function escapeCsvField(string $field): string
    {
        // Если поле содержит запятую, кавычку или перенос строки — экранируем
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            // Удваиваем кавычки
            $field = str_replace('"', '""', $field);
            // Оборачиваем в кавычки
            return '"' . $field . '"';
        }
        return $field;
    }

    /**
     * Helper to format bytes to human readable.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Сбор незавершённых вызовов из CoreTools::$tags (Reflection).
     * В CoreTools при первом вызове debugParser() записывается старт; при втором — данные переносятся в $parser->tags.
     * Если второго вызова не было, запись остаётся в CoreTools::$tags — считаем длительность от старта до stop().
     *
     * ДЛЯ MODX 3 / pdoTools 3.x: используем timings вместо tags (debugParser не работает).
     */
    protected function collectCoreToolsTags(float $endTime): void
    {
        if (!$this->modx->services->has('pdotools')) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[Modx3ProfilerToolbar] pdoTools service not found');
            return;
        }
        try {
            $pdoTools = $this->modx->services->get('pdotools');

            // Получаем timings (детальный лог всех операций pdoTools)
            $timings = $this->getPdoToolsTimings($pdoTools);

            if (empty($timings)) {
                return;
            }

            // Парсим timings для извлечения вызовов сниппетов/чанков
            $this->parsePdoToolsTimings($timings);

        } catch (\Throwable $e) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[Modx3ProfilerToolbar] collectCoreToolsTags: ' . $e->getMessage());
        }
    }

    /**
     * Получить timings из pdoTools через Reflection
     */
    protected function getPdoToolsTimings($pdoTools): array
    {
        try {
            $reflection = new \ReflectionClass($pdoTools);
            if (!$reflection->hasProperty('timings')) {
                return [];
            }
            $timingsProperty = $reflection->getProperty('timings');
            $timingsProperty->setAccessible(true);
            $timings = $timingsProperty->getValue($pdoTools);

            return is_array($timings) ? $timings : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Парсинг timings pdoTools для извлечения информации о сниппетах/чанках.
     * Формат записи: "0.0001234: Loaded "modSnippet" with name "ms3ProductSets""
     */
    protected function parsePdoToolsTimings(array $timings): void
    {
        foreach ($timings as $entry) {
            $time = (float) ($entry['time'] ?? 0);
            $message = $entry['message'] ?? '';

            // Загрузка сниппета (считаем как выполнение)
            if (preg_match('/Loaded "modSnippet" with name "([^"]+)"/', $message, $m)) {
                $snippetName = $m[1];

                $this->trackTag('{$_modx->runSnippet("' . $snippetName . '")}', [
                    'type' => 'snippet',
                    'cached' => false,
                    'parse_time' => $time,
                    'queries' => 0,
                    'query_time' => 0.0,
                    'attempts' => 1,
                ]);
            }
        }
    }

    /**
     * Helper to detect tag type from string (for pdoTools integration)
     */
    protected function detectTagTypeFromString(string $tagString): string
    {
        // Fenom tags: {$_modx->runSnippet('name')}
        if (preg_match('/{.*runSnippet.*[\'"]([^\'"]+)/', $tagString, $m)) {
            return 'snippet';
        }
        if (preg_match('/{.*getChunk.*[\'"]([^\'"]+)/', $tagString, $m)) {
            return 'chunk';
        }
        // Standard MODX tags
        if (preg_match('/^\[\[!?([%~+*#$])/', $tagString, $m)) {
            $token = $m[1];
            return match ($token) {
                '%' => 'lexicon',
                '~' => 'link',
                '+' => 'placeholder',
                '*' => 'resource_field',
                '#' => 'fastfield',
                '$' => 'chunk',
                default => 'unknown',
            };
        }
        if (preg_match('/^\[\[!?(\w+)/', $tagString, $m)) {
            return 'snippet';
        }
        return 'unknown';
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    /** @return array */
    public function getCollectors()
    {
        return $this->collectors;
    }

    /**
     * Track a parsed tag (called from Modx3ProfilerToolbarParser::processTag).
     * Groups by tag hash; same tag called N times = one entry with attempts = N.
     */
    public function trackTag(string $tagString, array $metrics): void
    {
        $hash = sha1($tagString);
        $parseTime = (float) ($metrics['parse_time'] ?? 0);
        $queries = (int) ($metrics['queries'] ?? 0);
        $queryTime = (float) ($metrics['query_time'] ?? 0);
        $attempts = (int) ($metrics['attempts'] ?? 1);

        if (isset($this->tags[$hash])) {
            $this->tags[$hash]['attempts'] += $attempts;
            $this->tags[$hash]['parse_time'] += $parseTime;
            $this->tags[$hash]['queries'] += $queries;
            $this->tags[$hash]['query_time'] += $queryTime;
        } else {
            $this->tags[$hash] = [
                'tag' => $tagString,
                'type' => $metrics['type'] ?? 'unknown',
                'cached' => $metrics['cached'] ?? null,
                'attempts' => $attempts,
                'parse_time' => $parseTime,
                'queries' => $queries,
                'query_time' => $queryTime,
            ];
        }
    }

    /** @return array Tag data for CollectorService / DebugParserService */
    public function getTags(): array
    {
        return array_values($this->tags);
    }

    /**
     * Track component execution (called from snippet/chunk process wrappers).
     * Used for Fenom-invoked snippets/chunks and ensures data is saved to DB.
     */
    public function trackComponent(string $type, string $name, float $duration, int $queries, float $queryTime, bool $cached): void
    {
        $hash = md5($type . '::' . $name);
        if (!isset($this->componentTraces[$hash])) {
            $this->componentTraces[$hash] = [
                'type' => $type,
                'name' => $name,
                'total_time' => 0.0,
                'call_count' => 0,
                'cached' => $cached,
                'queries' => 0,
                'query_time' => 0.0,
            ];
        }
        $this->componentTraces[$hash]['total_time'] += $duration;
        $this->componentTraces[$hash]['call_count']++;
        $this->componentTraces[$hash]['queries'] += $queries;
        $this->componentTraces[$hash]['query_time'] += $queryTime;
    }

    /** @return array Component traces for CollectorService */
    public function getComponentTraces(): array
    {
        return array_values($this->componentTraces);
    }

    /**
     * Replace $modx->parser with Modx3ProfilerToolbarParser wrapper so every processTag is profiled.
     * Работает для обычных MODX-сниппетов и pdoTools.
     */
    public function replaceParser(): void
    {
        $currentParser = $this->modx->parser;

        // Если уже обёрнут — пропускаем
        if ($currentParser instanceof \Modx3ProfilerToolbar\Parser\Modx3ProfilerToolbarParser) {
            return;
        }

        // Определяем путь через namespace
        $corePath = null;
        $namespace = $this->modx->getObject('modNamespace', 'modx3profilertoolbar');
        if ($namespace) {
            $corePath = $namespace->getCorePath();
        }
        if (!$corePath) {
            $corePath = $this->modx->getOption('core_path') . 'components/modx3profilertoolbar/';
        }

        // Создаём обёртку (работает и для modParser, и для pdoTools Parser)
        $parserFile = $corePath . 'src/Parser/Modx3ProfilerToolbarParser.php';
        if (file_exists($parserFile)) {
            require_once $parserFile;
            $wrapper = new \Modx3ProfilerToolbar\Parser\Modx3ProfilerToolbarParser($this->modx, $currentParser);
            $this->modx->parser = $wrapper;
        }
    }
}
