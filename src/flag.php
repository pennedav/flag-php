<?php declare(strict_types=1);

namespace Pennedav\Flag;

require 'num.php';
require 'str.php';

use RuntimeException;
use Pennedav\Flag\Types\{ Str\Str, Num\Num };

abstract class FlagError extends RuntimeException { }
class FlagOptionError extends FlagError { }
class FlagMissingArgumentError extends FlagError { }
class FlagUnexpectedOptionError extends FlagError { }

abstract class FlagStateItem {
    public protected(set) string $name;
    public protected(set) string $help;
    public protected(set) string $type;
    abstract public function accept(?string $val) : void;
    abstract public function defaultValueAsString() : string;
}

class FlagStateItem_Num extends FlagStateItem {
    public readonly Num $default;
    public Num $value;

    public function __construct(string $name, Num &$value, string $help) {
        $this->type = 'number';
        $this->name = $name;
        $this->default = clone $value;
        $this->value =& $value;
        $this->help = $help;
    }

    public function accept(?string $val) : void {
        if (is_null($val)) return; // noop
        try {
            $this->value = Flag::Num($val);
        }
        catch (\ValueError $e) {
            throw new FlagOptionError("Option `{$this->name}': Non-well-formed numerical value.", 0, $e);
        }
    }

    public function defaultValueAsString() : string {
        return strval($this->default);
    }
}

class FlagStateItem_str extends FlagStateItem {
    public readonly Str $default;
    public Str $value;

    public function __construct(string $name, Str &$value, string $help) {
        $this->type = 'string';
        $this->name = $name;
        $this->default = clone $value;
        $this->value =& $value;
        $this->help = $help;
    }

    public function accept(?string $val) : void {
        if (is_null($val)) return; // noop
        $this->value = Flag::Str($val);
    }

    public function defaultValueAsString() : string {
        return strval($this->default);
    }
}

class FlagStateItem_bool extends FlagStateItem {
    public readonly bool $default;
    public bool $value;

    public function __construct(string $name, bool &$value, string $help) {
        $this->type = 'bool';
        $this->name = $name;
        $this->default = $value;
        $this->value =& $value;
        $this->help = $help;
    }

    public function accept(?string $val) : void {
        $this->value = match(strtolower(strval($val))) {
            ''      => true,
            '1'     => true,
            'y'     => true,
            'yes'   => true,
            'on'    => true,
            '0'     => false,
            'n'     => false,
            'no'    => false,
            'off'   => false,
            default => throw new FlagOptionError(
                "Flag `{$this->name}': Invalid boolean value." .
                " Please supply something like 1|0|yes|no|on|off" .
                " (casing does not matter)."),
        };
    }

    public function defaultValueAsString() : string {
        return $this->default ? 'TRUE' : 'FALSE';
    }
}

class Flag {
    public static function Num(int|string|float $n) : Num {
        return new Num($n);
    }

    public static function Str(string $s) : Str {
        return new Str($s);
    }

    /** @var array<string, FlagStateItem_str|FlagStateItem_Num> */
    private array $options = [];

    /** @var array<string, FlagStateItem_bool> */
    private array $flags = [];

    /** @var list<string> */
    private array $known_flags = [];

    /** @var list<string> */
    private array $known_options = [];

    private ?string $program_name = null;

    public function __construct() {
    }

    public function bindBoolean(?bool &$x, string $option_name, bool $default_value, string $help_text) : void {
        $x = $default_value;
        $this->known_flags[] = $option_name;
        $this->flags[$option_name] = new FlagStateItem_bool(name: $option_name, value: $x, help: $help_text);
    }

    public function bindNum(Num &$x, string $option_name, Num $default_value, string $help_text) : void {
        $x = $default_value;
        $this->known_options[] = $option_name;
        $this->options[$option_name] = new FlagStateItem_Num(name: $option_name, value: $x, help: $help_text);
    }

    public function bindStr(Str &$x, string $option_name, Str $default_value, string $help_text) : void {
        $x = $default_value;
        $this->known_options[] = $option_name;
        $this->options[$option_name] = new FlagStateItem_str(name: $option_name, value: $x, help: $help_text);
    }

    /**
     * @param resource $fh
     */
    public function printUsage($fh = null) : void {
        $fh ??= STDERR;
        if (!is_resource($fh)) {
            throw new \InvalidArgumentException(
                "Argument 1 (\$fh) must be a valid stream resource.");
        }

        $all = [...$this->options, ...$this->flags];

        $usage = sprintf("Usage for `%s':\n", $this->program_name);
        foreach ($all as $flag_name => $flag_obj) {
            $usage .= sprintf("    -%s %s (default: %s)\n        %s\n",
                $flag_name,
                $flag_obj->type,
                $flag_obj->defaultValueAsString(),
                $flag_obj->help);
        }

        fprintf($fh, $usage);
    }

    /**
     * @param list<string> $argv
     */
    public function parseFromArgs(array &$argv) : void {

        $options_re = '@^--?(?<opt>' . implode('|', $this->known_options) . ')(?:=(?<val>.*))?$@';
        $flags_re   = '@^--?(?<flag>' . implode('|', $this->known_flags) . ')(?:=(?<val>.*))?$@';
        $rogue_re   = '@^--?(?<x>.+)@';

        $this->program_name = array_shift($argv);

        while ($arg = array_shift($argv))
        {
            if (preg_match($options_re, $arg, $m))
            {
                $option_name = $m['opt'];
                $option_value = $m['val'] ?? null; // E.g. --opt=val OR -o=v

                if (is_null($option_value))
                {
                    $option_value = array_shift($argv);
                    if (is_null($option_value)) {
                        throw new FlagMissingArgumentError(
                            "`{$arg}': requires an argument.");
                    }
                }

                $this->options[$option_name]->accept($option_value);
            }
            else if (preg_match($flags_re, $arg, $m))
            {
                $flag_name = $m['flag'];
                $flag_value = $m['val'] ?? null; // E.g. --flag=off OR -f=0 OR ...
                $this->flags[$flag_name]->accept($flag_value);
            }
            else if (preg_match($rogue_re, $arg, $m))
            {
                throw new FlagUnexpectedOptionError(
                    "`{$arg}': Unexpected option.");
            }
            else
            {
                // Put back the last token for the caller
                array_unshift($argv, $arg);
                break;
            }
        }
    }
}
