<?php
declare(strict_types=1);
//Call only from CLI

include('/var/www/html/parser/Selenium/vendor/autoload.php');

use App\Connect;
use App\ProjectConfig;

class ApplyFixtures
{
    const FIXTURES_EXTENSION_SQL = 'sql';
    const FIXTURES_EXTENSION_PHP = 'php';
    const FIXTURES_PATH = '/var/www/html/Behat/fixtures/';

    private $db;

    public function __construct()
    {
        $this->db = new Connect(ProjectConfig::getValue('testDbCredentials'));
    }

    public function indexAction(): void
    {
        try {
            $fixtures = scandir(self::FIXTURES_PATH, SCANDIR_SORT_ASCENDING);

            foreach ($fixtures as $fixture) {
                if (pathinfo($fixture, PATHINFO_EXTENSION) === self::FIXTURES_EXTENSION_SQL) {
                    $this->runSqlFixture($fixture);
                }

                if (pathinfo($fixture, PATHINFO_EXTENSION) === self::FIXTURES_EXTENSION_PHP) {
                    $this->runPhpFixture($fixture);
                }
            }
        } catch (\Throwable $e) {
            $this->db->con->rollBack();

            echo "An error occurred while applying fixtures: {$e->getMessage()}", PHP_EOL;
        }

        //$this->db->con->commit();

        echo 'Fixtures have been applied successfully', PHP_EOL;
    }

    private function runSqlFixture(string $fixture): void
    {
        $fixtureContent = file_get_contents(self::FIXTURES_PATH . $fixture);

        echo "Applying {$fixture} fixture", PHP_EOL;

        $result = $this->db->con->exec($fixtureContent);

        $x = 5;
    }

    private function runPhpFixture(string $fixture): void
    {

        echo "Applying PHP fixture - {$fixture}", PHP_EOL;

        try {
            include(self::FIXTURES_PATH . $fixture);
        } catch (\Exception $e) {
            //TODO: add logging here if needed
            throw new \Exception($e->getMessage());
        }
    }
}

$applyFixtures = new ApplyFixtures();
$applyFixtures->indexAction();
