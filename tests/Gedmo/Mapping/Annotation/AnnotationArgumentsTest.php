<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Annotation;

use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Gedmo\Mapping\Annotation\Annotation;
use Gedmo\Mapping\Annotation\Blameable;
use PHPUnit\Framework\TestCase;

/**
 * Remove this class when support for array based attributes in annotation classes is removed.
 *
 * @group legacy
 */
final class AnnotationArgumentsTest extends TestCase
{
    use VerifyDeprecations;

    /**
     * @param array<string, mixed> $expected
     * @param mixed[]              $args
     *
     * @dataProvider getGedmoAnnotations
     *
     * @param class-string<Annotation> $class
     */
    public function testArguments(array $expected, string $class, array $args, ?string $expectedDeprecationIdentifier = null): void
    {
        if (null !== $expectedDeprecationIdentifier) {
            $this->expectDeprecationWithIdentifier($expectedDeprecationIdentifier);
        }

        $annotation = new $class(...$args);

        foreach ($expected as $attribute => $value) {
            static::assertSame($value, $annotation->$attribute);
        }
    }

    /**
     * @phpstan-return iterable<string, array{0: array<string, string|null>, 1: class-string<Blameable>, 2: array<int, array<string, string>|string>, 3?: string}>
     */
    public static function getGedmoAnnotations(): iterable
    {
        yield 'args_without_data' => [['on' => 'delete', 'field' => 'some'], Blameable::class, [[], 'delete', 'some']];
        yield 'default_values_without_args' => [['on' => 'update', 'field' => null, 'value' => null], Blameable::class, []];
        yield 'default_values_with_args' => [['on' => 'update', 'field' => null, 'value' => null], Blameable::class, [[], 'update']];

        yield 'args_with_data' => [
            ['on' => 'delete', 'field' => 'some'],
            Blameable::class, [['on' => 'change', 'field' => 'id'], 'delete', 'some'],
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2375',
        ];
        yield 'data_without_args' => [
            ['on' => 'change', 'field' => 'id'],
            Blameable::class, [['on' => 'change', 'field' => 'id']],
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2375',
        ];
        yield 'default_values_with_args_and_data' => [
            ['on' => 'update', 'field' => null, 'value' => null],
            Blameable::class, [['on' => 'change'], 'update'],
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2375',
        ];
    }
}
