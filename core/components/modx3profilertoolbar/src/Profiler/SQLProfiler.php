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

    /** @var PDOProfiler|null */
    protected $pdoProfiler = null;

    /** @var PDOInterceptor|null */
    protected $interceptor = null;

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

        // Try to wrap PDO for query interception
        $this->wrapPdo();
    }

    /**
     * Wrap xPDO's PDO connection with our profiler.
     * Only wraps if not already wrapped and if xPDO allows it.
     */
    protected function wrapPdo(): void
    {
        try {
            $connection = $this->modx->getConnection();
            if (!$connection || !isset($connection->pdo) || !$connection->pdo instanceof \PDO) {
                $this->modx->log(\modX::LOG_LEVEL_DEBUG, '[Modx3ProfilerToolbar] PDO not available for wrapping');
                return;
            }

            // Skip if already wrapped
            if ($connection->pdo instanceof PDOProfiler) {
                $this->modx->log(\modX::LOG_LEVEL_DEBUG, '[Modx3ProfilerToolbar] PDO already wrapped');
                return;
            }

            $originalPdo = $connection->pdo;
            
            // Create interceptor and profiler
            $this->interceptor = new PDOInterceptor($this->modx, $this);
            $this->pdoProfiler = new PDOProfiler($originalPdo, $this, $this->interceptor);
            
            // Replace PDO in connection (xPDO uses __get/__set, so this should work)
            $connection->pdo = $this->pdoProfiler;
            
            // Start profiling
            $this->pdoProfiler->startProfiling();
            
            $this->modx->log(\modX::LOG_LEVEL_INFO, '[Modx3ProfilerToolbar] PDO wrapped successfully');
            
        } catch (\Throwable $e) {
            // Fallback: PDO wrapping failed, rely on queryLog
            $this->modx->log(\modX::LOG_LEVEL_WARN, '[Modx3ProfilerToolbar] PDO wrapping failed: ' . $e->getMessage());
        }
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
        // Return intercepted queries from PDO wrapper
        if (!empty($this->queries)) {
            return $this->queries;
        }
        
        // Fallback: try xPDO queryLog (older MODX versions)
        if (!empty($this->modx->queryLog)) {
            foreach ($this->modx->queryLog as $entry) {
                $sql = $entry['sql'] ?? '';
                $time = (float) ($entry['time'] ?? 0);
                
                // Skip duplicates
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
                        'is_n1' => false,
                        'is_slow' => false,
                        'warnings' => [],
                    ];
                }
            }
        }
        
        // Last resort: aggregate from executedQueries/queryTime
        if (empty($this->queries)) {
            $baseline = $this->modx->modx3profilertoolbar_sql_baseline ?? ['queries' => 0, 'time' => 0];
            $currentQueries = $this->modx->executedQueries ?? 0;
            $currentTime = $this->modx->queryTime ?? 0;
            
            $diffQueries = $currentQueries - $baseline['queries'];
            $diffTime = $currentTime - $baseline['time'];
            
            if ($diffQueries > 0) {
                $this->queries[] = [
                    'query' => sprintf('%d SQL queries executed', $diffQueries),
                    'query_text' => sprintf('%d SQL queries', $diffQueries),
                    'time' => $diffTime,
                    'count' => $diffQueries,
                    'source' => 'xPDO aggregate',
                    'is_n1' => false,
                    'is_slow' => false,
                    'warnings' => [],
                ];
            }
        }
        
        return $this->queries;
    }

    /**
     * Reset all tracked data (call between requests in long-running scripts).
     */
    public function reset(): void
    {
        $this->queries = [];
        if ($this->interceptor) {
            $this->interceptor->reset();
        }
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
