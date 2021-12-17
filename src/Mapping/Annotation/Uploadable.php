<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
 */
final class Uploadable extends Annotation
{
    /**
     * @var bool
     */
    public $allowOverwrite = false;

    /**
     * @var bool
     */
    public $appendNumber = false;

    /**
     * @var string
     */
    public $path = '';

    /**
     * @var string
     */
    public $pathMethod = '';

    /**
     * @var string
     */
    public $callback = '';

    /**
     * @var string
     */
    public $filenameGenerator = Validator::FILENAME_GENERATOR_NONE;

    /**
     * @var float
     */
    public $maxSize = 0;

    /**
     * @var string A list of comma separate values of allowed types, like "text/plain,text/css"
     */
    public $allowedTypes = '';

    /**
     * @var string A list of comma separate values of disallowed types, like "video/jpeg,text/html"
     */
    public $disallowedTypes = '';
}
