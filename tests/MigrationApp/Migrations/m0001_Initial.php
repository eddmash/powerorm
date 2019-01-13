<?php

/**Migration file generated at 07:01:51 on Sun, 13th January 2019 by PowerOrm(1.1.0-beta1)*/

namespace Eddmash\PowerOrm\Tests\MigrationApp\Migrations;

use Eddmash\PowerOrm\Migration\Migration;
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
                    'name' => 'Eddmash\PowerOrm\Tests\MigrationApp\Models\Book',
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
                    'name' => 'Eddmash\PowerOrm\Tests\MigrationApp\Models\Author',
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
        ];
    }
}
