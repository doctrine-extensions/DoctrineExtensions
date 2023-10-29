<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Gedmo\Mapping\Annotation\Annotation as GedmoAnnotation;
use Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorInterface;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * Uploadable annotation for Uploadable behavioral extension
 *
 * @Annotation
 *
 * @NamedArgumentConstructor
 *
 * @Target("CLASS")
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Uploadable implements GedmoAnnotation
{
    use ForwardCompatibilityTrait;

    public bool $allowOverwrite = false;

    public bool $appendNumber = false;

    public string $path = '';

    public string $pathMethod = '';

    public string $callback = '';

    /**
     * @phpstan-var Validator::FILENAME_GENERATOR_*|class-string<FilenameGeneratorInterface>
     */
    public string $filenameGenerator = Validator::FILENAME_GENERATOR_NONE;

    public string $maxSize = '0';

    /**
     * @var string A list of comma separate values of allowed types, like "text/plain,text/css"
     */
    public string $allowedTypes = '';

    /**
     * @var string A list of comma separate values of disallowed types, like "video/jpeg,text/html"
     */
    public string $disallowedTypes = '';

    /**
     * @param array<string, mixed> $data
     */
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

            $args = func_get_args();

            $this->allowOverwrite = $this->getAttributeValue($data, 'allowOverwrite', $args, 1, $allowOverwrite);
            $this->appendNumber = $this->getAttributeValue($data, 'appendNumber', $args, 2, $appendNumber);
            $this->path = $this->getAttributeValue($data, 'path', $args, 3, $path);
            $this->pathMethod = $this->getAttributeValue($data, 'pathMethod', $args, 4, $pathMethod);
            $this->callback = $this->getAttributeValue($data, 'callback', $args, 5, $callback);
            $this->filenameGenerator = $this->getAttributeValue($data, 'filenameGenerator', $args, 6, $filenameGenerator);
            $this->maxSize = $this->getAttributeValue($data, 'maxSize', $args, 7, $maxSize);
            $this->allowedTypes = $this->getAttributeValue($data, 'allowedTypes', $args, 8, $allowedTypes);
            $this->disallowedTypes = $this->getAttributeValue($data, 'disallowedTypes', $args, 9, $disallowedTypes);

            return;
        }

        $this->allowOverwrite = $allowOverwrite;
        $this->appendNumber = $appendNumber;
        $this->path = $path;
        $this->pathMethod = $pathMethod;
        $this->callback = $callback;
        $this->filenameGenerator = $filenameGenerator;
        $this->maxSize = $maxSize;
        $this->allowedTypes = $allowedTypes;
        $this->disallowedTypes = $disallowedTypes;
    }
}
