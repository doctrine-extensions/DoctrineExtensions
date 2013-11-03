<?php

namespace Gedmo\Sluggable\Util;

/**
 * This is the part taken from Doctrine 1.2.3
 * Doctrine inflector has static methods for inflecting text
 *
 * The methods in these classes are from several different sources collected
 * across several different php projects and several different authors. The
 * original author names and emails are not known
 *
 * Uses 3rd party libraries and functions:
 *         http://sourceforge.net/projects/phputf8
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 3189 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author         <hsivonen@iki.fi>
 */
class Urlizer
{
    /**
     * Check if a string has utf7 characters in it
     *
     * By bmorel at ssi dot fr
     *
     * @param  string $string
     * @return boolean $bool
     */
    public static function seemsUtf8($string)
    {
        for ($i = 0; $i < strlen($string); $i++) {
            if (ord($string[$i]) < 0x80) continue; # 0bbbbbbb
            elseif ((ord($string[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
            elseif ((ord($string[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
            elseif ((ord($string[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
            elseif ((ord($string[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
            elseif ((ord($string[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
            else return false; # Does not match any model
            for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == strlen($string)) || ((ord($string[$i]) & 0xC0) != 0x80))
                return false;
            }
        }
        return true;
    }

    /**
     * Remove any illegal characters, accents, etc.
     *
     * @param  string $string  String to unaccent
     * @return string $string  Unaccented string
     */
    public static function unaccent($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        if (self::seemsUtf8($string)) {
            $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
            chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
            chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
            chr(195).chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
            chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
            chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
            chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
            chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
            chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
            chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
            chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
            chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
            chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
            chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
            chr(197).chr(148) => 'R', chr(197).chr(149) => 'r',
            chr(197).chr(150) => 'R', chr(197).chr(151) => 'r',
            chr(197).chr(152) => 'R', chr(197).chr(153) => 'r',
            chr(197).chr(154) => 'S', chr(197).chr(155) => 's',
            chr(197).chr(156) => 'S', chr(197).chr(157) => 's',
            chr(197).chr(158) => 'S', chr(197).chr(159) => 's',
            chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
            chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
            chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
            chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
            chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
            chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
            chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
            chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
            chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
            chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
            chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
            chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
            chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
            chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
            chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
            chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
            // Euro Sign
            chr(226).chr(130).chr(172) => 'E',
            // GBP (Pound) Sign
            chr(194).chr(163) => '',
            'Ä' => 'Ae', 'ä' => 'ae', 'Ü' => 'Ue', 'ü' => 'ue',
            'Ö' => 'Oe', 'ö' => 'oe', 'ß' => 'ss',
            // Norwegian characters
            'Å'=>'Aa','Æ'=>'Ae','Ø'=>'O','æ'=>'a','ø'=>'o','å'=>'aa'
            );

            $string = strtr($string, $chars);
        } else {
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
            .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
            .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
            .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
            .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
            .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
            .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
            .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
            .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
            .chr(252).chr(253).chr(255);

            $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

            $string = strtr($string, $chars['in'], $chars['out']);
            $doubleChars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
            $doubleChars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
            $string = str_replace($doubleChars['in'], $doubleChars['out'], $string);
        }

        return $string;
    }

    /**
    * US-ASCII transliterations of Unicode text
    * Ported Sean M. Burke's Text::Unidecode Perl module (He did all the hard work!)
    * Warning: you should only pass this well formed UTF-8!
    * Be aware it works by making a copy of the input string which it appends transliterated
    * characters to - it uses a PHP output buffer to do this - it means, memory use will increase,
    * requiring up to the same amount again as the input string
    *
    * @see http://search.cpan.org/~sburke/Text-Unidecode-0.04/lib/Text/Unidecode.pm
    * @param string $str UTF-8 string to convert
    * @author <hsivonen@iki.fi>
    * @param string $unknown (default = ?) Character use if character unknown
    * @return string US-ASCII string
    */
    public static function utf8ToAscii($str, $unknown = '?')
    {
        static $UTF8_TO_ASCII;

        if (strlen($str) == 0) {
            return;
        }

        preg_match_all('/.{1}|[^\x00]{1,1}$/us', $str, $ar);
        $chars = $ar[0];

        foreach ($chars as $i => $c) {
            $ud = 0;
            if (ord($c{0})>=0 && ord($c{0})<=127) { continue; } // ASCII - next please
            if (ord($c{0})>=192 && ord($c{0})<=223) { $ord = (ord($c{0})-192)*64 + (ord($c{1})-128); }
            if (ord($c{0})>=224 && ord($c{0})<=239) { $ord = (ord($c{0})-224)*4096 + (ord($c{1})-128)*64 + (ord($c{2})-128); }
            if (ord($c{0})>=240 && ord($c{0})<=247) { $ord = (ord($c{0})-240)*262144 + (ord($c{1})-128)*4096 + (ord($c{2})-128)*64 + (ord($c{3})-128); }
            if (ord($c{0})>=248 && ord($c{0})<=251) { $ord = (ord($c{0})-248)*16777216 + (ord($c{1})-128)*262144 + (ord($c{2})-128)*4096 + (ord($c{3})-128)*64 + (ord($c{4})-128); }
            if (ord($c{0})>=252 && ord($c{0})<=253) { $ord = (ord($c{0})-252)*1073741824 + (ord($c{1})-128)*16777216 + (ord($c{2})-128)*262144 + (ord($c{3})-128)*4096 + (ord($c{4})-128)*64 + (ord($c{5})-128); }
            if (ord($c{0})>=254 && ord($c{0})<=255) { $chars{$i} = $unknown; continue; } //error

            $bank = $ord >> 8;

            if (!array_key_exists($bank, (array)$UTF8_TO_ASCII)) {
                $bankfile = __DIR__. '/data/'. sprintf("x%02x",$bank).'.php';
                if (file_exists($bankfile)) {
                    include $bankfile;
                } else {
                    $UTF8_TO_ASCII[$bank] = array();
                }
            }

            $newchar = $ord & 255;
            if (array_key_exists($newchar, $UTF8_TO_ASCII[$bank])) {
                $chars{$i} = $UTF8_TO_ASCII[$bank][$newchar];
            } else {
                $chars{$i} = $unknown;
            }
        }

        return implode('', $chars);
    }

    /**
     * Does not transliterate correctly eastern languages
     *
     * @param string $text
     * @param string $separator
     * @return string
     */
    public static function urlize($text, $separator = '-')
    {
        $text = self::unaccent($text);
        return self::postProcessText($text, $separator);
    }

    /**
     * Uses transliteration tables to convert any kind of utf8 character
     *
     * @param string $text
     * @param string $separator
     * @return string $text
     */
    public static function transliterate($text, $separator = '-')
    {
        if (preg_match('/[\x80-\xff]/', $text) && self::validUtf8($text)) {
            $text = self::utf8ToAscii($text);
        }
        return  self::postProcessText($text, $separator);
    }

    /**
    * Tests a string as to whether it's valid UTF-8 and supported by the
    * Unicode standard
    * Note: this function has been modified to simple return true or false
    * @author <hsivonen@iki.fi>
    * @param string UTF-8 encoded string
    * @return boolean true if valid
    * @see http://hsivonen.iki.fi/php-utf8/
    */
    public static function validUtf8($str)
    {
        $mState = 0;     // cached expected number of octets after the current octet
                         // until the beginning of the next UTF8 character sequence
        $mUcs4  = 0;     // cached Unicode character
        $mBytes = 1;     // cached expected number of octets in the current sequence

        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $in = ord($str{$i});
            if ($mState == 0) {
                // When mState is zero we expect either a US-ASCII character or a
                // multi-octet sequence.
                if (0 == (0x80 & ($in))) {
                    // US-ASCII, pass straight through.
                    $mBytes = 1;
                } elseif (0xC0 == (0xE0 & ($in))) {
                    // First octet of 2 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x1F) << 6;
                    $mState = 1;
                    $mBytes = 2;
                } elseif (0xE0 == (0xF0 & ($in))) {
                    // First octet of 3 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x0F) << 12;
                    $mState = 2;
                    $mBytes = 3;
                } elseif (0xF0 == (0xF8 & ($in))) {
                    // First octet of 4 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x07) << 18;
                    $mState = 3;
                    $mBytes = 4;
                } elseif (0xF8 == (0xFC & ($in))) {
                    /* First octet of 5 octet sequence.
                    *
                    * This is illegal because the encoded codepoint must be either
                    * (a) not the shortest form or
                    * (b) outside the Unicode range of 0-0x10FFFF.
                    * Rather than trying to resynchronize, we will carry on until the end
                    * of the sequence and let the later error handling code catch it.
                    */
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x03) << 24;
                    $mState = 4;
                    $mBytes = 5;
                } elseif (0xFC == (0xFE & ($in))) {
                    // First octet of 6 octet sequence, see comments for 5 octet sequence.
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 1) << 30;
                    $mState = 5;
                    $mBytes = 6;
                } else {
                    /* Current octet is neither in the US-ASCII range nor a legal first
                     * octet of a multi-octet sequence.
                     */
                    return false;
                }
            } else {
                // When mState is non-zero, we expect a continuation of the multi-octet
                // sequence
                if (0x80 == (0xC0 & ($in))) {
                    // Legal continuation.
                    $shift = ($mState - 1) * 6;
                    $tmp = $in;
                    $tmp = ($tmp & 0x0000003F) << $shift;
                    $mUcs4 |= $tmp;
                    /**
                    * End of the multi-octet sequence. mUcs4 now contains the final
                    * Unicode codepoint to be output
                    */
                    if (0 == --$mState) {
                        /*
                        * Check for illegal sequences and codepoints.
                        */
                        // From Unicode 3.1, non-shortest form is illegal
                        if (((2 == $mBytes) && ($mUcs4 < 0x0080)) ||
                            ((3 == $mBytes) && ($mUcs4 < 0x0800)) ||
                            ((4 == $mBytes) && ($mUcs4 < 0x10000)) ||
                            (4 < $mBytes) ||
                            // From Unicode 3.2, surrogate characters are illegal
                            (($mUcs4 & 0xFFFFF800) == 0xD800) ||
                            // Codepoints outside the Unicode range are illegal
                            ($mUcs4 > 0x10FFFF)
                        ) {
                            return false;
                        }
                        //initialize UTF8 cache
                        $mState = 0;
                        $mUcs4  = 0;
                        $mBytes = 1;
                    }
                } else {
                    /**
                    *((0xC0 & (*in) != 0x80) && (mState != 0))
                    * Incomplete multi-octet sequence.
                    */
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Cleans up the text and adds separator
     *
     * @param string $text
     * @param string $separator
     * @return string
     */
    private static function postProcessText($text, $separator)
    {
        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text);
        } else {
            $text = strtolower($text);
        }

        // Remove all none word characters
        $text = preg_replace('/\W/', ' ', $text);

        // More stripping. Replace spaces with dashes
        $text = strtolower(preg_replace('/[^A-Za-z0-9\/]+/', $separator,
                           preg_replace('/([a-z\d])([A-Z])/', '\1_\2',
                           preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1_\2',
                           preg_replace('/::/', '/', $text)))));

        return trim($text, $separator);
    }
}
