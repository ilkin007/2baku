<?php

namespace App\Parser;

use App\Connect;
use App\ProjectConfig;
use App\Services\Task\Task;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class AbstractParser
{
    protected $website = '';

    /** @var RemoteWebDriver|null */
    protected $driver;

    /** @var Connect */
    protected $db;

    /** @var Logger */
    protected $log;

    /** @var Task */
    protected $task;

    /** @var Connect|null */
    protected $opencartDb;

    /** @var int */
    protected $cookieCalls;

    /** @var ProjectConfig|null */
    protected $config;

    /** @var string */
    protected $cookiesString;

    /** @var int */
    protected $instanceId;

    public function initDriver(?int $instanceId)
    {
        if (!$instanceId) {
            $instanceId = $this->instanceId;
        }

        $host = 'selenium' . $instanceId . ':4444/wd/hub';
        $options = new ChromeOptions();
        $options->addArguments([
            '--disable-javascript',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--browserSessionReuse',
            '--disable-gpu'
        ]);
        $options->addArguments([
            '--blink-settings=imagesEnabled=false'
        ]);
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $capabilities->setPlatform("Linux");
        $capabilities->setCapability('loggingPrefs', ['browser' => 'ALL']);

        $this->driver = RemoteWebDriver::create($host, $capabilities, 5000);
    }

    public function __construct(
        Connect $db,
        Logger $log,
        ?Task $task = null,
        Connect $opencartDb = null,
        ProjectConfig $config = null,
        int $instanceId = 1
    ) {
        $this->db = $db;
        $this->opencartDb = $opencartDb;
        $this->log = $log;
        $this->task = $task;
        $this->config = $config;
        $this->instanceId = $instanceId;

        $logName = $this->log->getName();
        $log->pushHandler(new StreamHandler('/var/www/html/parser/Selenium/App/' . ucfirst($this->website) . '/log/' . $logName . '.log',
            Logger::INFO));
    }

    abstract protected function openInitialPage();

    public function prepareCookies(): void
    {
        $this->initDriver(1);
        $this->prepareParser();
        $this->turnOffBrowser();
        $this->log->debug('Refreshed cookies');
    }

    protected function convertIntPriceToFloat(int $price): float
    {
        $float = substr($price, -2, 2);
        $number = substr($price, 0, -2);

        return (float)$number . '.' . $float;
    }

    protected function saveCookies(): void
    {
        $cookies = $this->driver->manage()->getCookies();
        $this->createCookieString($cookies);

        $cookies = serialize($cookies);

        //Set new cookies array from DB
        $statement = $this->db->con->prepare('INSERT INTO `cookies` (`cookiedata`, `website`) VALUES (?, ?)');
        $statement->execute([$cookies, $this->website]);
    }

    abstract function isLoggedIn();

    protected function setWaitingTime(int $seconds)
    {
        $this->driver->manage()->timeouts()->implicitlyWait($seconds);
    }

    protected function unsetWaitingTime()
    {
        $this->driver->manage()->timeouts()->implicitlyWait(0);
    }

    protected function addCookies(array $cookies): void
    {
        try {
            /** @var Cookie $cookie */
            foreach ($cookies as $cookie) {
                $this->driver->manage()->addCookie($cookie->toArray());
            }
        } catch (\Exception $e) {
            $this->logIn();
        }
    }

    protected function getAndApplyCookies(): void
    {
        //Get latest cookies array from DB
        $sql = "SELECT * FROM `cookies` WHERE `website`= '" . $this->website . "' ORDER BY `id` DESC LIMIT 0,1";
        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->error('Error while getting cookies from db!');
        }

        if ($this->db->numRows($query) > 0) {
            $q = $this->db->fetch($query);

            $cookies = unserialize($q['cookiedata']);
            $this->createCookieString($cookies);
            $this->addCookies($cookies);
        } else {
            $this->saveCookies();
        }
    }

    protected function createCookieString(array $cookies): void
    {
        $cookiesHeaderString = 'Cookie: ';

        /** @var Cookie $cookie */
        foreach ($cookies as $cookie) {
            $cookiesHeaderString .= $cookie->getName() . '=' . $cookie->getValue() . '; ';
        }

        $this->cookiesString = $cookiesHeaderString;
    }

    protected function getDriver(): RemoteWebDriver
    {
        return $this->driver;
    }

    protected function turnOffBrowser()
    {
        if ($this->driver) {
            $this->driver->close();
            $this->driver->quit();
        }
    }
}
