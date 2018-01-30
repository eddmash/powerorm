<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\TestApp\Models;

use Eddmash\PowerOrm\Model\Model;

class Book extends Model
{
    public function unboundFields()
    {
        return [
            'author' => Model::ManyToManyField(['to' => Author::class]),
            'title' => Model::CharField(['maxLength' => 50]),
            'isbn' => Model::CharField(['maxLength' => 50]),
            'summary' => Model::CharField(['maxLength' => 50]),
            'price' => Model::DecimalField(['maxDigits' => 50, 'decimalPlaces' => 2]),
        ];
    }
}
