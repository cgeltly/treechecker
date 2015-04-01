<?php

class SourcesTest extends TestCase
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
    public function testIndividual()
    {
        // This GEDCOM has 5 sources in total (2 on individuals, 1 on family)
        $this->assertEquals($this->gedcom->sources()->count(), 3);

        // I1 has a referenced source
        $ind1 = GedcomIndividual::GedcomKey($this->gedcom->id, 'I1')->first();
        $this->assertEquals($ind1->sources()->count(), 1);
        $source1 = $ind1->sources()->first();
        $this->assertEquals($source1->title, 'This is a test source on an individual.');
        
        // I3 has a source on an event 
        $ind3 = GedcomIndividual::GedcomKey($this->gedcom->id, 'I3')->first();
        $this->assertEquals($ind3->sources()->count(), 1);
        $source3 = $ind3->sources()->first();
        $this->assertNotNull($source3->even_id);
    }

    /**
     * Test the linking of sources to families
     */
    public function testFamily()
    {
        // F1 has two sources, one of which related to an event
        $fam = GedcomFamily::GedcomKey($this->gedcom->id, 'F1')->first();
        $this->assertEquals($fam->sources()->count(), 1);
    }

    /**
     * Test the creation of a parse error
     */
    public function testParseErrors()
    {
        $this->assertEquals($this->gedcom->errors()->count(), 2);
        
        $error_messages = implode(' ', $this->gedcom->errors()->lists('message'));
        // S4 is not linked to any individual, family or event
        $this->assertContains('S4', $error_messages);
        // S5 is not defined in the GEDCOM file
        $this->assertContains('S5', $error_messages);
    }

}
