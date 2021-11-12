<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * IpTraceable annotation for IpTraceable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
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
