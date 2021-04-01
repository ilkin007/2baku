<?php

include('config.php');

if(!$_GET['action']){
    echo "No action selected!";
}

$action = $_GET['action'];

switch($action){
    case 'addToQueue':
        include('modules/addToQueue.php');
        break;
    case 'addCategory':
        include('modules/addCategory.php');
    break;
    case 'addMargin':
        include('modules/addMargin.php');
    break;
    case 'deleteCategory':
        include('modules/deleteCategory.php');
    break;
    case 'deleteMargin':
        include('modules/deleteMargin.php');
    break;
}
