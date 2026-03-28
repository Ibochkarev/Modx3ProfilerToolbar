<?php
/**
 * Advisor rule: N+1 query pattern.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Analyzer\Rules;

class N1QueryRule
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
        $n1 = [];
        foreach ($sql as $q) {
            if (!empty($q['is_n1']) || in_array('N+1', $q['warnings'] ?? [], true)) {
                $n1[] = $q['query'] ?? $q['source'] ?? 'unknown';
            }
        }
        if (empty($n1)) {
            return null;
        }
        return [
            'rule' => 'N1QueryRule',
            'severity' => 'warning',
            'message' => 'mpr_advisor_n1_query',
            'suggestion' => 'mpr_advisor_n1_suggestion',
            'affected' => array_slice($n1, 0, 5),
            'docs' => 'https://docs.modx.pro/components/pdotools/snippets/pdoresources#fastmode',
            'docs_links' => [
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdotools_joins'),
                    'url' => 'https://docs.modx.pro/components/pdotools/general-properties#parametry-vyborki-resursov',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdoresources_optimization'),
                    'url' => 'https://docs.modx.pro/components/pdotools/snippets/pdoresources',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdotools_select'),
                    'url' => 'https://docs.modx.pro/components/pdotools/general-properties',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_fenom_single_query'),
                    'url' => 'https://docs.modx.pro/components/pdotools/parser#sistemnye-peremennye',
                ],
            ],
        ];
    }
}
