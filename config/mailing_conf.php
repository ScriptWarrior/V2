<?php
## uni mailer conf
define('SMTP_AUTH',true);
define('SMTP_SECURE','ssl');
define('SMTP_HOST','smtp.ewil.pl');
define('SMTP_PORT',465);
define('SMTP_USERNAME','d@ewil.pl');
define('SMTP_PASSWORD','myfuckingpassword');
define('SMTP_CHARSET','utf-8');
define('SMTP_WORD_WRAP',50);
define('SMTP_FROM',SMTP_USERNAME);
define('SMTP_FROM_NAME',SMTP_USERNAME);
?>