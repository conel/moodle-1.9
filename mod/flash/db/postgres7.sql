

CREATE TABLE prefix_flash (
  id SERIAL PRIMARY KEY,
  course integer NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  moviename varchar(255) NOT NULL default '',
  grade integer NOT NULL default '0',
  gradingmethod varchar(255) default NULL,
  showgrades integer NOT NULL default '1',
  showheader integer NOT NULL default '1',
  size integer NOT NULL default '1',
  to_config integer default NULL,
  config text,
  q_no integer NOT NULL default '0',
  answers text,
  guestfeedback text,
  feedback text,
  usepreloader integer default NULL,
  fonts text,
  usesplash integer default NULL,
  splash text,
  splashformat integer default '0',
  timemodified integer NOT NULL default '0');


CREATE TABLE prefix_flash_accesses (
  id SERIAL PRIMARY KEY,
  flashid integer NOT NULL default '0',
  userid integer NOT NULL default '0',
  timemodified integer NOT NULL default '0');


CREATE TABLE prefix_flash_answers (
  id SERIAL PRIMARY KEY,
  answer text,
  q_no integer default NULL,
  accessid integer NOT NULL default '0',
  grade integer NOT NULL default '0');
        
CREATE TABLE prefix_flash_movies (
  id SERIAL PRIMARY KEY ,
  version integer NOT NULL ,
  bgcolor VARCHAR( 7 ) NOT NULL ,
  width integer NOT NULL ,
  height integer NOT NULL ,
  framerate integer NOT NULL ,
  moviename VARCHAR( 255 ) NOT NULL ,
  timemodified integer NOT NULL );
