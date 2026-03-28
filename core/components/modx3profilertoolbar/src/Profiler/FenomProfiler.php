<?php
/**
 * Fenom template render profiler (cache hit/miss, compile).
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Profiler;

class FenomProfiler
{
    /** @var \modX */
    protected $modx;

    /** @var \Modx3ProfilerToolbar\Service\ProfilerService */
    protected $profiler;

    /** @var array */
    protected $traces = [];

    public function __construct(\modX $modx, \Modx3ProfilerToolbar\Service\ProfilerService $profiler)
    {
        $this->modx = $modx;
        $this->profiler = $profiler;
    }

    public function register(): void
    {
        // Fenom render hook — when MODX uses Fenom for chunks/templates
    }

    public function getTraces(): array
    {
        return $this->traces;
    }
}
