<?php
$host = 'ADMIN-EXCH.conel.ac.uk';
$port = '25';
$from = 'NKowald@staff.conel.ac.uk';

ini_set('SMTP', $host);
ini_set('smtp_port', $port);
ini_set('sendmail_from', $from);

/* phpinfo(); exit(); */

// Currently only allows sending to emails on the conel domain
$result = mail('nkowald@staff.conel.ac.uk', 'test smtp', 'This is a test of smtp.');
var_dump($result);
?>