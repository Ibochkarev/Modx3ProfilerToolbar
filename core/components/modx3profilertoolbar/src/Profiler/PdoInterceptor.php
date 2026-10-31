<?php
/**
 * PDO interceptor for logging SQL queries with timing and N+1 detection.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Profiler;

class PdoInterceptor
{
    /** @var \modX */
    protected $modx;

    /** @var SQLProfiler */
    protected $sqlProfiler;

    /** @var array Query history for N+1 detection */
    protected $queryHistory = [];

    /** @var array Query patterns and their counts */
    protected $queryPatterns = [];

    public function __construct(\modX $modx, SQLProfiler $sqlProfiler)
    {
        $this->modx = $modx;
        $this->sqlProfiler = $sqlProfiler;
    }

    /**
     * Intercept a query execution.
     * This should be called by the extended xPDO class or via plugin.
     *
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @param float $time Execution time in seconds
     * @return void
     */
    public function interceptQuery(string $sql, array $params, float $time): void
    {
        // Normalize query for pattern detection
        $normalized = $this->normalizeQuery($sql);
        
        // Track query for N+1 detection
        $this->trackQueryPattern($normalized, $time);
        
        // Detect if this is an N+1 query
        $isN1 = $this->isN1Query($normalized);
        
        // Detect if this is a slow query
        $thresholdMs = (float) $this->modx->getOption('modx3profilertoolbar.slow_query_ms', null, 100);
        $isSlow = ($time * 1000) >= $thresholdMs;
        
        // Log the query
        $this->sqlProfiler->logQuery($sql, $params, $time, [
            'is_n1' => $isN1,
            'is_slow' => $isSlow,
            'warnings' => $isN1 ? ['N+1'] : ($isSlow ? ['slow'] : []),
        ]);
    }

    /**
     * Normalize query for pattern matching (remove values, keep structure).
     */
    protected function normalizeQuery(string $sql): string
    {
        // Remove comments
        $sql = preg_replace('/--.*$/', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Normalize whitespace
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        
        // Replace string literals with ?
        $sql = preg_replace('/\'(?:[^\']|\'\')*\'/', '?', $sql);
        $sql = preg_replace('/"(?:[^"]|"")*"/', '?', $sql);
        
        // Replace numeric literals with ?
        $sql = preg_replace('/\b\d+\b/', '?', $sql);
        
        return strtoupper($sql);
    }

    /**
     * Track query pattern for N+1 detection.
     */
    protected function trackQueryPattern(string $normalizedQuery, float $time): void
    {
        if (!isset($this->queryPatterns[$normalizedQuery])) {
            $this->queryPatterns[$normalizedQuery] = [
                'count' => 0,
                'times' => [],
                'first_seen' => microtime(true),
            ];
        }
        
        $this->queryPatterns[$normalizedQuery]['count']++;
        $this->queryPatterns[$normalizedQuery]['times'][] = $time;
    }

    /**
     * Detect N+1 query pattern.
     * A query is considered N+1 if:
     * - Same pattern executed more than 3 times
     * - Total execution time > threshold
     */
    protected function isN1Query(string $normalizedQuery): bool
    {
        if (!isset($this->queryPatterns[$normalizedQuery])) {
            return false;
        }
        
        $pattern = $this->queryPatterns[$normalizedQuery];
        
        // N+1 detection: same query executed many times in one request
        $threshold = (int) $this->modx->getOption('modx3profilertoolbar.n1_threshold', null, 5);
        
        return $pattern['count'] >= $threshold;
    }

    /**
     * Get query statistics.
     */
    public function getQueryStats(): array
    {
        return [
            'total_queries' => array_sum(array_column($this->queryPatterns, 'count')),
            'unique_patterns' => count($this->queryPatterns),
            'n1_queries' => count(array_filter($this->queryPatterns, function($pattern) {
                return $pattern['count'] >= (int) $this->modx->getOption('modx3profilertoolbar.n1_threshold', null, 5);
            })),
        ];
    }
}
