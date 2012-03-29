CREATE TABLE prefix_object (
id  SERIAL PRIMARY KEY,
name varchar(255) NOT NULL default '',
summary text NOT NULL DEFAULT '',
course integer NOT NULL default '0',
start_url text NOT NULL default '',
material_root text NOT NULL default '',
imsmanifest varchar(255) NOT NULL default ''
);