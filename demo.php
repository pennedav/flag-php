#!/usr/bin/env php
<?php declare(strict_types=1);

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
