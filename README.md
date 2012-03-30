# Moodle 1.9

## Environment
* Virtual Servers
    * moodle-web
        * Windows Server 2008 Standard (SP2) (64-bit)
        * (4x) 2.4 GHz Intel Processors
        * 8 GB RAM
        * IIS7
    * moodle-sql
        * Windows Server 2008 Standard (SP2) (64-bit)
        * (4x) 2.4 GHz Intel Processors
        * 4 GB RAM
* PHP 5.2.1
    * Non thread safe
    * PHP via FastCGI
    * Wincache PHP extension
* MySQL 5.1.3.6
    * InnoDB table engines used
    * Logs: slow queries (queries running for > 5 seconds)

## Changes
We've made a LOT of changes to the core code in our Moodle 1.9.  
I've not been able to upgrade for a long time, I fear overriding changed functionality.  

When we move to Moodle 2 I'm never going to change core code.  
Until the 4th of June 2010 I was documenting every change to the core code. 

These changes to the core code are documented in the [#changes](/conel/moodle-1.9/tree/master/%23changes) folder.  
There's a Word and Excel document. Also a text file so changes are searchable in GitHub.  

## Scheduled Tasks
Nine scheduled tasks have been set up:  

* Moodle LDAP Import
* Moodle Enrolments Sync
* Moodle DB Backup
* Subject Targets 1 - Tutor Import
* Subject Targets 2 - Subject Import
* Subject Targets 3 - Complete Update
* Expunge Windows Temp
* Moodle BKSB Sync [disabled]
