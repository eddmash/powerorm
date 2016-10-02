<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Migration\Operation\Operation;

/**
 * Renames a model's table.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AlterModelTable extends Operation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $table;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return sprintf('Rename table for %s to %s', $this->name, $this->table);
    }

    /**
     * {@inheritdoc}
     */
    public function updateState($state)
    {
        $state->modelStates[$this->name]->meta['dbTable'] = $this->table;
    }

}
