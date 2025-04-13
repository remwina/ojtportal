<?php 

define('STRING_FORMAT', "/.*[a-zA-Z./,-].*/");
define('SRCODE_FORMAT', "/^[0-9]{2}-[0-9]{5}$/");
define('EMAIL_FORMAT', "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/");
define('CONTACT_FORMAT', "/^\+?[\d]{8,30}$/");
define('UPPERCASE_FORMAT', "/.*[A-Z].*/");
define('LOWERCASE_FORMAT', "/.*[a-z].*/");
define('DIGIT_FORMAT', "/.*[0-9].*/");
define('SPECIAL_CHAR_FORMAT', "/.*[_\$\@\!\#\%\^\&\*\(\)\-\=\+\?\.\,].*/");
define('PASSWORD_FORMAT', "/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[_\$\@\!\#\%\^\&\*\(\)\-\=\+\?\.\,]).*$/");

?>