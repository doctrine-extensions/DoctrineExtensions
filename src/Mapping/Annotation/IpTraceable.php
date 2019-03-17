<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * IpTraceable annotation for IpTraceable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class IpTraceable extends Annotation
{
    /** @var string */
    public $on = 'update';
    /** @var string|array */
    public $field;
    /** @var mixed */
    public $value;
}
