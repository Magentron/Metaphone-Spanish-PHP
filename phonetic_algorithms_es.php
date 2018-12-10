<?php

namespace Magentron\MetaphoneSpanish;

/**
 *	The Spanish Metaphone Algorithm (Algoritmo del Metáfono para el Español)
 *
 *	This script implements the Metaphone algorithm (c) 1990 by Lawrence Philips.
 *	It was inspired by the English double metaphone algorithm implementation by
 *	Andrew Collins - January 12, 2007 who claims no rights to this work
 *	(http://www.atomodo.com/code/double-metaphone)
 *
 *	The metaphone port adapted to the Spanish Language is authored
 *	by Alejandro Mosquera <amosquera@dlsi.ua.es> November, 2011
 *  (https://github.com/amsqr/Spanish-Metaphone) and is covered
 *	under this copyright:
 *
 *	Copyright 2011, Alejandro Mosquera <amosquera@dlsi.ua.es>.  All rights reserved.
 *
 *	This metaphone port adapted to the Spanish Language for PHP is authored
 *	by Jeroen Derks <jeroen@derks.it> December, 2018
 *	(https://github.com/Magentron/Spanish-Metaphone-PHP) and is covered
 *	under this copyright:
 *
 *	Copyright 2018, Jeroen Derks <jeroen@derks.it>.  All rights reserved.
 *
 *	Redistribution and use in source and binary forms, with or without modification,
 *	are permitted provided that the following conditions are met:
 *
 *	1. Redistributions of source code must retain the above copyright notice, this
 *	list of conditions and the following disclaimer.
 *	2. Redistributions in binary form must reproduce the above copyright notice, this
 *	list of conditions and the following disclaimer in the documentation and/or
 *	other materials provided with the distribution.
 *
 *
 *	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 *	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 *	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 *	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 *	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 *	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *	ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
class PhoneticAlgorithmsES
{
	/**
	 * Check whether a substring of $string starting at position $start with
	 * length $length contains any of the string sequences in $list.
	 *
	 * @param  string  $string  The input string.
	 * @param  integer $start   The start position of the substring to check in $string.
	 * @param  integer $length  The length of the substring.
	 * @return boolean          True, if specified substring contains any of the string sequences;
	 *                          False, otherwise.
	 */
	static protected function string_at($string, $start, $length, array $list)
	{
		// check if valid substring
		if ($start < 0 or $start >= strlen($string)) {
			return false;
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
	 * @pre	$string consists of only uppercase characters
	 */
	static protected function is_vowel($string, $position)
	{
		$character = $string[$position];
		return in_array($character, ['A','E','I','O','U']);
	}

	/**
	 * Translate accented or special characters in Spanish.
	 *
	 * @param  string  $string  The input string.
	 * @return string           The result string after translating characters.
	 */
	static protected function strtr($string)
	{
		if ($string) {
			$translated = [
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
#				'z'  => 'S',
				'll' => 'Y',
			];
			$result = str_replace(array_keys($translated), $translated, $string);
			return $result;
		}
		return '';
	}

	/**
	 * Calculate the metaphone key of a Spanish string.
	 *
	 * @param  string  $string    The input string.
	 * @param  integer $phonemes  This parameter restricts the returned metaphone key to phonemes characters in length. The default value of 0 means no restriction.
	 * @return string             The metaphone key as a string, if successfully calculated;
	 *                            False, otherwise.
	 *
	 * @see https://php.net/metaphone
	 */
	public function metaphone($string, $phonemes = 0)
	{
		$meta_key	   = '';				// initialize metaphone key string
		$current_pos   = 0;					// set current position to the beginning
		$string_length = strlen($string);	// get string length

		$end_of_string_pos = $string_length - 1;	// set to the end of the string
		$original_string   = $string . '	';

		$original_string = self::strtr(mb_convert_case($original_string, MB_CASE_LOWER));	// let's replace some spanish characters easily confused
		$original_string = strtoupper($original_string);				// convert string to uppercase

		# main loop
		while (0 === $phonemes || strlen($meta_key) < $phonemes)
		{
			// break out of the loop if greater or equal than the length
			if ($current_pos >= strlen($original_string)) {
				break;
			}

			// get character from the string
			$current_char = $original_string[$current_pos];

			// if it is a vowel, and it is at the begining of the string,
			// set it as part of the meta key
			if (self::is_vowel($original_string, $current_pos) && 0 === $current_pos) {
				$meta_key	.= $current_char;
				$current_pos += 1;
			} else {
				// let's check for consonants that have a single sound
				// or already have been replaced because they share the same
				// sound like 'B' for 'V' and 'S' for 'Z'
				if (self::string_at($original_string, $current_pos, 1,['D','F','J','K','M','N','P','T','V','L','Y'])) {
					$meta_key .= $current_char;

					#increment by two if a repeated letter is found
					if (substr($original_string, $current_pos + 1, 1) == $current_char) {
						$current_pos += 2;
					} else { # increment only by one
						$current_pos += 1;
					}
				} else { 
					// check consonants with similar confusing sounds
					if ($current_char == 'C') {

						// special case 'macho', chato,etc.
#						if (substr($original_string, $current_pos + 1, 1) == 'H') {
#							$current_pos += 2;
#						}

						// special case 'acción', 'reacción',etc.
						if (substr($original_string, $current_pos + 1, 1) == 'C') {
							$meta_key	 .= 'X';
							$current_pos += 2;

						// special case 'cesar', 'cien', 'cid', 'conciencia'
						} elseif (self::string_at($original_string, $current_pos, 2, ['CE','CI'])) {
							$meta_key	 .= 'Z';
							$current_pos += 2;

						} else {
							$meta_key	 .= 'K';
							$current_pos += 1;
						}

				 	} elseif ($current_char == 'G') {

						// special case 'gente', 'ecologia',etc
						if (self::string_at($original_string, $current_pos, 2, ['GE','GI'])) {
					 		$meta_key	 .= 'J';
					 		$current_pos += 2;
						} else {
					 		$meta_key	 .= 'G';
					 		$current_pos += 1;
						}

					// since the letter 'h' is silent in spanish,
					// let's set the meta key to the vowel after the letter 'h'
				 	} elseif ($current_char =='H') {

						if (self::is_vowel($original_string, $current_pos + 1)) {
							$meta_key	 .= $original_string[$current_pos + 1];
							$current_pos += 2;

						} else {
							$meta_key	 .= 'H';
							$current_pos += 1;
						}

					} elseif ($current_char == 'Q') {

						if (substr($original_string, $current_pos + 1, 1) == 'U') {
					 		$current_pos += 2;
						} else {
							$current_pos += 1;
						}
						$meta_key	 .= 'K';

					} elseif ($current_char == 'W') {
						
#						if ($current_pos == 0) {
#							$meta_key	 .= 'V'
#							$current_pos += 2;
#						} else {

						$meta_key	 .= 'U';
						$current_pos += 1;

#						}

					// perro, arrebato, cara
					} elseif ($current_char == 'R') {

						$current_pos += 1;
						$meta_key	 .= 'R';

					// spain
					} elseif ($current_char == 'S') {

						if (!self::is_vowel($original_string, $current_pos + 1) && $current_pos == 0) {
							$meta_key	 .= 'ES';
							$current_pos += 1;
						} else {
							$current_pos += 1;
							$meta_key	 .= 'S';
						}

					// zapato
					} elseif ($current_char == 'Z') {

						$current_pos += 1;
						$meta_key	 .= 'Z';

					} elseif ($current_char == 'X') {

						// some mexican spanish words like'Xochimilco','xochitl'
#						if ($current_pos == 0)) {
#							$meta_key    .= 'S';
#							$current_pos += 2;
#						} else

						if (!self::is_vowel($original_string, $current_pos + 1) && strlen($string) > 1 && $current_pos == 0) {
							$meta_key	 .= 'EX';
							$current_pos += 1;
						} else {
							$meta_key	 .= 'X';
							$current_pos += 1;
						}

					} else {
						$current_pos += 1;
					}
				}
			}
		}

		// trim any blank characters
		$meta_key = trim($meta_key);

		// return the final meta key string
		return $meta_key;
	}
}

/**
 * Simple test to compare with Python version
 * @TODO move to unit test
 */
if (isset($_SERVER['argv']) && 1 === count($_SERVER['argv']) && $_SERVER['argv'][0] = 'phonetic_algorithm_es.php')
{
	$pa = new PhoneticAlgorithmsES();
	$words = [
		'X',
		'xplosion',
		'escalera',
		'scalera',
		'mi',
		'tu',
		'su',
		'te',
		'ochooomiiiillllllll',
		'complicado',
		'ácaro',
		'ácido',
		'clown',
		'down',
		'col',
		'clon',
		'waterpolo',
		'aquino',
		'rebosar',
		'rebozar',
		'grajea',
		'gragea',
		'encima',
		'enzima',
		'alhamar',
		'abollar',
		'aboyar',
		'huevo',
		'webo',
		'macho',
		'xocolate',
		'chocolate',
		'axioma',
		'abedul',
		'a',
		'gengibre',
		'yema',
		'wHISKY',
		'google',
		'xilófono',
		'web',
		'guerra',
		'pingüino',
		'si',
		'ke',
		'que',
		'tu',
		'gato',
		'gitano',
		'queso',
		'paquete',
		'cuco',
		'perro',
		'pero',
		'arrebato',
		'hola',
		'zapato',
		'españa',
		'garrulo',
		'expansión',
		'membrillo',
		'jamón',
		'risa',
		'caricia',
		'llaves',
		'paella',
		'cerilla',
	];
	foreach ($words as $s) {
		printf("%s -> %s\n", $s, $pa->metaphone($s, 6));
	}
}
