<?php
/*
 *  Copyright (c) Ian Williams <doubleskulls@gmail.com> 2011. All Rights Reserved.
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
   This module provides an "advanced" team creation interface so you can specify the types and quantites of players etc on
   the team and don't have to add each one manually
*/

class TeamCreator implements ModuleInterface
{

/***************
 * ModuleInterface requirements. These functions MUST be defined.
 ***************/

/*
 *  Wrapper main
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
        'moduleName' => 'TeamCreator',
        'date'       => '2011',
        'setCanvas'  => true, # If true, whenever your main() is run through Module::run() your code's output will be "sandwiched" into the standard HTML frame.
    );
}

/*
 *  This function returns the MySQL table definitions for the tables required by the module. If no tables are used array() should be returned.
 */
public static function getModuleTables()
{
    return array(
    );
}

public static function getModuleUpgradeSQL()
{
    return array(
    );
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
public function __construct()
{
}


/* Generates an array containing all the info needed for each player, to be converted to Javascript */
private static function addPlayer($pos, $d, $isStarPlayer) {
    global $lng;
    
    $p = array();
    
    $positionName = strtolower(str_replace(array('-', '\'', ' ', '(', ')', '+', '.'), '', $pos));
    if(!$isStarPlayer)
        $p['position'] = $lng->getTrn('position/' . $positionName);
    else
        $p['position'] = $lng->getTrn('starplayer/' . $positionName);
    
    $p['ma'] = $d['ma'];
    $p['st'] = $d['st'];
    $p['ag'] = $d['ag'];
    $p['av'] = $d['av'];
    $p['skills'] = implode(', ', skillsTrans($d['def']));
    $p['N'] = isset($d['norm']) ? implode('',$d['norm']) : "";
    $p['D'] = isset($d['doub']) ? implode('',$d['doub']) : "";
    $p['cost'] = $d['cost'] / 1000;
    $p['id'] = isset($d['pos_id']) ? $d['pos_id'] : $d['id'];
    $p['ind'] = isset($d['pos_id']) ? 0 : 1;
    $p['max'] = isset($d['qty']) ? $d['qty'] : 1;
    return $p;
}

/* Generates an array containing all the info needed for each inducement or other team attribute */
public static function addTeamAttribute($name, $cost, $max) {
   $att = array();
   $att['name'] = $name;
   if ($max == -1) {
      $max = 16;
   }
   $att['max'] = $max;
   $att['cost'] = $cost;
   $att['ind'] = 0;
   return $att;
}

/* Generates an array containing all the info needed for the teams, to be converted to Javascript */
public static function getRaceArray() {
   global $raceididx, $DEA, $stars, $rules, $racesNoApothecary, $inducements, $lng;

   $races = array();
   foreach ($raceididx as $rid => $rname) {
      $race = array();
      $race['name'] = $rname;
      $race['rid'] = $rid;
      $race['apoth'] = !in_array($rid, $racesNoApothecary);
	  if (array_key_exists($rid, $rules['initial_team_treasury'])) {
        $race['treasury'] = $rules['initial_team_treasury'][$rid];
	  } else {
		$race['treasury'] = $rules['initial_treasury'];
      };
      $race['players'] = array();
      $race['others'] = array();
      foreach ($DEA[$raceididx[$rid]]['players'] as $pos => $d) {
         $race['players'][] = self::addPlayer($pos, $d, false);
      }
      foreach($stars as $pos => $d) {
         if (in_array($rid, $d['races'])) {
            $race['players'][] = self::addPlayer($pos, $d, true);
         }
      }
      $race['player_count'] = sizeof($race['players']);

      if ($rules['max_rerolls'] <> 0) {
         $race['others'][] = self::addTeamAttribute('Rerolls',
                                          $DEA[$raceididx[$rid]]['other']['rr_cost'] / 1000,
                                           $rules['max_rerolls']);
      }

      if ($rules['max_fan_factor'] <> 0) {
         $race['others'][] = self::addTeamAttribute('Fan Factor',
                                          10,
                                           $rules['max_fan_factor']);
      }

      if ($rules['max_cheerleaders'] <> 0) {
         $race['others'][] = self::addTeamAttribute('Cheerleaders',
                                          10,
                                           $rules['max_cheerleaders']);
      }

      if ($rules['max_ass_coaches'] <> 0) {
         $race['others'][] = self::addTeamAttribute('Ass Coaches',
                                          10,
                                           $rules['max_ass_coaches']);
      }

      if ($race['apoth']) {
         $race['others'][] = self::addTeamAttribute('Apothecary',
                                          50,
                                           1);
      }

      foreach($inducements as $name => $d) {
         $inducement = array();
         $inducement['name'] = $name;
         $inducement['max'] = $d['max'];
         if($race['apoth'] && $name == 'Igor') {
            continue;
         } else if (!$race['apoth'] && $name == 'Wandering Apothecaries') {
            continue;
         }
         if (in_array($rid, $d['reduced_cost_races'])) {
            $inducement['cost'] = $d['reduced_cost'] / 1000;
	 } else if ($d['cost'] < 1) {continue; /* eliminates negative value inducements to lock out of non-qualifying races */
         } else {
            $inducement['cost'] = $d['cost'] / 1000;
         }
         $inducement['ind'] = 1;
         $race['others'][] = $inducement;
      }
      $race['other_count'] = sizeof($race['others']);

      $races[] = $race;
   }
   return $races;
}

/* Simple check to see if the logged in coach is allowed to create a team for the selected coach */
public static function allowEdit($cid, $coach) {
    return (is_object($coach) && ($cid == $coach->coach_id || $coach->mayManageObj(T_OBJ_COACH, $cid)));
}

/* Check to see if the league rules on team composition are being followed */
public static function checkLimit($limit, $value) {
   if ($limit == -1) return false;
   if ($limit == 0 && $value > 0) return true;
   return $value > $limit;
}

/* method to process create teams */
public static function handlePost($cid) {
   global $lng, $_POST, $coach, $raceididx, $DEA, $rules, $racesNoApothecary;
   if (!isset($_POST['action'])) return;
   if (!self::allowEdit($cid, $coach)) {
      status(false, $lng->getTrn('notallowed', 'TeamCreator'));
      return;
   }
   
   $lid_did = $_POST['lid_did'];
   @list($lid,$did) = explode(',',$_POST['lid_did']);
   setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => (int) $lid)); // Load correct $rules for league.

   if (get_magic_quotes_gpc()) {
      $_POST['tname'] = stripslashes($_POST['tname']);
   }

   $rid = $_POST['raceid'];
   $race = $DEA[$raceididx[$rid]];

   /* Handle or the 'other' stuff around the team - rerolls etc */
   $rerolls = $_POST['qtyo0'];
   $fans = $_POST['qtyo1'];
   $cl = $_POST['qtyo2'];
   $ac = $_POST['qtyo3'];   
   if (array_key_exists($rid, $rules['initial_team_treasury'])) {
        $init_treasury = $rules['initial_team_treasury'][$rid];
	  } else {
		$init_treasury = $rules['initial_treasury'];
      };
   $treasury = $init_treasury;
   $treasury -= $rerolls * $race['other']['rr_cost'];
   $treasury -= $fans * 10000;
   $treasury -= $cl * 10000;
   $treasury -= $ac * 10000;
   $rerolls += $rules['initial_rerolls'];
   $fans += $rules['initial_fan_factor'];
   $cl += $rules['initial_ass_coaches'];
   $ac += $rules['initial_cheerleaders'];
   if (!in_array($rid, $racesNoApothecary)) {
      $apoth = $_POST['qtyo4'];
      if ($apoth) {
         $treasury -= 50000;
      }
   } else {
      $apoth = 0;
   }

   /* Create an array with all the players in. Do this first to check for sufficient funds */
   $players = array();
   $idx = 0;
   $rosterNum = 1;
   foreach ($race['players'] as $pos => $d) {
      $pid = $_POST['pid' . $idx];
      if ($pid != $d['pos_id']) {
         // mismatched position ID
         status(false, $pid . ' but was ' . $d['pos_id']);
         return;
      }
      $qty =  $_POST['qtyp' . $idx];
      for($i = 0; $i < $qty; $i++) {
         $treasury -= $d['cost'];
         $player = array();
         $player['name'] = "";
         $player['nr'] = $rosterNum++;
         $player['f_pos_id'] = $d['pos_id'];
         $players[] = $player;
      }
      $idx++;
   }

   /* Enforce league rules and common BB ones */
   $errors = array();
   if ($treasury < 0) {
      $errors[] = $lng->getTrn('tooExpensive', 'TeamCreator') . ' (' . $init_treasury/1000 . ' kGP)';
   }
   if (sizeof($players) < 11) {
      $errors[] = $lng->getTrn('tooFewPlayers', 'TeamCreator');
   }
   if (sizeof($players) > $rules['max_team_players']) {
      $errors[] = $lng->getTrn('tooManyPlayers', 'TeamCreator');
   }
   if(self::checkLimit($rules['max_rerolls'], $rerolls)) {
      $errors[] = $lng->getTrn('tooManyRR', 'TeamCreator') . " " . $rerolls . " vs " . $rules['max_rerolls'];
   }
   if(self::checkLimit($rules['max_fan_factor'], $fans)) {
      $errors[] = $lng->getTrn('tooManyFF', 'TeamCreator') . " " . $fans . " vs " . $rules['max_fan_factor'];
   }
   if(self::checkLimit($rules['max_ass_coaches'], $ac)) {
      $errors[] = $lng->getTrn('tooManyAc', 'TeamCreator') . " " . $ac . " vs " . $rules['max_ass_coaches'];
   }
   if(self::checkLimit($rules['max_cheerleaders'], $cl)) {
      $errors[] = $lng->getTrn('tooManyCl', 'TeamCreator') . " " . $cl . " vs " . $rules['max_cheerleaders'];
   }

   /* Actually create the team in the database */
   if(sizeof($errors) == 0) {
      list($exitStatus, $tid) = Team::create(array(
         'name' => $_POST['tname'],
         'owned_by_coach_id' => (int) $cid,
         'f_race_id' => (int) $rid,
         'treasury' => $treasury,
         'apothecary' => $apoth,
         'rerolls' => $rerolls,
         'ff_bought' => $fans,
         'ass_coaches' => $ac,
         'cheerleaders' => $cl,
         'won_0' => 0, 'lost_0' => 0, 'draw_0' => 0, 'played_0' => 0, 'wt_0' => 0, 'gf_0' => 0, 'ga_0' => 0,
         'imported' => 0,
         'f_lid' => (int) $lid,
         'f_did' => isset($did) ? (int) $did : Team::T_NO_DIVISION_TIE,
         ));

      if($exitStatus) {
      	$errors[] = Team::$T_CREATE_ERROR_MSGS[$exitStatus];
      }
   }

   /* Actually create all the players in the database */
   if(sizeof($errors) == 0) {
      $opts = array();
      $opts['free'] = 1; // already deducted cost from treasry
      foreach($players as $player) {
         $player['team_id'] = $tid;
         list($exitStatus, $pid) = Player::create($player, $opts);
         if ($exitStatus) {
            $errors = array_merge($errors, Player::$T_CREATE_ERROR_MSGS[$exitStatus]);
         }
      }
   }

   /* Report errors and reset the form, or redirect to the team page */
   if (sizeof($errors) > 0) {
      $msg = implode(",<br />", $errors);
	  if ($_POST['action'] == 'create') {
		status(false, $msg); // Don't show error messages if there was no attempt to create team
	  }
      $post = (object) $_POST;
echo<<< EOQ
   <script type="text/javascript">
      $(document).ready(function() {
      document.getElementById('rid').value = $post->rid;
      changeRace($post->rid);
      document.getElementById('tname').value = '$post->tname';
EOQ;
      foreach($_POST as $element => $value) {
         if (0 == strncmp($element, "qty", 3)) {
            $idx = substr($element,4,1);
            $type = substr($element,3,1);
echo<<< EOQ

      document.getElementById('$element').selectedIndex = $value;
      updateQty($idx, '$type', $value);
EOQ;
         }
      }
echo<<< EOQ
      var lid = document.getElementById('lid_did');
      for (var i = 0; i < lid.options.length; i++) {
         if (lid.options[i].value=="$post->lid_did") {
            lid.selectedIndex = i;
            break;
         }
      }
      });
   </script>
EOQ;
   } else {
      // Everything worked, redirect to the team page
      status(true, $lng->getTrn('created', 'TeamCreator'));
      $teamUrl = "'" . str_replace("amp;", "", urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$tid,false,false)) . "'";
echo<<< EOQ
   <script type="text/javascript">
      $(document).ready(function() {
         window.location = $teamUrl;
      });
   </script>
EOQ;
   }
}

/* Used in standalone plugin mode */
public static function teamCreator() {
   global $coach, $lng;
   title($lng->getTrn('name', 'TeamCreator'));
   self::newTeam(isset($coach) ? $coach->coach_id : null);
}

/* Used when accessed from coach profile */
public static function newTeam($cid) {
   global $lng, $coach, $raceididx, $leagues, $rules, $divisions;
   self::handlePost($cid);
    // Show new team form.
   $easyconvert = new array_to_js();
   $races = self::getRaceArray();
   @$easyconvert->add_array($races, 'races');
   echo $easyconvert->output_all();

   // txt constants to show later
   $txtNoInduce = $lng->getTrn('noInduce', 'TeamCreator');
   $txtNoTeamName = $lng->getTrn('noTeamName', 'TeamCreator');
   $txtTooFewPlayers = $lng->getTrn('tooFewPlayers', 'TeamCreator');
   $txtRaceSelectTitle = $lng->getTrn('race', 'TeamCreator');
   $txtRaceSelectOption = $lng->getTrn('raceDefaultOption', 'TeamCreator');
   $txtTeamName = $lng->getTrn('teamName', 'TeamCreator');
   $txtNoRaceSelected = $lng->getTrn('noRaceSelected', 'TeamCreator');
   $txtCreateBtn = $lng->getTrn('createBtn', 'TeamCreator');

   // The page builds itself dynamically based on the selected race
echo<<< EOQ
   <script type="text/javascript">

   function setIndex(elem, value) {
      document.getElementById(elem).selectedIndex = value;
   }

   function getValue(elemId) {
      try {
         var elem = document.getElementById(elemId);
         return elem.options[elem.selectedIndex].value;
      } catch (err) {
         return "";
      }
   }

   function getText(elemId) {
      try {
         if(document.all){
            return document.getElementById(elemId).innerText;
         } else {
            return document.getElementById(elemId).textContent;
         }
      } catch (err) {
         return "";
      }
   }

   function addCellToRow(rowObj, cellValue, colspan) {
      var cell = rowObj.insertCell(rowObj.cells.length);
      if (colspan > 1) {
         cell.colSpan=colspan;
         cell.align="right";
         cell.innerHTML = cellValue + "&nbsp;&nbsp;";;
        } else {
         cell.innerHTML = cellValue;
        }
      return cell;
   }

   function makeInput(type, id, value) {
      return "<input type=\"" + type + "\" id=\"" + id + "\" name=\"" + id + "\" value=\"" + value+ "\" />";
   }

   function setText(element, text) {
      if(document.all){
           document.getElementById(element).innerText = text;
      } else{
          document.getElementById(element).textContent = text;
      }
   }


   function updateQty(id, type, newQty) {
      var race = races[document.getElementById("rid").value];
      if (type == 'p') {
         var players = race['players'];
      } else {
         var players = race['others'];
      }
      var player = players[id];
      var divS = 'sub' + type + id;
      var newCost = player['cost'] * newQty;
      setText(divS, newCost);
      updateTotal();
   }

   function makeSelect(id, type, max) {
      var str = "<select id=\"qty" + type + id + "\" name=\"qty" + type + id + "\" onchange=\"updateQty(" + id + ", '" + type + "', this.options[this.selectedIndex].value)\">";
      for (var i = 0; i <= max; i++) {
         str += "<option value=\"" + i + "\">" + i + "</option>";
      }
      str += "</select>";
      return str;
   }

   function changeInduce(check) {
      var oldInduce = document.getElementById('oldInduce');
      if (check != new Boolean(oldInduce.checked)) {
         oldInduce.checked = check;
         var race = races[document.getElementById("rid").value];
         var pCounts = new Array();
         var oCounts = new Array();
         for (var i=0; i < race["player_count"]; i++) {
            pCounts[i] = getValue('qtyp' + i);
         }
         for (i=0; i < race["other_count"]; i++) {
            oCounts[i] = getValue('qtyo' + i);
         }
         changeRace(getValue("rid"));
         for (var i=0; i < race["player_count"]; i++) {
            if (pCounts[i] > 0) {
               try {
                  setIndex('qtyp' + i, pCounts[i]);
                  updateQty(i, 'p', pCounts[i]);
               } catch (err) {
               }
            }
         }
         for (i=0; i < race["other_count"]; i++) {
            if (oCounts[i] > 0) {
               try {
                  setIndex('qtyo' + i, oCounts[i]);
                  updateQty(i, 'o', oCounts[i]);
               } catch (err) {
               }
            }
         }
      }
   }

   function changeRace(raceId) {
      if (raceId < 0) return;
      var oFormObject = document.forms['form_team'];
      var race = races[raceId];
      var players = race["players"];
      var others = race["others"];
	  var maximum = race['treasury']/1000;
      var i;
      var rowIdx;
      var table = document.getElementById('teamTable');
      var induce = document.getElementById('induce').checked;
      while (table.rows.length > 1) {
         table.deleteRow(1);
      }
      setText("pcnt", "0");
      setText("total", "0");
	  setText("total", '0/' + maximum.toString());
      document.getElementById("raceid").value = race.rid;

      rowIdx = 0;
      for (i = 0; i < race["player_count"]; i++) {
         var player = players[i];
         if (!induce && player['ind']) {
            continue;
         }
         rowIdx++;
         var row = table.insertRow(rowIdx);
         addCellToRow(row, player["position"], 1);
         addCellToRow(row, player["ma"], 1);
         addCellToRow(row, player["st"], 1);
         addCellToRow(row, player["ag"], 1);
         addCellToRow(row, player["av"], 1);
         addCellToRow(row, player["skills"], 1);
         addCellToRow(row, player["N"], 1);
         addCellToRow(row, player["D"], 1);
         addCellToRow(row, player["cost"], 1);
         addCellToRow(row, makeInput('hidden', 'pid' + i, player["id"]) + makeSelect(i, 'p', player["max"]), 1);
         addCellToRow(row, "<div id=\"subp" + i + "\"></div>", 1);
      }
      for (i = 0; i < race["other_count"]; i++) {
         var other = others[i];
         if (!induce && other['ind']) {
            continue;
         }
         rowIdx++;
         var row = table.insertRow(rowIdx);
         addCellToRow(row, other["name"], 8);
         addCellToRow(row, other["cost"], 1);
         addCellToRow(row, makeInput('hidden', 'oid' + i, other["name"]) + makeSelect(i, 'o', other["max"]), 1);
         addCellToRow(row, "<div id=\"subo" + i + "\"></div>", 1);
      }
      try {
         if(induce) {
            document.getElementById("createBtnTxt").title="$txtNoInduce";
            document.getElementById("createBtn").disabled="disabled";
         } else {
            document.getElementById("createBtnTxt").title="";
            document.getElementById("createBtn").disabled="";
         }
      } catch (err) {
         // ignore - probably not logged in
      }
   }

   function updateTotal() {
      var race = races[getValue("rid")];
      var playerCount = race['player_count'];
	  var maximum = race['treasury']/1000;

      var pCount = 0;
      var total = 0;
      var subTot = 0;

      for (var i=0; i < playerCount; i++) {
         pCount += new Number(getValue('qtyp' + i));
         subTot = getText('subp' + i);
         if (!isNaN(subTot)) {
            total +=  new Number(subTot);
         }
      }
      setText("pcnt", pCount);
      for (var i=0; i < race['other_count']; i++) {
         subTot = getText('subo' + i);
         if (!isNaN(subTot)) {
            total +=  new Number(subTot);
         }
      }
      setText("total", total.toString() + '/' + maximum.toString());
   }

   function createTeam() {
      var oForm = document.forms['form_team'];
      var spend = new Number(getText("total"));
      var pCount = new Number(getText("pcnt"));
      var tName = document.getElementById("tname").value;
      var submit = true;

      if (submit && tName.length == 0) {
         alert('$txtNoTeamName');
         submit = false;
      }

      if (submit && pCount < 11) {
         alert('$txtTooFewPlayers');
         submit = false;
      }

      if (submit) {
         oForm.submit();
      }

   }
   
   function changeLeague() {
      // Reload page with new league rules when League dropdown changes
	  // Set action field to 'leagueChange' for $_POST handling
	  document.getElementById("action").value = 'leagueChange';
	  // Submit form
	  document.getElementById("form_team").submit();
   }

   </script>
   <form method="POST" id="form_team">
   <input type="hidden" id="action" name="action" value="create" />
   <input type="hidden" id="raceid" name="raceid" value="" />
   <div class='boxWide'>
      <table class="common"><tr><td>
      <b>$txtRaceSelectTitle</b>: <select id="rid" name="rid" onchange="changeRace(this.options[this.selectedIndex].value)">
EOQ;
      echo "<option value='-1'>$txtRaceSelectOption</option>";
      $i = 0;
        foreach ($raceididx as $rname) {
            $translatedRaceName = $lng->getTrn('race/'.strtolower(str_replace(' ','', $rname)));
            echo "<option value='$i'>$translatedRaceName</option>";
            $i++;
      }
echo<<< EOQ
       </select></td>
EOQ;
      if (isset($coach)) {
         $lgeDiv = $lng->getTrn('common/league') . '/' . $lng->getTrn('common/division');
echo<<< EOQ
      <td align="right"><b>$txtTeamName</b>:</td><td><input type="text" id="tname" name="tname" size="20" maxlength="50"></td>
      <td align="right"><b>$lgeDiv</b>:</td><td><select name="lid_did" id="lid_did" onChange="changeLeague()">
EOQ;
         foreach ($leagues = Coach::allowedNodeAccess(Coach::NODE_STRUCT__TREE, $coach->coach_id, array(T_NODE_LEAGUE => array('tie_teams' => 'tie_teams'))) as $lid => $lstruct) {
            if ($lstruct['desc']['tie_teams']) {
               echo "<OPTGROUP LABEL='".$lng->getTrn('common/league').": ".$lstruct['desc']['lname']."'>\n";
               foreach ($lstruct as $did => $dstruct) {
                  if ($did != 'desc') {
                     echo "<option value='$lid,$did'>".$lstruct['desc']['lname'].": ".$dstruct['desc']['dname']."</option>";
                  }
               }
               echo "</OPTGROUP>\n";
            }
            else {
               echo "<option value='$lid'>". $lstruct['desc']['lname']."</option>";
            }
         }
echo<<< EOQ
      </select></td>
      <td><span id="createBtnTxt" title="$txtNoRaceSelected"><button id="createBtn" onclick="createTeam(); return false;" DISABLED>$txtCreateBtn</button></td>
EOQ;
      }

   $txtInducements = $lng->getTrn('inducementsCheck', 'TeamCreator');
   $txtPlayerCount = $lng->getTrn('playerCount', 'TeamCreator');
   $txtTotal = $lng->getTrn('total', 'TeamCreator');
   $txtPos = $lng->getTrn('common/pos');
   $txtSkills = $lng->getTrn('common/skills');
   $txtNorm = $lng->getTrn('normal', 'TeamCreator');
   $txtDoub = $lng->getTrn('double', 'TeamCreator');
   $txtDollar = $lng->getTrn('dollar', 'TeamCreator');
   $txtQuantity = $lng->getTrn('quantity', 'TeamCreator');
   $txtSubtotal = $lng->getTrn('subtotal', 'TeamCreator');
   

echo<<< EOQ
       <td align="right" id="indTxt">$txtInducements:</td>
       <td><input type="checkbox" id="induce" onChange="changeInduce(this.checked)" /><input type="hidden" id="oldInduce" value="false" /></td>
       <td align="right"><b>$txtPlayerCount</b>:</td><td><div id="pcnt"></div></td>
       <td align="right"><b>$txtTotal</b>:</td><td><div id="total"></div></td>
       </tr></table>
   </div>
   <div class="boxWide">
   <table class="common" id="teamTable">
      <tr class="commonhead">
         <th>$txtPos</th>
         <th>MA</th>
         <th>ST</th>
         <th>AG</th>
         <th>Av</th>
         <th>$txtSkills</th>
         <th>$txtNorm</th>
         <th>$txtDoub</th>
         <th>$txtDollar</th>
         <th>$txtQuantity</th>
         <th>$txtSubtotal</th>
      </tr>
   </table>
   </div>
   </form>
EOQ;
}
}
