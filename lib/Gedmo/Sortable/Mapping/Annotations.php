<?php

namespace Gedmo\Sortable\Mapping;

use Doctrine\Common\Annotations\Annotation;

/**
 * These are Sluggable extension annotations which should be used
 * for slug generation on any Entity from sluggable fields
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable.Mapping
 * @subpackage Annotations
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SortIdentifier extends Annotation {}
final class Sort extends Annotation {}