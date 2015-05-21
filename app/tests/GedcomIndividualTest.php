<?php

class GedcomIndividualTest extends TestCase
{

    protected $useDatabase = true;

    /**
     * Test the basic individual properties 
     */
    public function testIndividual()
    {
        $gedcom = $this->uploadAndParseFile('adoption.ged');

        // Basic checks
        // I1 (Max /Mustermann/, 15 OCT 1898 - ?) is adopted
        $ind = GedcomIndividual::GedcomKey($gedcom->id, 'I1')->first();
        $this->assertEquals($ind->first_name, 'Max');
        $this->assertEquals($ind->last_name, 'Mustermann');
        $this->assertEquals($ind->sex, 'm');
        
        // I1 has a BIRT and an ADOP event
        $this->assertEquals($ind->events()->count(), 2);
        $this->assertNotEmpty($ind->birth());
        $this->assertEquals($ind->birth()->date, '1898-10-15');
        $this->assertEmpty($ind->death());
        $this->assertNotEmpty($ind->isAdopted());
        
        // I2 is marked as private 
        $ind2 = GedcomIndividual::GedcomKey($gedcom->id, 'I2')->first();
        $this->assertTrue((bool) $ind2->private);
    }
    
    /**
     * Tests the family (father/mother)
     */
    public function testFamily()
    {
        $gedcom = $this->uploadAndParseFile('adoption.ged');
       
        // I2 (Rolf /Mustermann/) is the father of I1
        $ind = GedcomIndividual::GedcomKey($gedcom->id, 'I1')->first();
        $father = $ind->father;
        $this->assertEquals($father->gedcom_key, 'I2');
        $this->assertEquals($father->first_name, 'Rolf');
        $this->assertEquals($father->last_name, 'Mustermann');
        $this->assertEquals($father->sex, 'm');
        $this->assertEmpty($father->birth());
        $this->assertEmpty($father->death());
        $this->assertEmpty($father->isAdopted());

        // I3 (Renate /Musterfrau/) is the mother of I1
        $mother = $ind->mother;
        $this->assertEquals($mother->gedcom_key, 'I3');
    }

}
