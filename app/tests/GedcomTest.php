<?php

class GedcomTest extends TestCase
{

    protected $useDatabase = true;

    /**
     * Tests the properties of a Gedcom
     */
    public function testGedcom()
    {
        $gedcom = $this->uploadAndParseFile('adoption.ged');
        
        $this->assertEquals($gedcom->user->id, 1);

        // There are 4 individuals in this GEDCOM, 2 males, 2 females
        $this->assertEquals($gedcom->individuals()->count(), 4);
        $this->assertEquals($gedcom->individuals()->sex('m')->count(), 2);
        $this->assertEquals($gedcom->individuals()->sex('f')->count(), 2);

        // There are 2 families in this GEDCOM
        $this->assertEquals($gedcom->families()->count(), 2);
    }

}
