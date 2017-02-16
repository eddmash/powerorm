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
use Eddmash\PowerOrm\Checks\CheckError;

/**
 * An IntegerField that automatically increments according to available IDs.
 *
 * You usually won’t need to use this directly; a primary key field will automatically be added to your model
 * if you don’t specify otherwise. See Automatic primary key fields.
 *
 * @since 1.0.1
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AutoField extends Field
{
    public $primaryKey = true;

    public function checks()
    {
        $checks = [];
        $checks = array_merge($checks, $this->checkPrimaryKey());

        return $checks;
    }

    private function checkPrimaryKey()
    {
        $errors = [];

        if (!$this->primaryKey):
            $errors = [
                CheckError::createObject(
                    [
                        'message' => 'AutoFields must set primaryKey=true.',
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.E100',
                    ]
                ),
            ];
        endif;

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function dbType($connection)
    {
        return Type::INTEGER;
    }

    public function contributeToClass($field, $model)
    {
        parent::contributeToClass($field, $model);

        assert(
            !$model->meta->hasAutoField,
            sprintf(
                "The Model '%s' more than one AutoField, which is not allowed.",
                $this->scopeModel->meta->modelName
            )
        );

        $this->scopeModel->meta->hasAutoField = true;
        $this->scopeModel->meta->autoField = $this;
    }

    /**
     * {@inheritdoc}
     */
    public function formField($kwargs = [])
    {
        return;
    }
}
