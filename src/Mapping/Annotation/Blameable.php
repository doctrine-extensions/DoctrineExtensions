<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Deprecations\Deprecation;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * Blameable annotation for Blameable behavioral extension
 *
 * @Annotation
 *
 * @NamedArgumentConstructor
 *
 * @Target("PROPERTY")
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Blameable implements GedmoAnnotation
{
    use ForwardCompatibilityTrait;

    public string $on = 'update';
    /** @var string|string[] */
    public $field;
    /** @var mixed */
    public $value;
    /** @var string */
    public $setterMethod;

    /**
     * @param array<string, mixed> $data
     * @param string|string[]|null $field
     * @param mixed                $value
     */
    public function __construct(
        array $data = [],
        string $on = 'update',
        $field = null,
        $value = null,
        string $setterMethod = ''
    ) {
        if ([] !== $data) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2375',
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            );

            $args = func_get_args();

            $this->on = $this->getAttributeValue($data, 'on', $args, 1, $on);
            $this->field = $this->getAttributeValue($data, 'field', $args, 2, $field);
            $this->value = $this->getAttributeValue($data, 'value', $args, 3, $value);
            $this->setterMethod = $this->getAttributeValue($data, 'setterMethod', $args, 4, $setterMethod);

            return;
        }

        $this->on = $on;
        $this->field = $field;
        $this->value = $value;
        $this->setterMethod = $setterMethod;
    }
}
