<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tool;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * TODO: Remove it when dropping support of doctrine/dbal 2
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class QueryAnalyzer implements SQLLogger
{
    /**
     * Used database platform
     *
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * List of queries executed
     *
     * @var string[]
     */
    private $queries = [];

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    public function startQuery($sql, array $params = null, array $types = null): void
    {
        $this->queries[] = $this->generateSql($sql, $params, $types);
    }

    public function stopQuery(): void
    {
    }

    public function cleanUp(): self
    {
        $this->queries = [];

        return $this;
    }

    /**
     * @return string[]
     */
    public function getExecutedQueries(): array
    {
        return $this->queries;
    }

    public function getNumExecutedQueries(): int
    {
        return count($this->queries);
    }

    /**
     * Create the SQL with mapped parameters
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
