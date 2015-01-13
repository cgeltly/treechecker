The files in this folder and subfolders are taken from webtrees (http://www.webtrees.net/index.php/en/). 
The following modifications were made: 

- Family.php -> removed __construct__ function; we fetch the husband and wife in another way.
- Family.php -> modified getChildren() to directly return the GEDCOM keys without looking them up in the database (we'll do that in our own database of course).
- GedcomRecord.php -> canShow() will always return true; we don't care about user management. 
- Place.php -> most of the methods in the class removed, because a new Geocode.php class will deal with much of the place processing.
- Date.php -> the ability to parse dates in format d(d)?m(m)?(yyyy) or m(m)?d(d)?y(yyy) added; distinguishing between the two variants is an error checking function
- Date.php -> addition of a boolean indicator as to whether the date appears to be estimated or not  