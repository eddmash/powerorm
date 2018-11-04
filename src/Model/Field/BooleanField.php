<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field;

use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\Backends\ConnectionInterface;

class BooleanField extends Field
{
    public function __construct(array $config = [])
    {
        $config['formBlank'] = true;
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function dbType(ConnectionInterface $connection)
    {
        return Type::BOOLEAN;
    }

    /**
     * {@inheritdoc}
     */
    public function formField($kwargs = [])
    {
        $kwargs['fieldClass'] = \Eddmash\PowerOrm\Form\Fields\BooleanField::class;

        return parent::formField($kwargs);
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructorArgs()
    {
        $kwargs = parent::getConstructorArgs();
        unset($kwargs['formBlank']);

        return $kwargs;
    }
}
