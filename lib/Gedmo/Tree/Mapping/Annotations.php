<?php

namespace Gedmo\Tree\Mapping;

use Doctrine\Common\Annotations\Annotation;


/**
 * These are Tree extension annotations which should be used
 * on proper Tree Node Entity to activate the Tree listener
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Mapping
 * @subpackage Annotations
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TreeLeft extends Annotation {}
final class TreeRight extends Annotation {}
final class TreeParent extends Annotation {}
final class TreeLevel extends Annotation {}