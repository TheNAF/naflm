<?php

define('T_NO_STARTUP', true);
require('header.php'); // Includes and constants.
HTMLOUT::frame_begin(false,false);
title('OBBLM setup');
if (isset($_POST['setup']) || (isset($argv[1]) ? $argv[1] == 'setup' : false)) {
    $setupOK = setup_database();
    if ($setupOK) {
        echo "<br><b><font color='green'>Finished</font></b>";
        $helpURL = DOC_URL;
        echo <<<EOL
<br><br>
<b>What now?</b><br>
&mdash; Renaming the <i>install.php</i> file from your OBBLM folder. Please continue to <a href='index.php'>the main page</a>.<br>
&mdash; Once at the main page login using the coach account 'root' with password 'root'<br>
&mdash; From there you may enter the <i>administration</i> section and add new users (coaches) including changing the root password.<br>
&mdash; For further help visit the <a href='$helpURL'>OBBLM wiki</a>.<br>
<br>
<b>Need help? Encountering errors?</b><br>
&mdash; If you are encountering errors please visit <a href='http://code.google.com/p/obblm/issues/list'>code.google.com/p/obblm</a> and create a bug report.<br>
EOL;
        echo "<br><br>";
        HTMLOUT::dnt();
	// issue #213 - removing the install.php file to support docker
        rename (getcwd()."/install.php", getcwd()."/install.php.rename");    
    }
    else {
        echo "<br><b><font color='red'>Failed</font></b>";
        echo <<<EOL
<br><br>
<b>Need help? Encountering errors?</b><br>
&mdash; If you are encountering errors please visit <a href='http://code.google.com/p/obblm/issues/list'>code.google.com/p/obblm</a> and create a bug report.<br>
EOL;
    }
} else {
	// Make sure OBBLM is not already installed
	$conn = mysql_up();
	if(mysql_query("DESCRIBE coaches")) {
		echo <<<EOL
It seems OBBLM is already installed.<br>You can safely delete the file <i>install.php</i> and go to the main page.
<br><br>If you need help please visit <a href='http://code.google.com/p/obblm/issues/list'>code.google.com/p/obblm</a> and create a bug report.<br>
EOL;
	} else {
		?>
		Please, <b>before starting the installation process</b>, make sure that the MySQL user and database you have specified in <i>settings.php</i> exist and are valid.<br><br>When ready, press the button below:<br><br>
		<form method="POST">
			<input type="submit" name="setup" value="Setup DB for OBBLM">
		</form>
		<?php
	}
}
HTMLOUT::frame_end();
