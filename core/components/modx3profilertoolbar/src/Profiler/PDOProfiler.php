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
        $statement = false;
        $thrownException = null;

        try {
            $statement = $this->originalPdo->query($query, $fetchMode, ...$fetchModeArgs);
        } catch (\Throwable $e) {
            $thrownException = $e;
        }

        $time = microtime(true) - $startTime;
        
        // Always log the query, even if it failed
        $this->interceptor->interceptQuery($query, [], $time);

        if ($thrownException !== null) {
            throw $thrownException;
        }

        return $statement;
    }

    /**
     * Intercept prepare() calls.
     * Returns PDOStatementProfiler wrapper (compatible with xPDO).
     */
    public function prepare(string $query, array $options = []): mixed
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
        $result = false;
        $thrownException = null;

        try {
            $result = $this->originalPdo->exec($query);
        } catch (\Throwable $e) {
            $thrownException = $e;
        }

        $time = microtime(true) - $startTime;
        $this->interceptor->interceptQuery($query, [], $time);

        if ($thrownException !== null) {
            throw $thrownException;
        }

        return $result;
    }

    /**
     * Delegate other PDO methods to the original PDO.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->originalPdo->$name(...$arguments);
    }

    /**
     * Delegate __get to original PDO.
     */
    public function __get(string $name): mixed
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
