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
	This module is to provide a league table for tournaments that can be accessed away from the home page.
	Added because the home page does not show historical tournaments, and this allows better display of more information
	than comfortably fits within the column.

	TODO - allow sorting by other fields.
*/

class LeagueTables implements ModuleInterface
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
        'moduleName' => 'LeagueTables',
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
    );
}

public static function getModuleUpgradeSQL()
{
    return array();
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

function __construct($conf_id)
{
}

/* This function is the primary one to display a league table */
public static function showTables() {
    global $lng, $tours;
    title($lng->getTrn('name', 'LeagueTables'));

	// Selector for the tournament
    $tour_id = 0;
    if (isset($_POST['tour_id'])) {
    	$tour_id = $_POST['tour_id'];
    } else if (isset($_GET['tour_id'])) {
    	$tour_id = $_GET['tour_id'];
    }
	$firstTour = 0;
    ?>
    <div class='boxWide'>
        <h3 class='boxTitle2'><?php echo $lng->getTrn('tours', 'LeagueTables');?></h3>
        <div class='boxBody'>
			<form method="POST">
				<select name="tour_id">
					<?php
					$rTours = array_reverse($tours, true);
					foreach ($rTours as $trid => $desc) {
						if ($firstTour == 0) {
							$firstTour = $trid;
						}
						echo "<option value='$trid'" . ($trid==$tour_id ? 'SELECTED' : '') . " >$desc[tname]</option>\n";
					}
                    /*Replace the prior foreach with this once lid is known.
                    foreach ($rTours as $trif => $desc) {
                      if($firstTour == 0 && $lid == $_SESSION["NS_node_id"]) {
                        $firstTour = $trid;
                      }
                      if($lid == $_SESSION["NS_node_id"]) {
                        echo "<option value='$trid'" . ($trid==$tour_id ? 'SELECTED' : '') . " >$desc[tname]</option>\n";
                      }
                    }
                    */
					?>
				</select>
				<input type="submit" value="OK">
			</form>
        </div>
    </div>
    <?php
    if ($tour_id == 0) {
    	$tour_id = $firstTour;
	}

	// create the tournament and get the sorting rules
	$tour = new Tour($tour_id);
	$SR = array_map(create_function('$val', 'return $val[0]."mv_".substr($val,1);'), $tour->getRSSortRule());

	// load all the teams according to the sorting rule
    list($teams, ) = Stats::getRaw(T_OBJ_TEAM, array(T_NODE_TOURNAMENT => $tour_id), array(1,1000), $SR, false);

    // Dump all the raw info for the first team as debug so I can work out the fields

/*
	echo "<!--\n";
	foreach (array_keys($teams[0]) as $field) {
		echo $field. "=" . $teams[0][$field] . "\n";
	}
	echo "-->\n";
	*/
	// Hard coded list of fields to show - matching existing SLOBB/ECBBL league tables
	$fields = array(
		$lng->getTrn('table-coach', 'LeagueTables') => 'f_cname',
		$lng->getTrn('table-name', 'LeagueTables') => 'name',
		$lng->getTrn('table-race', 'LeagueTables') => 'f_rname',
		$lng->getTrn('table-tv', 'LeagueTables') => 'tv',
		$lng->getTrn('table-played', 'LeagueTables') => 'mv_played',
		$lng->getTrn('table-won', 'LeagueTables') => 'mv_won',
		$lng->getTrn('table-draw', 'LeagueTables') => 'mv_draw',
		$lng->getTrn('table-loss', 'LeagueTables')=> 'mv_lost',
		$lng->getTrn('table-td', 'LeagueTables') => 'mv_sdiff',
		$lng->getTrn('table-cas', 'LeagueTables') => 'mv_tcdiff',
		$lng->getTrn('table-points', 'LeagueTables') => 'mv_pts',
		);

	$unplayedTeams = self::getUnplayedTeamsForTournament($tour_id);
	$confs = 0;
	if (Module::isRegistered('Conference'))    {
		$confs = Conference::getConferencesForTour($tour_id);
	}
	// Now the clean output.
?>
	<div class='boxWide'>
		<h3 class='boxTitle<?php echo T_HTMLBOX_STATS;?>'><?php echo $tour->name;?></h3>
<?php
	if ($confs == 0 || empty($confs)) {
		// no conferences at all, or not for this league - normal format
echo<<< EOQ
		<div class='boxBody'>
			<table class="boxTable">
EOQ;
				$i = 0;
				self::showHeader($fields);
				self::showPlayedTeams($teams, $fields, $i);
				self::showUnplayedTeams($unplayedTeams, $i);
echo<<< EOQ
			</table>
		</div>
EOQ;
	} else {
		// conferences - so show them one at a time
		$allConfIds = array();
		foreach($confs as $conf) {
			$allConfIds = array_merge($allConfIds, $conf->teamIds);
echo<<< EOQ
		<div class='boxWide'>
			<h4 class='boxTitleConf'>$conf->name</h4>
				<table class="boxTable">
EOQ;
					$i = 0;
					self::showHeader($fields);
					self::showPlayedTeams($teams, $fields, $i, $conf->teamIds);
					self::showMissingConferenceTeams($teams, $i, $conf);
echo<<< EOQ
				</table>
		</div>
EOQ;
		}
		// now check to see if we have teams that aren't in a conference and show them
		$allConfIds = array_unique($allConfIds);
		if (sizeof($allConfIds) < (sizeof($teams) + sizeof($unplayedTeams))) {
			$title = $lng->getTrn('no-conf', 'LeagueTables');
echo<<< EOQ
		<div class='boxWide'>
			<h4 class='boxTitleConf'>$title</h4>
			<table class="boxTable">
EOQ;
			$noConfTeam = array();
			$noConfUPTeam = array();
			foreach ($teams as $t) {
				if (!in_array($t['team_id'], $allConfIds)) {
					array_push($noConfTeam, $t);
				}
			}
			foreach ($unplayedTeams as $t) {
				if (!in_array($t->team_id, $allConfIds)) {
					array_push($noConfUPTeam, $t);
				}
			}
			$i = 0;
			self::showHeader($fields);
			self::showPlayedTeams($noConfTeam, $fields, $i);
			self::showUnplayedTeams($noConfUPTeam, $i);
echo<<< EOQ
			</table>
		</div>
EOQ;
		}
	}
	echo "</div>";

}

/* The header row for each table */
public static function showHeader($fields) {
    global $lng;
	echo "<tr>\n<th align=\"left\">" . $lng->getTrn('table-pos', 'LeagueTables') . "</th>";
	foreach (array_keys($fields) as $field) {
		echo "<th align=\"left\">" . $field . "</th>";
	}
	echo "</tr>\n";
}

/* Teams that have played one or more games */
public static function showPlayedTeams($teams, $fields, &$i, $allowedTeams = 0) {
	foreach ($teams as $t) {
		if ($allowedTeams == 0 || in_array($t['team_id'], $allowedTeams)) {
			echo "<tr>\n";
			echo "<td>" . ++$i . "</td>";
			foreach ($fields as $field) {
				$value = $t[$field];
				if ($field == 'tv') {
					$value = ($value / 1000) . "k";
				} else if ($field == 'name') {
					$value = "<a href='". urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$t['team_id'],false,false)."'>$t[$field]</a>";
				} else if ($field == 'f_cname') {
					$value = "<a href='". urlcompile(T_URL_PROFILE,T_OBJ_COACH,$t['owned_by_coach_id'],false,false) ."'>$t[$field]</a>";
				} else if ($field == 'f_rname') {
					$value = "<a href='". urlcompile(T_URL_PROFILE,T_OBJ_RACE,$t['f_race_id'],false,false) ."'>$t[$field]</a>";
				}
				echo "<td>" . $value  . "</td>";
			}
			echo "</tr>\n";
		}
	}


}

/* Teams that haven't played any games */
public static function showMissingConferenceTeams($teams, &$i, $conf) {
	$conf->loadTeams();
	foreach($conf->teams as $ct) {
		$matched = false;
		foreach($teams as $t) {
			if ($t['team_id']==$ct->team_id) {
				$matched = true;
				break;
			}
		}
		if (!$matched) {
			self::showTeam($ct, $i);
		}
	}
}


/* Teams that haven't played any games */
public static function showUnplayedTeams($unplayedTeams, &$i, $allowedTeams = 0) {
	// put any team that has not played a game at the bottom.
	foreach ($unplayedTeams as $t) {
		if ($allowedTeams == 0 || in_array($t->team_id, $allowedTeams)) {
			self::showTeam($t,$i);
		}
	}
}

public static function showTeam($t, & $i) {
	echo "<tr>\n";
	echo "<td>" . ++$i . "</td>";
	echo "<td><a href='". urlcompile(T_URL_PROFILE,T_OBJ_COACH,$t->owned_by_coach_id,false,false) ."'>" .$t->f_cname ."</a></td>";
	echo "<td><a href='". urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$t->team_id,false,false) ."'>" .$t->name ."</a></td>";
	echo "<td><a href='". urlcompile(T_URL_PROFILE,T_OBJ_RACE,$t->f_race_id,false,false) ."'>" .$t->f_rname ."</a></td>";
	echo "<td>" . ($t->tv / 1000) . "k</td>";
	echo "<td>0</td>";
	echo "<td>0</td>";
	echo "<td>0</td>";
	echo "<td>0</td>";
	echo "<td>0</td>";
	echo "<td>0</td>";
	echo "<td>0</td>";
	echo "</tr>\n";
}

/* This function finds the teams for a tournament that have not yet played a game */
public static function getUnplayedTeamsForTournament($tour_id) {
	$teams = array();
	$query = "SELECT team1_id FROM matches WHERE date_played IS NULL AND f_tour_id=$tour_id AND team1_id NOT IN (SELECT f_tid FROM mv_teams WHERE f_trid=$tour_id) UNION SELECT team2_id FROM matches WHERE date_played IS NULL AND f_tour_id=$tour_id AND team2_id NOT IN (SELECT f_tid FROM mv_teams WHERE f_trid=$tour_id)";
	$result = mysql_query($query);
	while($row = mysql_fetch_assoc($result)) {
		array_push($teams, new Team($row['team1_id']));
	}
	return $teams;

}
}

?>
