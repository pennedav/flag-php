<?php declare(strict_types=1);

namespace Pennedav\Flag\Types\Str;

use Stringable;

final class Str implements Stringable {
    public function __construct(public private(set) readonly string $value) {
    }

    public function empty() : bool {
        return strlen($this->value) === 0;
    }

    public function append(string|Str $other) : Str {
        if (is_string($other)) {
            return new static($this->value . $other);
        }
        else {
            return new static($this->value . $other->value);
        }
    }

    public function toString() : string {
        return $this->value;
    }

    public function __toString() {
        return $this->value;
    }
}
