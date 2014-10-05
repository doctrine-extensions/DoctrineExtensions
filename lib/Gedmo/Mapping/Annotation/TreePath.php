<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * TreePath annotation for Tree behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author <rocco@roccosportal.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TreePath extends Annotation
{
    public $separator = ',';

    public $appendId = null;

    public $startsWithSeparator = false;

    public $endsWithSeparator = true;
}
