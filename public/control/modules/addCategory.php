<?php

use App\Connect;
use App\ProjectConfig;

if (!isset($_POST[ 'title' ])) {
    throw new Exception('Please provide a title');
}

$title = $_POST[ 'title' ];

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));

$category = new \App\Zalando\Domain\Product\ProductCategory($title);

$sql = "SELECT * FROM `categories` WHERE `title`='{$title}'";
$query = $db->query($sql);
if($db->numRows($query) > 0){
    echo "Category with name {$title} already exists!";
    exit;
}

$sql = "INSERT INTO categories (`id`, `title`) VALUES ('{$category->getId()}', '{$category->toString()}')";
$query = $db->query($sql);

echo "<script>location.href='/category.php';</script>";
