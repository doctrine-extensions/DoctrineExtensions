<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * SlugHandler annotation for Sluggable behavioral extension
 *
 * @Annotation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SlugHandler extends Annotation
{
    public $class = '';
    public $options = [];
}
