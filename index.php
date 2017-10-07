<?php

spl_autoload_register(function ($class_name) {
    include 'src/' .$class_name . '.php';
});

$session = Session::start("Test");



$segment = $session->segment("Test_Seg");
$session->name = 'foo';
# Setting Segment
$segment->name = 'bar';

# Setting Flash
$session->flash->name = 'foobar';
# Setting Segment Flash
$segment->flash->name = 'barfoo';

#$session->commit();

var_dump($session->all());