<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AggregateVersioning implements \Gedmo\Mapping\Annotation\Annotation
{
    /** @var string */
    public $aggregateRootMethod = 'getRoot';

    /**
     * @param array|string $data data array managed by the Doctrine Annotations library or the path
     */
    public function __construct(
        $data = [],
        string $aggregateRootMethod = 'getRoot'
    ) {
        if (\is_string($data)) {
            $data = ['aggregateRootMethod' => $data];
        } elseif (!\is_array($data)) {
            throw new \TypeError(sprintf('"%s": Argument $data is expected to be a string or array, got "%s".', __METHOD__, get_debug_type($data)));
        }

        $this->aggregateRootMethod = $data['aggregateRootMethod'] ?? $aggregateRootMethod;
    }
}
