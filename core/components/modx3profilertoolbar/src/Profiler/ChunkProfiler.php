<?php
/**
 * Chunk render profiler.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Profiler;

class ChunkProfiler
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
        // modChunk::process hook — implemented when needed
    }

    public function getTraces(): array
    {
        return $this->traces;
    }

    public function addTrace(string $chunkName, float $duration, int $callCount = 1): void
    {
        $this->traces[] = [
            'type' => 'chunk',
            'name' => $chunkName,
            'duration' => $duration,
            'call_count' => $callCount,
        ];
    }
}
