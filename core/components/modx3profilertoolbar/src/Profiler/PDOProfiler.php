<?php
/**
 * PDO wrapper for SQL query interception and N+1 detection.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Profiler;

use PDO;
use PDOStatement;

class PDOProfiler extends PDO
{
    /** @var PDO */
    protected $originalPdo;

    /** @var SQLProfiler */
    protected $sqlProfiler;

    /** @var PDOInterceptor */
    protected $interceptor;

    /** @var bool */
    protected $isProfiling = false;

    public function __construct(PDO $pdo, SQLProfiler $sqlProfiler, PDOInterceptor $interceptor)
    {
        // Don't call parent::__construct — we delegate to the real PDO
        $this->originalPdo = $pdo;
        $this->sqlProfiler = $sqlProfiler;
        $this->interceptor = $interceptor;
    }

    public function startProfiling(): void
    {
        $this->isProfiling = true;
    }

    public function stopProfiling(): void
    {
        $this->isProfiling = false;
    }

    /**
     * Intercept query() calls.
     */
    public function query($query, $args = []): false|PDOStatement
    {
        if (!$this->isProfiling) {
            return $this->originalPdo->query($query, $args);
        }

        $startTime = microtime(true);
        try {
            $statement = $this->originalPdo->query($query, $args);
            $time = microtime(true) - $startTime;
            $this->interceptor->interceptQuery($query, [], $time);
            return $statement;
        } catch (\Throwable $e) {
            $time = microtime(true) - $startTime;
            $this->interceptor->interceptQuery($query, [], $time);
            throw $e;
        }
    }

    /**
     * Intercept prepare() calls.
     */
    public function prepare($query, $args = []): false|PDOStatement
    {
        if (!$this->isProfiling) {
            return $this->originalPdo->prepare($query, $args);
        }

        $startTime = microtime(true);
        $statement = $this->originalPdo->prepare($query, $args);
        $time = microtime(true) - $startTime;

        // Log the prepare (execution happens via execute())
        if ($statement) {
            return new PDOStatementProfiler($statement, $query, $this->interceptor, $this->isProfiling);
        }

        return $statement;
    }

    /**
     * Delegate other PDO methods to the original PDO.
     */
    public function __call($name, $arguments)
    {
        return $this->originalPdo->$name(...$arguments);
    }

    /**
     * Delegate __get to original PDO.
     */
    public function __get($name)
    {
        return $this->originalPdo->$name;
    }

    /**
     * Delegate __set to original PDO.
     */
    public function __set($name, $value): void
    {
        $this->originalPdo->$name = $value;
    }

    /**
     * Get the original PDO connection.
     */
    public function getOriginalPdo(): PDO
    {
        return $this->originalPdo;
    }
}
