<?php

/**Migration file generated at 10:01:47 on Mon, 29th January 2018 by PowerOrm(1.1.0-beta1)*/

namespace Eddmash\PowerOrm\Tests\TestApp\Migrations;

use Eddmash\PowerOrm\Migration\Migration;
use Eddmash\PowerOrm\Migration\Operation\Field as FieldOps;
use Eddmash\PowerOrm\Migration\Operation\Model as ModelOps;
use Eddmash\PowerOrm\Model\Model;

class m0001_Initial extends Migration
{
    public function getDependency()
    {
        return [
        ];
    }

    public function getOperations()
    {
        return [
            ModelOps\CreateModel::createObject(
                [
                    'name' => 'Eddmash\PowerOrm\Tests\TestApp\Models\Book',
                    'fields' => [
                        'title' => Model::CharField([
                            'maxLength' => 50,
                        ]),
                        'isbn' => Model::CharField([
                            'maxLength' => 50,
                        ]),
                        'summary' => Model::CharField([
                            'maxLength' => 50,
                        ]),
                        'price' => Model::DecimalField([
                        ]),
                        'id' => Model::AutoField([
                            'primaryKey' => true,
                            'autoCreated' => true,
                        ]),
                    ],
                ]
            ),
            ModelOps\CreateModel::createObject(
                [
                    'name' => 'Eddmash\PowerOrm\Tests\TestApp\Models\Author',
                    'fields' => [
                        'name' => Model::CharField([
                            'maxLength' => 200,
                        ]),
                        'email' => Model::EmailField([
                            'maxLength' => 254,
                        ]),
                        'id' => Model::AutoField([
                            'primaryKey' => true,
                            'autoCreated' => true,
                        ]),
                    ],
                ]
            ),
            FieldOps\AddField::createObject(
                [
                    'modelName' => 'Eddmash\PowerOrm\Tests\TestApp\Models\Book',
                    'name' => 'author',
                    'field' => Model::ForeignKey([
                        'to' => 'Eddmash\PowerOrm\Tests\TestApp\Models\Author',
                    ]),
                ]
            ),
        ];
    }
}
