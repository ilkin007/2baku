<?php

use App\Connect;
use App\ProjectConfig;

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$sql = "SELECT * FROM `margins` ORDER BY `fromPrice`";

$query = $db->query($sql);

$table = "<table border='1' cellpadding='10'>
<div class='table-responsive'>
<table class='table'>
<tbody>
<thead>
    <tr>
        <th>Id</th>
        <th>From price</th>
        <th>To price</th>
        <th>Margin</th>
        <th>Description</th>
        <th>Control</th>
    </tr>
</thead>
";

while ($q = $db->fetch($query)) {
    $table .= <<<HTML
        <tr>
            <td>{$q['id']}</td>
            <td>{$q['fromPrice']}</td>
            <td>{$q['toPrice']}</td>
            <td>{$q['marginPercent']}</td>
            <td>{$q['description']}</td>
            <td>
                <form method="post" action="action.php?action=deleteMargin">
                    <input type="hidden" name="id" value="{$q['id']}"/>
                    <button style="color: red; font-weight: bold;">Delete</button>
                </form>
            </td>
        </tr>
HTML;
}

$table .= '</tbody></table></div>';

echo $table;
