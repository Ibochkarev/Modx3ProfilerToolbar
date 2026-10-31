<?php
/**
 * PDOStatement wrapper for query execution interception.
 *
 * @package modx3profilertoolbar
 */

namespace Modx3ProfilerToolbar\Profiler;

use PDOStatement;

class PDOStatementProfiler
{
    /** @var PDOStatement */
    protected $statement;

    /** @var string */
    protected $query;

    /** @var PDOInterceptor */
    protected $interceptor;

    /** @var bool */
    protected $isProfiling;

    /** @var array */
    protected $boundParams = [];

    /** @var array References for bindParam */
    protected $boundParamRefs = [];

    public function __construct(PDOStatement $statement, string $query, PDOInterceptor $interceptor, bool $isProfiling)
    {
        $this->statement = $statement;
        $this->query = $query;
        $this->interceptor = $interceptor;
        $this->isProfiling = $isProfiling;
    }

    /**
     * Intercept execute() calls.
     */
    public function execute(?array $params = null): bool
    {
        if (!$this->isProfiling) {
            return $this->statement->execute($params);
        }

        $startTime = microtime(true);
        $result = false;
        $thrownException = null;

        try {
            // Update bound params from references before execution
            foreach ($this->boundParamRefs as $key => &$ref) {
                $this->boundParams[$key] = $ref;
            }

            // If params were bound via bindParam/bindValue, execute without params
            // Otherwise use provided params
            if (!empty($this->boundParams)) {
                $result = $this->statement->execute();
            } else {
                $result = $this->statement->execute($params);
            }
        } catch (\Throwable $e) {
            $thrownException = $e;
        }

        $time = microtime(true) - $startTime;

        // Use explicit params or bound params for logging
        $finalParams = $params ?? $this->boundParams;

        $this->interceptor->interceptQuery($this->query, $finalParams, $time);

        if ($thrownException !== null) {
            throw $thrownException;
        }

        return $result;
    }

    /**
     * Intercept bindValue() to capture parameters.
     */
    public function bindValue(int|string $param, mixed $value, int $type = \PDO::PARAM_NULL): bool
    {
        $this->boundParams[$param] = $value;
        return $this->statement->bindValue($param, $value, $type);
    }

    /**
     * Intercept bindParam() to capture parameter references.
     */
    public function bindParam(int|string $param, mixed &$value, int $type = \PDO::PARAM_NULL, ?int $maxLength = null): bool
    {
        // Store reference for later retrieval
        $this->boundParamRefs[$param] = &$value;
        $this->boundParams[$param] = $value; // Initial value
        return $this->statement->bindParam($param, $value, $type, $maxLength ?? 0);
    }

    /**
     * Delegate other PDOStatement methods to the original statement.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->statement->$name(...$arguments);
    }

    /**
     * Delegate __get to original PDOStatement.
     */
    public function __get(string $name): mixed
    {
        return $this->statement->$name;
    }

    /**
     * Get the original PDOStatement.
     */
    public function getOriginalStatement(): PDOStatement
    {
        return $this->statement;
    }
}
