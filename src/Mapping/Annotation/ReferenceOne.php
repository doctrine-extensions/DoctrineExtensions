<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Attribute;

/**
 * Reference annotation for ORM -> ODM references extension
 * to be user like "@ReferenceOne(type="entity", class="MyEntity", identifier="entity_id")"
 *
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * @Annotation
 *
 * @NamedArgumentConstructor
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ReferenceOne extends Reference
{
}
