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
 * SlugHandlerOption annotation for Sluggable behavioral extension
 *
 * @Annotation
 *
 * @NamedArgumentConstructor
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.18, will be removed in version 4.0. Configure the options as
 *             an associative array on the {@see SlugHandler} attribute instead.
 */
final class SlugHandlerOption implements GedmoAnnotation
{
    use ForwardCompatibilityTrait;

    public string $name;

    /**
     * @var mixed
     */
    public $value;

    /**
     * @param array<string, mixed> $data
     * @param mixed                $value
     */
    public function __construct(
        array $data = [],
        string $name = '',
        $value = null
    ) {
        if ([] !== $data) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2379',
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            );

            $args = func_get_args();

            $this->name = $this->getAttributeValue($data, 'name', $args, 1, $name);
            $this->value = $this->getAttributeValue($data, 'value', $args, 2, $value);

            return;
        }

        $this->name = $name;
        $this->value = $value;
    }
}
