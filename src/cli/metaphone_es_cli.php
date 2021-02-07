<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
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
 *
 *  This is the file metaphone_es_cli.php.
 *
 *  This script functions as a simple example on how to use the metaphone
 *  MetaphoneSpanish class and allows to call the algorithm via a
 *  command line program.
 */
use Magentron\MetaphoneSpanish\MetaphoneSpanish;

// track errors if possible
ini_set('track_errors', true);

require_once __DIR__ . '/../MetaphoneSpanish.php';

define('PROCESS_STDIN', -1);

// get information from the command line
$argv = $_SERVER['argv'];
define('PROG', array_shift($argv));
$argc = count($argv);

/**
 * Show usage message to the user and exit.
 *
 * @param string|null  $message
 * @param integer      $exitcode
 *
 * @codeCoverageIgnore
 */
function usage($exitcode = 1)
{
    fprintf(STDERR, "usage: %s [ < word | file | -> ... \n", PROG);
    fputs(STDERR, "\tuse - to read from standard input\n");
    exit($exitcode);
}

/**
 * Retrieve file and line number from backtrace entry or last error.
 *
 * @param  array   $backtraceOrError
 * @return string
 *
 * @codeCoverageIgnore
 */
function getFileLineNumber(array $backtraceOrError)
{
    $file = isset($backtraceOrError['file']) ? $backtraceOrError['file'] : 'Unknown';
    $line = isset($backtraceOrError['line']) ? $backtraceOrError['line'] : 0;
    return sprintf('%s:%s', $file, $line);
}

/**
 * Retrieve system error message.
 *
 * @return string
 *
 * @codeCoverageIgnore
 */
function getSystemErrorMessage()
{
    if (version_compare(PHP_VERSION, '5.2', '>=')) {
        $error = error_get_last();
        if (null !== $error) {
            return isset($error['message']) ? $error['message'] : '';
        }

        $backtrace   = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller      = getFileLineNumber($backtrace ? $backtrace[0] : array());
        return sprintf('an unknown error occurred in %s', $caller);
    }

    global $php_errormsg;

    return $php_errormsg;
}

/**
 * Retrieve source as human-readable text.
 *
 * @param  string|integer  $source
 * @return string
 */
function getSourceAsText($source)
{
    if (PROCESS_STDIN === $source) {
        // @codeCoverageIgnoreStart
        return 'standard input';
        // @codeCoverageIgnoreEnd
    } elseif (is_integer($source)) {
        return 'argument ' . $source;
    }

    return $source;
}

/**
 * Show error message to the user and exit.
 *
 * @param string|null  $message
 * @param integer      $exitcode
 *
 * @codeCoverageIgnore
 */
function error($message = null, $exitcode = 1)
{
    $includeSystemError = null === $message;
    $systemError        = null;

    if ($includeSystemError) {
        $systemError = getSystemErrorMessage();
        if ($systemError) {
            $message = null !== $message ? sprintf("%s (%s)", $message, $systemError) : $systemError;
        }
    }

    if ('' == $message) {
        $message = 'unknown error';
    }

    fprintf(STDERR, "%s: error: %s\n", PROG, $message);
    exit($exitcode);
}


/**
 * Calculate the metaphonic key for the supplied words from the supplied source.
 *
 * @param string|integer  $source
 * @param array           $words
 */
function process($source, $words)
{
    $paes   = new MetaphoneSpanish();
    $source = getSourceAsText($source);

    // for each word calculate the metaphonic key
    foreach ($words as $word) {
        $key = $paes->metaphone($word);
        printf("%s\n", $key);
    }
}

/**
 *  Main
 */
if (0 >= $argc) {
    // no input given
    // @codeCoverageIgnoreStart
    return usage();
    // @codeCoverageIgnoreEnd
}

// for every argument
foreach ($argv as $index => $fileOrWord) {
    // check to use stdin
    if ('-' === $fileOrWord) {
        // @codeCoverageIgnoreStart
        while (false !== ($line = fgets(STDIN, 4096))) {
            $line = trim($line);
            if ('' !== $line) {
                process(PROCESS_STDIN, array($line));
            }
        }
        // @codeCoverageIgnoreEnd
    // check to read from file
    } elseif (file_exists($fileOrWord)) {
        // check whether it is a file (and not e.g. a directory)
        if (!is_file($fileOrWord)) {
            // @codeCoverageIgnoreStart
            error('not a file: ' . $fileOrWord, 4);
            // @codeCoverageIgnoreEnd
        } elseif (!is_readable($fileOrWord)) {
            // @codeCoverageIgnoreStart
            error('unable to read file: ' . $fileOrWord, 3);
            // @codeCoverageIgnoreEnd
        } else {
            // read the contents of the file
            $content = @file_get_contents($fileOrWord);
            if (false === $content) {
                // @codeCoverageIgnoreStart
                error(null, 2);
                // @codeCoverageIgnoreEnd
            } else {
                $lines = preg_split('/(\s*\n+\s*)+/', trim($content));
                process($fileOrWord, $lines);
            }
        }
    } else {
        // @codeCoverageIgnoreStart
        process($index, array($fileOrWord));
        // @codeCoverageIgnoreEnd
    }
}
