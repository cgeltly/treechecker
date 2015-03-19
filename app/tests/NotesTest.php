<?php

class NotesTest extends TestCase
{

    protected $useDatabase = true;

    /**
     * Test the linking of notes to individuals
     */
    public function testNotesIndividual()
    {
        $gedcom = $this->uploadAndParseFile('notes.ged');

        // This GEDCOM has 3 notes in total
        $this->assertEquals($gedcom->notes()->count(), 2);

        // I1 has a note
        $ind = GedcomIndividual::GedcomKey($gedcom->id, 'I1')->first();
        $this->assertEquals($ind->notes()->count(), 1);
        $note = $ind->notes()->first();
        $this->assertEquals($note->note, 'This is a test note on an individual.');

        // F1 has a note
        $fam = GedcomFamily::GedcomKey($gedcom->id, 'F1')->first();
        $this->assertEquals($fam->notes()->count(), 1);
    }

}
