<?php

spl_autoload_register(function ($class_name) {
    include 'src/' .$class_name . '.php';
});

$session = Session::start("Test");

var_dump($session->all());