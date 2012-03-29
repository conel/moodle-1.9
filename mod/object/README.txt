Object Module by Tom Flannaghan, Andrew Walker, Helen Foster, Alton College, Hampshire, UK


This module allows teachers to browse and preview learning objects (e.g. NLN materials) in a repository, then add them to their courses.

A learning object is a folder containing a top-level imsmanifest.xml file. This file contains information such as the title of the learning object and whether any internal navigation is required.


TO INSTALL OR UPDATE THIS MODULE

1. Install the core scripts of the object module by downloading the zip file and extracting it to moodle/mod. This will create a folder called "object" in the mod folder.

2. Install the language pack by extracting en.zip to moodle/lang

3. Visit your admin page to complete the installation.


To SET UP THE REPOSITORY

Copy learning objects to moodle/mod/object/repository.

If an alternative location for the repository is preferred, then the file path to the new location should be added in Administration >> Configuration >> Modules >> Object.

Please note: The directory structure of the repository is used to create the object navigation so it is important to set this up in a logical way to enable teachers to easily find relevant learning objects.

Student access to the repository (without an "add to course" option) may be provided with the following URL:
http://yourmoodlesite.org/mod/object/finder.php?hidebutton=true


For future development: a meta-data search facility, integration into "Repository" resource type in Moodle 1.6.


22nd August 2005