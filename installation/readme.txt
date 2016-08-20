WELCOME TO TUSKFISH CMS
=======================

INSTALLATION
------------


SECURING YOUR INSTALLATION
--------------------------
Lock down your Tuskfish CMS installation by doing this:

1. Ensure that your trust_path folder lies OUTSIDE of your web root, so that they are not browsable.

2. Adjust the file permmissions (CHMOD) the following, to restrict access:
* mainfile.php: 0444
* /trustpath/masterfile.php: 0444
* /trust_path/database/: 0700
* /trust_path/database/your_sqlite_database.db: 0600
* /trust_path/libraries/configuration/config.php: 0444 

3. Bear in mind that access to the SQLite database is restricted PURELY by file permissions. There 
are NO password or username controls; anyone that can browse this file can download it and read it.
This is why the database directory is kept outside of the web root, and has restrictive permissions
(basically only the webserver should be able to read and write to the database file/folder).