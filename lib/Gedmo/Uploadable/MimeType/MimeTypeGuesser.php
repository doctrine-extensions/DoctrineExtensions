<?php

namespace Gedmo\Uploadable\MimeType;

use Gedmo\Exception\UploadableFileNotReadableException;
use Gedmo\Exception\UploadableInvalidFileException;

/**
 * Mime type guesser
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MimeTypeGuesser implements MimeTypeGuesserInterface
{
    public function guess($filePath)
    {
        if (!is_file($filePath)) {
            throw new UploadableInvalidFileException(sprintf('File "%s" does not exist.',
                $filePath
            ));
        }

        if (!is_readable($filePath)) {
            throw new UploadableFileNotReadableException(sprintf('File "%s" is not readable.',
                $filePath
            ));
        }

        if (function_exists('finfo_open')) {
            if (!$finfo = new \finfo(FILEINFO_MIME_TYPE)) {
                return null;
            }

            return $finfo->file($filePath);
        }

        return null;
    }
}
