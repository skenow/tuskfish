Tuskfish skeleton module
========================

The code in this folder provide a basic directory and file structure for
creating a Tuskfish module. Of course, you need to design the class files,
controller scripts and templates yourself, but at least you can see what files
are needed and where they go. Rename the files to suit your own purposes.

As the structure suggests, the bulk of the module should be placed in the
trust_path/modules/someModule directory, where someModule is whatever you have
decided to call it. 

The front end and admin controller scripts go in public_html and
public_html/admin/ respectively, where public_html is the web root for your
Tuskfish installation. Use the existing Tuskfish control scripts as examples, if
you need to.

The only exception are the HTML template files (someModule/templates). Copies of
these need to be placed in each theme you want to use your module with, ie. in
public_html/themes/someTheme/ and so on.