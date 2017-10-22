<?php

/*
 *  Copyright (c) William Leonard <email protected> 2009. All Rights Reserved.
 *
 *
 *  This file is part of OBBLM.
 *
 *  OBBLM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OBBLM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once('modules/registration/header.php');
 
class Registration implements ModuleInterface
{
    /***************
     * Properties 
     ***************/

    //User table values
    public $username        = '';
    public $password        = '';
    public $email           = '';
    public $error           = '';
    public $isCommissioner  = false;
    public $leagueId          = '';

    //Password reset specific values
    public $reset_code      = '';
    
    /***************
     * Methods 
     ***************/
    
    function __construct($username, $password = "", $email = "", $isCommissioner, $leagueId, $form = "register") {

        switch ( $form ){

            case "register":
                $this->username = $username;
                $this->password = $password;
                $this->email = $email;
                $this->isCommissioner = $isCommissioner;
                $this->leagueId = $leagueId;

                // Check to see if coach name already exists.
                if ( !$this->chk_username() || !$this->chk_password() || !$this->chk_email() )
                {
                    return false;  //Use ->error to display the error message to the user.
                }
                
                if(!$leagueId && !$isCommissioner) {
                    $this->error = 'You must either select a league or be a league commissioner. If your league isn\'t listed, talk with your commissioner!';
                    return false;                    
                }
                
                $this->create();
                if ( !$this->sendemail($leagueId) )
                {
                    $this->error = SEND_EMAIL_ERROR;
                }
                break;
            case "forgot":
                $this->username = $username;

                // Check to see if coach name exists.
                if ( $this->chk_username() || strlen($this->username) < 3 )
                {
                    $this->error = USERNAME_RESET_ERROR;
                    return false;  //Use ->error to display the error message to the user.
                }
                else $this->error = "";

                $this->email = get_alt_col(USERTABLE, USERNAME, $this->username, EMAIL);
                if ( !$this->chk_email() ) return false;
                $this->createResetCode();
                $this->sendResetemail();
                break;
            case "activate":

                if ( !isset($_SESSION['coach_id']) )
                {
                    $this->error = "You must be logged in to use this page.";
                    return false;
                }

                $myCoach = new Coach($_SESSION['coach_id']);
                $coachToActivate = Coach::getByName($username);
                if ( !$myCoach->mayManageObj(T_OBJ_COACH, $coachToActivate->coach_id) )
                {
                    $this->error = "You must be an administrator to access this page.";
                    return false;
                }

                $this->username = $username;

                // Check to see if coach name exists.
                if ( $this->chk_username() || strlen($this->username) < 3 )
                {
                    $this->error = USERNAME_RESET_ERROR;
                    return false;  //Use ->error to display the error message to the user.
                }
                else $this->error = "";

                $this->email = get_alt_col(USERTABLE, USERNAME, $this->username, EMAIL);
                if ( !$this->chk_email() ) return false;
                if ( !$this->activateUser() ) return false;
                if ( !$this->sendActivatedemail() )
                {
                    $this->error = SEND_EMAIL_ERROR_ACTIVATED;
                    return false;
                };
                break;

        }


    }

    public function chk_username() {

        $status = true;
        $min_length = 3;

        if ( get_alt_col(USERTABLE, USERNAME, $this->username, USERNAME) || strlen($this->username) < $min_length )
        {
            $this->error = USERNAME_ERROR;
            $status = false;
        }

        return $status;

    }

    public function chk_password() {

        //Will add regexp check later
        $status = true;
        $min_length = 5;

        if ( strlen($this->password) < $min_length )
        {
            $this->error = PASSWORD_ERROR;
            $status = false;
        }
#        else $this->password = md5( $this->password );

        return $status;

    }

    public function chk_email() {

        #'/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i' : 
        #'/^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i' 

        $status = true;
        $domain = '';

        $atpos = strpos($this->email, '@');
        if ( $atpos ) $domain = substr($this->email, $atpos + 1);

        $emailexp = "/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i";

        if ( !preg_match($emailexp, $this->email) ) $status = false;

        if ( !getmxrr($domain, $mxrecords) || !$status )
        {
            $this->error = EMAIL_ERROR;
            $status = false;
        }

        return $status;

    }

    public function create() {

        $status = true;
        global $settings;

        Coach::create(
            $input = array(
                'name' => $this->username,
                'passwd' => $this->password,
                'mail' => $this->email,
                'def_leagues' => array($this->leagueId),
                'ring' => ACCESS_LEVEL,
                'realname' => '',
                'phone' => '',
                'settings' => array('lang' => $settings['lang'])
            )
        );
        
        #Input: name, realname, passwd, mail, phone, ring, settings, def_leagues (array of LIDs)

#        $query = sprintf( "INSERT INTO %s ( %s, %s, %s, %s, %s ) VALUES ( '%s', '%s', '%s', %d, %d )",
#                 USERTABLE,
#                 USERNAME, PASSWORD, EMAIL, ACTIVATION, ACCESS,
#                 mysql_real_escape_string($this->username), $this->password, mysql_real_escape_string($this->email), NOT_ACTIVATED, ACCESS_LEVEL );

        $query = sprintf( "UPDATE %s SET %s = %d WHERE %s = '%s' LIMIT 1",
                 USERTABLE,
                 ACTIVATION, NOT_ACTIVATED, 
                 USERNAME, mysql_real_escape_string($this->username) );


        $results = mysql_query($query);
        if ( !$results )
        {
            $status = false;
            $this->error = mysql_error();
        }
                            
        return $status;

    }

    private function sendemail($leagueId) {
        $status = true;

        global $settings;
        $webmaster = $settings['registration_webmaster'];

        if($leagueId)
            $to = Email::getLeagueCommissionerEmails($leagueId);
        else
            $to = Email::getAdministratorEmails();
        
        $subject = EMAIL_SUBJECT;
        $message = EMAIL_MESSAGE 
                    . $this->username . ", " 
                    . $this->email . "\n" 
                    . "http://" . $_SERVER["SERVER_NAME"]."/handler.php?type=registration&form=activate\n"
                    . "League ID: " . $leagueId . "\n"
                    . "Is Commissioner: " . $this->isCommissioner;
        $headers = 'From: '.$webmaster. "\r\n" .
                   'Reply-To: '.$webmaster. "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        $mailed = mail($to, $subject, $message, $headers);

        if ( !$mailed ) 
            $status = false;

        return $status;

    }

    /***************
     * Statics
     ***************/
     
    private static function form() {
        
        /**
         * Creates a registration form.
         *
         * 
         **/

        $form = '
        <form id="registrationForm" method="POST" action="handler.php?type=registration" data-bind="with: registrationViewModel">
            <div class="boxCommon">
                <div class="boxTitle'.T_HTMLBOX_COACH.'">
                    Register
                </div>
                <div class="boxBody">
                    Username<br> <input type="text" name="new_name" size="20" maxlength="50"><br><br>
                    Email<br> <input type="text" name="new_mail" size="20" maxlength="129"><br><br>
                    Password<br> <input type="password" name="new_passwd" size="20" maxlength="50"><br><br>
                    <strong>I\'m a league commissioner:</strong> <input type="checkbox" name="is_commissioner" data-bind="checked: isCommissioner"><br /><br />
                    <div data-bind="visible: showLeagueSelection">
                        <div>League</div>
                        <select name="new_league" data-bind="options: leagues, optionsText: \'name\', optionsValue: \'lid\', value: selectedLeague, optionsCaption: \'Select a league.\'"></select>
                    </div>
                    <br />
                    *Admin activation is required.
                    <br><br>
                    <input type="submit" name="button" value="Create user">
                </div>
            </div>
        </form>
        ';
        
        return $form;
    }

    private static function submitForm($username, $password, $email, $isCommissioner, $league) {

        $register = new Registration($username, $password, $email, $isCommissioner, $league);
        if ( !$register->error )
        {
            Print SUCCESS_MSG;
            unset($register);
        }
        else
        {
            status(0, $register->error);
            unset($register);
            unset($_POST['new_name']);
            unset($_POST['new_mail']);
            unset($_POST['new_passwd']);
            Registration::main(array());
        }

    }

    public static function getModuleAttributes()
    {
        return array(
            'author'     => 'William Leonard',
            'moduleName' => 'Registration',
            'date'       => '2009',
            'setCanvas'  => true,
        );
    }

    public static function getModuleTables()
    {
        return array();
    }

    public static function getModuleUpgradeSQL()
    {
        return array();
    }
    
    public static function triggerHandler($type, $argv){}

    public static function main($argv) {
        
        // Module registered main function.
        global $settings;
        if ( !$settings['allow_registration'] ) die ("Registration is currently disabled.");
        $form = "";
        if ( isset($_GET['form']) ) $form = $_GET['form'];

        if ( isset($_POST['new_name']) && isset($_POST['new_mail']) && isset($_POST['new_passwd']) )
        {
            $username = $_POST['new_name'];
            $password = $_POST['new_passwd'];
            $email = $_POST['new_mail'];
            $isCommissioner = isset($_POST['is_commissioner']);
            $league = $_POST['new_league'];
            self::submitForm($username, $password, $email, $isCommissioner, $league);
            return true;
        }
        if ( $form == "forgot" )
        {

            if ( isset($_POST['new_name']) )
            {
                $username = $_POST['new_name'];
                self::forgotsubmitForm($username);
            }
            else
            {
                Print "<html><body>";
                Print Registration::forgotform();
                Print "</body></html>";
            }
        }

        if ( $form == "activate" )
        {

            if ( !isset($_SESSION['coach_id']) )
            {
                Print "You must be logged in to use this page.";
                return false;
            }
            
            
            if(!isset($_POST['activate_name'])) {
                Print "<html><body>";
                Print Registration::activateform();
                Print "</body></html>";
            } else {
                $coachToActivate = Coach::getByName($_POST['activate_name']);
                $myCoach = new Coach($_SESSION['coach_id']);
                
                if ( !$myCoach->mayManageObj(T_OBJ_COACH, $coachToActivate->coach_id) ) {
                    Print "You must be an administrator to access this page.";
                    return;
                }

                $username = $_POST['activate_name'];
                self::activatesubmitForm($username);
            }
        }

        else
        {

            Print "<html><body>";
            Print Registration::form();
            Print "</body></html>";

        }
    }

    private static function forgotform() {
        
        /**
         * Creates a forgot password form.
         *
         * 
         **/

        $form = "
        <form method='POST' action='handler.php?type=registration&form=forgot'>
            <div class='adminBox'>
                <div class='boxTitle3'>
                    Register
                </div>
                <div class='boxBody'>
                    Username :<br> <input type='text' name='new_name' size='20' maxlength='50'><br><br>
                    *Instructions to reset <br>your password will be <br>emailed to you.
                    <br><br>
                    <input type='submit' name='button' value='Reset password'>
                </div>
            </div>
        </form>
        ";

        return $form;

    }

    private static function forgotsubmitForm($username) {

        $register = new Registration($username, '', '', '', '', "forgot");
        if ( !$register->error )
        {
            Print "Instructions to reset your password have been sent to the email address on file.";
            unset($register);
        }
        else
        {
            Print "<br><b>Error: {$register->error}</b><br>";
            unset($register);
            unset($_POST['new_name']);
            Registration::main(array());
        }

    }

    function createResetCode() {

        $i = 0;
        while ( $i <10 )
        {
            $this->reset_code .= dechex ( rand(0, 15) );
            $i++;
        }

    }

    function sendResetemail() {

        $status = true;

        global $settings;
        $webmaster = $settings['registration_webmaster'];

        $to      = $this->email;
        $subject = 'Password Reset Instructions';
        $message = "Your password reset code is: ".$this->reset_code;
        $headers = 'From: '.$webmaster. "\r\n" .
                   'Reply-To: '.$webmaster. "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        $mailed = mail($to, $subject, $message, $headers);

        if ( !$mailed ) $status = false;

        return $status;

    }

    /*
    Activation methods
    */
    private static function activateform() {

        /**
         * Creates an activation form.
         *
         * 
         **/

        $form = "
        <form method='POST' action='handler.php?type=registration&form=activate'>
            <div class='boxCommon'>
                <div class='boxTitle3'>
                    Activate user
                </div>
                <div class='boxBody'>
                    Username :<br> <input type='text' name='activate_name' size='20' maxlength='50'><br><br>
                    <br><br>
                    <input type='submit' name='button' value='Activate'>
                </div>
            </div>
        </form>
        ";


        return $form;

    }

    private static function activatesubmitForm($username) {

        $register = new Registration($username, '', '', '', '', "activate");
        if ( !$register->error )
        {
            Print "The user was successfully activated.";
            unset($register);
        }
        else
        {
            Print "<br><b>Error: {$register->error}</b><br>";
            unset($register);
            unset($_POST['activate_name']);
            Registration::main(array());
        }

    }

    function activateUser() {

        $coach_id = get_alt_col(USERTABLE, USERNAME, $this->username, USERID);
        $c = new Coach($coach_id);
        if ( $c->retired == 2 )
        {
            mysql_query("UPDATE coaches SET retired = 0 WHERE coach_id = $coach_id");
        }
        else
        {
            $this->error = ACTIVATION_NOT_NEEDED;
            return false;
        }

        return true;

    }

    function sendActivatedemail() {

        $status = true;

        global $settings;
        $webmaster = $settings['registration_webmaster'];

        $to      = $this->email;
        $subject = EMAIL_SUBJECT_ACTIVATED;
        $message = $this->username.EMAIL_MESSAGE_ACTIVATED."http://".$_SERVER["SERVER_NAME"];
        $headers = 'From: '.$webmaster. "\r\n" .
                   'Reply-To: '.$webmaster. "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        $mailed = mail($to, $subject, $message, $headers);

        if ( !$mailed ) $status = false;

        return $status;

    }

}

?>
