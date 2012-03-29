<?php

// connect to a DSN "mydb" with a user and password "marin" 
$connect = odbc_connect("EBS_DATA", "quis", "shazam");

// query the users table for name and surname
$query = "SELECT * FROM BLOBS";

// perform the query
$result = odbc_exec($connect, $query);

var_dump($result);

// fetch the data from the database
while(odbc_fetch_row($result)){
	var_dump($result);
	/*
  $name = odbc_result($result, 1);
  $surname = odbc_result($result, 2);
  print("$name $surname\n");
  */
}

// close the connection
odbc_close($connect);

?>