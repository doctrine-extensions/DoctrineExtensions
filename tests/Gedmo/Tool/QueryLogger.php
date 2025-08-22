<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tool;

use Psr\Log\AbstractLogger;

final class QueryLogger extends AbstractLogger
{
    /** @var array<int, array{message: string, context: mixed[]}> */
    public array $queries = [];

    /**
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->queries[] = [
            'message' => $message,
            'context' => $context,
        ];
    }

    public function reset(): void
    {
        $this->queries = [];
    }
}
