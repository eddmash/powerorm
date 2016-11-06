<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/2/16.
 */
namespace Eddmash\PowerOrm\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;

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

    public $help = 'dummy tests';

    public function handle()
    {
        //        $conn = BaseOrm::getDbConnection();
//        $platform = $conn->getDatabasePlatform();

//        $schemaM = $conn->getSchemaManager();
//        $schema = $schemaM->createSchema();

//        $this->fetch($conn);
    }

    /**
     * @param Connection $connection
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function fetch($connection)
    {
        $connection->createQueryBuilder()->from();
    }

    /**
     * @param AbstractSchemaManager $schemaM
     * @param Schema $schema
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function readSchema($schemaM, $schema)
    {
        $table = $schema->getTable('testing_jaked');

        /** @var $fk ForeignKeyConstraint */
        foreach ($table->getForeignKeys() as $fk) :
            echo '-----------------------------' . PHP_EOL;
            echo 'name :' . $fk->getName() . PHP_EOL;
            echo 'columns :' . implode(',', $fk->getColumns()) . PHP_EOL;
            echo 'columns :' . implode(',', $fk->getLocalColumns()) . PHP_EOL;
            echo 'foreign table :' . $fk->getForeignTableName() . PHP_EOL;
            echo 'foreign columns :' . implode(',', $fk->getForeignColumns()) . PHP_EOL;
            echo PHP_EOL . PHP_EOL;
        endforeach;
    }

    /**
     * @param AbstractSchemaManager $schemaM
     * @param Schema $schema
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function createTable($schemaM, $schema)
    {
        if ($schemaM->tablesExist('artists')):

            $schemaM->dropTable('artists');
        endif;
        if ($schemaM->tablesExist('user')):
            $schemaM->dropTable('user');
        endif;
        echo 'Tables :: ';

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

        $schemaM->createTable($UTable);

        $schemaM->createTable($myTable);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName($this->guessCommandName())
            ->setDescription($this->help)
            ->setHelp($this->help);
    }
}
