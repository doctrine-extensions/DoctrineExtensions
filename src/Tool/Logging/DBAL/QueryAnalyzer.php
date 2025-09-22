<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\Logging\DBAL;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.5.
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class QueryAnalyzer implements SQLLogger
{
    /**
     * Used database platform
     *
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * Start time of currently executed query
     */
    private ?float $queryStartTime = null;

    /**
     * Total execution time of all queries
     */
    private int $totalExecutionTime = 0;

    /**
     * List of queries executed
     *
     * @var string[]
     */
    private array $queries = [];

    /**
     * Query execution times indexed
     * in same order as queries
     *
     * @var float[]
     */
    private array $queryExecutionTimes = [];

    /**
     * Initialize log listener with database
     * platform, which is needed for parameter
     * conversion
     */
    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return void
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        $this->queryStartTime = microtime(true);
        $this->queries[] = $this->generateSql($sql, $params, $types);
    }

    /**
     * @return void
     */
    public function stopQuery()
    {
        $ms = (int) (round(microtime(true) - $this->queryStartTime, 4) * 1000);
        $this->queryExecutionTimes[] = $ms;
        $this->totalExecutionTime += $ms;
    }

    /**
     * Clean all collected data
     *
     * @return QueryAnalyzer
     */
    public function cleanUp()
    {
        $this->queries = [];
        $this->queryExecutionTimes = [];
        $this->totalExecutionTime = 0;

        return $this;
    }

    /**
     * Dump the statistics of executed queries
     *
     * @param bool $dumpOnlySql
     *
     * @return string
     */
    public function getOutput($dumpOnlySql = false)
    {
        $output = '';
        if (!$dumpOnlySql) {
            $output .= 'Platform: '.$this->platform->getName().PHP_EOL;
            $output .= 'Executed queries: '.count($this->queries).', total time: '.$this->totalExecutionTime.' ms'.PHP_EOL;
        }
        foreach ($this->queries as $index => $sql) {
            if (!$dumpOnlySql) {
                $output .= 'Query('.($index + 1).') - '.$this->queryExecutionTimes[$index].' ms'.PHP_EOL;
            }
            $output .= $sql.';'.PHP_EOL;
        }
        $output .= PHP_EOL;

        return $output;
    }

    /**
     * Index of the slowest query executed
     *
     * @return int
     */
    public function getSlowestQueryIndex()
    {
        $index = 0;
        $slowest = 0;
        foreach ($this->queryExecutionTimes as $i => $time) {
            if ($time > $slowest) {
                $slowest = $time;
                $index = $i;
            }
        }

        return $index;
    }

    /**
     * Get total execution time of queries
     *
     * @return float
     */
    public function getTotalExecutionTime()
    {
        return $this->totalExecutionTime;
    }

    /**
     * Get all queries
     *
     * @return string[]
     */
    public function getExecutedQueries()
    {
        return $this->queries;
    }

    /**
     * Get number of executed queries
     *
     * @return int
     */
    public function getNumExecutedQueries()
    {
        return count($this->queries);
    }

    /**
     * Get all query execution times
     *
     * @return float[]
     */
    public function getExecutionTimes()
    {
        return $this->queryExecutionTimes;
    }

    /**
     * Create the SQL with mapped parameters
     *
     * @param array<int|string, mixed>|null       $params
     * @param array<int|string, string|Type>|null $types
     */
    private function generateSql(string $sql, ?array $params, ?array $types): string
    {
        if (null === $params || [] === $params) {
            return $sql;
        }
        $converted = $this->getConvertedParams($params, $types);
        if (is_int(key($params))) {
            $index = key($converted);
            $sql = preg_replace_callback('@\?@sm', static function ($match) use (&$index, $converted) {
                return $converted[$index++];
            }, $sql);
        } else {
            foreach ($converted as $key => $value) {
                $sql = str_replace(':'.$key, $value, $sql);
            }
        }

        return $sql;
    }

    /**
     * Get the converted parameter list
     *
     * @param array<int|string, mixed>       $params
     * @param array<int|string, string|Type> $types
     *
     * @return array<int|string, mixed>
     */
    private function getConvertedParams(array $params, array $types): array
    {
        $result = [];
        foreach ($params as $position => $value) {
            if (isset($types[$position])) {
                $type = $types[$position];
                if (is_string($type)) {
                    $type = Type::getType($type);
                }
                if ($type instanceof Type) {
                    $value = $type->convertToDatabaseValue($value, $this->platform);
                }
            } else {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format($this->platform->getDateTimeFormatString());
                } elseif (null !== $value) {
                    $type = Type::getType(gettype($value));
                    $value = $type->convertToDatabaseValue($value, $this->platform);
                }
            }
            if (is_string($value)) {
                $value = "'{$value}'";
            } elseif (null === $value) {
                $value = 'NULL';
            }
            $result[$position] = $value;
        }

        return $result;
    }
}
