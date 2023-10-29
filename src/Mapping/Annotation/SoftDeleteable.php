<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;

/**
 * Group annotation for SoftDeleteable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * @Annotation
 *
 * @NamedArgumentConstructor
 *
 * @Target("CLASS")
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class SoftDeleteable implements GedmoAnnotation
{
    use ForwardCompatibilityTrait;

    public string $fieldName = 'deletedAt';

    public bool $timeAware = false;

    public bool $hardDelete = true;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [], string $fieldName = 'deletedAt', bool $timeAware = false, bool $hardDelete = true)
    {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);

            $args = func_get_args();

            $this->fieldName = $this->getAttributeValue($data, 'fieldName', $args, 1, $fieldName);
            $this->timeAware = $this->getAttributeValue($data, 'timeAware', $args, 2, $timeAware);
            $this->hardDelete = $this->getAttributeValue($data, 'hardDelete', $args, 3, $hardDelete);

            return;
        }

        $this->fieldName = $fieldName;
        $this->timeAware = $timeAware;
        $this->hardDelete = $hardDelete;
    }
}
