<?php

namespace Gedmo\Uploadable\MimeType;

/**
 * Interface for mime type guessers
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface MimeTypeGuesserInterface
{
    public function guess($filePath);
}
