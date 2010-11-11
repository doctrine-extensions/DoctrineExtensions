<?php

namespace DoctrineExtensions\Tree\Mapping;

use Doctrine\Common\Annotations\Annotation;


/**
 * These are Tree extension annotations which should be used
 * on proper Tree Node Entity to activate the Tree listener
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Tree.Mapping
 * @subpackage Annotations
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Left extends Annotation {}
final class Right extends Annotation {}
final class Ancestor extends Annotation {}