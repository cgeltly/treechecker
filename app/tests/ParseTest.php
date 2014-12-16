<?php

class ParseTest extends TestCase
{

    protected $useDatabase = true;
    
    public function testParse()
    {
        $this->uploadFile('missing_child.ged');
        
        // Check whether the import has succeeded 
        $gedcom = Gedcom::where('file_name', 'missing_child.ged')->first();

        // Parse the file, assert it isn't yet parsed
        $this->assertFalse((bool) $gedcom->parsed);
        $this->parseFile('missing_child.ged');

        // Refetch the gedcom, assert it is parsed
        $gedcom = $gedcom->find($gedcom->id);
        $this->assertTrue((bool) $gedcom->parsed);
    }
}
