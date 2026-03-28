<?php
/**
 * Advisor rule: slow SQL queries.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Analyzer\Rules;

class SlowQueryRule
{
    /** @var \modX */
    protected $modx;

    public function __construct(\modX $modx)
    {
        $this->modx = $modx;
    }

    public function evaluate(array $traceData): ?array
    {
        $sql = $traceData['sql'] ?? [];
        $slow = [];
        $thresholdMs = (float) $this->modx->getOption('modx3profilertoolbar.slow_query_ms', null, 100);
        $threshold = $thresholdMs / 1000;
        foreach ($sql as $q) {
            $time = (float) ($q['time'] ?? 0);
            if ($time >= $threshold) {
                $slow[] = ($q['query'] ?? $q['source'] ?? 'unknown');
            }
        }
        if (empty($slow)) {
            return null;
        }
        return [
            'rule' => 'SlowQueryRule',
            'severity' => 'warning',
            'message' => 'mpr_advisor_slow_query',
            'message_params' => [count($slow)],
            'suggestion' => 'mpr_advisor_slow_query_suggestion',
            'affected' => array_slice($slow, 0, 5),
            'docs' => 'https://docs.modx.pro/en/components/pdotools/general-properties',
            'docs_links' => [
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdotools_general'),
                    'url' => 'https://docs.modx.pro/components/pdotools/general-properties',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdotools_parser'),
                    'url' => 'https://docs.modx.pro/components/pdotools/parser',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdoresources'),
                    'url' => 'https://docs.modx.pro/components/pdotools/snippets/pdoresources',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_fenom_docs'),
                    'url' => 'https://github.com/fenom-template/fenom/blob/master/docs/ru/readme.md',
                ],
            ],
        ];
    }
}
