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
use Eddmash\PowerOrm\Migration\State\ModelState;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;

class CreateModel extends Operation
{
    public $name;
    public $fields;
    /**
     * @var Meta
     */
    public $meta;
    public $extends;

    public function getDescription()
    {
        return sprintf('Create %smodel %s',
            (isset($this->meta['proxy']) && $this->meta['proxy']) ? 'proxy ' : '', $this->name);
    }

    public function getConstructorArgs()
    {
        $constructorArgs = parent::getConstructorArgs();
        if (isset($constructorArgs['meta']) && empty($constructorArgs['meta'])):
            unset($constructorArgs['meta']);
        endif;
        if (isset($constructorArgs['extends']) && !empty($constructorArgs['extends'])):

            if (in_array($constructorArgs['extends'], [Model::getFullClassName(), \PModel::getFullClassName()])):
                unset($constructorArgs['extends']);

            endif;
        endif;

        return $constructorArgs;
    }

    /**
     * {@inheritdoc}
     */
    public function updateState($state)
    {
        $state->addModelState(ModelState::createObject(
            $this->name, $this->fields, ['meta' => $this->meta, 'extends' => $this->extends]));
    }

}
