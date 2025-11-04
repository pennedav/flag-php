#!/usr/bin/env php
<?php declare(strict_types=1);
/*
 * FILE: demo.php
 *
 * Copyright (c) 2025 David M. Penner
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the “Software”), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

require 'src/flag.php';

use Pennedav\Flag\{ Flag, FlagError };

/**
 * @param list<string> $argv
 */
function main(array $argv) : int
{
    $flags = new Flag();

    $x = null;
    $flags->bindBoolean($x, 'x', false, "Help text for flag -x");

    $n = Flag::Num(0);
    $flags->bindNum($n, "n", Flag::Num(2), "Help text for option -n");

    $m = Flag::Num(0);
    $flags->bindNum($m, "m", Flag::Num(3), "Help text for option -m");

    $s = Flag::Str("");
    $flags->bindStr($s, "s", Flag::Str("default"), "Help text for option -s");

    try {
        $flags->parseFromArgs($argv);
    }
    catch (FlagError $e) {
        fwrite(STDERR, "Option parse error: " . $e->getMessage() . "\n");
        $flags->printUsage();
        exit(-1);
    }

    printf("\$x = %s\n", $x ? 'true' : 'false');
    printf("\$n = '%s'\n", $n->toString());
    printf("\$m = '%s'\n", $m->toString());
    printf("\$s = '%s'\n", $s);
    printf("\$argv = %s\n", json_encode($argv));

    return 0;
}

function dd(mixed ...$args) : never
{
    foreach ($args as $n => $arg) {
        fprintf(STDERR, "=== Arg %d ===\n", $n);
        var_dump($arg);
    }
    exit(1);
}

//
// MAIN
//

set_error_handler(function (int $errno, string $errmsg, string $file, int $line) {
    fprintf(STDERR,
        "at %s(%d):\n" .
        "error: (%d) >>>%s<<<\n",
        $file, $line,
        $errno, $errmsg);
    exit(-1);
});

/** @var list<string> $argv */
global $argv;

exit(main($argv));
