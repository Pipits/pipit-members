<?php

// $API  = new PerchAPI(1.0, 'perch_members');
$Members = new PerchMembers_Members($API);

$members = $Members->get_filtered_listing([
    'template'  => 'members/admin/csv.html',
]);


header('Content-type: text/csv', true);
header("Content-Disposition: attachment; filename=\"".'members-'.date('Y-m-d-H:i').".csv\"", true);
die($members);