<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Expression;

class Combinable
{
    const ADD = '+';

    const SUB = '-';

    const MUL = '*';

    const DIV = '/';

    const POW = '^';

    // The following is a quoted % operator - it is quoted because it can be
    // used in strings that also have parameter substitution.
    const MOD = '%%';

    public function add($other)
    {
        return $this->combine($other, static::ADD, false);
    }

    public function sub($other)
    {
        return $this->combine($other, static::SUB, false);
    }

    public function div($other)
    {
        return $this->combine($other, static::DIV, false);
    }

    public function mult($other)
    {
        return $this->combine($other, static::MUL, false);
    }

    public function mod($other)
    {
        return $this->combine($other, static::MOD, false);
    }

    public function pow($other)
    {
        return $this->combine($other, static::POW, false);
    }

    private function combine($other, $connector, $reversed, $node = null)
    {
        if (!$other instanceof ResolvableExpInterface):
            //todo date
            $other = new Value($other);
        endif;

        return new CombinedExpression($this, $connector, $other);
    }

    public function __debugInfo()
    {
        return [];
    }
}
