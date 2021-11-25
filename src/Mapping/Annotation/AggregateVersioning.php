<?php

declare(strict_types=1);

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 *
 * @author Maksim Vorozhtsov <myks1992@mail.ru>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AggregateVersioning extends Annotation
{
    /** @var string */
    public $aggregateRootMethod;
}
