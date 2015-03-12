<?php

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Configuration found on http://snooptank.com/testing-with-database-in-laravel-4/
 */
class TestCase extends Illuminate\Foundation\Testing\TestCase
{

    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $unitTesting = true;

        $testEnvironment = 'testing';

        return require __DIR__ . '/../../bootstrap/start.php';
    }

    /**
     * Default preparation for each TestCase
     */
    public function setUp()
    {
        parent::setUp();

        if ($this->useDatabase)
        {
            $this->setUpDb();

            $user = User::findOrFail(1);
            $this->be($user);
        }
    }

    /**
     * Migrate and seed the database
     */
    private function setUpDb()
    {
        Artisan::call('migrate');
        $this->seed();
    }

    /**
     * Tear down the database after use
     */
    public function tearDown()
    {
        parent::tearDown();
        //Artisan::call('migrate:reset');
    }

    public function uploadFile($filename)
    {
        // Clean the upload directory
        $user_dir = Config::get('app.upload_dir') . '/' . Auth::id() . '/';
        $this->removeDir($user_dir);

        // Copy the file and create an UploadedFile
        $filename = DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $filename;
        copy(__DIR__ . $filename, __DIR__ . 'copy.ged');
        $file = new UploadedFile(__DIR__ . 'copy.ged', $filename, 'txt');

        // Call the controller
        $this->call('POST', 'fileuploads/upload', array('tree_name' => 'test_tree'), array('uploads' => array($file)));
    }

    public function parseFile($filename)
    {
        // Check whether the import has succeeded 
        $gedcom = Gedcom::where('file_name', $filename)->first();

        // Parse the file
        $this->action('GET', 'ParseController@getParse', array('id' => $gedcom->id));

        return $gedcom->find($gedcom->id);
    }

    public function uploadAndParseFile($filename)
    {
        $this->uploadFile($filename);
        return $this->parseFile($filename);
    }

    /**
     * Removes a directory and its contents, recursively.
     * @param string $directory
     */
    private function removeDir($directory)
    {
        if (file_exists($directory))
        {
            foreach (glob("{$directory}/*") as $file)
            {
                if (is_dir($file))
                {
                    $this->removeDir($file);
                }
                else
                {
                    unlink($file);
                }
            }
            rmdir($directory);
        }
    }

}
