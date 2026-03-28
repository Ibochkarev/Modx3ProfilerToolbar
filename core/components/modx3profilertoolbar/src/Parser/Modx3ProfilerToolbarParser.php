<?php
/**
 * Parser wrapper: delegates to real parser (modParser or pdoTools Parser) and profiles every processTag().
 * Implements debugPdoParser interface to receive stats from pdoTools internal debugParser() calls.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Parser;

use MODX\Revolution\modChunk;
use MODX\Revolution\modParser;
use MODX\Revolution\modSnippet;

// Load global debugPdoParser interface
require_once __DIR__ . '/debugPdoParser.php';
require_once __DIR__ . '/ElementProcessWrapper.php';

class Modx3ProfilerToolbarParser extends modParser implements \debugPdoParser
{
    /** @var \modX */
    public $modx;

    /** @var modParser|object Wrapped parser (modParser or ModxPro\PdoTools\Parsing\Parser) */
    protected $wrappedParser;

    /** @var array Tags collected by pdoTools debugParser() - populated automatically */
    public $tags = [];

    public function __construct(\modX $modx, $wrappedParser)
    {
        parent::__construct($modx);
        $this->modx = $modx;
        $this->wrappedParser = $wrappedParser;
    }

    /**
     * Return wrapped snippet/chunk so process() is tracked (Fenom and standard MODX).
     * NOT USED by pdoTools - it caches elements internally, but kept for standard MODX tags.
     */
    public function getElement($class, $name)
    {
        $element = $this->wrappedParser->getElement($class, $name);

        if ($element === false || $element === null || empty($this->modx->modx3profilertoolbar)) {
            return $element;
        }
        if ($element instanceof modSnippet) {
            return new SnippetProcessWrapper($element, $this->modx, (string) $name);
        }
        if ($element instanceof modChunk) {
            return new ChunkProcessWrapper($element, $this->modx, (string) $name);
        }

        return $element;
    }

    /**
     * @param array $tag [0] => outerTag, [1] => innerTag
     * @param bool $processUncacheable
     * @return mixed
     */
    public function processTag($tag, $processUncacheable = true)
    {
        $tagString = is_array($tag) ? ($tag[0] ?? '') : (string) $tag;
        $parseTimeStart = microtime(true);
        $queryTimeStart = $this->modx->queryTime ?? 0;
        $queriesStart = $this->modx->executedQueries ?? 0;

        $result = $this->wrappedParser->processTag($tag, $processUncacheable);

        $parseTime = microtime(true) - $parseTimeStart;
        $queryTime = ($this->modx->queryTime ?? 0) - $queryTimeStart;
        $queries = ($this->modx->executedQueries ?? 0) - $queriesStart;

        if (!empty($this->modx->modx3profilertoolbar)) {
            $this->modx->modx3profilertoolbar->trackTag($tagString, [
                'type' => $this->detectTagType($tagString),
                'cached' => strpos($tagString, '[[!') !== 0,
                'parse_time' => $parseTime,
                'queries' => $queries,
                'query_time' => $queryTime,
            ]);
        }

        return $result;
    }

    /**
     * CRITICAL: processElementTags must be explicitly forwarded to keep pdoTools/Fenom context.
     * This method is used by pdoTools Parser to process Fenom tags in templates.
     */
    public function processElementTags($parentTag, &$content, $processUncacheable = false, $removeUnprocessed = false, $prefix = '[[', $suffix = ']]', $tokens = [], $depth = 0)
    {
        return $this->wrappedParser->processElementTags($parentTag, $content, $processUncacheable, $removeUnprocessed, $prefix, $suffix, $tokens, $depth);
    }

    /**
     * Forward all other calls to wrapped parser.
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->wrappedParser, $method], $args);
    }

    /**
     * Detect tag type from string
     */
    protected function detectTagType(string $tagString): string
    {
        if (preg_match('/^\[\[!?([%~+*#$])/', $tagString, $m)) {
            $token = $m[1];
            return match ($token) {
                '%' => 'lexicon',
                '~' => 'link',
                '+' => 'placeholder',
                '*' => 'resource_field',
                '#' => 'fastfield',
                '$' => 'chunk',
                default => 'unknown',
            };
        }
        if (preg_match('/^\[\[!?(\w+)/', $tagString, $m)) {
            return 'snippet';
        }
        // Fenom tags: {$_modx->runSnippet('name')}
        if (preg_match('/{.*runSnippet.*[\'"]([^\'"]+)/', $tagString, $m)) {
            return 'snippet';
        }
        if (preg_match('/{.*getChunk.*[\'"]([^\'"]+)/', $tagString, $m)) {
            return 'chunk';
        }
        return 'unknown';
    }

    /** Forward parser state to wrapped parser so pdoTools/Fenom see correct isProcessingUncacheable etc. */
    public function isProcessingUncacheable()
    {
        return method_exists($this->wrappedParser, 'isProcessingUncacheable')
            ? $this->wrappedParser->isProcessingUncacheable()
            : parent::isProcessingUncacheable();
    }

    public function isRemovingUnprocessed()
    {
        return method_exists($this->wrappedParser, 'isRemovingUnprocessed')
            ? $this->wrappedParser->isRemovingUnprocessed()
            : parent::isRemovingUnprocessed();
    }

    public function isProcessingTag()
    {
        return method_exists($this->wrappedParser, 'isProcessingTag')
            ? $this->wrappedParser->isProcessingTag()
            : parent::isProcessingTag();
    }

    public function isProcessingElement()
    {
        return method_exists($this->wrappedParser, 'isProcessingElement')
            ? $this->wrappedParser->isProcessingElement()
            : parent::isProcessingElement();
    }
}
