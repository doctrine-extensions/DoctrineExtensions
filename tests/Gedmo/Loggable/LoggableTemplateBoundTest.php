<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Loggable\LogEntryInterface;
use Gedmo\Loggable\LoggableListener;
use PHPUnit\Framework\TestCase;

final class LoggableTemplateBoundTest extends TestCase
{
    /**
     * @return \Generator<string, array{class-string, string}>
     */
    public static function templateBoundProvider(): \Generator
    {
        yield 'loggable listener' => [LoggableListener::class, '@template'];
        yield 'log entry interface' => [LogEntryInterface::class, '@phpstan-template'];
        yield 'abstract log entry' => [AbstractLogEntry::class, '@phpstan-template'];
    }

    /**
     * @param class-string $className
     *
     * @dataProvider templateBoundProvider
     *
     * @throws \ReflectionException
     */
    public function testTemplateBoundIsObject(string $className, string $template): void
    {
        $docComment = (new \ReflectionClass($className))->getDocComment();

        static::assertIsString($docComment);
        static::assertStringContainsString($template.' T of object', $docComment);
    }
}
