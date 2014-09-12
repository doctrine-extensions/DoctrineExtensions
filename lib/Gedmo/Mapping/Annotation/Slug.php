<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Slug annotation for Sluggable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Slug extends Annotation
{
    /** @var array<string> @Required */
    public $fields = array();
    /** @var boolean */
    public $updatable = true;
    /** @var string */
    public $style = 'default'; // or "camel"
    /** @var boolean */
    public $unique = true;
    /** @var string */
    public $unique_base = null;
    /** @var string */
    public $separator = '-';
    /** @var string */
    public $prefix = '';
    /** @var string */
    public $suffix = '';
    /** @var array<Gedmo\Mapping\Annotation\SlugHandler> */
    public $handlers = array();
    /** @var string */
    public $dateFormat = 'Y-m-d-H:i';
}
