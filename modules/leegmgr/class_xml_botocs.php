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

class XML_BOTOCS implements ModuleInterface
{
    /***************
     * Properties 
     ***************/

    public $noninjplayercount = 0;
    public $rosterplayercount = 0;
    public $exist_journeyman = false;
    public $jm = 0;
    //team
    public $roster = '';
    public $team_id = 0;
    public $games = 0;
    public $name = '';
    public $race = '';
    public $coach_name = '';
    public $rerolls = 0;
    public $fan_factor = 0;
    public $ass_coaches = 0;
    public $cheerleaders = 0;
    public $apothecary = "false";
    public $treasury = 0;
    //players
    public $players;

    public $tv = 0;
    public $cyroster = '';
    public $obblm_team = array();
    public $cy = 0;
    public $cy_teamname = '';

    public $error = '';

    /***************
     * Methods 
     ***************/

    function __construct($team_id, $jm) {

        $this->team_id = $team_id;
        $this->jm = $jm;

        $team = new Team ( $_GET["teamid"] );
            $this->games = $team->mv_won + $team->mv_lost + $team->mv_draw;
            $this->players = $team->getPlayers();
            $this->name = $team->name;
            $this->race = $team->f_rname;
            $this->race_id = $team->f_race_id;
            $this->coach_name = $team->f_cname;
            $this->rerolls = $team->rerolls;
            $this->fan_factor = $team->rg_ff;
            $this->ass_coaches = $team->ass_coaches;
            $this->cheerleaders = $team->cheerleaders;
            $this->apothecary = $team->apothecary;
                $this->apothecary = ( $this->apothecary == "1" ) ? "true" : "false";
            $this->treasury = $team->treasury;
            $this->tv = $team->value; #for cyanide roster only
            
        if ( !$this->checkJourneymen() ) return false;

        $this->name = $team->name;
        $this->coach_name = $team->f_cname;
        $this->createRoster();

    }

    function createRoster() {

        $this->roster .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $this->roster .= "<?xml-stylesheet type=\"text/xsl\" href=\"/modules/leegmgr/team.xsl\"?>\n";
        $this->roster .= "<team>\n";
        $this->roster .= "    <name>".htmlspecialchars($this->name, ENT_NOQUOTES, 'UTF-8')."</name>\n";
        $this->roster .= "    <race>".htmlspecialchars($this->race, ENT_NOQUOTES, 'UTF-8')."</race>\n";
        $this->roster .= "    <coach>".htmlspecialchars($this->coach_name, ENT_NOQUOTES, 'UTF-8')."</coach>\n";
        $this->roster .= "    <rerolls>".$this->rerolls."</rerolls>\n";
        $this->roster .= "    <fanfactor>".$this->fan_factor."</fanfactor>\n";
        $this->roster .= "    <assistants>".$this->ass_coaches."</assistants>\n";
        $this->roster .= "    <cheerleaders>".$this->cheerleaders."</cheerleaders>\n";
        $this->roster .= "    <apothecary>".$this->apothecary."</apothecary>\n";
        $this->roster .= "    <treasury>".$this->treasury."</treasury>\n";
        $this->roster .= "    <players>\n";

        foreach ( $this->players as $p )
        {

            if ( !$p->is_dead && !$p->is_sold )
            {
                $skills = $p->getSkillsStr();
                $a_skills = explode(', ', $skills);
                $this->roster .= "        <player number=\"".$p->nr."\">\n";
                $this->roster .= "            <name>".htmlspecialchars($p->name, ENT_NOQUOTES, 'UTF-8')."</name>\n";
                $this->roster .= "            <position>".htmlspecialchars($p->pos, ENT_NOQUOTES, 'UTF-8')."</position>\n";
                $this->roster .= "            <ma>".$p->ma."</ma>\n";
                $this->roster .= "            <st>".$p->st."</st>\n";
                $this->roster .= "            <ag>".$p->ag."</ag>\n";
                $this->roster .= "            <av>".$p->av."</av>\n";
                $this->roster .= "            <skills>\n";

                //For Cyanide Roster BEGIN
                $this->obblm_team['players'][$p->nr]['id']			= $p->nr;
                $this->obblm_team['players'][$p->nr]['name'] 		= $p->name;
                $this->obblm_team['players'][$p->nr]['type'] 		= $p->pos ;
                $this->obblm_team['players'][$p->nr]['skin'] 		= 0;//@FIXME need to map race skin number options
                $this->obblm_team['players'][$p->nr]['age']  		= '100.';
                $this->obblm_team['players'][$p->nr]['Number']		= $p->nr; //int 1-32 only
                $this->obblm_team['players'][$p->nr]['MA']			= $p->ma;
                $this->obblm_team['players'][$p->nr]['ST']			= $p->st;
                $this->obblm_team['players'][$p->nr]['AG']			= $p->ag;
                $this->obblm_team['players'][$p->nr]['AV']			= $p->av;
                $this->obblm_team['players'][$p->nr]['Level']		= FALSE;
                $this->obblm_team['players'][$p->nr]['SPP']		      = $p->mv_spp;
                $this->obblm_team['players'][$p->nr]['COST']		= 0;
                $this->obblm_team['players'][$p->nr]['VALUE']		= $p->value / 1000;


                $chrs = array();
                $extras = empty($p->extra_skills) ? array() : skillsTrans($p->extra_skills);

                if ($p->ach_ma > 0) array_push($chrs, "+1 Ma");
                if ($p->ach_ma > 1) array_push($chrs, "+1 Ma");
                if ($p->ach_st > 0) array_push($chrs, "+1 St");
                if ($p->ach_st > 1) array_push($chrs, "+1 St");
                if ($p->ach_ag > 0) array_push($chrs, "+1 Ag");
                if ($p->ach_ag > 1) array_push($chrs, "+1 Ag");
                if ($p->ach_av > 0) array_push($chrs, "+1 Av");
                if ($p->ach_av > 1) array_push($chrs, "+1 Av");

                $skillstr = skillsTrans(array_merge($p->ach_nor_skills, $p->ach_dob_skills));
                if ($p->f_pos_id == 201) {
                        array_push($extras, 'Sprint');
                }
                if ($p->is_journeyman) {
                        array_push($extras, 'Loner');
                }
                $cy_skills = array_merge(empty($skillstr) ? array() : $skillstr, $extras, $chrs);

                $i = 0;

                $this->obblm_team['players'][$p->nr]['Skills'][0] = false;

                    while ( $i < count( $cy_skills ) && strlen( $cy_skills[0] ) > 0 )
                    {

                        $this->obblm_team['players'][$p->nr]['Skills'][$i]	= $cy_skills[$i];

                        $i++;

                    }

                $this->obblm_team['players'][$p->nr]['Casualty'][0] = ( $p->is_mng ) ? "Pinched Nerve" : false;
                $i = ( $this->obblm_team['players'][$p->nr]['Casualty'][0] ) ? 1 : 0;
                while ( $i < $p->inj_ni )
                {
                    $this->obblm_team['players'][$p->nr]['Casualty'][$i] = "Damaged Back";
                    $i++;
                }
                if ( $p->is_mng ) unset($this->obblm_team['players'][$p->nr]);
                //For Cyanide Roster END

                $i = 0;

                    while ( $i < count( $a_skills ) && strlen( $a_skills[0] ) > 0 )
                    {

                        if ( strpos($a_skills[$i], "Ball & Chain") !== FALSE ) $a_skills[$i] = "Ball and Chain";
                        if ( strpos($a_skills[$i], "Nurgle's Rot") !== FALSE ) $a_skills[$i] = "Nurgles Rot";
                        if ( strpos($a_skills[$i], "Claw/Claws") !== FALSE ) $a_skills[$i] = "Claws";
                        if ( strpos($a_skills[$i], "*") ) $a_skills[$i] = str_replace("*","",$a_skills[$i]);
				$this->roster .= "                <skill>".htmlspecialchars($a_skills[$i], ENT_NOQUOTES, 'UTF-8')."</skill>\n";
                        $i++;

                    }

                $injured = ( $p->is_mng ) ? "true" : "false";
                $this->roster .= "            </skills>\n";
                $this->roster .= "            <spp>".$p->mv_spp."</spp>\n";
                $this->roster .= "            <nigglings>".$p->inj_ni."</nigglings>\n";
                $this->roster .= "            <injured>".$injured."</injured>\n";
                $this->roster .= "            <value>".$p->value."</value>\n";
                $this->roster .= "        </player>\n";

            }

        }

        $this->roster .= "    </players>\n";
        $this->roster .= "</team>";

    }

    function checkJourneymen()
    {

        foreach ( $this->players as $p )
        {
            if ( !$p->is_dead && !$p->is_sold && $p->is_journeyman && $p->mv_spp > 0 )
            {
                Print "You have a journeyman with star players points.  Please hire or fire him.";
                return false;
            }
            if ( !$p->is_dead && !$p->is_sold && !$p->is_mng ) $this->noninjplayercount ++;
            if ( !$p->is_dead && !$p->is_sold ) $this->rosterplayercount ++;
            if ( !$p->is_dead && !$p->is_sold && $p->is_journeyman )
            {
                $this->exist_journeyman = true;
            }
        }
	
        if ( ($this->exist_journeyman && $this->games == 0) || ($this->noninjplayercount < 11 && $this->games == 0) )
        {
            Print "You have an illegal starting roster.";
            return false;
        }

        if ( !$this->jm && $this->noninjplayercount < 11 )
        {
            Print "You may have forgotten to hire a journeyman for the next match.  Please hire a player as a journeyman or hire a player.<br>";
            $rosterjm = $this->curPageURL();
            Print "If you want to ignore this error, use the following for your roster:<br><b>{$rosterjm}</b>";
            return false;
        }

        if ( $this->exist_journeyman && $this->noninjplayercount > 11 )
        {
            Print "You have a journeyman and more than 11 players that can participate in the next match.";
            return false;
        }

        if ( !$p->is_dead && !$p->is_sold && $p->nr > 89 )
        {
            Print "Player numbers from 90 to 100 is reserved for newly raised zombies and rotters.  Please renumber the player.";
            return false;
        }

        if ( $this->rosterplayercount > 16 )
        {
            Print "You have more than 16 players on the roster.  Please remove a player from your roster.";
            return false;
        }

        return true;

    }

    function curPageURL() {

        $pageURL = 'http';
        $pageURL .= "://";
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        $pageURL .= "&jm=1";
        return $pageURL;

    }

    private static function random_team($coach_id) {

        $teams = array();
        
        $result = mysql_query( "SELECT team_id FROM teams WHERE owned_by_coach_id = $coach_id AND name LIKE '%[P]' AND RETIRED = 0 ORDER BY name ASC" );
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $teams[] = $row['team_id'];
            }
        }
        if ( count($teams) < 5 ) return false;
       $key = array_rand($teams);

       return $teams[$key];

    }
    
    /*
     * Module interface
     */ 

    public static function main($argv) {
        
        // Module registered main function.
        global $settings;
        if ( !$settings['leegmgr_enabled'] ) die ("LeegMgr is currently disabled.");

        if ( isset($_GET["coachid"]) )
        {
            $_GET["teamid"] = self::random_team($_GET["coachid"]);
        }
    
        if ( isset($_GET["teamid"]) && $_GET["teamid"] )
        {
            $team_id = $_GET["teamid"];
            if ( isset($_GET["jm"]) ) $jm = $_GET["jm"];
            else $jm = 0;
            $roster = new XML_BOTOCS( $team_id, $jm );
            if ( !isset($_GET["cy"]) ) Print $roster->roster;
            else
            {
                if (!class_exists("PDO")) die("PDO must be installed for the Cyanide module to work properly.");
                $roster->cy = ($_GET["cy"] > 0) ? $_GET["cy"] : 0;
                $roster->createCyRoster();
                if ( !$roster->error )
                {
                    #Specify the header so that the browser is prompted to download the teamname.db file.
                    header('Content-type: application/octec-stream');
                    header('Content-Disposition: attachment; filename="'.$roster->cy_teamname.'.db"');
                    #Whatever is printed to the screen will be in the file.
                    Print $roster->cyroster;
                }
                else Print $roster->error;
            }
        }
        else
        {
            Print "<html><body>";
            Print "A valid team id was not found";
            Print "</body></html>";
        }

    }
    
    public static function getModuleAttributes()
    {
        return array(
            'author'     => 'William Leonard',
            'moduleName' => 'BOTOCS team XML export',
            'date'       => '2009',
            'setCanvas'  => false,
        );
    }

    public static function getModuleTables() {return array();}
    public static function getModuleUpgradeSQL() {return array();}
    public static function triggerHandler($type, $argv) {}

    /*
     * Cyanide Roster
     */

    function createCyRoster() {

/*
    public $noninjplayercount = 0;
    public $exist_journeyman = false;
    public $jm = 0;
    //team
    public $roster = '';
    public $team_id = 0;
    public $games = 0;
    public $name = '';
    public $race = '';
    public $coach_name = '';
    public $rerolls = 0;
    public $fan_factor = 0;
    public $ass_coaches = 0;
    public $cheerleaders = 0;
    public $apothecary = "false";
    public $treasury = 0;
    //players
    public $players;

    public $cyroster = '';
*/
global $settings;

$T_RACES_CYANIDE_1 = array(
    T_RACE_CHAOS,
    T_RACE_HUMAN,
    T_RACE_DWARF,
    T_RACE_SKAVEN,
    T_RACE_LIZARDMAN,
    T_RACE_WOOD_ELF,
    T_RACE_ORC,
    T_RACE_GOBLIN,
    T_RACE_DARK_ELF
);
$T_RACES_CYANIDE_2 = array_merge($T_RACES_CYANIDE_1, array(
    T_RACE_AMAZON,
    T_RACE_ELF,
    T_RACE_HALFLING,
    T_RACE_HIGH_ELF,
    T_RACE_KHEMRI,
    T_RACE_NECROMANTIC,
    T_RACE_NORSE,
    T_RACE_NURGLE,
    T_RACE_OGRE,
    T_RACE_UNDEAD,
    T_RACE_VAMPIRE,
));

if (!in_array($this->race_id, ${"T_RACES_CYANIDE_".$settings['leegmgr_cyanide_edition']})) {
    $this->error = "The race '$this->race' is not a valid Cyanide race in Cyanide edition $settings[leegmgr_cyanide_edition].";
    return false;
}

include('cyanide/lib_cy_team_db.php');
$cy 	 = new cyanide;
$cy_team = new cy_team_db;

$obblm_team['race']			    = $this->race;
$obblm_team['id']  			    = $this->team_id;
$obblm_team['name']  			= $this->name;
$obblm_team['colorid'] 			= $this->cy;//rand(1,51); //@FIXME need to map cy colors 51 = brown
$obblm_team['TeamMOTO'] 		= 'Live and Let Die!';
$obblm_team['TeamBackground'] 	= 'This team is new and needs to prove it can cust the mustard';
$obblm_team['TeamValue']		= $this->tv / 1000;
$obblm_team['TeamFanFactor']	= $this->fan_factor;
$obblm_team['gold']			    = $this->treasury;
$obblm_team['Cheerleaders']		= $this->cheerleaders;
$obblm_team['AssistantCoaches']	= $this->ass_coaches;
$obblm_team['apothecary']		= ( $this->apothecary == "true" ) ? 1 : 0;
$obblm_team['rerolls']			= $this->rerolls;


//conversions
$cy->convert_race_id('cyid',$obblm_team['race']);

//BUILD TEAM DATA
$cy->set_team_constants();
$cy->set_team_id($obblm_team['id']);
$cy->set_team_name($obblm_team['name']);
$cy->set_team_race_id($cy->race['id']);
$cy->set_team_logo($cy->race['name'].'_01');
$cy->set_team_color($obblm_team['colorid']);
$cy->set_team_moto($obblm_team['TeamMOTO']);
$cy->set_team_background($obblm_team['TeamBackground']);
$cy->set_team_value($obblm_team['TeamValue']);
$cy->set_team_fanfactor($obblm_team['TeamFanFactor']);
$cy->set_team_gold($obblm_team['gold']);
$cy->set_team_cheerleaders($obblm_team['Cheerleaders']);
$cy->set_team_assistantcoaches($obblm_team['AssistantCoaches']);
$cy->set_team_apothecary($obblm_team['apothecary']);
$cy->set_team_rerolls($obblm_team['rerolls']);
$cy->set_team_rank_constants();
//Build Race Data
$cy->set_reroll_price($obblm_team['race']);

$obblm_team['players'] = $this->obblm_team['players'];


foreach ($obblm_team['players'] as $i => $player) {
    if ( $player['Number'] < 1 || $player['Number'] > 32 )
    {
        $this->error = "Player numbers must be between 1 and 32 to be valid for this roster output.";
        return false;
    }
	$local_id = $player['id'];
	$cy->add_player_to_array(
		$local_id,//Player uniq id.. obblm player id should be fine
		$player['name'],//Players name String limit 50
		$cy->convert_player_type($player['type']), // blitzer lineman etc
		$cy->team['ID'],
		$cy->race['id'],
		$player['skin'],// skintexture.. 0 Randaom skin; most models only have 3 if your not sure leave 0
		$player['age'], // age 0 - 100% expresed with decimal but no decimal place. 100. or 001. String
		$player['Number'],// Player number Int 1-32, higher values should be converted to 1-32
		$cy->convert_ma($player['MA']),
		$cy->convert_st($player['ST']),
		$cy->convert_ag($player['AG']),
		$cy->convert_av($player['AV']),
		$player['Level'],//level
		$player['SPP'], //spp
		$player['COST'], //player cost
		$player['VALUE']//player value
	);
	//SKILLS
	foreach ($player['Skills'] as $ii => $skill) {
		if($skill == False) {
			
		} else {
			$cy->set_player_skills($local_id,$skill);
		}
	}
	//casulalty and injuries
	foreach ($player['Casualty'] as $ii => $cas) {
		if($cas == False) {
			
		} else {
			$cy->set_player_casualty($local_id,$cas);
		}
	}
}
$tempdir = sys_get_temp_dir();
        $cy_team->make_cy_roster(sys_get_temp_dir()."/",'NotUsed',$cy->players,$cy->team,$cy->race,$cy->player_skills,$cy->casualty);
        $filename = sys_get_temp_dir()."/".$obblm_team['name'].".db";
        $this->cy_teamname = $obblm_team['name'];
        $handle = fopen($filename, "r");
        $this->cyroster = fread($handle, filesize($filename));
        fclose($handle);

    }
    
}

?>
