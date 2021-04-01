<?php

use App\Connect;
use App\ProjectConfig;
use Monolog\Logger;

if (!isset($_POST[ 'fromPrice' ])) {
    throw new Exception('Please provide all data');
}

$fromPrice = $_POST[ 'fromPrice' ];
$toPrice = $_POST[ 'toPrice' ];
$marginPercent = $_POST[ 'marginPercent' ];
$description = $_POST[ 'description' ];

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('margin');

$sql = "INSERT INTO `margins` (`id`, `fromPrice`, `toPrice`, `marginPercent`, `description`) VALUES (NULL, '{$fromPrice}', '{$toPrice}', '{$marginPercent}', '{$description}')";

$query = $db->query($sql);

if (!$query) {
    throw new Exception("Couldn't insert new margin! Contact the developer!");
}

echo "<script>location.href='/margin.php';</script>";
