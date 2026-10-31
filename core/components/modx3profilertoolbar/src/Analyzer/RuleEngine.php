<?php
/**
 * Runs advisor rules on trace data.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Analyzer;

class RuleEngine
{
    /** @var \modX */
    protected $modx;

    /** @var object[] */
    protected $rules = [];

    public function __construct(\modX $modx)
    {
        $this->modx = $modx;
        $this->registerRules();
    }

    protected function registerRules(): void
    {
        $this->rules[] = new Rules\UncachedSnippetRule($this->modx);
        $this->rules[] = new Rules\N1QueryRule($this->modx);
        $this->rules[] = new Rules\SlowQueryRule($this->modx);
        $this->rules[] = new Rules\ChunkOveruseRule($this->modx);
        $this->rules[] = new Rules\TooManySnippetsRule($this->modx);
    }

    /**
     * @return array List of recommendation arrays (rule, severity, message, suggestion, affected)
     */
    public function analyze(array $traceData): array
    {
        // Ensure lexicon is loaded
        $this->modx->lexicon->load('modx3profilertoolbar:default');
        
        $recommendations = [];
        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($traceData);
            if ($result !== null) {
                // Translate message and suggestion using lexicon
                if (isset($result['message'])) {
                    $msgKey = $result['message'];
                    $msgParams = $result['message_params'] ?? [];
                    $result['message'] = $this->modx->lexicon($msgKey, $msgParams);
                }
                if (isset($result['suggestion'])) {
                    $sugKey = $result['suggestion'];
                    $result['suggestion'] = $this->modx->lexicon($sugKey);
                }
                $recommendations[] = $result;
            }
        }
        return $recommendations;
    }
}
