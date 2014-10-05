<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * Uploadable annotation for Uploadable behavioral extension
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Uploadable extends Annotation
{
    /** @var boolean */
    public $allowOverwrite = false;

    /** @var boolean */
    public $appendNumber = false;

    /** @var string */
    public $path = '';

    /** @var string */
    public $pathMethod = '';

    /** @var string */
    public $callback = '';

    /** @var string */
    public $filenameGenerator = Validator::FILENAME_GENERATOR_NONE;

    /** @var double */
    public $maxSize = 0;

    /** @var array */
    public $allowedTypes = '';

    /** @var array */
    public $disallowedTypes = '';
}
