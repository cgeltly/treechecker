<?php

class NotesTest extends TestCase
{
    protected $useDatabase = true;
    private $gedcom;

    public function setUp()
    {
        parent::setUp();

        $this->gedcom = $this->uploadAndParseFile('notes.ged');
    }

    /**
     * Test the linking of notes to individuals
     */
    public function testIndividual()
    {
        // This GEDCOM has 3 notes in total
        $this->assertEquals($this->gedcom->notes()->count(), 2);

        // I1 has a note
        $ind = GedcomIndividual::GedcomKey($this->gedcom->id, 'I1')->first();
        $this->assertEquals($ind->notes()->count(), 1);
        $note = $ind->notes()->first();
        $this->assertEquals($note->note, 'This is a test note on an individual.');
    }

    /**
     * Test the linking of notes to families
     */
    public function testFamily()
    {
        // F1 has a note
        $fam = GedcomFamily::GedcomKey($this->gedcom->id, 'F1')->first();
        $this->assertEquals($fam->notes()->count(), 1);
    }

    /**
     * Test the creation of a parse error
     */
    public function testParseErrors()
    {
        // N4 is not linked to any individual, family or event
        $this->assertEquals($this->gedcom->errors()->count(), 2);
        
        $error_messages = implode(' ', $this->gedcom->errors()->lists('message'));
        $this->assertContains('N4', $error_messages);
        $this->assertContains('N5', $error_messages);
    }

}
