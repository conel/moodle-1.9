# Moodle 1.9
---
### Environment
* Virtual Server 
    * Windows Server 2008 Standard (SP2)
    * x4 2.4 GHz Intel Processors
    * 8 GB RAM
    * 64-bit
* PHP 5.2.1
    * IIS7 (PHP via FastCGI)
    * Wincache PHP extension
* MySQL 
    * Version 5.1.3.6
    * Server: moodle-sql
    * InnoDB table engines used
---
### Scheduled Tasks
Nine scheduled tasks have been set up.
* Moodle BKSB Sync (disabled)
* Moodle LDAP Import
* Moodle Enrolments Sync
* Moodle DB Backup
* Subject Targets 1 - Tutor Import
* Subject Targets 2 - Subject Import
* Subject Targets 3 - Complete Update
* Expunge Windows Temp
---
### Changes
We've made a LOT of changes to the core code in our Moodle 1.9.  
I've not been able to upgrade for a long time, I fear overriding changed functionality.  

When we move to Moodle 2 I'm never going to change core code.  
Until the 4th of June 2010 I was documenting every change to the core code. 

These changes to the core code are documented in the [#changes](/conel/moodle-1.9/tree/master/%23changes) folder.  
There's a Word and Excel document. Also a text file so changes are searchable in GitHub.  
