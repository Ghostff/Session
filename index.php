<?php
spl_autoload_register(function ($name) {
    include 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $name) . '.php';
});

if (!empty($_POST))
{
    $session = Session::start('Demo');
    new Dump($session->name);
}
else
{
    $session = Session::start('Demo');

    $session->registerErrorHandler(function($error)
    {
        throw new  RuntimeException('error: ' . $error);
    });

    $segment = $session->segment('hey');
    $session->flash->name = 'foobar flash';
    $session->flash->lname = 'foobar flash';
    $session->name = 'foobar';

    $segment->name = 'foobar segment';
    $segment->flash->name = 'foobar segment flash';

    $segment->remove->name;
    $session->remove->flash->lname;
	
	$session1 = Session::start('Demo1');
	$session1->name = 'chrys';


	new Dump($session1->getAll(), $segment->flash->name, $session->getAll());
}
?>
<form method="post">
    <input type="submit" name="who" value="diffrent page"></form>
</form>
