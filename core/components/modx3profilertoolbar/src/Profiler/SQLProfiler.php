<?php
/**
 * SQL query profiler (xPDO / PDO).
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Profiler;

class SQLProfiler
{
    /** @var \modX */
    protected $modx;

    /** @var \Modx3ProfilerToolbar\Service\ProfilerService */
    protected $profiler;

    /** @var array */
    protected $queries = [];

    public function __construct(\modX $modx, \Modx3ProfilerToolbar\Service\ProfilerService $profiler)
    {
        $this->modx = $modx;
        $this->profiler = $profiler;
    }

    public function register(): void
    {
        // Включаем xPDO query tracking
        $this->modx->setOption(\xPDO\xPDO::OPT_CACHE_DB, false);
        
        // Сохраняем начальные метрики SQL
        $this->modx->modx3profilertoolbar_sql_baseline = [
            'queries' => $this->modx->executedQueries ?? 0,
            'time' => $this->modx->queryTime ?? 0,
        ];
    }

    public function logQuery(string $sql, array $params, float $time, array $options = []): void
    {
        $this->queries[] = [
            'query' => $sql,
            'query_text' => $sql,
            'params' => $params,
            'time' => $time,
            'query_time' => $time,
            'count' => $options['count'] ?? 1,
            'call_count' => $options['count'] ?? 1,
            'source' => $options['source'] ?? $this->detectSource(),
            'is_n1' => $options['is_n1'] ?? false,
            'is_slow' => $options['is_slow'] ?? false,
            'warnings' => $options['warnings'] ?? [],
        ];
    }

    public function getQueries(): array
    {
        // Получаем реальные запросы из xPDO queryLog
        if (!empty($this->modx->queryLog)) {
            foreach ($this->modx->queryLog as $entry) {
                $sql = $entry['sql'] ?? '';
                $time = (float) ($entry['time'] ?? 0);
                
                // Пропускаем дубликаты (если уже залогированы вручную)
                $alreadyLogged = false;
                foreach ($this->queries as $existing) {
                    if (($existing['query'] ?? '') === $sql) {
                        $alreadyLogged = true;
                        break;
                    }
                }
                
                if (!$alreadyLogged && $sql !== '') {
                    $this->queries[] = [
                        'query' => $sql,
                        'query_text' => $sql,
                        'time' => $time,
                        'count' => 1,
                        'source' => 'xPDO queryLog',
                    ];
                }
            }
        }
        
        // Fallback: если queryLog пустой, используем агрегат
        $baseline = $this->modx->modx3profilertoolbar_sql_baseline ?? ['queries' => 0, 'time' => 0];
        $currentQueries = $this->modx->executedQueries ?? 0;
        $currentTime = $this->modx->queryTime ?? 0;
        
        $diffQueries = $currentQueries - $baseline['queries'];
        $diffTime = $currentTime - $baseline['time'];
        
        // Если queryLog дал запросы, не добавляем агрегат
        if ($diffQueries > 0 && empty($this->modx->queryLog)) {
            $this->queries[] = [
                'query' => sprintf('%d SQL queries', $diffQueries),
                'query_text' => sprintf('%d SQL queries executed', $diffQueries),
                'time' => $diffTime,
                'count' => $diffQueries,
                'source' => 'xPDO aggregate',
            ];
        }
        
        return $this->queries;
    }

    protected function detectSource(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
        foreach ($trace as $frame) {
            $class = $frame['class'] ?? '';
            if (strpos($class, 'modSnippet') !== false) {
                return 'snippet:' . ($frame['function'] ?? 'unknown');
            }
            if (strpos($class, 'modPlugin') !== false) {
                return 'plugin:' . ($frame['function'] ?? 'unknown');
            }
        }
        return 'unknown';
    }
}
