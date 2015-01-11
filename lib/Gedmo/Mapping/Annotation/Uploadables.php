<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * Uploadables annotation for multiple Uploadable behavioral extension
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Franz Bruckner <franz.bruckner@gmx.at>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Uploadables
{
    
    /** @var array<\Gedmo\Mapping\Annotation\Uploadable> */
    public $uploadables = array();
}
