<?php declare(strict_types=1);
/*
 * FILE: src/num.php
 *
 * Copyright (c) 2025 David M. Penner <pennedav@gmail.com>
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

namespace Pennedav\Flag\Types\Num;

use Stringable;
use RuntimeException;
use InvalidArgumentException;
use LogicException;
use BcMath;

enum NumCompareResult : int {
    case Equal      =  0;
    case Greater    =  1;
    case Less       = -1;
}

final class Num implements Stringable {
    private readonly BcMath\Number $bc;

    private static function float_to_str(float $x) : string {
        // TODO : Implement this properly to parse scientific notation into
        // something that bcmath will accept.
        return sprintf("%.17g", $x);
    }

    public function __construct(BcMath\Number|int|string|float $src) {
        if ($src instanceof BcMath\Number) {
            $this->bc = clone $src;
        }
        else if (is_int($src) || is_string($src)) {
            $this->bc = new BcMath\Number($src);
        }
        // @phpstan-ignore function.alreadyNarrowedType
        else if (is_float($src)) {
            if (is_finite($src)) {
                $x = static::float_to_str($src);
                $this->bc = new BcMath\Number($x);
            }
            else {
                throw new InvalidArgumentException("floats must be finite");
            }
        }
        else {
            // We forgot to update a branch. Maybe added more types?
            throw new LogicException("Unreachable");
        }
    }

    public function add(string|int|float|Num $other) : Num {
        if (is_int($other) || is_float($other) || is_string($other)) {
            $other = new Num($other);
        }
        return new Num($this->bc->add($other->bc));
    }

    public function sub(string|int|float|Num $other) : Num {
        if (is_int($other) || is_float($other) || is_string($other)) {
            $other = new Num($other);
        }
        return new Num($this->bc->sub($other->bc));
    }

    public function mul(string|int|float|Num $other) : Num {
        if (is_int($other) || is_float($other) || is_string($other)) {
            $other = new Num($other);
        }
        return new Num($this->bc->mul($other->bc));
    }

    public function div(string|int|float|Num $other) : Num {
        if (is_int($other) || is_float($other) || is_string($other)) {
            $other = new Num($other);
        }
        return new Num($this->bc->div($other->bc));
    }

    public function compare(string|int|float|Num $other) : NumCompareResult {
        if (is_int($other) || is_float($other) || is_string($other)) {
            $other = new Num($other);
        }
        return NumCompareResult::from($this->bc->compare($other->bc));
    }

    public function toInt() : int {
        return intval("{$this->bc}");
    }

    public function toFloat() : float {
        return floatval("{$this->bc}");
    }

    public function toString() : string {
        return "{$this->bc}";
    }

    public function __toString() {
        return "{$this->bc}";
    }
}
