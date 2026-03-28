<?php
/**
 * Runs advisor rules and performance score on trace data.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Service;

use Modx3ProfilerToolbar\Analyzer\PerformanceScore;
use Modx3ProfilerToolbar\Analyzer\RuleEngine;

class AnalyzerService
{
    /** @var \modX */
    protected $modx;

    /** @var ProfilerService */
    protected $profiler;

    /** @var RuleEngine */
    protected $ruleEngine;

    public function __construct(\modX $modx, ProfilerService $profiler)
    {
        $this->modx = $modx;
        $this->profiler = $profiler;
        $this->ruleEngine = new RuleEngine($modx);
    }

    /**
     * @return array ['advisor' => array, 'score' => int]
     */
    public function analyze(array $traceData): array
    {
        $advisor = $this->ruleEngine->analyze($traceData);
        $traceData['advisor'] = $advisor;
        $score = PerformanceScore::calculate($traceData);
        return [
            'advisor' => $advisor,
            'score' => $score,
        ];
    }
}
