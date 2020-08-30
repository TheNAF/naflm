<?php
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

class Scheduler implements ModuleInterface
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
	global $coach;
	$IS_LOCAL_OR_GLOBAL_ADMIN = (isset($coach) && ($coach->ring == Coach::T_RING_GLOBAL_ADMIN || $coach->ring == Coach::T_RING_LOCAL_ADMIN));

    // Deny un-authorized users.
    if (!$IS_LOCAL_OR_GLOBAL_ADMIN)
        fatal("Sorry. Your access level does not allow you opening the requested page.");
		
	?>
	<div class="module_schedule">
	<?php 
	self::inlineCSS();
	
	$step = 1;
	if (isset($_GET['step'])) { $step = $_GET['step']; }
	if (isset($_POST['step'])) { $step = $_POST['step']; }
	
	switch ($step) {
			case '2':
				self::step2();
				break;		
			default:
				self::step1();
				break;
	}
	
	?>
	</div>
	<?php 
	return true;
}

public static function inlineCSS() {
?>
<style type="text/css">
 .module_schedule {
	margin: 30px 10px;
	padding: 10px;
 }
 
 .module_schedule .success {
	background-color: #CCFFCC;
 }
 
 .module_schedule .error {
	background-color: #FFCCCC;
 }
 
 .module_schedule input {
	padding:5px 10px;
 }
 
 
 .module_schedule .league,
 .module_schedule .division,
 .module_schedule .tour {
	
	margin: 0 0 20px;
 }
 
.module_schedule .teamPool,
.module_schedule .step2 #selectedTeams {
 	width: 250px;
	float: left;
	margin-right: 10px;
	cursor:pointer;
}
	
.module_schedule  .team,
.module_schedule  .slot {
	margin: 5px 0;
	padding: 10px;
 }
 
 .module_schedule .team {
	width: 200px;
	height: 32px;
	line-height:32px;
	background-color: #DDDDDD;
	border: 1px solid #BBBBBB;
 }
 
 .module_schedule .team:hover{
  background-color: #AAAAAA;
 }
 
.module_schedule #schedule .team:hover,
.module_schedule .step1 .team:hover {
	background-color: #DDDDDD;
 }
.module_schedule #schedule .team,
.module_schedule .step1 .teamPool,
.module_schedule .step1 .team {
	cursor: default;
 }
 
 .module_schedule .step2 .draw { 
 	height: 62px;
 }
.module_schedule .step2 .slot { 
 	width: 25px;
	height: 32px;
	font-size: 30px;
	font-weight: bold;
	float:left;
}

 .module_schedule .step2 .notselected {
	background-color: #FFCCCC;
	border: 1px solid #FF0000;
	cursor:pointer;
 }

  .module_schedule .step2 .notselected:hover {
	background-color: #FFAAAA;
}

  .module_schedule .step2 .selected {
	background-color: #CCFFCC;
	border: 1px solid #00FF00;
	cursor:pointer;
 }
   .module_schedule .step2 .selected:hover {
	background-color: #AAFFAA;
}
 
 .module_schedule .step2 .schedulebutton {
	background-color: #BBBBBB;
	border: 1px solid #444444;
	border-radius: 6px;
	-moz-border-radius: 6px;
	-webkit-border-radius: 6px;
	padding: 10px;
	width: 200px;
	height: 32px;
	margin: 5px;
	float:left;
	cursor:pointer;
	display:none;
	font-size: 14px;
	text-align: center;
 }
 
 .module_schedule .step2 .schedulebutton:hover{
	background-color: #888888;
}

 .module_schedule .step2 #draw {
	width: 300px;
	float:left;
	margin: 0 10px;

 }
 
 .module_schedule .step2 #matches {
	width: 600px;
	height:100%;
	float:right;
	margin: 0 10px;
 }
 
 .module_schedule .step2 #matches .teamname {
	display:none;
 }
 
  .module_schedule .step2 #drawsched #draw .draw .team {
	width: 200px;
	height:32px;
	float:right;
 }
 
 .module_schedule .step2 #drawsched .boxWide {
	clear:none;
 }
 
 .module_schedule .step2 #drawsched #schedule {
	float:right;
	margin-top:0;
 }
.module_schedule .step2 #drawsched #schedule {
	width: 785px;
 }
 .module_schedule .step2 #drawsched #schedule table.tours {
	width: 765px;
 }
 
 .module_schedule .clear {
	clear:both;
 }
 
 .module_schedule .droppable {
	border: 1px dashed #545454;

	border-radius: 4px;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
 }
 
  .module_schedule .droppableActive {
	background-color: #CCFFCC;
	border: 1px dashed #00FF00;

	border-radius: 4px;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
 }
 
 .module_schedule #roundselection {
	width: 230px;
	float:left;
	margin-top: 0px;
 }
 .module_schedule .roundPool {
  	width: 200px;
	height: 20px;
	padding: 2px;
	margin: 2px;
	float: left;
	line-height:20px;
	background-color: #DDDDDD;
	border: 1px solid #BBBBBB;
 }
 
 .module_schedule #hometeam {
	float: left;
 }
 .module_schedule #awayteam {
	float: right;
 }
 
 .module_schedule #schedule {
	text-align: center;
 }
 .module_schedule #schedule .vs {
	font-size: 32px;
	line-height: 62px;
 }
  .module_schedule #schedule .team {
	line-height: 16px;
  }
 .module_schedule #schedule #selectedRound {
	line-height: 16px;
	font-size: 16px;
	font-weight: bold;
  }
</style>
<?php 
}

/**
 * Step 1 of the scheduler.
 *
 * In step 1, an overview over leagues/divisions/tournaments and their teams is shown. 
 * The user can select a tournament
 */
public static function step1() {
	global $lng;
	
	?><div class="step1">
	<h1><?php echo $lng->getTrn('scheduler_info', __CLASS__);?></h1><?php 
	// Get all Leagues, div, tours 
	$query = "SELECT leagues.lid,leagues.name as leaguename,leagues.tie_teams,divisions.did,divisions.name AS divisionsname,tours.tour_id,tours.name as toursname
			FROM
				leagues,
				divisions,
				tours
			WHERE
				tours.f_did = divisions.did
			AND
				divisions.f_lid = leagues.lid
			AND 
				tours.locked = 0
			AND 
				tours.is_finished = 0
			ORDER BY 
				leagues.name,divisions.name,tours.name";

	$result = mysql_query($query) or die(mysql_error());
    if ($result && mysql_num_rows($result) > 0) {
	
		$current_lid = 0;
		$current_did = 0;
        while ($row = mysql_fetch_assoc($result)) {
			// If a new league id comes up, close the previous
			// league div and open a new one.
			
			if ($row['did'] != $current_did && $current_did != 0) {
				self::closeDiv();
				self::closeDiv();
			}
			
			if ($row['lid'] != $current_lid && $current_lid != 0) {
				self::closeDiv();
				self::closeDiv();
			}
			
			if ($row['lid'] != $current_lid) {
				self::openLeagueTag($row['lid'],$row['leaguename']);
				
				if ($row['tie_teams'] == 0) {
					self::showTeamPoolForLeague($row['lid'], false, 1);
				} 
				
				$current_lid = $row['lid'];	
			}
			
			if ($row['did'] != $current_did) {
				self::openDivisionTag($row['did'], $row['divisionsname']);
				$current_did = $row['did'];
				
				if ($row['tie_teams'] == 1) {
					self::showTeamPoolForDivision($row['did'], false, 1);
				} 
			}
			
			self::openToursTag($row['tour_id'], $row['toursname']);
			self::selectLink($row['tour_id'], $row['did'], $row['lid'], $row['tie_teams']);
			self::closeDiv();
			self::closeDiv();
        }
			
		self::closeDiv();
		self::closeDiv();
		
    }
	
	?></div><?php 
}

public static function openLeagueTag($id,$name) {
	?>
		<div class="league boxWide" id="lid_<?php echo $id; ?>">
			<div class="boxTitle1"><?php echo $name; ?></div>
		<div class="boxBody">
	<?php 
}

public static function openDivisionTag($id,$name) {
	?>
		<div class="division boxWide" id="did_<?php echo $id; ?>">
			<div class="boxTitle3"><?php echo $name; ?></div>
			<div class="boxBody">
	<?php 
}

public static function openToursTag($id,$name) {
	?>
		<div class="tour boxWide" id="tour_<?php echo $id; ?>">
			<div class="tour_content boxTitle2"><h3><?php echo $name; ?></h3></div>
			<div class="boxBody">
	<?php 
}

public static function selectLink($tourid, $divid, $leagueid, $tie_teams) {
	global $lng;
	
	?>
		<form method="post">
			<input type="hidden" name="trid" value="<?php echo $tourid ?>" />
			<input type="hidden" name="did" value="<?php echo $divid ?>" />
			<input type="hidden" name="lid" value="<?php echo $leagueid ?>" />
			<input type="hidden" name="tie_teams" value="<?php echo $tie_teams ?>" />
			
			<input type="hidden" name="step" value="2" />
			<input type="submit" name="select" value="<?php echo $lng->getTrn('select', __CLASS__);?>" />
		</form>
	
	<?php 
}

public static function closeDiv() {
	?>
		</div>
	<?php 
}

public static function showTeamPoolForLeague($id, $showScheduleLink = false, $step) {
	global $lng;
	
	?>
		<div class="boxWide">
			<div class="boxTitle4">
				<h3><?php echo $lng->getTrn('teampool', __CLASS__);?></h3>
			</div>
		<div class="boxBody">
		
		<?php if ($step == 1) { ?>
			<p><?php echo $lng->getTrn('teampool_desc_step1_league', __CLASS__);?></p>
			<div class="clear"></div>
		<?php } else if ($step==2) { ?>
			<p><?php echo $lng->getTrn('teampool_desc', __CLASS__);?></p>
			<div class="clear"></div>
		<?php } 
		
		?><div class="teamPool"><?php 
			$query = "SELECT team_id from teams where f_lid = $id and f_did = 0";
			
			$result = mysql_query($query) or die(mysql_error());
			if ($result && mysql_num_rows($result) > 0) {
				$seperator = 0;
				while ($row = mysql_fetch_assoc($result)) {
					self::showTeam($row['team_id'], $showScheduleLink);
					$seperator += 1;
					
					if ($seperator == 5) {
						$seperator = 0;
						?></div>
						<div class="teamPool"><?php 
					}
				}
			}
				?>
		</div>
		<div class="clear"></div>
		
		</div></div>
		
		<?php 
			if ($showScheduleLink) {
				self::showScheduleTypeSelection();
			}
		?>


	<?php 
}

public static function showTeamPoolForDivision($id, $showScheduleLink = false, $step) {
	global $lng;
	?>
		<div class="boxWide">
			<div class="boxTitle4">
				<h3><?php echo $lng->getTrn('teampool', __CLASS__);?></h3>
			</div>
		<div class="boxBody">
		
		<?php if ($step == 1) { ?>
			<p><?php echo $lng->getTrn('teampool_desc_step1_division', __CLASS__);?></p>
			<div class="clear"></div>
		<?php } else if ($step==2) { ?>
			<p><?php echo $lng->getTrn('teampool_desc', __CLASS__);?></p>
			<div class="clear"></div>
		<?php } 
		?><div class="teamPool"><?php 
			$query = "SELECT team_id FROM teams WHERE f_did = " . $id . " ORDER by name";
			
			$result = mysql_query($query) or die(mysql_error());
			if ($result && mysql_num_rows($result) > 0) {
				$seperator = 0;
				while ($row = mysql_fetch_assoc($result)) {
					self::showTeam($row['team_id'], $showScheduleLink);
					$seperator += 1;
					
					if ($seperator == 5) {
						$seperator = 0;
						?></div>
						<div class="teamPool"><?php 
					}
				}
			}
				?>
		</div>
		<div class="clear"></div>
		
		</div></div>
		<?php 
			if ($showScheduleLink) {
				self::showScheduleTypeSelection();
			}
		?>
	<?php 
}

/**
 * Shows a team box.
 * 
 * @param id the id of the team
 * @param selectable true/false if this the div box should be selectable or not. 
 *                               If true a selected teams will get a green background, 
 *								 unselected teams get red backgrounds. 
 *                               If false the team will get a gray background.
 */
public static function showTeam($id, $selectable = true, $dragable = false) {
	$team = new Team($id);
	$teamLogo = new ImageSubSys(IMGTYPE_TEAMLOGO, $team->team_id);
	
	$selectClass = "";
	if ($selectable) {
		$selectClass = "notselected onofftoggle";
	} else if ($dragable) {
		$selectClass = "draggable";
	}
	?>
	<div id="pool<?echo $id; ?>" class="team <?php echo $selectClass; ?>">
		<img border='0px' height='30' width='30' title='<?php echo $team->name;?>' alt='<?php echo $team->name;?>' src='<?php echo $teamLogo->getPath();?>'>
		<span class="teamname"><?php echo $team->name;?></span>
	</div>	
	<?php 
}

/**
 * A container that contains all scheduler links. By default they will be hidden and 
 * only if a valid team selection has been made, they will be shown.
 */
public static function showScheduleTypeSelection() {
	global $lng;
?>
	<div class="boxWide">
		<div class="boxTitle5"><?php echo $lng->getTrn('available_schedulers', __CLASS__);?></h2></div>
		<div class="boxBody">
			<div id="apaauto" class="schedulebutton">
				<?php echo $lng->getTrn('all_play_all_auto', __CLASS__);?>
			</div>
			<div id="apamanual" class="schedulebutton">
				<?php echo $lng->getTrn('all_play_all_manual', __CLASS__);?>
			</div>
			<div id="custom" class="schedulebutton">
				<?php echo $lng->getTrn('custom_schedule', __CLASS__);?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
<?php 
}

/**
 * Checks if a schedule is available for a number of teams (submitted via. POST).
 *
 * @return 1 if a predefined schedule is found, 0 otherwise.
 */
public static function apa_schedule_available() {
	if (!isset($_POST['numberOfTeams'])) {
		echo 0;
	}
	
	$filename = './modules/scheduler/templates/apa_' . intval($_POST['numberOfTeams']) . '.txt';
	if (file_exists($filename)) {
		echo 1;
	} else {
		echo 0;
	}
}

/**
 * Generates an automatic draw.
 *
 * The teams for the draw needs to be submitted via POST. The teams are organized
 * in an array. This array will be shuffled and assiged to the Slots A...n
 */
public static function apa_generate_draw() {
	global $lng;
	
	?><div id="draw" class="boxWide">
	<div class="boxTitle1"><?php echo $lng->getTrn('draw', __CLASS__);?></div><?php 
	$teams = json_decode($_POST['teams']);

	$draw = array();
	foreach($teams->teams as $team) {
		$draw[] = str_replace("pool","", $team);
	}
	
	shuffle($draw);
	?>
	<div class="boxBody">
	<?php 
	foreach($draw as $k => $v) {
		?><div class="draw">
		<?php 
		?><div class="slot">
			<?echo chr(65 + $k);
		?></div><?php 
		self::showTeam($v, false);
		?></div><?php 
		?><div class="clear"></div><?php 
	}
	
	?></div></div><?php 
	self::apa_generate_schedule($draw);
}

/**
 * Show manual draw.
 *
 */
public static function show_manual_draw() {
	global $lng;
	
	$teams = json_decode($_POST['teams']);
	$teamsClean = array();
	foreach($teams->teams as $team) {
		$teamsClean[] = str_replace("pool","", $team);
	}
	self::showTeamPoolForTeams($teamsClean);
	
	?><div id="draw" class="boxWide">
	<div class="boxTitle1"><?php echo $lng->getTrn('draw', __CLASS__);?></div><?php 
	?>
	<div class="boxBody">
	<?php 
	foreach($teamsClean as $k => $v) {
		?>
		<div class="draw slot<?php echo 65 + $k ?>">
			<div class="slot">
				<?echo chr(65 + $k);
			?></div><?php 
		?><div class="draw team droppable">Drag 'n' drop team here</div><?php 
		?></div><?php 
		?><div class="clear"></div><?php 
	}
	
	?>
	<div id="apamanualschedule" class="schedulebutton">Schedule games</div>
	</div>
	
	
	</div><?php 

}

/**
 * Show custom draw.
 *
 */
public static function show_custom_draw() {
	global $lng;
	
	$teams = json_decode($_POST['teams']);
	$teamsClean = array();
	foreach($teams->teams as $team) {
		$teamsClean[] = str_replace("pool","", $team);
	}
	self::showTeamPoolForTeams($teamsClean);
	
	?>
	<div id="roundselection" class="boxWide">
		<div class="boxTitle1"><?php echo $lng->getTrn('roundselection', __CLASS__);?></div>
		<div class="boxBody">
	<?php 
	foreach (array(RT_FINAL => 'Final', RT_3RD_PLAYOFF => '3rd play-off', RT_SEMI => 'Semi final', RT_QUARTER => 'Quarter final', RT_ROUND16 => 'Round of 16 match') as $r => $d) {
		?><div id="round_<?php echo $r; ?>" class="roundPool notselected onoffsingletoggle"><?php 
		echo $d;
		?></div><?php 
    }
	
	$pure_rounds = array(); 
	
	for ($i=1;$i<30;$i++) $pure_rounds[$i] = "Round #$i match";
	foreach ($pure_rounds as $r => $d) {
		?><div id="round_<?php echo $r; ?>" class="roundPool notselected onoffsingletoggle"><?php 
		echo $d;
		?></div><?php 
	}

	?><div class="clear"></div></div></div><?php 
	
		?>
	<div id="schedule" class="boxWide">
		<div class="boxTitle1"><?php echo $lng->getTrn('schedule', __CLASS__);?></div>
		<div class="boxBody">
			<div id="selectedRound"></div>
			<div id="hometeam">
				<div class="team hometeam">Click a team from the teampool as HOME team</div>
			</div>
			<span class="vs"><strong>vs.</strong></span>
			<div id="awayteam">
				<div class="team awayteam">Click another team from the teampool as AWAY team</div>
			</div>
	<?php 
	?><div class="clear"></div></div><?php 
	
	?><div id="log" class="boxWide">
		<div class="boxTitle1"><?php echo $lng->getTrn('log', __CLASS__);?></div>
		<div class="boxBody">
			
	<?php 
	?><div class="clear"></div></div><?php 

}

public static function showTeamPoolForTeams($teams) {
	global $lng;
	?>
		<div class="boxWide">
			<div class="boxTitle4">
				<h3><?php echo $lng->getTrn('teampool', __CLASS__);?></h3>
			</div>
		<div class="boxBody">
		<div class="teamPool"><?php 
			if(is_array($teams)) {
				$seperator = 0;
				foreach($teams as $teamid) {
					self::showTeam($teamid, false, true);
					
					$seperator += 1;
					
					if ($seperator == 4) {
						$seperator = 0;
						?></div>
						<div class="teamPool"><?php 
					}
				}
			}
				?>
		</div>
		<div class="clear"></div>
		
		</div></div>
	<?php 
}

/**
 * Generates a schedule according to the draw.
 * 
 */
public static function apa_generate_schedule($draw) {
	global $lng;
	
	?>
	<div id="schedule" class="boxWide">
		<div class="boxTitle1"><?php echo $lng->getTrn('schedule', __CLASS__);?></div>
	<?php 

	$filename = './modules/scheduler/templates/apa_' . count($draw) . '.txt';

	$row = 1;
	$schedule = array();
	if (($handle = fopen($filename, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, " ")) !== FALSE) {
			$row++;

			$round = $data[0];
			$game = $data[1];
			$home = $data[2]{0};
			$away = $data[2]{2};

			$schedule[$round][$game][0] = $draw[ord($home) - 65];
			$schedule[$round][$game][1] = $draw[ord($away) - 65];
		}
		fclose($handle);
	}	

	$cols = '4';
	?>
	<div class="boxBody">
	<?php 
	echo "<table class='tours'>\n";
	foreach($schedule as $rnd => $value) {
        $round = '';
		$rnd = intval($rnd);
		if     ($rnd == RT_FINAL)         $round = $lng->getTrn('matches/tourmatches/roundtypes/final');
		elseif ($rnd == RT_3RD_PLAYOFF)   $round = $lng->getTrn('matches/tourmatches/roundtypes/thirdPlayoff');
		elseif ($rnd == RT_SEMI)          $round = $lng->getTrn('matches/tourmatches/roundtypes/semi');
		elseif ($rnd == RT_QUARTER)       $round = $lng->getTrn('matches/tourmatches/roundtypes/quarter');
		elseif ($rnd == RT_ROUND16)       $round = $lng->getTrn('matches/tourmatches/roundtypes/rnd16');
		else                              $round = $lng->getTrn('matches/tourmatches/roundtypes/rnd').": $rnd";
		
		if ($rnd > 1) {
            echo "<tr><td colspan='$cols' class='seperator'></td></tr>";
		}
            echo "<tr><td colspan='$cols' class='round'><center><b>$round</b></center></td></tr>";
            echo "<tr><td colspan='$cols' class='seperator'></td></tr>";
		

		foreach($value as $game => $teams) {
			$t1 = new Team($teams[0]);
			$t2 = new Team($teams[1]);
			
			?>
			<tr>
			<td class="match" style="text-align: right;"><?php echo $t1->name;?></td>
            <td class="match" style="text-align: center;">-</td>
            <td class="match" style="text-align: left;"><?php echo $t2->name;?></td>

			<?php 
				list($exitStatus, $mid) = Match::create(
					array(
						'team1_id' => $t1->team_id, 
						'team2_id' => $t2->team_id, 
						'round' => $rnd, 
						'f_tour_id' => (int) $_POST['f_tour_id']
					)
				);
			?>
				<?php 
					if ($exitStatus == '') {
						echo "<td class=\"success\">" . $lng->getTrn('schedule_successfull', __CLASS__). "</td>";
					} else {
						echo "<td class=\"error\">" . Match::$T_CREATE_ERROR_MSGS[$exitStatus] . "</td>";
					}
				?>
			</tr>
			<?php 
		}
	}
	?></table>
	</div></div><?php 
}

public static function schedule_custom_game() {
	global $lng;
	
	$teams = json_decode($_POST['teams']);
	$teamsClean = array();
	foreach($teams->teams as $team) {
		$teamsClean[] = str_replace("pool","", $team);
	}
	
	$t1 = new Team($teamsClean[0]);
	$t2 = new Team($teamsClean[1]);
	$rnd = (int) str_replace('round_', '', $_POST['round']);
	
	list($exitStatus, $mid) = Match::create(
		array(
			'team1_id' => $t1->team_id, 
			'team2_id' => $t2->team_id, 
			'round' => $rnd, 
			'f_tour_id' => (int) $_POST['f_tour_id']
		)
	);
	
	if     ($rnd == RT_FINAL)         $round = $lng->getTrn('matches/tourmatches/roundtypes/final');
	elseif ($rnd == RT_3RD_PLAYOFF)   $round = $lng->getTrn('matches/tourmatches/roundtypes/thirdPlayoff');
	elseif ($rnd == RT_SEMI)          $round = $lng->getTrn('matches/tourmatches/roundtypes/semi');
	elseif ($rnd == RT_QUARTER)       $round = $lng->getTrn('matches/tourmatches/roundtypes/quarter');
	elseif ($rnd == RT_ROUND16)       $round = $lng->getTrn('matches/tourmatches/roundtypes/rnd16');
	else                              $round = $lng->getTrn('matches/tourmatches/roundtypes/rnd').": $rnd";
		
	
	if ($exitStatus == '') {
		$status =  $lng->getTrn('schedule_successfull', __CLASS__);
		$htmlclass = 'success';
	} else {
		$status = Match::$T_CREATE_ERROR_MSGS[$exitStatus];
		$htmlclass = 'error';
	}
	
	?>
	<div class="<?php echo $htmlclass; ?>">
		<?php echo $round ?> - <?php echo $t1->name; ?> vs. <?php echo $t2->name; ?> - <?php echo $status; ?>
	</div>
	<?php 
	
	
}

public static function step2() {
	global $lng;
	
?><div class="step2">
<script type="text/javascript">
// the direct source of the delay function in 1.4+
$.fn.extend({
    delay: function( time, type ) {
        time = $.fx ? $.fx.speeds[time] || time : time;
        type = type || "fx";

        return this.queue( type, function() {
            var elem = this;
            setTimeout(function() {
                $.dequeue( elem, type );
            }, time );
        });
    }
});


	function getNumberOfSelectedTeams() {
		return $('.selected').length;
	}
	
	function automaticAllPlayAllIsPossible() {
		$.ajax({
			url: "handler.php?type=scheduler",
			dataType: "json",
			data: {
				subtype : 'apa_schedule_available',
				numberOfTeams : getNumberOfSelectedTeams()
			},
			type: 'post',
			success: function(output) {
				if (output == 1) {
					$('#apaauto').fadeIn();
					$('#apamanual').fadeIn();
				} else {
					$('#apaauto').fadeOut();
					$('#apamanual').fadeOut();
				}
            },
		});
		
		if (getNumberOfSelectedTeams() >= 2) {
			$('#custom').fadeIn();
		} else {
			$('#custom').fadeOut();
		}
	}
	
	function getAutomatedDraw() {
		var teams = [];
		$('.selected').each(function(index) { 
			teams.push(this.id);
		});
		
		$.ajax({
			url: "handler.php?type=scheduler",
			data: {
				subtype : 'apa_generate_draw',
				teams : JSON.stringify({teams: teams}),
				f_tour_id : <?php echo $_POST['trid']; ?>
			},
			type: 'post',
			success: function(output) {	
				$("#teamPool").fadeOut(500);
				
				$('#drawsched').html(output);
				$('#drawsched').delay(500).fadeIn(500);
            },
		});
	}
	
	function getManualDraw() {
		var teams = [];
		
		$('.selected').each(function(index) { 
			teams.push(this.id);
		});

		// show available teams
		$.ajax({
			url: "handler.php?type=scheduler",
			data: {
				subtype : 'manual_draw',
				teams : JSON.stringify({teams: teams}),
				f_tour_id : <?php echo $_POST['trid']; ?>
			},
			type: 'post',
			success: function(output) {	
				$("#teamPool").fadeOut(500);
				$("#teamPool").remove();
				
				$('#drawsched').html(output);
				$('#drawsched').delay(500).fadeIn(500);
				registerDraggableEvents();
            },
		});
	}

	function getManualSchedule() {
		var teams = [];
		
		$('.draw > .team').each(function(index, value) { 
			teams.push(this.id); 
		});
		
		$.ajax({
			url: "handler.php?type=scheduler",
			data: {
				subtype : 'manual_schedule',
				teams : JSON.stringify({teams: teams}),
				f_tour_id : <?php echo $_POST['trid']; ?>
			},
			type: 'post',
			success: function(output) {					
				$('#drawsched').append(output);
            },
		});
		
	}

	function getCustomSchedule() {
		var teams = [];
		
		$('.selected').each(function(index) { 
			teams.push(this.id);
		});

		// show available teams
		$.ajax({
			url: "handler.php?type=scheduler",
			data: {
				subtype : 'custom_draw',
				teams : JSON.stringify({teams: teams}),
				f_tour_id : <?php echo $_POST['trid']; ?>
			},
			type: 'post',
			success: function(output) {	
				$("#teamPool").fadeOut(500);
				$("#teamPool").remove();
				
				$('#drawsched').html(output);
				$('#drawsched').delay(500).fadeIn(500);
				$('#selectedRound').html('Select a round first');
				 
				$('.onoffsingletoggle').each(function(index) {
					$(this).click(function() {
						$('.roundPool').removeClass('selected');
						$('.roundPool').addClass('notselected');
						
						$(this).removeClass('notselected');
						$(this).addClass('selected');
						$('#selectedRound').html($(this).html());
						
						scheduleCustomDrawGame();
					});
				});
				
				$('.teamPool > .team').each(function(index) {
					$(this).click(function() {
						// get current home team name
						$hometeamname = $('#hometeam > .team > .teamname').html();
						if ($hometeamname == null) {
							$('#hometeam').html($(this).attr('outerHTML'));
							scheduleCustomDrawGame();
							return;
						} 
						$awayteamname = $('#awayteam > .team > .teamname').html();
						if ($awayteamname == null) {
							$('#awayteam').html($(this).attr('outerHTML'));
							scheduleCustomDrawGame();
							return;
						}
					});
				});
			}
		});
	}
	
	function scheduleCustomDrawGame() {
		$round = $('.selected').attr('id');
		$home = $('#schedule #hometeam > .team').attr('id');
		$away = $('#schedule #awayteam > .team').attr('id');
		
		if ($round === undefined || $home == '' || $away == '') {
			return;
		}
		
		var teams = [];
		teams.push($home); 
		teams.push($away); 
		
		$.ajax({
			url: "handler.php?type=scheduler",
			data: {
				subtype : 'custom_game',
				teams : JSON.stringify({teams: teams}),
				round : $round,
				f_tour_id : <?php echo $_POST['trid']; ?>
			},
			type: 'post',
			success: function(output) {					
				$('#log .boxBody').prepend(output);
				$('#hometeam').html('<div class="team hometeam">Click a team from the teampool as HOME team</div>');
				$('#awayteam').html('<div class="team awayteam">Click another team from the teampool as AWAY team</div>');
            },
		});
	}
	
	function registerDraggableEvents() {
		$('.draggable').draggable( {
			snap : '.droppable',
			stop : handleDragStopEvent,
			revert: function (event, ui) {
				$(this).data("draggable").originalPosition = {
					top: 0,
					left: 0
				};
            
				return !event;
			}
		});
		$('.droppable').droppable( {
			drop: handleDropEvent,
			activeClass : 'droppableActive'
		});
	}
	
	function handleDragStopEvent( event, ui ) {
		$('.droppable').removeClass('droppableActive');
	}
	
	function handleDropEvent( event, ui ) {
		ui.draggable.position( { of: $(this), my: 'left top', at: 'left top' } );
		
		$(this).droppable( 'disable' );
		$(this).replaceWith(ui.draggable);
		
		$('.droppable').removeClass('droppableActive');
		ui.draggable.css( { "top": "0px", "left": "0px", "cursor" : "pointer" } );
		
		// check if all slots are taken.
		if ($('.teamPool > .team').length == 0) {
			$('.teamPool').parent().parent().fadeOut();
			
			$('#apamanualschedule').fadeIn();
			$('#apamanualschedule').click(function() {
				getManualSchedule();
			});
		}
		
		ui.draggable( 'disable' ); // Must be last! (otherwise above commands don't work - raises some strange error)
	}
	
	$(document).ready(function() {
		$('.onofftoggle').each(function(index) {
			$(this).click(function() {
				if ($(this).hasClass("notselected")) {
					$(this).removeClass("notselected");
					$(this).addClass("selected");
					
				} else if ($(this).hasClass("selected")) {
					$(this).removeClass("selected");
					$(this).addClass("notselected");
				}
				
				automaticAllPlayAllIsPossible();
			});
		});
		
		$('#apaauto').click(function() {
			getAutomatedDraw();
		});
		$('#apamanual').click(function() {
			getManualDraw();
		});
		$('#custom').click(function() {
			getCustomSchedule();
		});
	});
</script>
<?php $t = (isset($_POST['trid'])) ? new Tour($_POST['trid']) : null; ?>

<h1><?php echo $lng->getTrn('create_schedule', __CLASS__);?> <i><?php echo $t->name; ?></i></h1>
<div id="teamPool">
<?php 
	if ($_POST['tie_teams'] == 1) {
		self::showTeamPoolForDivision($_POST['did'], true, 2);
	} else if ($_POST['tie_teams'] == 0) {
		self::showTeamPoolForLeague($_POST['lid'], true, 2);
	} 
	?>
</div>	
	<div id="drawsched" style="display:none;">
	</div>
	
	<?php 
}

/*
 *  This function returns information about the module and its author.
 */
public static function getModuleAttributes()
{
    return array(
        'author'     => 'Juergen Unfried',
        'moduleName' => 'Scheduler',
        'date'       => '2012', # For example '2009'.
        'setCanvas'  => true, # If true, whenever your main() is run through Module::run() your code's output will be "sandwiched" into the standard HTML frame.
    );
}

/*
 *  This function returns the MySQL table definitions for the tables required by the module. If no tables are used array() should be returned.
 */
public static function getModuleTables()
{
    return array(
        # Table name => column definitions
        'MyTable_1' => array(
            # Column name => column definition
            'col1' => 'MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
            'col1' => 'MEDIUMINT UNSIGNED',
            'col1' => 'MEDIUMINT UNSIGNED',
        ),
        'MyTable_2' => array(
            'col1' => 'MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
            'col1' => 'MEDIUMINT UNSIGNED',
            'col1' => 'MEDIUMINT UNSIGNED',
        ),
    );
}    

public static function getModuleUpgradeSQL()
{
    return array();
}

public static function triggerHandler($type, $argv){

    // Do stuff on trigger events.
    // $type may be any one of the T_TRIGGER_* types.
}

/***************
 * OPTIONAL subdivision of module code into class methods.
 * 
 * These work as in ordinary classes with the exception that you really should (but are strictly required to) only interact with the class through static methods.
 ***************/

private $attribute = 'Default value';

public function __construct($arg1)
{
    $this->attribute = $arg1;
}

public function myMethod()
{
    return $this->attribute;
}

public static function myStaticMethod($arg)
{
    $obj = new self('New value');
    echo $obj->myMethod();
}

}
?>
