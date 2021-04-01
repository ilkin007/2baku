<?php

include('/var/www/html/parser/Selenium/vendor/autoload.php');

use App\Connect;
use App\ProjectConfig;
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;


/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
    private $db;

    /**
     * Initializes context.
     * Every scenario gets its own context object.
     *
     * @param array $parameters context parameters (set them up through Behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->db = new Connect(ProjectConfig::getValue('testDbCredentials'));
    }

    /**
     * @Given /^I have done something with "([^"]*)"$/
     */
    public function iHaveDoneSomethingWith($argument)
    {
        echo $argument;
    }





//
// Place your definition and hook methods here:
//
//    /**
//     * @Given /^I have done something with "([^"]*)"$/
//     */
//    public function iHaveDoneSomethingWith($argument)
//    {
//        doSomethingWith($argument);
//    }
//
}
