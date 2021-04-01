<?php

use App\Connect;
use App\ProjectConfig;

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$sql = "SELECT * FROM `categories` ORDER BY `sorted_id` DESC";

$query = $db->query($sql);

$options = '';

while ($q = $db->fetch($query)) {
    $options .= <<<HTML
        <option value="{$q['id']}">{$q['title']}</option>
HTML;
}

echo $options;
