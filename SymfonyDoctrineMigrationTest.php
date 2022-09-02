<?php

namespace AppBundle\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Driver\PDOMysql\Driver as MySQLDriver;

class SymfonyDoctrineMigrationTest extends WebTestCase
{
    protected function setUp()
    {
        $this->environment = 'test_mysql';

        /* @var \Doctrine\ORM\EntityManager */
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();
        $driver = $connection->getDriver();
        if (!$driver instanceof MySQLDriver) {
            $this->markTestSkipped('This test requires MySQL.');
        }

        try {
            $databaseName = $this->getContainer()->getParameter('database_name');
            if (in_array($databaseName, $connection->getSchemaManager()->listDatabases())) {
                $schemaTool = new SchemaTool($em);
                // Drop all tables, so we can test on a clean DB
                $schemaTool->dropDatabase();
            }
        } catch (\Exception $e) {
            $this->fail('Could not cleanup test database for migration test: ' . $e->getMessage());
        }
    }

    /**
     * @group mysql
     */
    public function testMigrations()
    {
        // Test if all migrations run through
        $output = $this->runCommand('doctrine:migrations:migrate', ['--no-interaction']);
        $this->assertRegExp('/\d+ sql queries\n$/', $output);

        // Validate that the mapping files are correct and in sync with the database.
        $output = $this->runCommand('doctrine:schema:validate');
        $this->assertContains('[Mapping]  OK - The mapping files are correct.', $output);
        $this->assertContains('[Database] OK - The database schema is in sync with the mapping files.', $output);
    }
}
