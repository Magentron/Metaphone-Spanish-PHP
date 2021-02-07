<?php

namespace Magentron\MetaphoneSpanish;

/**
 *  The Spanish Metaphone Algorithm (Algoritmo del Metáfono para el Español)
 *
 *  This script implements the Metaphone algorithm (c) 1990 by Lawrence Philips.
 *  It was inspired by the English double metaphone algorithm implementation by
 *  Andrew Collins - January 12, 2007 who claims no rights to this work
 *  (http://www.atomodo.com/code/double-metaphone)
 *
 *  The metaphone port adapted to the Spanish Language is authored
 *  by Alejandro Mosquera <amosquera@dlsi.ua.es> November, 2011
 *  (https://github.com/amsqr/Spanish-Metaphone) and is covered
 *  under this copyright:
 *
 *  Copyright 2011, Alejandro Mosquera <amosquera@dlsi.ua.es>.  All rights reserved.
 *
 *  This metaphone port adapted to the Spanish Language for PHP is authored
 *  by Jeroen Derks <jeroen@derks.it> December, 2018
 *  (https://github.com/Magentron/Spanish-Metaphone-PHP) and is covered
 *  under this copyright:
 *
 *  Copyright 2018, Jeroen Derks <jeroen@derks.it>.  All rights reserved.
 *
 *  Redistribution and use in source and binary forms, with or without modification,
 *  are permitted provided that the following conditions are met:
 *
 *  1. Redistributions of source code must retain the above copyright notice, this
 *  list of conditions and the following disclaimer.
 *  2. Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 *
 *
 *  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 *  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 *  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 *  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 *  ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 *  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
class MetaphoneSpanish
{
    /**
     * Check whether a substring of $string starting at position $start with
     * length $length contains any of the string sequences in $list.
     *
     * @param  string  $string  The input string.
     * @param  integer $start   The start position of the substring to check in $string.
     * @param  integer $length  The length of the substring.
     * @param  array   $list    The string sequences to check for.
     * @return boolean          True, if specified substring contains any of the string sequences;
     *                          False, otherwise.
     */
    protected static function stringAt($string, $start, $length, array $list)
    {
        // check if valid substring
        if ($start < 0 or $start >= strlen($string)) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        // check substring for each sequence
        foreach ($list as $expr) {
            if (false !== strpos(substr($string, $start, $length), $expr)) {
                return true;
            }
        }

        // not found
        return false;
    }

    /**
     * Check whether the character at position $postion in $string is a vowel.
     *
     * @param  string  $string    The input string.
     * @param  integer $position  The position of the character to check in $string.
     * @return boolean            True, if specified character is a vowel;
     *                            False, otherwise.
     *
     * @pre    $string consists of only uppercase characters
     */
    protected static function isVowel($string, $position)
    {
        $character = $string[$position];
        return in_array($character, array('A', 'E', 'I', 'O', 'U'));
    }

    /**
     * Translate accented or special characters in Spanish.
     *
     * @param  string  $string  The input string.
     * @return string           The result string after translating characters.
     */
    protected static function strtr($string)
    {
        if ($string) {
            $translated = array(
                'á'  => 'A',
                'ch' => 'X',
                'ç'  => 'S',
                'é'  => 'E',
                'í'  => 'I',
                'ó'  => 'O',
                'ú'  => 'U',
                'ñ'  => 'NY',
                'gü' => 'W',
                'ü'  => 'U',
                'b'  => 'V',
#                'z'  => 'S',
                'll' => 'Y',
            );
            $result = str_replace(array_keys($translated), $translated, $string);
            return $result;
        }
        // @codeCoverageIgnoreStart
        return '';
        // @codeCoverageIgnoreEnd
    }

    /**
     * Calculate the metaphone key of a Spanish string.
     *
     * @param  string  $string    The input string.
     * @param integer $phonemes   This parameter restricts the returned metaphone key to phonemes characters in length.
     *                            The default value of 0 means no restriction.
     * @return string             The metaphone key as a string, if successfully calculated;
     *                            False, otherwise.
     *
     * @see https://php.net/metaphone
     */
    public function metaphone($string, $phonemes = 0)
    {
        $metaKey        = '';                  // initialize metaphone key string
        $currentPos     = 0;                   // set current position to the beginning
        $originalString = $string . '    ';

        // let's replace some spanish characters easily confused
        $originalString = self::strtr(mb_convert_case($originalString, MB_CASE_LOWER));

        // convert string to uppercase
        $originalString = strtoupper($originalString);

        # main loop
        while (0 === $phonemes || strlen($metaKey) < $phonemes) {
            // break out of the loop if greater or equal than the length
            if ($currentPos >= strlen($originalString)) {
                break;
            }

            // get character from the string
            $currentChar = $originalString[$currentPos];

            // if it is a vowel, and it is at the begining of the string,
            // set it as part of the meta key
            if (self::isVowel($originalString, $currentPos) && 0 === $currentPos) {
                $metaKey    .= $currentChar;
                $currentPos += 1;
            } else {
                // let's check for consonants that have a single sound
                // or already have been replaced because they share the same
                // sound like 'B' for 'V' and 'S' for 'Z'
                $singleSoundChars = array('D', 'F', 'J', 'K', 'M', 'N', 'P', 'T', 'V', 'L', 'Y');
                if (self::stringAt($originalString, $currentPos, 1, $singleSoundChars)) {
                    $metaKey .= $currentChar;

                    #increment by two if a repeated letter is found
                    if (substr($originalString, $currentPos + 1, 1) == $currentChar) {
                        $currentPos += 2;
                    } else { # increment only by one
                        $currentPos += 1;
                    }
                } else {
                    // check consonants with similar confusing sounds
                    if ($currentChar == 'C') {
                        // special case 'macho', chato, etc.
#                        if (substr($originalString, $currentPos + 1, 1) == 'H') {
#                            $currentPos += 2;
#                        }

                        // special case 'acción', 'reacción', etc.
                        if (substr($originalString, $currentPos + 1, 1) == 'C') {
                            $metaKey     .= 'X';
                            $currentPos += 2;

                        // special case 'cesar', 'cien', 'cid', 'conciencia'
                        } elseif (self::stringAt($originalString, $currentPos, 2, array('CE', 'CI'))) {
                            $metaKey     .= 'Z';
                            $currentPos += 2;
                        } else {
                            $metaKey     .= 'K';
                            $currentPos += 1;
                        }
                    } elseif ($currentChar == 'G') {
                        // special case 'gente', 'ecologia', etc.
                        if (self::stringAt($originalString, $currentPos, 2, array('GE', 'GI'))) {
                             $metaKey     .= 'J';
                             $currentPos += 2;
                        } else {
                             $metaKey     .= 'G';
                             $currentPos += 1;
                        }

                    // since the letter 'h' is silent in spanish,
                    // let's set the meta key to the vowel after the letter 'h'
                    } elseif ($currentChar =='H') {
                        if (self::isVowel($originalString, $currentPos + 1)) {
                            $metaKey     .= $originalString[$currentPos + 1];
                            $currentPos += 2;
                        } else {
                            // @codeCoverageIgnoreStart
                            $metaKey     .= 'H';
                            $currentPos += 1;
                            // @codeCoverageIgnoreEnd
                        }
                    } elseif ($currentChar == 'Q') {
                        if (substr($originalString, $currentPos + 1, 1) == 'U') {
                             $currentPos += 2;
                        } else {
                            // @codeCoverageIgnoreStart
                            $currentPos += 1;
                            // @codeCoverageIgnoreEnd
                        }
                        $metaKey     .= 'K';
                    } elseif ($currentChar == 'W') {
#                        if ($currentPos == 0) {
#                            $metaKey     .= 'V'
#                            $currentPos += 2;
#                        } else {

                        $metaKey     .= 'U';
                        $currentPos += 1;

#                        }

                    // perro, arrebato, cara
                    } elseif ($currentChar == 'R') {
                        $currentPos += 1;
                        $metaKey     .= 'R';

                    // spain
                    } elseif ($currentChar == 'S') {
                        if (!self::isVowel($originalString, $currentPos + 1) && $currentPos == 0) {
                            $metaKey     .= 'ES';
                            $currentPos += 1;
                        } else {
                            $currentPos += 1;
                            $metaKey     .= 'S';
                        }

                    // zapato
                    } elseif ($currentChar == 'Z') {
                        $currentPos += 1;
                        $metaKey     .= 'Z';
                    } elseif ($currentChar == 'X') {
                        // some mexican spanish words like 'Xochimilco', 'xochitl'
#                        if ($currentPos == 0)) {
#                            $metaKey    .= 'S';
#                            $currentPos += 2;
#                        } else

                        if (!self::isVowel($originalString, $currentPos + 1)
                                && strlen($string) > 1
                                && $currentPos == 0) {
                            $metaKey     .= 'EX';
                            $currentPos += 1;
                        } else {
                            $metaKey     .= 'X';
                            $currentPos += 1;
                        }
                    } else {
                        $currentPos += 1;
                    }
                }
            }
        }

        // trim any blank characters
        $metaKey = trim($metaKey);

        // return the final meta key string
        return $metaKey;
    }
}
