<?php

class UploadTest extends TestCase
{

    protected $useDatabase = true;
    
    public function testImport()
    {
        $this->uploadFile('adoption.ged');
        
        // Check whether we are redirected to the correct route
        $this->assertRedirectedTo('gedcoms/index');
    }
}
