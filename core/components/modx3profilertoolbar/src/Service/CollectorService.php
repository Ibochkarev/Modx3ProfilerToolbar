<?php
/**
 * Collects data from ProfilerService events and all profilers into a single trace structure.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Service;

require_once dirname(__DIR__, 2) . '/include/helpers.php';

class CollectorService
{
    /** @var \modX */
    protected $modx;

    /** @var ProfilerService */
    protected $profiler;

    public function __construct(\modX $modx, ProfilerService $profiler)
    {
        $this->modx = $modx;
        $this->profiler = $profiler;
    }

    /**
     * Build full trace data for the request.
     *
     * @param array $data start_time, end_time, start_memory, end_memory, events, collectors
     * @return array Trace data ready for AnalyzerService and StorageService
     */
    public function collect(string $requestId, array $data): array
    {
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        $startMemory = (int) ($data['start_memory'] ?? 0);
        $endMemory = (int) ($data['end_memory'] ?? 0);
        $events = $data['events'] ?? [];
        $collectors = $data['collectors'] ?? [];

        $totalTime = $endTime - $startTime;
        $phpTime = $totalTime;
        $sqlTime = 0.0;
        $sqlList = [];
        $snippetCount = 0;
        $pluginCount = 0;
        $chunkCount = 0;

        // SQL из SQLProfiler
        if (isset($collectors['sql']) && method_exists($collectors['sql'], 'getQueries')) {
            $sqlList = $collectors['sql']->getQueries();
            foreach ($sqlList as $q) {
                $sqlTime += (float) ($q['time'] ?? 0);
            }
        }

        // Теги из parser (для подсчёта, debug report и создания components)
        $tags = $this->profiler->getTags();
        
        // Создаём массив components из tags (для сохранения в БД)
        $components = [];
        foreach ($tags as $t) {
            $type = $t['type'] ?? 'unknown';
            if (!in_array($type, ['snippet', 'chunk'], true)) {
                continue; // Только snippet и chunk сохраняем как компоненты
            }
            
            $attempts = (int) ($t['attempts'] ?? 1);
            $parseTime = (float) ($t['parse_time'] ?? 0);
            $name = modx3profilertoolbar_tagDisplayName($t['tag'] ?? '');
            
            $hash = md5($type . '::' . $name);
            if (!isset($components[$hash])) {
                $components[$hash] = [
                    'type' => $type,
                    'name' => $name,
                    'total_time' => 0.0,
                    'call_count' => 0,
                    'cached' => isset($t['cached']) ? $t['cached'] : null,
                    'queries' => 0,
                    'query_time' => 0.0,
                ];
            }
            $components[$hash]['total_time'] += $parseTime;
            $components[$hash]['call_count'] += $attempts;
            $components[$hash]['queries'] += (int) ($t['queries'] ?? 0);
            $components[$hash]['query_time'] += (float) ($t['query_time'] ?? 0);
            
            // Подсчёт для snippet_count / chunk_count
            if ($type === 'snippet') {
                $snippetCount += $attempts;
            } elseif ($type === 'chunk') {
                $chunkCount += $attempts;
            }
        }

        $eventsNormalized = $this->normalizeEvents($events, $collectors, $startTime);
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        return [
            'request_id' => $requestId,
            'url' => $url,
            'method' => $method,
            'timestamp' => $startTime,
            'context' => $this->modx->context->key ?? 'web',
            'user_id' => $this->modx->user ? (int) $this->modx->user->id : 0,
            'metrics' => [
                'total_time' => $totalTime,
                'ttfb' => $totalTime,
                'php_time' => $phpTime,
                'sql_time' => $sqlTime,
                'memory_peak' => max($endMemory, memory_get_peak_usage(true)),
            ],
            'events' => $eventsNormalized,
            'tags' => $tags,
            'components' => array_values($components), // Added for saving to DB
            'sql' => $this->normalizeSql($sqlList),
            'snippet_count' => $snippetCount,
            'plugin_count' => $pluginCount,
            'chunk_count' => $chunkCount,
            'sql_count' => count($sqlList),
        ];
    }

    protected function normalizeEvents(array $events, array $collectors, float $baseTime): array
    {
        $out = [];
        foreach ($events as $key => $ev) {
            $name = $ev['name'] ?? $key;
            $start = (float) ($ev['start'] ?? 0);
            $end = (float) ($ev['end'] ?? $start);
            $duration = (float) ($ev['duration'] ?? ($end - $start));
            $out[] = [
                'name' => $name,
                'start' => $start - $baseTime,
                'end' => $end - $baseTime,
                'duration' => $duration,
                'memory_delta' => isset($ev['memory_delta']) ? (int) $ev['memory_delta'] : null,
                'children' => $this->getChildrenForEvent($name, $collectors, $baseTime),
            ];
        }
        $snippetTraces = isset($collectors['snippet']) && method_exists($collectors['snippet'], 'getTraces')
            ? $collectors['snippet']->getTraces() : [];
        $pluginTraces = isset($collectors['plugin']) && method_exists($collectors['plugin'], 'getTraces')
            ? $collectors['plugin']->getTraces() : [];
        $chunkTraces = isset($collectors['chunk']) && method_exists($collectors['chunk'], 'getTraces')
            ? $collectors['chunk']->getTraces() : [];
        foreach (array_merge($snippetTraces, $pluginTraces, $chunkTraces) as $t) {
            $out[] = [
                'type' => $t['type'] ?? 'snippet',
                'name' => $t['name'] ?? '',
                'start' => (isset($t['start']) ? (float) $t['start'] : 0) - $baseTime,
                'end' => (isset($t['end']) ? (float) $t['end'] : 0) - $baseTime,
                'duration' => (float) ($t['duration'] ?? 0),
                'cached' => $t['cached'] ?? null,
                'properties' => $t['properties'] ?? null,
                'call_count' => $t['call_count'] ?? 1,
            ];
        }
        return $out;
    }

    protected function getChildrenForEvent(string $eventName, array $collectors, float $baseTime): array
    {
        return [];
    }

    protected function normalizeSql(array $queries): array
    {
        $out = [];
        foreach ($queries as $q) {
            $out[] = [
                'query' => $q['query'] ?? $q['query_text'] ?? '',
                'params' => $q['params'] ?? [],
                'time' => (float) ($q['time'] ?? $q['query_time'] ?? 0),
                'count' => (int) ($q['count'] ?? $q['call_count'] ?? 1),
                'source' => $q['source'] ?? '',
                'warnings' => $q['warnings'] ?? [],
                'is_n1' => !empty($q['is_n1']),
                'is_slow' => !empty($q['is_slow']),
            ];
        }
        return $out;
    }
}
