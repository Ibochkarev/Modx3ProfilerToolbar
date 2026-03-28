<?php
/**
 * Advisor rule: chunk called too many times in one request.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Analyzer\Rules;

class ChunkOveruseRule
{
    /** @var \modX */
    protected $modx;
    protected $callThreshold = 50;

    public function __construct(\modX $modx)
    {
        $this->modx = $modx;
    }

    public function evaluate(array $traceData): ?array
    {
        $events = $traceData['events'] ?? [];
        $overuse = [];
        foreach ($events as $event) {
            if (($event['type'] ?? '') !== 'chunk') {
                continue;
            }
            $calls = (int) ($event['call_count'] ?? 1);
            if ($calls >= $this->callThreshold) {
                $overuse[] = ($event['name'] ?? 'unknown') . ' (' . $calls . ' calls)';
            }
        }
        if (empty($overuse)) {
            return null;
        }
        
        $chunkList = implode(', ', $overuse);
        return [
            'rule' => 'ChunkOveruseRule',
            'severity' => 'info',
            'message' => 'mpr_advisor_chunk_overuse',
            'message_params' => [$chunkList],
            'suggestion' => 'mpr_advisor_chunk_overuse_suggestion',
            'affected' => $overuse,
            'docs' => 'https://docs.modx.pro/components/pdotools/parser',
            'docs_links' => [
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdotools_chunk_types'),
                    'url' => 'https://docs.modx.pro/components/pdotools/parser',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_fenom_syntax'),
                    'url' => 'https://docs.modx.pro/components/pdotools/parser#sintaksis',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_fenom_modifiers'),
                    'url' => 'https://docs.modx.pro/components/pdotools/parser#modifikatory',
                ],
                [
                    'title' => $this->modx->lexicon('mpr_doc_pdotools_fastmode'),
                    'url' => 'https://docs.modx.pro/components/pdotools/general-properties#parametry-rezultatov',
                ],
            ],
        ];
    }
}
