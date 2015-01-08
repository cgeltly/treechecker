The files in this folder and subfolders are taken from webtrees (http://www.webtrees.net/index.php/en/). 
The following modifications were made: 

- Family.php -> removed __construct__ function; we fetch the husband and wife in another way.
- Family.php -> modified getChildren() to directly return the GEDCOM keys without looking them up in the database (we'll do that in our own database of course).
- GedcomRecord.php -> canShow() will always return true; we don't care about user management. 
- Place.php -> most of the methods in the class removed, because the Geocode.php class deals with much of the place processing.