<?php

use App\Connect;
use App\ProjectConfig;

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$sql = "SELECT * FROM `categories` ORDER BY `title`";

$query = $db->query($sql);

$table = "
<div class='table-responsive'>
<table class='table'>
<tbody>
<thead>
    <tr>
        <th>Title</th>
        <th>Control</th>
    </tr>
</thead>
";

while ($q = $db->fetch($query)) {
    $table .= <<<HTML
        <tr>
            <td>{$q['title']}</td>
            <td>
                <form method="post" action="action.php?action=deleteCategory">
                    <input type="hidden" name="id" value="{$q['id']}"/>
                    <button style="color: red; font-weight: bold;">Delete</button>
                </form>
            </td>
        </tr>
HTML;
}

$table .= '</tbody></table></div>';

echo $table;
