<?php
/**
 * Wraps modSnippet/modChunk so process() is tracked for debug report (Fenom + standard runSnippet/getChunk).
 * SnippetProcessWrapper / ChunkProcessWrapper extend the correct element type so modX::runSnippet/getChunk
 * instanceof checks succeed (MODX core requires modSnippet / modChunk, not modElement).
 *
 * @package Modx3ProfilerToolbar
 */

namespace Modx3ProfilerToolbar\Parser;

use MODX\Revolution\modChunk;
use MODX\Revolution\modElement;
use MODX\Revolution\modSnippet;

trait ProfilesElementTrait
{
    /** @var modSnippet|modChunk */
    protected $wrappedElement;

    /** @var string snippet|chunk */
    protected $elementType;

    /** @var string */
    protected $elementName;

    protected function initWrapper(modElement $element, string $elementType, string $elementName): void
    {
        $this->wrappedElement = $element;
        $this->elementType = $elementType;
        $this->elementName = $elementName;
    }

    public function process($properties = null, $content = null)
    {
        $parseTimeStart = microtime(true);
        $queryTimeStart = $this->modx->queryTime ?? 0;
        $queriesStart = $this->modx->executedQueries ?? 0;

        $output = $this->wrappedElement->process($properties, $content);

        $parseTime = microtime(true) - $parseTimeStart;
        $queryTime = ($this->modx->queryTime ?? 0) - $queryTimeStart;
        $queries = ($this->modx->executedQueries ?? 0) - $queriesStart;

        if (!empty($this->modx->modx3profilertoolbar)) {
            $tagString = $this->elementType === 'chunk'
                ? '[[$' . $this->elementName . ']]'
                : '[[!' . $this->elementName . ']]';

            $this->modx->modx3profilertoolbar->trackTag($tagString, [
                'type' => $this->elementType,
                'cached' => false,
                'parse_time' => $parseTime,
                'queries' => $queries,
                'query_time' => $queryTime,
            ]);

            $this->modx->modx3profilertoolbar->trackComponent(
                $this->elementType,
                $this->elementName,
                $parseTime,
                $queries,
                $queryTime,
                false
            );
        }

        return $output;
    }

    public function get($k, $format = null, $formatTemplate = null)
    {
        return $this->wrappedElement->get($k, $format, $formatTemplate);
    }

    public function set($k, $v = null, $vType = '')
    {
        return $this->wrappedElement->set($k, $v, $vType);
    }

    public function __call(string $name, array $arguments)
    {
        return $this->wrappedElement->$name(...$arguments);
    }
}

class SnippetProcessWrapper extends modSnippet
{
    use ProfilesElementTrait;

    public function __construct(modSnippet $element, \modX $modx, string $elementName)
    {
        parent::__construct($modx);
        $this->xpdo = $modx;
        $this->modx = $modx;
        $this->initWrapper($element, 'snippet', $elementName);
    }
}

class ChunkProcessWrapper extends modChunk
{
    use ProfilesElementTrait;

    public function __construct(modChunk $element, \modX $modx, string $elementName)
    {
        parent::__construct($modx);
        $this->xpdo = $modx;
        $this->modx = $modx;
        $this->initWrapper($element, 'chunk', $elementName);
    }
}
