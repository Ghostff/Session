<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


spl_autoload_register(function ($name) {
    $name = 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $name) . '.php';
    include $name;
});


Session::registerErrorHandler(function($error, $error_code)
{
    new Dump($error, $error_code);
});


$session = Session::start();
session_start();
echo $_SESSION['name'];





exit;
$session->name = 'chrys';
$session->flash->name = 'ugwu';

$segment = $session->segment('Users');
$segment->name = 'chrys';
$segment->flash->name = 'ugwu';


$session->commit();

$session->remove->flash->name;
$segment->remove->flash->name;


new Dump($session->all());











