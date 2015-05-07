<?php

class DuplicateEventsTest extends TestCase
{

    protected $useDatabase = true;

    /**
     * Test whether parse errors are created
     */
    public function testParseErrors()
    {
        $gedcom = $this->uploadAndParseFile('duplicate_events.ged');

        // I1 (John /Smith/) has two BIRT events, this leads to a parse error
        $ind = GedcomIndividual::GedcomKey($gedcom->id, 'I1')->first();
        $this->assertEquals(2, $ind->eventsByType('BIRT')->count());
        $ind_parse_error = $ind->errors()->first(); 
        $this->assertEquals('duplicate event', $ind_parse_error->type_specific);
        $this->assertEquals('warning', $ind_parse_error->eval_broad);
        
        // F1 has two MARR events, this leads to a parse error
        $fam = GedcomFamily::GedcomKey($gedcom->id, 'F1')->first();
        $this->assertEquals(2, $fam->eventsByType('MARR')->count());
        $fam_parse_error = $fam->errors()->first(); 
        $this->assertEquals('duplicate event', $fam_parse_error->type_specific);
        $this->assertEquals('warning', $fam_parse_error->eval_broad);
    }

}
