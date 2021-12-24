<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * Uploadable annotation for Uploadable behavioral extension
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Uploadable implements GedmoAnnotation
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
     * @var string
     */
    public $maxSize = '0';

    /**
     * @var string A list of comma separate values of allowed types, like "text/plain,text/css"
     */
    public $allowedTypes = '';

    /**
     * @var string A list of comma separate values of disallowed types, like "video/jpeg,text/html"
     */
    public $disallowedTypes = '';

    public function __construct(
        array $data = [],
        bool $allowOverwrite = false,
        bool $appendNumber = false,
        string $path = '',
        string $pathMethod = '',
        string $callback = '',
        string $filenameGenerator = Validator::FILENAME_GENERATOR_NONE,
        string $maxSize = '0',
        string $allowedTypes = '',
        string $disallowedTypes = ''
    ) {
        if ([] !== $data) {
            @trigger_error(sprintf(
                'Passing an array as first argument to "%s()" is deprecated. Use named arguments instead.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        $this->allowOverwrite = $data['allowOverwrite'] ?? $allowOverwrite;
        $this->appendNumber = $data['appendNumber'] ?? $appendNumber;
        $this->path = $data['path'] ?? $path;
        $this->pathMethod = $data['pathMethod'] ?? $pathMethod;
        $this->callback = $data['callback'] ?? $callback;
        $this->filenameGenerator = $data['filenameGenerator'] ?? $filenameGenerator;
        $this->maxSize = $data['maxSize'] ?? $maxSize;
        $this->allowedTypes = $data['allowedTypes'] ?? $allowedTypes;
        $this->disallowedTypes = $data['disallowedTypes'] ?? $disallowedTypes;
    }
}
