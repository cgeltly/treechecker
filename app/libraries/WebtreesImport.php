<?php

// Import-specific functions
//
// webtrees: Web based Family History software
// Copyright (C) 2014 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2002 to 2009 PGV Development Team.  All rights reserved.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA

/*
 * The functions here have been slightly refactored for use in TreeChecker
 */

namespace Webtrees;

class Import
{

    /**
     * Create a pseudo-random UUID
     * @author webtrees
     * @return string The generated ID
     */
    public static function uuid()
    {
        // Official Format with dashes ('%04x%04x-%04x-%04x-%04x-%04x%04x%04x')
        // Most users want this format (for compatibility with PAF)
        $fmt = '%04X%04X%04X%04X%04X%04X%04X%04X';

        $uid = sprintf(
                $fmt,
                // 32 bits for "time_low"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0x0fff) | 0x4000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant RFC4122
                mt_rand(0, 0x3fff) | 0x8000,
                // 48 bits for "node"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        return sprintf('%s%s', $uid, Import::getCheckSums($uid));
    }

    /**
     * Produces checksums compliant with a Family Search guideline from 2007
     * these checksums are compatible with PAF, Legacy, RootsMagic and other applications
     * following these guidelines. This prevents dropping and recreation of UID's
     *
     * @author Veit Olschinski
     * @param string $uid the 32 hexadecimal character long uid
     * @return string containing the checksum string for the uid
     */
    private static function getCheckSums($uid)
    {
        $checkA = 0; // a sum of the bytes
        $checkB = 0; // a sum of the incremental values of checkA
        // Compute both checksums
        for ($i = 0; $i < 32; $i+=2)
        {
            $checkA += hexdec(substr($uid, $i, 2));
            $checkB += $checkA & 0xFF;
        }
        return strtoupper(sprintf('%s%s', substr(dechex($checkA), -2), substr(dechex($checkB), -2)));
    }

    /**
     * Tidy up a gedcom record on import, so that we can access it consistently/efficiently.
     * @author webtrees
     * @global string $WORD_WRAPPED_NOTES
     * @global string $GEDCOM_MEDIA_PATH
     * @param string $rec
     * @return string
     */
    public static function reformat_record_import($rec)
    {
        global $WORD_WRAPPED_NOTES, $GEDCOM_MEDIA_PATH;

        // Strip out UTF8 formatting characters
        $rec = str_replace(array(WT_UTF8_BOM, WT_UTF8_LRM, WT_UTF8_RLM), '', $rec);

        // Strip out mac/msdos line endings
        $rec = preg_replace("/[\r\n]+/", "\n", $rec);

        // Extract lines from the record; lines consist of: level + optional xref + tag + optional data
        $num_matches = preg_match_all('/^[ \t]*(\d+)[ \t]*(@[^@]*@)?[ \t]*(\w+)[ \t]?(.*)$/m', $rec, $matches, PREG_SET_ORDER);

        // Process the record line-by-line
        $newrec = '';
        foreach ($matches as $n => $match)
        {
            list(, $level, $xref, $tag, $data) = $match;
            $tag = strtoupper($tag); // Tags should always be upper case
            switch ($tag)
            {
                // Convert PGV tags to WT
                case '_PGVU':
                    $tag = '_WT_USER';
                    break;
                case '_PGV_OBJS':
                    $tag = '_WT_OBJE_SORT';
                    break;
                // Convert FTM-style "TAG_FORMAL_NAME" into "TAG".
                case 'ABBREVIATION':
                    $tag = 'ABBR';
                    break;
                case 'ADDRESS':
                    $tag = 'ADDR';
                    break;
                case 'ADDRESS1':
                    $tag = 'ADR1';
                    break;
                case 'ADDRESS2':
                    $tag = 'ADR2';
                    break;
                case 'ADDRESS3':
                    $tag = 'ADR3';
                    break;
                case 'ADOPTION':
                    $tag = 'ADOP';
                    break;
                case 'ADULT_CHRISTENING':
                    $tag = 'CHRA';
                    break;
                case 'AFN':
                    // AFN values are upper case
                    $data = strtoupper($data);
                    break;
                case 'AGENCY':
                    $tag = 'AGNC';
                    break;
                case 'ALIAS':
                    $tag = 'ALIA';
                    break;
                case 'ANCESTORS':
                    $tag = 'ANCE';
                    break;
                case 'ANCES_INTEREST':
                    $tag = 'ANCI';
                    break;
                case 'ANNULMENT':
                    $tag = 'ANUL';
                    break;
                case 'ASSOCIATES':
                    $tag = 'ASSO';
                    break;
                case 'AUTHOR':
                    $tag = 'AUTH';
                    break;
                case 'BAPTISM':
                    $tag = 'BAPM';
                    break;
                case 'BAPTISM_LDS':
                    $tag = 'BAPL';
                    break;
                case 'BAR_MITZVAH':
                    $tag = 'BARM';
                    break;
                case 'BAS_MITZVAH':
                    $tag = 'BASM';
                    break;
                case 'BIRTH':
                    $tag = 'BIRT';
                    break;
                case 'BLESSING':
                    $tag = 'BLES';
                    break;
                case 'BURIAL':
                    $tag = 'BURI';
                    break;
                case 'CALL_NUMBER':
                    $tag = 'CALN';
                    break;
                case 'CASTE':
                    $tag = 'CAST';
                    break;
                case 'CAUSE':
                    $tag = 'CAUS';
                    break;
                case 'CENSUS':
                    $tag = 'CENS';
                    break;
                case 'CHANGE':
                    $tag = 'CHAN';
                    break;
                case 'CHARACTER':
                    $tag = 'CHAR';
                    break;
                case 'CHILD':
                    $tag = 'CHIL';
                    break;
                case 'CHILDREN_COUNT':
                    $tag = 'NCHI';
                    break;
                case 'CHRISTENING':
                    $tag = 'CHR';
                    break;
                case 'CONCATENATION':
                    $tag = 'CONC';
                    break;
                case 'CONFIRMATION':
                    $tag = 'CONF';
                    break;
                case 'CONFIRMATION_LDS':
                    $tag = 'CONL';
                    break;
                case 'CONTINUED':
                    $tag = 'CONT';
                    break;
                case 'COPYRIGHT':
                    $tag = 'COPR';
                    break;
                case 'CORPORATE':
                    $tag = 'CORP';
                    break;
                case 'COUNTRY':
                    $tag = 'CTRY';
                    break;
                case 'CREMATION':
                    $tag = 'CREM';
                    break;
                case 'DATE':
                    // Preserve text from INT dates
                    if (strpos($data, '(') !== false)
                    {
                        list($date, $text) = explode('(', $data, 2);
                        $text = ' (' . $text;
                    }
                    else
                    {
                        $date = $data;
                        $text = '';
                    }
                    // Capitals
                    $date = strtoupper($date);
                    // Temporarily add leading/trailing spaces, to allow efficient matching below
                    $date = " {$date} ";
                    // Ensure space digits and letters
                    $date = preg_replace('/([A-Z])(\d)/', '$1 $2', $date);
                    $date = preg_replace('/(\d)([A-Z])/', '$1 $2', $date);
                    // Ensure space before/after calendar escapes
                    $date = preg_replace('/@#[^@]+@/', ' $0 ', $date);
                    // "BET." => "BET"
                    $date = preg_replace('/(\w\w)\./', '$1', $date);
                    // "CIR" => "ABT"
                    $date = str_replace(' CIR ', ' ABT ', $date);
                    $date = str_replace(' APX ', ' ABT ', $date);
                    // B.C. => BC (temporarily, to allow easier handling of ".")
                    $date = str_replace(' B.C. ', ' BC ', $date);
                    // "BET X - Y " => "BET X AND Y"
                    $date = preg_replace('/^(.* BET .+) - (.+)/', '$1 AND $2', $date);
                    $date = preg_replace('/^(.* FROM .+) - (.+)/', '$1 TO $2', $date);
                    // "@#ESC@ FROM X TO Y" => "FROM @#ESC@ X TO @#ESC@ Y"
                    $date = preg_replace('/^ +(@#[^@]+@) +FROM +(.+) +TO +(.+)/', ' FROM $1 $2 TO $1 $3', $date);
                    $date = preg_replace('/^ +(@#[^@]+@) +BET +(.+) +AND +(.+)/', ' BET $1 $2 AND $1 $3', $date);
                    // "@#ESC@ AFT X" => "AFT @#ESC@ X"
                    $date = preg_replace('/^ +(@#[^@]+@) +(FROM|BET|TO|AND|BEF|AFT|CAL|EST|INT|ABT) +(.+)/', ' $2 $1 $3', $date);
                    // Ignore any remaining punctuation, e.g. "14-MAY, 1900" => "14 MAY 1900"
                    // (don't change "/" - it is used in NS/OS dates)
                    $date = preg_replace('/[.,:;-]/', ' ', $date);
                    // BC => B.C.
                    $date = str_replace(' BC ', ' B.C. ', $date);
                    // Append the "INT" text
                    $data = $date . $text;
                    break;
                case 'DEATH':
                    $tag = 'DEAT';
                    break;
                case '_DEGREE':
                    $tag = '_DEG';
                    break;
                case 'DESCENDANTS':
                    $tag = 'DESC';
                    break;
                case 'DESCENDANT_INT':
                    $tag = 'DESI';
                    break;
                case 'DESTINATION':
                    $tag = 'DEST';
                    break;
                case 'DIVORCE':
                    $tag = 'DIV';
                    break;
                case 'DIVORCE_FILED':
                    $tag = 'DIVF';
                    break;
                case 'EDUCATION':
                    $tag = 'EDUC';
                    break;
                case 'EMIGRATION':
                    $tag = 'EMIG';
                    break;
                case 'ENDOWMENT':
                    $tag = 'ENDL';
                    break;
                case 'ENGAGEMENT':
                    $tag = 'ENGA';
                    break;
                case 'EVENT':
                    $tag = 'EVEN';
                    break;
                case 'FACSIMILE':
                    $tag = 'FAX';
                    break;
                case 'FAMILY':
                    $tag = 'FAM';
                    break;
                case 'FAMILY_CHILD':
                    $tag = 'FAMC';
                    break;
                case 'FAMILY_FILE':
                    $tag = 'FAMF';
                    break;
                case 'FAMILY_SPOUSE':
                    $tag = 'FAMS';
                    break;
                case 'FIRST_COMMUNION':
                    $tag = 'FCOM';
                    break;
                case '_FILE':
                    $tag = 'FILE';
                    break;
                case 'FORMAT':
                    $tag = 'FORM';
                case 'FORM':
                    // Consistent commas
                    $data = preg_replace('/ *, */', ', ', $data);
                    break;
                case 'GEDCOM':
                    $tag = 'GEDC';
                    break;
                case 'GIVEN_NAME':
                    $tag = 'GIVN';
                    break;
                case 'GRADUATION':
                    $tag = 'GRAD';
                    break;
                case 'HEADER':
                    $tag = 'HEAD';
                case 'HEAD':
                    // HEAD records don't have an XREF or DATA
                    if ($level == '0')
                    {
                        $xref = '';
                        $data = '';
                    }
                    break;
                case 'HUSBAND':
                    $tag = 'HUSB';
                    break;
                case 'IDENT_NUMBER':
                    $tag = 'IDNO';
                    break;
                case 'IMMIGRATION':
                    $tag = 'IMMI';
                    break;
                case 'INDIVIDUAL':
                    $tag = 'INDI';
                    break;
                case 'LANGUAGE':
                    $tag = 'LANG';
                    break;
                case 'LATITUDE':
                    $tag = 'LATI';
                    break;
                case 'LONGITUDE':
                    $tag = 'LONG';
                    break;
                case 'MARRIAGE':
                    $tag = 'MARR';
                    break;
                case 'MARRIAGE_BANN':
                    $tag = 'MARB';
                    break;
                case 'MARRIAGE_COUNT':
                    $tag = 'NMR';
                    break;
                case 'MARR_CONTRACT':
                    $tag = 'MARC';
                    break;
                case 'MARR_LICENSE':
                    $tag = 'MARL';
                    break;
                case 'MARR_SETTLEMENT':
                    $tag = 'MARS';
                    break;
                case 'MEDIA':
                    $tag = 'MEDI';
                    break;
                case '_MEDICAL':
                    $tag = '_MDCL';
                    break;
                case '_MILITARY_SERVICE':
                    $tag = '_MILT';
                    break;
                case 'NAME':
                    // Tidy up whitespace
                    $data = preg_replace('/  +/', ' ', trim($data));
                    break;
                case 'NAME_PREFIX':
                    $tag = 'NPFX';
                    break;
                case 'NAME_SUFFIX':
                    $tag = 'NSFX';
                    break;
                case 'NATIONALITY':
                    $tag = 'NATI';
                    break;
                case 'NATURALIZATION':
                    $tag = 'NATU';
                    break;
                case 'NICKNAME':
                    $tag = 'NICK';
                    break;
                case 'OBJECT':
                    $tag = 'OBJE';
                    break;
                case 'OCCUPATION':
                    $tag = 'OCCU';
                    break;
                case 'ORDINANCE':
                    $tag = 'ORDI';
                    break;
                case 'ORDINATION':
                    $tag = 'ORDN';
                    break;
                case 'PEDIGREE':
                    $tag = 'PEDI';
                case 'PEDI':
                    // PEDI values are lower case
                    $data = strtolower($data);
                    break;
                case 'PHONE':
                    $tag = 'PHON';
                    break;
                case 'PHONETIC':
                    $tag = 'FONE';
                    break;
                case 'PHY_DESCRIPTION':
                    $tag = 'DSCR';
                    break;
                case 'PLACE':
                    $tag = 'PLAC';
                case 'PLAC':
                    // Consistent commas
                    $data = preg_replace('/ *(،|,) */', ', ', $data);
                    // The Master Genealogist stores LAT/LONG data in the PLAC field, e.g. Pennsylvania, USA, 395945N0751013W
                    if (preg_match('/(.*), (\d\d)(\d\d)(\d\d)([NS])(\d\d\d)(\d\d)(\d\d)([EW])$/', $data, $match))
                    {
                        $data = $match[1] . "\n" .
                                ($level + 1) . " MAP\n" .
                                ($level + 2) . " LATI " . ($match[5] . (round($match[2] + ($match[3] / 60) + ($match[4] / 3600), 4))) . "\n" .
                                ($level + 2) . " LONG " . ($match[9] . (round($match[6] + ($match[7] / 60) + ($match[8] / 3600), 4)));
                    }
                    break;
                case 'POSTAL_CODE':
                    $tag = 'POST';
                    break;
                case 'PROBATE':
                    $tag = 'PROB';
                    break;
                case 'PROPERTY':
                    $tag = 'PROP';
                    break;
                case 'PUBLICATION':
                    $tag = 'PUBL';
                    break;
                case 'QUALITY_OF_DATA':
                    $tag = 'QUAL';
                    break;
                case 'REC_FILE_NUMBER':
                    $tag = 'RFN';
                    break;
                case 'REC_ID_NUMBER':
                    $tag = 'RIN';
                    break;
                case 'REFERENCE':
                    $tag = 'REFN';
                    break;
                case 'RELATIONSHIP':
                    $tag = 'RELA';
                    break;
                case 'RELIGION':
                    $tag = 'RELI';
                    break;
                case 'REPOSITORY':
                    $tag = 'REPO';
                    break;
                case 'RESIDENCE':
                    $tag = 'RESI';
                    break;
                case 'RESTRICTION':
                    $tag = 'RESN';
                case 'RESN':
                    // RESN values are lower case (confidential, privacy, locked, none)
                    $data = strtolower($data);
                    if ($data == 'invisible')
                    {
                        $data = 'confidential'; // From old versions of Legacy.
                    }
                    break;
                case 'RETIREMENT':
                    $tag = 'RETI';
                    break;
                case 'ROMANIZED':
                    $tag = 'ROMN';
                    break;
                case 'SEALING_CHILD':
                    $tag = 'SLGC';
                    break;
                case 'SEALING_SPOUSE':
                    $tag = 'SLGS';
                    break;
                case 'SOC_SEC_NUMBER':
                    $tag = 'SSN';
                    break;
                case 'SEX':
                    switch (trim($data))
                    {
                        case 'M':
                        case 'F':
                        case 'U':
                            break;
                        case 'm':
                            $data = 'M';
                            break;
                        case 'f':
                            $data = 'F';
                            break;
                        default:
                            $data = 'U';
                            break;
                    }
                    break;
                case 'SOURCE':
                    $tag = 'SOUR';
                    break;
                case 'STATE':
                    $tag = 'STAE';
                    break;
                case 'STATUS':
                    $tag = 'STAT';
                case 'STAT':
                    if ($data == 'CANCELLED')
                    {
                        // PGV mis-spells this tag - correct it.
                        $data = 'CANCELED';
                    }
                    break;
                case 'SUBMISSION':
                    $tag = 'SUBN';
                    break;
                case 'SUBMITTER':
                    $tag = 'SUBM';
                    break;
                case 'SURNAME':
                    $tag = 'SURN';
                    break;
                case 'SURN_PREFIX':
                    $tag = 'SPFX';
                    break;
                case 'TEMPLE':
                    $tag = 'TEMP';
                case 'TEMP':
                    // Temple codes are upper case
                    $data = strtoupper($data);
                    break;
                case 'TITLE':
                    $tag = 'TITL';
                    break;
                case 'TRAILER':
                    $tag = 'TRLR';
                case 'TRLR':
                    // TRLR records don't have an XREF or DATA
                    if ($level == '0')
                    {
                        $xref = '';
                        $data = '';
                    }
                    break;
                case 'VERSION':
                    $tag = 'VERS';
                    break;
                case 'WEB':
                    $tag = 'WWW';
                    break;
            }
            // Suppress "Y", for facts/events with a DATE or PLAC
            if ($data == 'y')
            {
                $data = 'Y';
            }
            if ($level == '1' && $data == 'Y')
            {
                for ($i = $n + 1; $i < $num_matches - 1 && $matches[$i][1] != '1'; ++$i)
                {
                    if ($matches[$i][3] == 'DATE' || $matches[$i][3] == 'PLAC')
                    {
                        $data = '';
                        break;
                    }
                }
            }
            // Reassemble components back into a single line
            switch ($tag)
            {
                default:
                    // Remove tabs and multiple/leading/trailing spaces
                    if (strpos($data, "\t") !== false)
                    {
                        $data = str_replace("\t", ' ', $data);
                    }
                    if (substr($data, 0, 1) == ' ' || substr($data, -1, 1) == ' ')
                    {
                        $data = trim($data);
                    }
                    while (strpos($data, '  '))
                    {
                        $data = str_replace('  ', ' ', $data);
                    }
                    $newrec.=($newrec ? "\n" : '') . $level . ' ' . ($level == '0' && $xref ? $xref . ' ' : '') . $tag . ($data === '' && $tag != "NOTE" ? '' : ' ' . $data);
                    break;
                case 'NOTE':
                case 'TEXT':
                case 'DATA':
                case 'CONT':
                    $newrec.=($newrec ? "\n" : '') . $level . ' ' . ($level == '0' && $xref ? $xref . ' ' : '') . $tag . ($data === '' && $tag != "NOTE" ? '' : ' ' . $data);
                    break;
                case 'FILE':
                    // Strip off the user-defined path prefix
                    if ($GEDCOM_MEDIA_PATH && strpos($data, $GEDCOM_MEDIA_PATH) === 0)
                    {
                        $data = substr($data, strlen($GEDCOM_MEDIA_PATH));
                    }
                    // convert backslashes in filenames to forward slashes
                    $data = preg_replace("/\\\/", "/", $data);

                    $newrec.=($newrec ? "\n" : '') . $level . ' ' . ($level == '0' && $xref ? $xref . ' ' : '') . $tag . ($data === '' && $tag != "NOTE" ? '' : ' ' . $data);
                    break;
                case 'CONC':
                    // Merge CONC lines, to simplify access later on.
                    $newrec.=($WORD_WRAPPED_NOTES ? ' ' : '') . $data;
                    break;
            }
        }
        return $newrec;
    }

}
