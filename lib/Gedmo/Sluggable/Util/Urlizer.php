<?php

namespace Gedmo\Sluggable\Util;

use Behat\Transliterator\Transliterator;

/**
 * Transliteration utility
 */
class Urlizer extends Transliterator
{
    /**
     * Generates a slug of the text after transliterating the UTF-8 string to ASCII.
     *
     * Unaccent umlauts/accents prior to transliteration.
     * Uses transliteration tables to convert any kind of utf8 character.
     *
     * @param string $text
     * @param string $separator
     *
     * @return string $text
     */
    public static function transliterate($text, $separator = '-')
    {
        $text = parent::unaccent($text);
        return parent::transliterate($text, $separator);
    }
}
