<?php
/**
 * Plugin (event) execution profiler.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Profiler;

class PluginProfiler
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
        // Event-based timing can be added via plugin invoker hooks
    }

    public function getTraces(): array
    {
        return $this->traces;
    }

    public function addTrace(string $eventName, float $start, float $end): void
    {
        $this->traces[] = [
            'type' => 'plugin',
            'name' => $eventName,
            'start' => $start,
            'end' => $end,
            'duration' => $end - $start,
        ];
    }
}
