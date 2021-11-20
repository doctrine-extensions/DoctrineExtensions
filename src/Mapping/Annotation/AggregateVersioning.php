<?php

declare(strict_types=1);

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 */
final class AggregateVersioning extends Annotation
{
    /** @var string */
    public $aggregateRootMethod;
}
