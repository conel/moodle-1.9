CREATE TABLE prefix_object (
id int(10) unsigned NOT NULL auto_increment,
name varchar(255) NOT NULL default '',
summary text NOT NULL DEFAULT '',
course int(10) unsigned NOT NULL default '0',
start_url text NOT NULL default '',
material_root text NOT NULL default '',
imsmanifest varchar(255) NOT NULL default '',
PRIMARY KEY (id)
)