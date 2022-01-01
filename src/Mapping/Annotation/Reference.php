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
 * Reference annotation for ORM -> ODM references extension
 * to be user like "@ReferenceMany(type="entity", class="MyEntity", identifier="entity_id")"
 *
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @Annotation
 */
abstract class Reference implements GedmoAnnotation
{
    /**
     * @var string|null
     * @phpstan-var 'entity'|'document'|null
     */
    public $type;

    /**
     * @var string|null
     * @phpstan-var class-string|null
     */
    public $class;

    /**
     * @var string|null
     */
    public $identifier;

    /**
     * @var string|null
     */
    public $mappedBy;

    /**
     * @var string|null
     */
    public $inversedBy;

    /**
     * @phpstan-param class-string|null $class
     */
    public function __construct(
        array $data = [],
        ?string $type = null,
        ?string $class = null,
        ?string $identifier = null,
        ?string $mappedBy = null,
        ?string $inversedBy = null
    ) {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        $this->type = $data['type'] ?? $type;
        $this->class = $data['class'] ?? $class;
        $this->identifier = $data['identifier'] ?? $identifier;
        $this->mappedBy = $data['mappedBy'] ?? $mappedBy;
        $this->inversedBy = $data['inversedBy'] ?? $inversedBy;
    }
}
