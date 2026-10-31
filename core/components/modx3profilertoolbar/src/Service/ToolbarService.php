<?php
/**
 * Injects Vue Petite toolbar into frontend page output.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Service;

class ToolbarService
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

    public function shouldInject(): bool
    {
        if ($this->modx->context->key !== 'web') {
            return false;
        }
        if (!$this->modx->getOption('modx3profilertoolbar.frontend_toolbar', null, false)) {
            return false;
        }
        
        // Тулбар показывается только авторизованным в менеджере
        $user = $this->modx->user;
        return $user && $user->hasSessionContext('mgr');
    }

    public function inject(string $requestId, array $traceData): void
    {
        if (!$this->shouldInject()) {
            return;
        }
        
        $assetsUrl = $this->modx->getOption('assets_url') . 'components/modx3profilertoolbar/';

        $metrics = $traceData['metrics'] ?? [];
        $totalTimeSec = (float) ($metrics['total_time'] ?? 0);
        $queryTimeSec = (float) ($metrics['sql_time'] ?? 0);
        $phpTimeSec = $totalTimeSec - $queryTimeSec;
        $source = $this->modx->resourceGenerated ? 'database' : 'cache';

        $toolbarData = [
            'request_id' => $requestId,
            'metrics' => [
                'total_time' => (int) round($totalTimeSec * 1000),
                'ttfb' => (int) round(($metrics['ttfb'] ?? 0) * 1000),
                'php_time' => (int) round($phpTimeSec * 1000),
                'sql_time' => (int) round($queryTimeSec * 1000),
                'memory_peak' => (int) ($metrics['memory_peak'] ?? 0),
                'snippet_count' => (int) ($traceData['snippet_count'] ?? 0),
                'sql_count' => (int) ($traceData['sql_count'] ?? 0),
            ],
            'info' => [
                'queryTime' => round($queryTimeSec, 4) . ' s',
                'source' => $source,
            ],
            // Added advisor and performance score for UI
            'advisor' => $traceData['advisor'] ?? [],
            'performance_score' => $traceData['performance_score'] ?? null,
            'slow_components' => $this->getSlowComponents($traceData),
            'timeline' => $this->getTimelineData($traceData),
            'sql' => $this->getSQLData($traceData),
            'lexicon' => $this->getLexicon(),
        ];

        $json = json_encode($toolbarData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        $script = "\n<script>window.modx3profilertoolbarToolbarData = {$json};</script>\n";
        $css = '<link rel="stylesheet" href="' . $assetsUrl . 'css/toolbar.css">';
        $js = '<script type="module" src="' . $assetsUrl . 'js/toolbar/toolbar.js"></script>';

        $output = $this->modx->resource->_output ?? '';
        
        if (stripos($output, '</body>') !== false) {
            $output = str_ireplace('</body>', $css . $script . $js . '</body>', $output);
            $this->modx->resource->_output = $output;
        }
    }

    protected function getSlowComponents(array $traceData): array
    {
        $components = [];
        foreach ($traceData['events'] ?? [] as $event) {
            $type = $event['type'] ?? null;
            if (!in_array($type, ['snippet', 'plugin', 'chunk'], true)) {
                continue;
            }
            $components[] = [
                'type' => ucfirst($type),
                'name' => $event['name'] ?? '',
                'time' => (int) round(($event['duration'] ?? 0) * 1000),
                'cached' => $event['cached'] ?? null,
                'calls' => $event['call_count'] ?? null,
            ];
        }
        // Профайлеры сниппетов/чанков могут не подменять runSnippet — добавляем из тегов парсера
        foreach ($traceData['tags'] ?? [] as $t) {
            $tagType = $t['type'] ?? '';
            if (!in_array($tagType, ['snippet', 'chunk'], true)) {
                continue;
            }
            $parseTimeMs = (int) round(((float) ($t['parse_time'] ?? 0)) * 1000);
            $components[] = [
                'type' => ucfirst($tagType),
                'name' => $this->tagDisplayName($t['tag'] ?? ''),
                'time' => $parseTimeMs,
                'cached' => isset($t['cached']) ? (bool) $t['cached'] : null,
                'calls' => (int) ($t['attempts'] ?? 1),
            ];
        }
        usort($components, static fn($a, $b) => ($b['time'] ?? 0) <=> ($a['time'] ?? 0));
        return array_slice($components, 0, 10);
    }

    protected function tagDisplayName(string $tag): string
    {
        $t = trim($tag);
        $t = preg_replace('/^\[\[!?/', '', $t);
        $t = preg_replace('/\]\]$/', '', $t);
        $t = trim($t);
        return $t !== '' ? mb_substr($t, 0, 255) : 'tag';
    }

    protected function getTimelineData(array $traceData): array
    {
        $out = [];
        foreach ($traceData['events'] ?? [] as $event) {
            $out[] = [
                'name' => $event['name'] ?? $event['type'] ?? 'event',
                'duration' => (int) round(($event['duration'] ?? 0) * 1000),
            ];
        }
        return array_slice($out, 0, 20);
    }

    protected function getSQLData(array $traceData): array
    {
        $out = [];
        foreach ($traceData['sql'] ?? [] as $q) {
            $out[] = [
                'text' => $q['query'] ?? '',
                'time' => (int) round(($q['time'] ?? 0) * 1000),
                'count' => (int) ($q['count'] ?? 1),
                'is_n1' => !empty($q['is_n1']),
            ];
        }
        return array_slice($out, 0, 20);
    }

    protected function getLexicon(): array
    {
        $this->modx->lexicon->load('modx3profilertoolbar:default');
        return [
            'performance_profiler' => $this->modx->lexicon('modx3profilertoolbar'),
            'overview' => $this->modx->lexicon('mpr_tab_overview'),
            'slow_components' => $this->modx->lexicon('mpr_slow_components'),
            'timeline' => $this->modx->lexicon('mpr_timeline'),
            'sql' => $this->modx->lexicon('mpr_sql'),
            'total_time' => $this->modx->lexicon('mpr_total_time'),
            'ttfb' => $this->modx->lexicon('mpr_ttfb'),
            'php' => $this->modx->lexicon('mpr_php'),
            'sql_time' => $this->modx->lexicon('mpr_sql_time'),
            'memory' => $this->modx->lexicon('mpr_memory'),
            'snippets' => $this->modx->lexicon('mpr_snippets'),
            'sql_queries' => $this->modx->lexicon('mpr_sql_queries'),
            'query_time' => $this->modx->lexicon('mpr_query_time'),
            'source' => $this->modx->lexicon('mpr_source'),
            'open_trace' => $this->modx->lexicon('mpr_open_trace'),
            'time' => $this->modx->lexicon('mpr_time'),
            'calls' => $this->modx->lexicon('mpr_calls'),
            'cached' => $this->modx->lexicon('mpr_cached'),
            'yes' => $this->modx->lexicon('yes'),
            'no' => $this->modx->lexicon('no'),
            'possible_n1' => $this->modx->lexicon('mpr_possible_n1'),
            // Added for new UI elements (Issue #3)
            'advisor' => $this->modx->lexicon('mpr_advisor'),
            'performance_score' => $this->modx->lexicon('mpr_performance_score'),
            'status_excellent' => $this->modx->lexicon('mpr_status_excellent'),
            'status_fair' => $this->modx->lexicon('mpr_status_fair'),
            'status_critical' => $this->modx->lexicon('mpr_status_critical'),
            'no_data' => $this->modx->lexicon('mpr_no_data'),
        ];
    }
}
