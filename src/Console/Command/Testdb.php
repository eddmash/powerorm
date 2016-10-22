<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/2/16.
 */
namespace Eddmash\PowerOrm\Console\Command;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Eddmash\PowerOrm\BaseOrm;

/**
 * Borrowed from fuelphp oil robot.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Testdb extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public $systemCheck = false;

    public $help = 'A little fun is good for the soul';

    public function handle()
    {
        $conn = BaseOrm::getDbConnection();
        $platform = $conn->getDatabasePlatform();
        var_dump('DB platform :: '.$platform->getName());

        $schemaM = $conn->getSchemaManager();
        $schema = $schemaM->createSchema();

        $table = $schema->getTable('testing_jaked');

        /** @var $fk ForeignKeyConstraint */
        foreach ($table->getForeignKeys() as $fk) :
            echo '-----------------------------'.PHP_EOL;
            echo 'name :'.$fk->getName().PHP_EOL;
            echo 'columns :'.implode(',', $fk->getColumns()).PHP_EOL;
            echo 'columns :'.implode(',', $fk->getLocalColumns()).PHP_EOL;
            echo 'foreign table :'.$fk->getForeignTableName().PHP_EOL;
            echo 'foreign columns :'.implode(',', $fk->getForeignColumns()).PHP_EOL;
            echo PHP_EOL.PHP_EOL;
        endforeach;

    }

    /**
     * @param AbstractSchemaManager $schemaM
     * @param Schema                $schema
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function createTable($schemaM, $schema) {
        if ($schemaM->tablesExist('artists')):

            $schemaM->dropTable('artists');
        endif;
        if ($schemaM->tablesExist('user')):
            $schemaM->dropTable('user');
        endif;
        echo 'Tables :: ';
        var_dump(implode('::', $schema->getTableNames()));

        $UTable = $schema->createTable('user');
        $UTable->addColumn('id', 'integer',
            ['unsigned' => true, 'autoincrement' => true]);
        $UTable->addColumn('name', 'string', ['length' => 60]);
        $UTable->setPrimaryKey(['id']);

        $myTable = $schema->createTable('artists');
        $myTable->addColumn('id', 'integer',
            ['unsigned' => true, 'autoincrement' => true]);
        $myTable->addColumn('user_id', 'integer', ['unsigned' => true]);
        $myTable->addColumn('name', 'string', ['length' => 60]);
        $myTable->setPrimaryKey(['id']);
        $myTable->addForeignKeyConstraint($UTable, array('user_id'), array('id'), array('onUpdate' => 'CASCADE'));

//        var_dump($schema->toSql($platform));
        $schemaM->createTable($UTable);

        $schemaM->createTable($myTable);
    }
}