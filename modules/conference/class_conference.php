<?php
/*
 *  Copyright (c) Ian Williams <email is protected> 2011. All Rights Reserved.
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
/*
    This file is a template for modules.

    Note: the two terms functions and methods are used loosely in this documentation. They mean the same thing.

    How to USE a module once it's written:
    ---------------------------------
        Firstly you will need to register it in the modules/modsheader.php file.
        The existing entries and comments should be enough to figure out how to do that.
        Now, let's say that your module (as an example) prints some kind of statistics containing box.
        What should you then write on the respective page in order to print the box?

            if (Module::isRegistered('MyModule')) {
                Module::run('MyModule', array());
            }

        The second argument passed to Module::run() is the $argv array passed on to main() (see below).
*/
/*
	This module is to allow tournaments to group teams into conferences, and then show tables of rankings by conference instead of purely on the league
*/

class Conference implements ModuleInterface
{

/***************
 * ModuleInterface requirements. These functions MUST be defined.
 ***************/

/*
 *  Basically you are free to design your main() function as you wish.
 *  If you are writing a simple module that merely echoes out some data, you may want to have main() doing all the work (i.e. place all your code here).
 *  If you on the other hand are writing a module which is divided into several routines, you may (and should) use the main() as a wrapper for calling the appropriate code.
 *
 *  The below main() example illustrates how main() COULD work as a wrapper, when the subdivision of code is done into functions in this SAME class.
 */
public static function main($argv) # argv = argument vector (array).
{
    /*
        Let $argv[0] be the name of the function we wish main() to call.
        Let the remaining contents of $argv be the arguments of that function, in the correct order.

        Please note only static functions are callable through main().
    */

    $func = array_shift($argv);
    return call_user_func_array(array(__CLASS__, $func), $argv);
}

/*
 *  This function returns information about the module and its author.
 */
public static function getModuleAttributes()
{
    return array(
        'author'     => 'DoubleSkulls',
        'moduleName' => 'Conference',
        'date'       => '2011', # For example '2009'.
        'setCanvas'  => true, # If true, whenever your main() is run through Module::run() your code's output will be "sandwiched" into the standard HTML frame.
    );
}

/*
 *  This function returns the MySQL table definitions for the tables required by the module. If no tables are used array() should be returned.
 */
public static function getModuleTables()
{
    global $CT_cols;

	return array(
        # Table name => column definitions
        'conferences' => array(
            # Column name => column definition
				'conf_id'       => $CT_cols[T_NODE_TOURNAMENT].' NOT NULL PRIMARY KEY AUTO_INCREMENT',
				'f_tour_id'       => $CT_cols[T_NODE_TOURNAMENT].' NOT NULL ',
				'name'          => $CT_cols['name'],
				'type'          => 'TINYINT UNSIGNED',
				'date_created'  => 'DATETIME',
        ),
        'conference_teams' => array(
			'f_conf_id'       => $CT_cols[T_NODE_TOURNAMENT].' NOT NULL ',
	        'f_team_id'      => $CT_cols[T_OBJ_TEAM].' NOT NULL ',
			'PRIMARY KEY'  => '(f_conf_id,f_team_id)',
        ),
    );
}

public static function getModuleUpgradeSQL()
{
    return array(
        '096-097' => array(
            'CREATE TABLE IF NOT EXISTS conferences
            (
                conf_id         MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                f_tour_id       MEDIUMINT UNSIGNED NOT NULL,
                name            VARCHAR(60),
                type            TINYINT UNSIGNED,
                date_created    DATETIME
            )',
            'CREATE TABLE IF NOT EXISTS conference_teams
            (
                f_conf_id       MEDIUMINT UNSIGNED NOT NULL,
                f_tour_id       MEDIUMINT UNSIGNED NOT NULL,
                primary key     (f_conf_id, f_tour_id)
            )'            
        ),
    );
}

public static function triggerHandler($type, $argv){
}

/***************
 * OPTIONAL subdivision of module code into class methods.
 *
 * These work as in ordinary classes with the exception that you really should (but are strictly required to) only interact with the class through static methods.
 ***************/

/***************
 * Properties
 ***************/
public $conf_id      = 0;
public $f_tour_id         = 0;
public $name       = '';
public $type        = '';
public $date_created        = '';
public $teamIds        = array();
public $teams          = array();


function __construct()
{
}

/* Gets the IDs of the teams already allocated to this conference */
function loadTeamIds() {
    $result = mysql_query("SELECT f_team_id FROM conference_teams WHERE f_conf_id=" . $this->conf_id);
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            array_push($this->teamIds, $row['f_team_id']);
        }
    }
}

/* Loads the team objects for the teams IDs */
function loadTeams() {
	$this->teamIds = array();
	$this->teams = array();
	$this->loadTeamIds();
	foreach($this->teamIds as $team_id) {
		array_push($this->teams, new Team($team_id));
	}
}

public static function getConferencesForTour($tour_id)
{
    $conferences = array();

    $result = mysql_query("SELECT conf_id, f_tour_id, name, type, date_created FROM conferences WHERE f_tour_id=$tour_id ORDER by name");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
        	$conf = new Conference();
            foreach ($row as $key => $val) {
                $conf->$key = $val;
                $conf->loadTeamIds();
            }
            array_push($conferences, $conf);
        }
    }

    return $conferences;
}

public static function addTeamToConference($conf_id,$team_id)  {
    global $lng;
    $result = mysql_query("INSERT INTO conference_teams (f_conf_id, f_team_id) VALUES ($conf_id, $team_id)");
    if ($result) {
		echo "<div class='boxWide'>";
		HTMLOUT::helpBox($lng->getTrn('addedTeam', 'Conference'));
		echo "</div>";
    } else {
		echo "<div class='boxWide'>";
		HTMLOUT::helpBox($lng->getTrn('failedAddTeam', 'Conference'), '', 'errorBox');
		echo "</div>";
    }
}

public static function removeTeamFromConference($conf_id,$team_id)  {
    global $lng;
    $result = mysql_query("DELETE FROM conference_teams WHERE f_conf_id=$conf_id AND f_team_id=$team_id");
    if ($result) {
		echo "<div class='boxWide'>";
		HTMLOUT::helpBox($lng->getTrn('removedTeam', 'Conference'));
		echo "</div>";
    } else {
		echo "<div class='boxWide'>";
		HTMLOUT::helpBox($lng->getTrn('failedRemoveTeam', 'Conference'), '', 'errorBox');
		echo "</div>";
    }
}

public static function removeConference($conf_id)  {
    global $lng;
    $result = mysql_query("DELETE FROM conference_teams WHERE f_conf_id=$conf_id");
    if ($result) {
    	$result = mysql_query("DELETE FROM conferences WHERE conf_id=$conf_id");
	}
    if ($result) {
		echo "<div class='boxWide'>";
		HTMLOUT::helpBox($lng->getTrn('removedConf', 'Conference'));
		echo "</div>";
    } else {
		echo "<div class='boxWide'>";
		HTMLOUT::helpBox($lng->getTrn('failedRemoveConf', 'Conference'), '', 'errorBox');
		echo "</div>";
    }
}

public static function addConference($tour_id, $conf_name)  {
    global $lng;
    $result = mysql_query("INSERT INTO conferences (f_tour_id, name, type, date_created) VALUES ($tour_id, '".mysql_real_escape_string($conf_name)."', 1, now())");
    if ($result) {
		echo "<div class='boxWide'>";
		HTMLOUT::helpBox($lng->getTrn('addedConf', 'Conference'));
		echo "</div>";
    } else {
		echo "<div class='boxWide'>";
		HTMLOUT::helpBox($lng->getTrn('failedAddConf', 'Conference'), '', 'errorBox');
		echo "</div>";
    }
}


public static function handleActions() {
    global $lng, $coach;

    if (isset($_POST['action']) && is_object($coach) && $coach->isNodeCommish(T_NODE_TOURNAMENT, $_POST['tour_id'])) {

		$tour_id = $_POST['tour_id'];
        $conf_name = isset($_POST['conf_name']) ? stripslashes($_POST['conf_name']) : 0 ;
        $conf_id = isset($_POST['conf_id']) ? $_POST['conf_id'] : 0 ;
        $team_id = isset($_POST['team_id']) ? $_POST['team_id'] : 0 ;

        switch ($_POST['action'])
        {
            case 'add_team':
            	self::addTeamToConference($conf_id, $team_id);
            	break;
            case 'add_conf':
            	self::addConference($tour_id, $conf_name);
            	break;
            case 'remove_team':
            	self::removeTeamFromConference($conf_id, $team_id);
            	break;
            case 'remove_conf':
            	self::removeConference($conf_id);
            	break;
        }
    }
}

/*
 *	Displays the list of tournaments this coach can manage
 */
public static function tournamentSelector($tour_id) {
    global $lng, $tours, $coach;
	$manageable_tours = array();
	foreach ($tours as $trid => $desc) {
		if ($coach->isNodeCommish(T_NODE_TOURNAMENT, $trid)) {
			$manageable_tours[$trid] = $desc;
		}
	}
	$manageable_tours = array_reverse($manageable_tours, true);
	$firstTour = 0;
    ?>
    <div class='boxWide'>
        <h3 class='boxTitle2'><?php echo $lng->getTrn('tours', 'Conference');?></h3>
        <div class='boxBody'>
			<form method="POST">
				<select name="tour_id">
					<?php
					foreach ($manageable_tours as $trid => $desc) {
						if ($firstTour == 0) {
							$firstTour = $trid;
						}
						echo "<option value='$trid'" . ($trid==$tour_id ? 'SELECTED' : '') . " >$desc[tname]</option>\n";
					}
					?>
				</select>
				<input type="submit" value="OK" <?php echo (empty($manageable_tours)) ? 'DISABLED' : '';?>>
			</form>
        </div>
    </div>
    <?php
    return $firstTour;
}

/* Main function for displaying conference administration page */
public static function conferenceAdmin() {
    global $lng, $tours, $coach;
    title($lng->getTrn('name', 'Conference'));
    self::handleActions();

    $tour_id = 0;
    if (isset($_POST['tour_id'])) {
    	$tour_id = $_POST['tour_id'];
    }

    $firstTour = self::tournamentSelector($tour_id);

	// no tournament - they need to select something to see more.
	if ($tour_id == 0) {
    	$tour_id = $firstTour;
	}

	// double check this coach is allowed to administer this tournament
    if(!$coach->isNodeCommish(T_NODE_TOURNAMENT, $tour_id)) {
    	echo "<div class='boxWide'>";
    	HTMLOUT::helpBox($lng->getTrn('not-admin', 'Conference'));
    	echo "</div>";
    	return;
    }

	$tour = new Tour($tour_id);
	$addConfTitle = $lng->getTrn('addConf', 'Conference');
echo<<< EOQ
	<div class='boxWide'>
		<h3 class='boxTitle4'>$tour->name</h3>
		<br />
		<form name="add_conf" method="POST" action="handler.php?type=conference">
			<input name='action' type='hidden' value='add_conf' />
			<input name='tour_id' type='hidden' value='$tour_id' />
			<input name='conf_name' type='hidden' value='$tour_id' />
			<input id='conf_name' type="text" name="conf_name" size="30" maxlength="50">
			<input type="submit" value="$addConfTitle">
		</form>
	</div>
    <script language="javascript">
    	function removeTeam(conf_id, team_id) {
			document.forms["remove_team"].conf_id.value=conf_id;
			document.forms["remove_team"].team_id.value=team_id;
			document.forms["remove_team"].submit();
		}
    	function removeConf(conf_id) {
			document.forms["remove_conf"].conf_id.value=conf_id;
			document.forms["remove_conf"].submit();
		}
    </script>
	<form name="remove_team" method="POST" action="handler.php?type=conference">
		<input name='action' type='hidden' value='remove_team' />
		<input name='tour_id' type='hidden' value='$tour_id' />
		<input name='conf_id' id='conf_id' type='hidden' value='0' />
		<input name='team_id' id='team_id' type='hidden' value='0' />
	</form>
	<form name="remove_conf" method="POST" action="handler.php?type=conference">
		<input name='action' type='hidden' value='remove_conf' />
		<input name='tour_id' type='hidden' value='$tour_id' />
		<input name='conf_id' id='conf_id' type='hidden' value='0' />
	</form>
	<table class="boxTable">
EOQ;
	$confs = self::getConferencesForTour($tour->tour_id);
	$idx = 0;
	foreach($confs as $conf) {
		$conf->loadTeams();
		if ($idx % 2 == 0) echo "<tr valign='top'>";
echo<<< EOQ
		<td>
		<div class='boxCommon' style='margin-top: 0px; width=325px'>
			<h4 class='boxTitleConf'>$conf->name <a onclick="return removeConf($conf->conf_id);"><img src="images/remove.png" height="16" width="16" title="remove $conf->name" alt="remove $conf->name"/></a></h4>
			<div style='white-space:nowrap; margin: 0px; padding: 5px; padding-top: 0px; line-height:175%; border 0px;'>
EOQ;
		self::findTeam($tour->tour_id, $conf->conf_id);
		foreach($conf->teams as $team) {
			$link = "<a href='". urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false)."'>$team->name</a>";

echo<<< EOQ
				<br />$link <a onclick="return removeTeam($conf->conf_id,$team->team_id);"><img src="images/remove.png" height="12" width="12" title="remove $team->name from $conf->name" alt="remove $team->name from $conf->name"/></a>
EOQ;
		}
echo<<< EOQ
			</div>
		</div>
		</td>
EOQ;
		if ($idx % 2 == 1) echo "</tr>";
		$idx++;
	}
echo<<< EOQ
	</table>
EOQ;
}

/* Find Team Autocomplete Box */
public static function findTeam($tour_id, $conf_id)
{
    global $lng;
    ?>
    <script>
        $(document).ready(function(){
            var options, a;
            options = {
                minChars:2,
                serviceUrl:'handler.php?type=confcomplete&obj=<?php echo T_OBJ_TEAM;?>',
                onSelect: function(value, data){
                	document.forms["add_team<?php echo $conf_id;?>"].team_id.value=data;
                	document.forms["add_team<?php echo $conf_id;?>"].submit();
                },
            };
            a = $('#team<?php echo $conf_id;?>').autocomplete(options);
        });
    </script>
	<form name="add_team<?php echo $conf_id;?>" method="POST" action="handler.php?type=conference">
		<input name='action' type='hidden' value='add_team' />
		<input name='tour_id' type='hidden' value='<?php echo $tour_id;?>' />
		<input name='conf_id' type='hidden' value='<?php echo $conf_id;?>' />
		<input name='team_id' id='team_id' type='hidden' value='0' />
	</form>
	<?php echo $lng->getTrn('findTeam', 'Conference');?><input id='team<?php echo $conf_id;?>' type="text" name="team" size="30" maxlength="50">
	<?php
}


}
?>
