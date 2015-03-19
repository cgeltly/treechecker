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
        $this->assertEquals($this->gedcom->notes()->count(), 3);

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
        $this->assertEquals($this->gedcom->errors()->count(), 3);
        
        $error_messages = implode(' ', $this->gedcom->errors()->lists('message'));
        $this->assertContains('N3', $error_messages); // This is a note on an event: not parsed yet!
        $this->assertContains('N4', $error_messages);
        $this->assertContains('N5', $error_messages);
    }

}
