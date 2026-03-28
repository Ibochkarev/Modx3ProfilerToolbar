<?php
/**
 * Advisor rule: too many snippets on one page.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Analyzer\Rules;

class TooManySnippetsRule
{
    /** @var \modX */
    protected $modx;

    public function __construct(\modX $modx)
    {
        $this->modx = $modx;
    }

    public function evaluate(array $traceData): ?array
    {
        $snippetCount = (int) ($traceData['snippet_count'] ?? 0);

        if ($snippetCount < 5) {
            return null;
        }

        if ($snippetCount >= 10) {
            return [
                'rule' => 'TooManySnippetsRule',
                'severity' => 'warning',
                'message' => 'mpr_advisor_too_many_snippets',
                'message_params' => [$snippetCount],
                'suggestion' => 'mpr_advisor_too_many_snippets_suggestion',
                'affected' => [],
                'docs' => 'https://docs.modx.com/3.x/en/building-sites/tag-syntax/common',
                'docs_links' => [
                    [
                        'title' => $this->modx->lexicon('mpr_doc_modx_caching'),
                        'url' => 'https://docs.modx.com/3.x/en/building-sites/tag-syntax/common',
                    ],
                    [
                        'title' => $this->modx->lexicon('mpr_doc_pdotools_replace_snippets'),
                        'url' => 'https://docs.modx.pro/components/pdotools/snippets/pdoresources',
                    ],
                    [
                        'title' => $this->modx->lexicon('mpr_doc_fenom_in_chunks'),
                        'url' => 'https://docs.modx.pro/components/pdotools/parser#shablonizator-fenom',
                    ],
                    [
                        'title' => $this->modx->lexicon('mpr_doc_pdotools_fastmode'),
                        'url' => 'https://docs.modx.pro/components/pdotools/general-properties#parametry-rezultatov',
                    ],
                ],
            ];
        }

        if ($snippetCount >= 5) {
            return [
                'rule' => 'TooManySnippetsRule',
                'severity' => 'info',
                'message' => 'mpr_advisor_too_many_snippets_info',
                'message_params' => [$snippetCount],
                'suggestion' => 'mpr_advisor_too_many_snippets_info_suggestion',
                'affected' => [],
            ];
        }

        return null;
    }
}
