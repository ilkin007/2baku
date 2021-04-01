<?php

use App\Connect;
use App\ProjectConfig;

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$sql = "SELECT 
       q.link as link, 
       c.title as category, 
       q.minimum as minimum, 
       q.limitation as limitation,
       q.type as type,
       q.status as status
FROM `queue` q 
LEFT JOIN `categories` c ON (q.category_id=c.id)
ORDER BY q.`created_at` DESC LIMIT 0,10";

$query = $db->query($sql);

$table = "<div class='table-responsive'>
<table class='table'>
<tbody>
<thead>
    <tr>
        <th>Link</th>
        <th>Category</th>
        <th>Min</th>
        <th>Limit (max)</th>
        <th>Type</th>
        <th>Status</th>
    </tr>
</thead>
";

while ($q = $db->fetch($query)) {
    $table .= <<<HTML
        <tr>
            <td>{$q['link']}</td>
            <td>{$q['category']}</td>
            <td>{$q['minimum']}</td>
            <td>{$q['limitation']}</td>
            <td>{$q['type']}</td>
            <td>{$q['status']}</td>
        </tr>
HTML;
}

$table .= '</tbody></table></div>';

echo $table;
