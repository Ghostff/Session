<?php declare(strict_types=1);

use Ghostff\Session\Session;

class FileSessionTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Session::updateConfiguration([Session::CONFIG_START_OPTIONS => [Session::CONFIG_START_OPTIONS_SAVE_PATH => $this->session_path]]);
    }

    public function tearDown(): void
    {
        parent::tearDown(); // TODO: Change the autogenerated stub
        $files = glob($this->session_path . '/*'); // get all file names
        foreach($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file); // delete file
            }
        }
    }
}
