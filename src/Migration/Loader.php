<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

class Loader
{
    public $graph;

    public function __construct($connection = null, $loadGraph = true)
    {
        if ($loadGraph):
            $this->buildGraph();
        endif;
    }

    public function detectConflicts()
    {
        return null;
    }

    public function getProjectState()
    {
        return null;
    }

    public function buildGraph()
    {
        return new Graph();
    }
}
