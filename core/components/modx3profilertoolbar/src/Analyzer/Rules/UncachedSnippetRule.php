<?php
/**
 * Advisor rule: slow uncached snippets.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Analyzer\Rules;

class UncachedSnippetRule
{
    /** @var \modX */
    protected $modx;

    public function __construct(\modX $modx)
    {
        $this->modx = $modx;
    }

    public function evaluate(array $traceData): ?array
    {
        $events = $traceData['events'] ?? [];
        $uncached = [];
        $thresholdMs = (float) $this->modx->getOption('modx3profilertoolbar.slow_snippet_ms', null, 50);
        $threshold = $thresholdMs / 1000;
        foreach ($events as $event) {
            if (($event['type'] ?? '') !== 'snippet') {
                continue;
            }
            if ($event['cached'] ?? false) {
                continue;
            }
            $duration = (float) ($event['duration'] ?? 0);
            if ($duration > $threshold) {
                $uncached[] = $event['name'] ?? 'unknown';
            }
        }
        if (empty($uncached)) {
            return null;
        }
        
        $snippetList = implode(', ', array_unique($uncached));
        return [
            'rule' => 'UncachedSnippetRule',
            'severity' => 'warning',
            'message' => 'mpr_advisor_uncached_snippet',
            'message_params' => [$snippetList],
            'suggestion' => 'mpr_advisor_uncached_suggestion',
            'affected' => array_values(array_unique($uncached)),
            'docs' => 'https://docs.modx.pro/components/pdotools/snippets/pdoresources#tplcache',
            'docs_links' => [
                [
                    'title' => $this->modx->lexicon('mpr_doc_modx_caching'),
                    'url' => 'https://docs.modx.com/3.x/en/building-sites/tag-syntax/common',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdotools_cache'),
                    'url' => 'https://docs.modx.pro/components/pdotools/general-properties#parametry-rezultatov',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_fenom_cache_control'),
                    'url' => 'https://docs.modx.pro/components/pdotools/parser#upravlenie-keshirovaniem',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdotools_performance'),
                    'url' => 'https://docs.modx.pro/components/pdotools/general-properties',
                ],
            ],
        ];
    }
}
