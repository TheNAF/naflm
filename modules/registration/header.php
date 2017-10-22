<?php
// User table information
define('USERTABLE', 'coaches');
define('USERNAME', 'name');
define('USERID', 'coach_id');
define('PASSWORD', 'passwd');
define('EMAIL', 'mail');
define('ACTIVATION', 'retired');
define('NOT_ACTIVATED', 2);
define('IS_ACTIVATED', 0);
define('ACCESS', 'ring');
define('ACCESS_LEVEL', Coach::T_RING_GLOBAL_NONE);

// Error messages *NOTE: Error messages are not concatenated as only one needs to be seen by the user.
define('USERNAME_ERROR', 'The username already exists or must be at least 3 characters long.');
define('PASSWORD_ERROR', 'The password must be at least 5 characters long');
define('EMAIL_ERROR', 'The email address is not valid.');
define('EMAIL_SUBJECT', 'New user registration');
define('EMAIL_MESSAGE', 'You have received a new registration.  To activate the user, use the activation page by visiting the link below.

coach: ');

define('EMAIL_SUBJECT_ACTIVATED', 'Your coach has been activated.');
//The coach name is printed before the following message
define('EMAIL_MESSAGE_ACTIVATED', ': your coach on the site has been activated.

Website: ');

define('SEND_EMAIL_ERROR', 'Registration was successful, but the administrators were NOT notified via email.  Your account will not be activated unless you contact an administrator.');
define('SEND_EMAIL_ERROR_ACTIVATED', 'Activation was successful, but the user was not notified by email.');
define('SUCCESS_MSG', 'Registration was successful.  A site administrator will enable your account or contact you for verification.');
define('USERNAME_RESET_ERROR', 'The username does not exist.');
define('ACTIVATION_NOT_NEEDED', "The username specified does not need to be activated.")
?>
