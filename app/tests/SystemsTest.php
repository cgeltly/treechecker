<?php

class SystemsTest extends TestCase
{
    protected $useDatabase = true;
    private $gedcom;

    public function setUp()
    {
        parent::setUp();

        $this->gedcom = $this->uploadAndParseFile('sources.ged');
    }

    /**
     * Test the linking of sources to individuals
     */
    public function testSystem()
    {
        $system = $this->gedcom->system;
        $this->assertEquals($system->system_id, 'FTW');
        $this->assertEquals($system->version_number, '5.00');
        $this->assertEquals($system->product_name, 'Family Tree Maker for Windows');
        $this->assertEquals($system->corporation, 'Broderbund Software, Banner Blue Division');
    }

}
