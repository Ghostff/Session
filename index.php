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

    #$session->remove->name;




	new Dump($session->all());
}
?>
<form method="post">
    <input type="submit" name="who" value="diffrent page"></form>
</form>
