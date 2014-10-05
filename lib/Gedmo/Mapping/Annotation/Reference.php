<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Reference annotation for ORM -> ODM references extension
 * to be user like "@ReferenceMany(type="entity", class="MyEntity", identifier="entity_id")"
 *
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @Annotation
 */
abstract class Reference extends Annotation
{
    public $type;
    public $class;
    public $identifier;
    public $mappedBy;
    public $inversedBy;
}
