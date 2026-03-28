<?php
/**
 * Snippet execution profiler (wrapper timing).
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Profiler;

class SnippetProfiler
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
        // Хукаем выполнение сниппетов через xPDO logging
        if ($this->modx->getDebug() === false) {
            $this->modx->setDebug(true);
        }
        
        // Сохраняем оригинальный runSnippet
        $this->modx->modx3profilertoolbar_snippet_hook = function($snippetName, $properties = []) {
            $start = microtime(true);
            $result = call_user_func_array([$this->modx, 'runSnippet'], [$snippetName, $properties]);
            $duration = microtime(true) - $start;
            
            $this->traces[] = [
                'type' => 'snippet',
                'name' => $snippetName,
                'duration' => $duration,
                'call_count' => 1,
                'properties' => $properties,
            ];
            
            return $result;
        };
    }

    public function trace(string $snippetName, callable $execution, array $options = [])
    {
        $start = microtime(true);
        $memStart = memory_get_usage(true);
        $result = $execution();
        $end = microtime(true);
        $memEnd = memory_get_usage(true);
        $this->traces[] = [
            'type' => 'snippet',
            'name' => $snippetName,
            'start' => $start,
            'end' => $end,
            'duration' => $end - $start,
            'memory_delta' => $memEnd - $memStart,
            'cached' => $options['cached'] ?? false,
            'properties' => $options['properties'] ?? null,
        ];
        return $result;
    }

    public function getTraces(): array
    {
        return $this->traces;
    }
}
