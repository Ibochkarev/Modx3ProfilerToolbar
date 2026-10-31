<?php
/**
 * PDO wrapper for SQL query interception and N+1 detection.
 * Uses composition instead of inheritance to avoid PDO constructor issues.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Profiler;

use PDO;
use PDOStatement;

class PDOProfiler
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
     * @return PDOStatement|false
     */
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        if (!$this->isProfiling) {
            return $this->originalPdo->query($query, $fetchMode, ...$fetchModeArgs);
        }

        $startTime = microtime(true);
        try {
            $statement = $this->originalPdo->query($query, $fetchMode, ...$fetchModeArgs);
            $time = microtime(true) - $startTime;
            
            if ($statement !== false) {
                $this->interceptor->interceptQuery($query, [], $time);
            }
            
            return $statement;
        } catch (\Throwable $e) {
            $time = microtime(true) - $startTime;
            $this->interceptor->interceptQuery($query, [], $time);
            throw $e;
        }
    }

    /**
     * Intercept prepare() calls.
     * @return PDOStatementProfiler|false
     */
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        if (!$this->isProfiling) {
            return $this->originalPdo->prepare($query, $options);
        }

        $statement = $this->originalPdo->prepare($query, $options);
        
        if ($statement !== false) {
            return new PDOStatementProfiler($statement, $query, $this->interceptor, $this->isProfiling);
        }

        return $statement;
    }

    /**
     * Delegate exec() calls.
     */
    public function exec(string $query): int|false
    {
        if (!$this->isProfiling) {
            return $this->originalPdo->exec($query);
        }

        $startTime = microtime(true);
        try {
            $result = $this->originalPdo->exec($query);
            $time = microtime(true) - $startTime;
            $this->interceptor->interceptQuery($query, [], $time);
            return $result;
        } catch (\Throwable $e) {
            $time = microtime(true) - $startTime;
            $this->interceptor->interceptQuery($query, [], $time);
            throw $e;
        }
    }

    /**
     * Delegate other PDO methods to the original PDO.
     */
    public function __call($name, $arguments): mixed
    {
        return $this->originalPdo->$name(...$arguments);
    }

    /**
     * Delegate __get to original PDO.
     */
    public function __get($name): mixed
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
