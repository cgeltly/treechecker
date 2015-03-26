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
        // This GEDCOM has 5 notes in total (3 on individuals, 2 on family)
        $this->assertEquals($this->gedcom->notes()->count(), 5);

        // I1 has a referenced note
        $ind1 = GedcomIndividual::GedcomKey($this->gedcom->id, 'I1')->first();
        $this->assertEquals($ind1->notes()->count(), 1);
        $note1 = $ind1->notes()->first();
        $this->assertEquals($note1->note, 'This is a test note on an individual.');

        // I2 has an embedded note
        $ind2 = GedcomIndividual::GedcomKey($this->gedcom->id, 'I2')->first();
        $this->assertEquals($ind2->notes()->count(), 1);
        $note2 = $ind2->notes()->first();
        $this->assertNull($note2->gedcom_key);
        
        // I3 has a note on an event 
        $ind3 = GedcomIndividual::GedcomKey($this->gedcom->id, 'I3')->first();
        $this->assertEquals($ind3->notes()->count(), 1);
        $note3 = $ind3->notes()->first();
        $this->assertNotNull($note3->even_id);
    }

    /**
     * Test the linking of notes to families
     */
    public function testFamily()
    {
        // F1 has two notes, one of which related to an event
        $fam = GedcomFamily::GedcomKey($this->gedcom->id, 'F1')->first();
        $this->assertEquals($fam->notes()->count(), 2);
    }

    /**
     * Test the creation of a parse error
     */
    public function testParseErrors()
    {
        $this->assertEquals($this->gedcom->errors()->count(), 2);
        
        $error_messages = implode(' ', $this->gedcom->errors()->lists('message'));
        // N4 is not linked to any individual, family or event
        $this->assertContains('N4', $error_messages);
        // N5 is not defined in the GEDCOM file
        $this->assertContains('N5', $error_messages);
    }

}
