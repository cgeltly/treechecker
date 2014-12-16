<?php

class HomeTest extends TestCase
{

    protected $useDatabase = false;

    /**
     * Basic test of the 'Hello World' page.
     *
     * @return void
     */
    public function testHome()
    {
        $this->call('GET', 'home');

        $this->assertResponseOk();
    }

}
