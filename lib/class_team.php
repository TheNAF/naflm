<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2007-2011. All Rights Reserved.
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

class Team
{
    const T_NO_DIVISION_TIE = 0;

    /***************
     * Properties
     ***************/

    // MySQL stored information
    public $team_id           = 0;
    public $name              = '';
    public $owned_by_coach_id = 0;
    public $f_race_id         = 0;
    public $f_lid             = 0;
    public $f_did             = 0;
    public $f_rname           = '';
    public $f_cname           = '';
    public $treasury          = 0;
    public $apothecary        = 0;
    public $rerolls           = 0;
    public $ass_coaches       = 0;
    public $cheerleaders      = 0;
    public $rdy               = 1; // Ready bool.
    public $imported          = false;
    public $is_retired        = 0;
    
    public $value = 0; public $tv = 0; # Identical.
    public $ff_bought = 0;

    // MySQL stored initials for imported teams
    public $won_0  = 0;
    public $lost_0 = 0;
    public $draw_0 = 0;
    public $played_0 = 0;
    public $sw_0   = 0;
    public $sl_0   = 0;
    public $sd_0   = 0;
    public $wt_0   = 0;
    public $gf_0   = 0;
    public $ga_0   = 0;

    /***************
     * Methods
     ***************/

    function __construct($team_id) {

         // MySQL stored information
        $this->team_id = $team_id;
        $this->setStats(false,false,false);

        $this->is_retired = ($this->retired || get_alt_col('coaches', 'coach_id', $this->owned_by_coach_id, 'retired'));
        unset($this->retired); // We use $this->is_retired instead.
        $this->imported = ($this->imported == 1); // Make boolean.
        $this->value = $this->tv;
        
        return true;
    }

    public function doubleRRprice() 
    {
        global $rules;
        setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => $this->f_lid)); // Load correct $rules for league.
        $this->doubleRRprice = (!$rules['static_rerolls_prices'] && $this->mv_played > 0 && $this->mv_played != $this->played_0);
        return $this->doubleRRprice;
    }

    public function mayBuyFF()
    {
        global $rules;
        setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => $this->f_lid)); // Load correct $rules for league.
        $this->mayBuyFF = ($rules['post_game_ff'] || $this->mv_played == 0 || $this->mv_played == $this->played_0);
        return $this->mayBuyFF;
    }

    public function setStats($node, $node_id, $set_avg = false)
    {
        foreach (Stats::getAllStats(STATS_TEAM, $this->team_id, $node, $node_id, $set_avg) as $key => $val) {
            $this->$key = $val;
        }
        return true;
    }

// No longer supported ! See http://code.google.com/p/obblm/issues/detail?id=463#c5
#    public function setOwnership($cid) {
#        $query = "UPDATE teams SET owned_by_coach_id = $cid WHERE team_id = $this->team_id";
#        return mysql_query($query) && ($this->owned_by_coach_id = $cid) && SQLTriggers::run(T_SQLTRIG_TEAM_UPDATE_CHILD_RELS, array('id' => $this->team_id, 'obj' => $this));
#    }
    
    public function getPlayers() {
        $this->_players = array();
        $result = mysql_query("SELECT player_id FROM players WHERE owned_by_team_id = $this->team_id ORDER BY nr ASC, name ASC");
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($this->_players, new Player($row['player_id']));
            }
        }
        return $this->_players;
    }

    public function getWonTours() {
    
        // Returns an array of tournament objects for those tournaments this team has won.
        return array_map(create_function('$t', 'return new Tour($t->tour_id);'), get_rows('tours', array('tour_id'), array("winner = $this->team_id")));
    }

    public function getLatestTour() {

        /**
         * Returns the ID of latest tournament competed in.
         **/

        $query = "SELECT f_tour_id FROM matches WHERE team1_id = $this->team_id OR team2_id = $this->team_id ORDER BY date_played DESC LIMIT 1";
        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            return $row['f_tour_id'];
        }

        return false;
    }

    public function getGoods($use_dynamic_RR_prices = true) {

        /**
         * Returns array containing buyable stuff for teams in their coach corner.
         *
         *  Setting $use_dynamic_RR_prices forces non-doubled RR prices.
         **/

        // Setup correct $rules for when calling $race->getGoods() which uses the $rules['max_*'] entries.
        global $rules;
        setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => $this->f_lid)); // Load correct $rules for league.

        $race = new Race($this->f_race_id);
        return $race->getGoods($use_dynamic_RR_prices ? $this->doubleRRprice() : false);
    }

    public function delete() {

        /**
         * Deletes team if deletable.
         **/

        if ($this->isDeletable()) {
            $query = "DELETE FROM match_data WHERE f_team_id = $this->team_id"; mysql_query($query); // These entries occur only when players are imported.
            $query = "DELETE FROM players WHERE owned_by_team_id = $this->team_id"; mysql_query($query);
            $query = "DELETE FROM teams WHERE team_id = $this->team_id"; mysql_query($query);
            $query = "DELETE FROM mv_players WHERE f_tid = $this->team_id"; mysql_query($query);
            $query = "DELETE FROM mv_teams WHERE f_tid = $this->team_id"; mysql_query($query);
            SQLTriggers::run(T_SQLTRIG_COACH_TEAMCNT, array('id' => $this->owned_by_coach_id, 'obj' => new Coach($this->owned_by_coach_id)));
            SQLTriggers::run(T_SQLTRIG_RACE_TEAMCNT, array('id' => $this->f_race_id, 'obj' => new Race($this->f_race_id)));
            return true;
        }

        return false;
    }

    public function rename($new_name) {

        /**
         * Renames team.
         **/

        // Do not allow changing the team name to an other existing team's name (to avoid confusion).
        if (empty($new_name))
            return false;

        $query  = "SELECT team_id FROM teams WHERE team_id != $this->team_id AND name = '" . mysql_real_escape_string($new_name) . "'";
        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0)
            return false;

        $query = "UPDATE teams SET name = '" . mysql_real_escape_string($new_name) . "' WHERE team_id = $this->team_id";
        return mysql_query($query) && SQLTriggers::run(T_SQLTRIG_TEAM_UPDATE_CHILD_RELS, array('id' => $this->team_id, 'obj' => $this));
    }

    public function setRetired($bool) {

        return mysql_query("UPDATE teams SET retired = ".(($bool) ? 1 : 0)." WHERE team_id = $this->team_id");
    }

    public function buy($thing) {

        /**
         * Buy team stuff (coaching staff/re-rolls/fan factor).
         **/

        global $rules;

        $team_goods = $this->getGoods();

        // Valid item?
        if (!array_key_exists($thing, $team_goods))
            return false;

        // Is post game FF purchaseable?
        if ($thing == 'ff_bought' && !$this->mayBuyFF())
            return false;

        // Enough money?
        if ($this->treasury - $team_goods[$thing]['cost'] < 0)
            return false;

        // Reached max allow quantity of item?
        if ($this->$thing >= $team_goods[$thing]['max'] && $team_goods[$thing]['max'] != -1)
            return false;

        // Buy that thing!
        $price = $team_goods[$thing]['cost'];
        if (mysql_query("UPDATE teams SET treasury = treasury - $price, $thing = $thing + 1 WHERE team_id = $this->team_id")) {
            SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('id' => $this->team_id, 'obj' => $this)); # Update TV.
            $this->$thing++;
            $this->treasury -= $price;
            return true;
        }
        else {
            return false;
        }
    }

    public function unbuy($thing) {

        /**
         * Regret the purchase of team stuff (coaching staff/re-rolls/fan factor) and get full refund.
         **/

        $team_goods = $this->getGoods();

        // Valid item?
        if (!array_key_exists($thing, $team_goods))
            return false;

        // Have more than 0 of item?
        if ($this->$thing <= 0 || ($thing == 'ff_bought' && $this->ff_bought <= 0))
            return false;

        // Un-buy!
        $price = $team_goods[$thing]['cost'];
        if (mysql_query("UPDATE teams SET treasury = treasury + $price, $thing = $thing - 1 WHERE team_id = $this->team_id")) {
            SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('id' => $this->team_id, 'obj' => $this));
            $this->$thing--;
            $this->treasury += $price;
            return true;
        }
        else {
            return false;
        }
    }

    public function drop($thing) {

        /**
         * Let go of team stuff (coaching staff/re-rolls/fan factor) WITHOUT refund.
         **/

        global $rules;
        $goods = $this->getGoods();
        $price = null;

        // May drop post FF?
        if ($thing == 'ff_bought' && !$this->mayBuyFF())
            return false;

        if (array_key_exists($thing, $goods))
            $price = $goods[$thing]['cost'];
        else
            return false;

        if ($this->unbuy($thing)) {
            if ($this->dtreasury(-1 * $price)) {
                SQLTriggers::run(T_SQLTRIG_TEAM_DPROPS, array('id' => $this->team_id, 'obj' => $this));
                return true;
            }
            else
                $this->buy($thing); # Do not allow a situation, where we have removed the team "thing", and were not able to throw the refund away.
        }

        return false;
    }

    public function dtreasury($delta) {

        /**
         * Add a delta to team's treasury.
         **/

        $query = "UPDATE teams SET treasury = treasury + $delta WHERE team_id = $this->team_id";
        if (mysql_query($query)) {
            $this->treasury += $delta;
            return true;
        }
        else {
            return false;
        }
    }

	public function setff_bought($integer) {

        /**
         * Set team's bought fan factor
         **/
        $integer = intval(max($integer,0));

        $query = "UPDATE teams SET ff_bought = $integer WHERE team_id = $this->team_id";
        if (mysql_query($query)) {
            $this->ff_bought+= $integer;
            return true;
        }
        else {
            return false;
        }
    }

    public function setReady($bool) {

        mysql_query("UPDATE teams SET rdy = ".(($bool) ? 1 : 0)." WHERE team_id = $this->team_id");
        $this->rdy = $bool;
        return true;
    }
    
    public function isDeletable() {

        /**
         * Tests if a team is deletable (has not participated in any matches)
         **/

        $query = "SELECT match_id FROM matches WHERE team1_id = $this->team_id OR team2_id = $this->team_id LIMIT 1";
        $result = mysql_query($query);

        return (mysql_num_rows($result) > 0) ? false : true;
    }

    public function isFull() {

        /**
         * Returns true/false depending on, if the team may purchase more players/has reached the max. player limit.
         **/

        global $rules;

        setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => $this->f_lid)); // Load correct $rules for league.

        $query = "SELECT (COUNT(*) >= ".$rules['max_team_players'].") FROM players
            WHERE owned_by_team_id = $this->team_id AND date_sold IS NULL AND status NOT IN (".DEAD.")";
        $result = mysql_query($query);
        $row = mysql_fetch_row($result);
        return (bool) $row[0];
    }

    public function isPlayerBuyable($pos_id) {
        
        /* 
            Checks whether maximum number of allowed positionals is reached. 
        */
        
        $query = "SELECT IFNULL(COUNT(*) < qty, TRUE) FROM players, game_data_players 
            WHERE f_pos_id = pos_id AND owned_by_team_id = $this->team_id AND f_pos_id = $pos_id AND date_died IS NULL AND date_sold IS NULL";
        $result = mysql_query($query);
        $row = mysql_fetch_row($result);
        return ((bool) $row[0]) && $this->isPlayerPosValid($pos_id);
    }

    public function isPlayerPosValid($pos_id) {

        // Is $pos_id a valid position for this team's race?
        
        global $DEA;
        
        foreach ($DEA[$this->f_rname]['players'] as $pos => $desc) {
            if ($desc['pos_id'] == $pos_id) {
                return true;
            }
        }
        
        return false;
    }
    
    public function isPlayerNumberOccupied($nr) {
        return SQLBoolEval("SELECT COUNT(*) FROM players WHERE owned_by_team_id = $this->team_id AND nr = $nr AND date_sold IS NULL AND date_died IS NULL AND status != ".DEAD);
    }
    
    public function isJMLimitReached() {
        global $rules;
        setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => $this->f_lid)); // Load correct $rules for league.
        return ($rules['journeymen_limit'] <= (int) SQLFetchField("SELECT COUNT(*) FROM players WHERE owned_by_team_id = $this->team_id AND date_sold IS NULL AND date_died IS NULL AND status = ".NONE));
    }

    public function getToursPlayedIn($ids_only = false)
    {
        $tours = array();

        $query = "SELECT DISTINCT(f_tour_id) FROM matches, tours
                WHERE f_tour_id = tour_id AND team1_id = $this->team_id OR team2_id = $this->team_id
                ORDER BY tours.date_created ASC";
        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($tours, ($ids_only) ? $row['f_tour_id'] : new Tour($row['f_tour_id']));
            }
        }

        return $tours;
    }
    
    public function getFreePlayerNr()
    {
        global $T_ALLOWED_PLAYER_NR;
        $query = "SELECT GROUP_CONCAT(nr) FROM players WHERE owned_by_team_id = $this->team_id GROUP BY owned_by_team_id";
        $result = mysql_query($query);
        list($inUse) = mysql_fetch_row($result);
        $inUse = explode(',',$inUse);
        $free = array_diff($T_ALLOWED_PLAYER_NR, $inUse);
        return current($free);
    }

    public function saveText($str) {

        $txt = new ObjDescriptions(T_TEXT_TEAM, $this->team_id);
        return $txt->save($str);
    }

    public function getText() {

        $desc = new ObjDescriptions(T_TEXT_TEAM, $this->team_id);
        return $desc->txt;
    }

    public function saveLogo($name) {
        $img = new ImageSubSys(IMGTYPE_TEAMLOGO, $this->team_id);
        list($retstatus, $error) = $img->save($name);
        return array($retstatus, $error);
    }

    public function saveStadiumPic($name = false) {
        $img = new ImageSubSys(IMGTYPE_TEAMSTADIUM, $this->team_id);
        list($retstatus, $error) = $img->save($name);
        return array($retstatus, $error);
    }
    
    public function deleteLogo() {
        $img = new ImageSubSys(IMGTYPE_TEAMLOGO, $this->team_id);
        return $img->delete();
    }

    public function deleteStadiumPic() {
        $img = new ImageSubSys(IMGTYPE_TEAMSTADIUM, $this->team_id);
        return $img->delete();    
    }

    public function writeNews($txt) {
        return TeamNews::create($txt, $this->team_id);
    }

    public function getNews($n = false) {
        return TeamNews::getNews($this->team_id, $n);
    }

    public function deleteNews($news_id) {
        $news = new TeamNews($news_id);
        return $news->delete();
    }
    public function editNews($news_id, $new_txt) {
        $news = new TeamNews($news_id);
        return $news->edit($new_txt);
    }
    
    // Run this after having imported THIS team.
    public function postImportSync() {
        return mysql_query("CALL match_sync(0,0,$this->team_id,$this->team_id,0)");
    }
    
    public function allowEdit() {
        global $coach;
        return is_object($coach) 
            && ($this->owned_by_coach_id == $coach->coach_id || $coach->mayManageObj(T_OBJ_TEAM, $this->team_id)) 
            && !$this->is_retired;
    }

    /***************
     * Statics
     ***************/

    public static function exists($id) 
    {
        $result = mysql_query("SELECT COUNT(*) FROM teams WHERE team_id = $id");
        list($CNT) = mysql_fetch_row($result);
        return ($CNT == 1);
    }

    public static function getTeams($race_id = false, $f_lids = array(), $noObj = false) {

        /**
         * Returns an array of all team objects.
         **/

        $teams = array();
        $where = array();
        if ($race_id !== false) { 
            $where[] = "f_race_id = $race_id";
        }
        if (!empty($f_lids)) {
            $where[] = "f_lid IN (".implode(',',$f_lids).")";
        }
        $query = "SELECT team_id,name FROM teams".(!empty($where) ? ' WHERE '.implode(' AND ', $where) : '').' ORDER BY name ASC';
        $result = mysql_query($query);
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                if ($noObj) {
                    $teams[$row['team_id']] = $row['name'];
                }
                else {
                    array_push($teams, new Team($row['team_id']));
                }
            }
        }

        return $teams;
    }

    const T_CREATE_SUCCESS = 0;
    const T_CREATE_ERROR__SQL_QUERY_FAIL = 1;
    const T_CREATE_ERROR__UNEXPECTED_INPUT = 2;
    const T_CREATE_ERROR__INVALID_RACE = 3;
    const T_CREATE_ERROR__INVALID_COACH = 4;
    const T_CREATE_ERROR__INVALID_NAME = 5;
    const T_CREATE_ERROR__INVALID_LEAGUE = 6;
    const T_CREATE_ERROR__INVALID_DIVISION = 7;

    public static $T_CREATE_ERROR_MSGS = array(
        self::T_CREATE_ERROR__SQL_QUERY_FAIL   => 'SQL query failed.',
        self::T_CREATE_ERROR__UNEXPECTED_INPUT => 'Unexpected input.',
        self::T_CREATE_ERROR__INVALID_RACE     => 'Illegal/invalid team race.',
        self::T_CREATE_ERROR__INVALID_COACH    => 'Illegal/invalid parent coach ID.',
        self::T_CREATE_ERROR__INVALID_NAME     => 'Illegal/invalid team name.',
        self::T_CREATE_ERROR__INVALID_LEAGUE   => 'Illegal/invalid league ID.',
        self::T_CREATE_ERROR__INVALID_DIVISION => 'Illegal/invalid division ID.',
    );

    public static $T_CREATE_SQL_ERROR = array(
        'query' => null, # mysql fail query.
        'error' => null, # mysql_error()
    );

    // Required passed fields (input) to create().
    public static $createEXPECTED = array(
        'name','owned_by_coach_id','f_race_id','f_lid','f_did',
        'treasury', 'apothecary', 'rerolls', 'ff_bought', 'ass_coaches', 'cheerleaders',
        'won_0','lost_0','draw_0','played_0','wt_0','gf_0','ga_0','imported',
    );

    public static function create(array $input) {

        /**
         * Creates a new team.
         **/

        global $raceididx;

        $EXPECTED = self::$createEXPECTED;
        sort($EXPECTED);
        ksort($input);
        
        $errors = array(
            self::T_CREATE_ERROR__UNEXPECTED_INPUT => $EXPECTED !== array_keys($input),
            self::T_CREATE_ERROR__INVALID_RACE     => !in_array((int) $input['f_race_id'], array_keys($raceididx)),
            self::T_CREATE_ERROR__INVALID_COACH    => !get_alt_col('coaches', 'coach_id', (int) $input['owned_by_coach_id'], 'coach_id'),
            self::T_CREATE_ERROR__INVALID_NAME     => get_alt_col('teams', 'name', mysql_real_escape_string($input['name']), 'team_id') || empty($input['name']),
            self::T_CREATE_ERROR__INVALID_LEAGUE   => get_alt_col('coaches', 'coach_id', (int) $input['owned_by_coach_id'], 'ring') != Coach::T_RING_GLOBAL_ADMIN && 0 == (int) SQLFetchField("SELECT COUNT(*) FROM memberships WHERE lid = ".((int) $input['f_lid'])." AND cid = ".((int) $input['owned_by_coach_id'])." AND ring >= ".Coach::T_RING_LOCAL_REGULAR),
            self::T_CREATE_ERROR__INVALID_DIVISION => $input['f_did'] != self::T_NO_DIVISION_TIE && $input['f_lid'] != get_alt_col('divisions', 'did', (int) $input['f_did'], 'f_lid'),
        );
        foreach ($errors as $exitStatus => $halt) {
            if ($halt) return array($exitStatus, null);
        }
            
        $input['name'] = "'".mysql_real_escape_string($input['name'])."'"; # Need to quote strings when using INSERT statement.

        $query = "INSERT INTO teams (".implode(',',$EXPECTED).") VALUES (".implode(',', $input).")";
        if (mysql_query($query))
            $tid = mysql_insert_id();
        else {
            self::$T_CREATE_SQL_ERROR['query'] = $query;
            self::$T_CREATE_SQL_ERROR['error'] = mysql_error();
            return array(self::T_CREATE_ERROR__SQL_QUERY_FAIL, null);
        }
        
        SQLTriggers::run(T_SQLTRIG_TEAM_NEW, array('id' => $tid, 'obj' => new self($tid)));
        
        return array(self::T_CREATE_SUCCESS, $tid);
    }
}

