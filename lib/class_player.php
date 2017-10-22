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
 
// Player number restrictions.
define('T_MAX_PLAYER_NR', 100);
$T_RESERVED_PLAYER_NR = array();
$T_ALLOWED_PLAYER_NR = array_diff(range(1,T_MAX_PLAYER_NR), $T_RESERVED_PLAYER_NR); # These are the non-reserved player numbers allowed by regular players to use.
$T_ALL_PLAYER_NR = range(1,T_MAX_PLAYER_NR); # = array_merge($T_ALLOWED_PLAYER_NR, $T_RESERVED_PLAYER_NR)

// Stars and mercenaries.
define('ID_MERCS',       -1); // Mercenaries player_id.
define('ID_STARS_BEGIN', -5); // First star's player_id, second id is one smaller and so on.

// Player types.
define('PLAYER_TYPE_NORMAL',  1);
define('PLAYER_TYPE_JOURNEY', 2);

$skillcats = array(
    'N' => array('DEA_idx' => 'norm', 'obj_idx' => 'ach_nor_skills'), 
    'D' => array('DEA_idx' => 'doub', 'obj_idx' => 'ach_dob_skills'), 
    'E' => array('DEA_idx' => null,   'obj_idx' => 'extra_skills'),
);

$CHR_CONV = array(MA => 'ma', AG => 'ag', AV => 'av', ST => 'st');

class Player
{
    /***************
     * Properties 
     ***************/

    // MySQL stored information
    public $player_id = 0;
    public $type = 1;
    public $name = '';
    public $owned_by_team_id = 0;
    public $nr = 0;
    public $f_pos_id = 0;
    public $position = ''; public $pos = ''; // $position duplicate. $position may be edited for display purposes (=not actual position string used in $DEA). This is though.
    public $date_bought = '';
    public $date_sold   = '';
    public $ach_ma = 0;
    public $ach_st = 0;
    public $ach_ag = 0;
    public $ach_av = 0;
    public $ach_nor_skills = array();
    public $ach_dob_skills = array();
    public $extra_skills   = array();
    public $current_skills   = array();
    public $extra_spp = 0;
    public $extra_val = 0;
    public $may_buy_new_skill = 0;
    
    public $value = 0;
    public $date_died = '';

    // Characteristics
    public $ma = 0;
    public $ag = 0;
    public $av = 0;
    public $st = 0;
    
    // Unadjusted Characteristics (adjusted limits are the 1/10 limit and the def +/- 2 limit)
    public $ma_ua = 0;
    public $ag_ua = 0;
    public $av_ua = 0;
    public $st_ua = 0;

    // Base characteristics
    public $def_ma = 0;
    public $def_av = 0;
    public $def_ag = 0;
    public $def_st = 0;
    public $def_skills = array();

    // Injuries
    public $inj_ma = 0;
    public $inj_st = 0;
    public $inj_ag = 0;
    public $inj_av = 0;
    public $inj_ni = 0;

    // Player status
    public $is_sold             = false;
    public $is_dead             = false;
    public $is_mng              = false;
    public $is_journeyman       = false;
    public $is_used_journeyman  = false;

    // Others
    public $icon = "";
    public $qty = 0;
    public $choosable_skills = array('norm' => array(), 'doub' => array(), 'chr' => array());
    
    // Relations
    public $f_tname = "";
    public $f_cid = 0;
    public $f_cname = "";
    public $f_rid = 0;
    public $f_rname = "";
        
    /***************
     * Methods 
     ***************/

    function __construct($player_id) {

        global $DEA;

        // Get relaveant store game data.
        $result = mysql_query("SELECT player_id,
            game_data_players.qty AS 'qty', game_data_players.pos AS 'pos', game_data_players.skills AS 'def_skills', 
            game_data_players.ma AS 'def_ma', game_data_players.st AS 'def_st', game_data_players.ag AS 'def_ag', game_data_players.av AS 'def_av'
            FROM players, game_data_players WHERE player_id = $player_id AND f_pos_id = pos_id");
        foreach (mysql_fetch_assoc($result) as $col => $val) {
            $this->$col = ($val) ? $val : 0;
        }
        $this->position = $this->pos;
        
        /* 
            Set general stats.
        */

        $this->setStats(false,false,false);
        
        $this->def_skills = empty($this->def_skills) ? array() : explode(',', $this->def_skills);
        $this->setSkills();
        
        $this->is_dead              = ($this->status == DEAD);
        $this->is_mng               = !in_array($this->status, array(NONE, DEAD));
        $this->is_sold              = (bool) $this->date_sold;
        $this->is_journeyman        = ($this->type == PLAYER_TYPE_JOURNEY);
        $this->is_journeyman_used   = ($this->type == PLAYER_TYPE_JOURNEY) && ($this->mv_played > 0);
        
        /*
            Misc
        */
        
        $this->icon = PLAYER_ICONS.'/' . $DEA[$this->f_rname]['players'][$this->pos]['icon'] . '.gif';
        
        if (empty($this->name)) {
            $this->name = 'Unnamed';
        }
        
        if ($this->type == PLAYER_TYPE_JOURNEY) { # Check if player is journeyman like this - don't assume setStatusses() has ben called setting $this->is_journeyman.
            $this->position .= ' [J]';
            $this->def_skills[] = 99; # Loner.
        }
        
        $this->current_skills = $this->getSkillsStr(true);
        $this->may_buy_new_skill = $this->mayHaveNewSkill();
        $this->setChoosableSkills();
    }
    
    public function setStats($node, $node_id, $set_avg = false)
    {
        foreach (Stats::getAllStats(T_OBJ_PLAYER, $this->player_id, $node, $node_id, $set_avg) as $key => $val) {
            $this->$key = $val;
        }
        
        return true;
    }
    
    public function setSkills() {
        global $skillcats;
        foreach ($skillcats as $t => $grp) {
            $result = mysql_query("SELECT GROUP_CONCAT(f_skill_id) FROM players_skills WHERE f_pid = $this->player_id AND type = '$t'");
            $row = mysql_fetch_row($result);
            $this->{$grp['obj_idx']} = empty($row[0]) ? array() : explode(',', $row[0]);
        }
    }
    
    public function setChoosableSkills() {

        global $DEA, $skillarray, $skillcats, $IllegalSkillCombinations, $rules;
        # Var. format: "$IllegalSkillCombinations as $hasSkill => $dropSkills"
        
        $this->setSkills();
        $current_skills = array_merge($this->def_skills, $this->extra_skills, $this->ach_nor_skills, $this->ach_dob_skills);
        $illegal_skills_arr = array_intersect_key($IllegalSkillCombinations, array_flip($current_skills)); # Array of arrays of illegal skills.
        $illegal_skills = array();
        # Flatten $illegal_skills_arr.
        foreach ($illegal_skills_arr as $hasSkill => $dropSkills) {
            $illegal_skills = array_merge($illegal_skills, $dropSkills);
        }
        
        // Initial population of allowed skills (those not already picked).
        foreach (array('N', 'D') as $type) {
            $stype_DEA_idx = $skillcats[$type]['DEA_idx'];
            foreach ($DEA[$this->f_rname]['players'][$this->pos][$stype_DEA_idx] as $category) {
                $this->choosable_skills[$stype_DEA_idx] = array_merge(
                    $this->choosable_skills[$stype_DEA_idx], # self
                    array_diff(array_keys($skillarray[$category]), $current_skills, $illegal_skills) # Filter away skills we already have and illegal due to skills we already have.
                );
            }
        }
        $this->choosable_skills['chr'] = array(MA,AG,AV,ST);
        
        // Now remove those skills not allowed by the improvement roll the player made.
        $N_allowed_new_skills = $this->mayHaveNewSkill();
        $query = "SELECT 
            ir1_d1 AS 'D11', ir1_d2 AS 'D12',
            ir2_d1 AS 'D21', ir2_d2 AS 'D22',
            ir3_d1 AS 'D31', ir3_d2 AS 'D32'
        FROM match_data, matches WHERE f_match_id = match_id AND f_player_id = $this->player_id AND (ir1_d1 != 0 OR ir1_d2 != 0 OR ir2_d1 != 0 OR ir2_d2 != 0 OR ir3_d1 != 0 OR ir3_d2 != 0) ORDER BY date_played DESC LIMIT $N_allowed_new_skills";
        $result = mysql_query($query);
        $IRs = array();
        while ($D6s = mysql_fetch_assoc($result)) {
            foreach (range(1,3) as $i) {
                if ($D6s["D${i}1"]+$D6s["D${i}2"] > 0) {
                    $IRs[] = array($D6s["D${i}1"], $D6s["D${i}2"]);
                    if (count($IRs) >= $N_allowed_new_skills) {
                        break 2;
                    }
                }
            }
        }
        $allowed = $NONE_ALLOWED = array('N' => false, 'D' => false, 'C' => array());
        foreach (array_reverse($IRs) as $IR) {
            list($D1,$D2) = $IR;
            switch ($D1+$D2) {
                case 12: $chr = array(ST); break;
                case 11: $chr = array(AG); break;
                case 10: $chr = array(MA,AV); break;
                default: $chr = array(); break;
            }
            $allowed['C'] = array_unique(array_merge($allowed['C'], $chr));
            $allowed['N'] = true; # May always select a new Normal skill when rolled no matter the outcome.
            $allowed['D'] |= ($D1 == $D2); # May select from Double skills when D6s are equal.
            /* 
                Normally we allow coaches to selected amongst all player skills the available/new improvement rolls allow, but 
                instead we now limit the player to select ONE skill at a time for a given improvement roll (in chronological order).
            */
            break;
        }
        
        /* 
            If a player has SPPs enough for a new skill but has NOT (ever) improvement rolled 2xD6 according to match_data entries, 
            then allow player to select amongst all possible skills.
        */
        if ($N_allowed_new_skills > 0 && (count($IRs) > 0 || $rules['force_IR'])) {
            if (!$allowed['N']) {$this->choosable_skills['norm'] = array();}
            if (!$allowed['D']) {$this->choosable_skills['doub'] = array();}
            $this->choosable_skills['chr'] = array();
            foreach ($allowed['C'] as $chr) {
                if ($this->chrLimits('ach', $chr)) {
                    $this->choosable_skills['chr'][] = $chr;
                }
            }
        }
        
        return !($allowed === $NONE_ALLOWED);
    }
    
    public function mayHaveNewSkill() {

        global $sparray;
        
        $this->setSkills();
        
        $skill_count =   count($this->ach_nor_skills)
                       + count($this->ach_dob_skills)
                       + $this->ach_ma
                       + $this->ach_st
                       + $this->ach_ag
                       + $this->ach_av;
                       
        $allowable_skills = 0; # Allowable skills = player level = SPR

        foreach (array_reverse($sparray) as $rank => $details) { # Loop through $sparray reversed so highest ranks come first.
            if ($this->mv_spp >= $details['SPP']) {
                $allowable_skills = $details['SPR'];
                break;
            }
        }
        
        # Returns the NUMBER of skills/chrs the player may take.
        $skill_diff = $allowable_skills - $skill_count;
        return ($this->is_sold || $this->is_dead || $skill_diff < 0) ? 0 : $skill_diff;
    }

    public function is_unbuyable() {
        // Is able to be un-bought, does not mean that player is not buyable!
        // If the player has NOT participated in any matches then player is un-buyable.
        $query = "SELECT COUNT(*) AS 'cnt' FROM match_data WHERE f_player_id = $this->player_id";
        return !(($result = mysql_query($query)) && ($row = mysql_fetch_assoc($result)) && $row['cnt'] > 0);
    }

    public function sell() {
    
        /**
         * Sell player.
         **/
    
        global $rules;
        $lid = get_alt_col('teams', 'team_id', $this->owned_by_team_id, 'f_lid');
        setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => (int) $lid)); // Load correct $rules for league.
    
        if ($this->is_sold || $this->is_dead)
            return false;

        $team = new Team($this->owned_by_team_id);
        $val = $this->is_journeyman ? 0 : $this->value;
        
        if (!$team->dtreasury($val * $rules['player_refund']))
            return false;

        if (!mysql_query("UPDATE players SET date_sold = NOW() WHERE player_id = $this->player_id"))
            return false;

        $this->is_sold = true;
        SQLTriggers::run(T_SQLTRIG_PLAYER_DPROPS, array('id' => $this->player_id, 'obj' => $this)); # Update PV and TV.
        return true;
    }

    public function unsell() {

        /**
         * Regret selling player (un-sell).
         **/

        global $rules;
        $lid = get_alt_col('teams', 'team_id̈́', $this->owned_by_team_id, 'f_lid');
        setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => (int) $lid)); // Load correct $rules for league.

        if (!$this->is_sold || $this->is_dead)
            return false;
            
        $team = new Team($this->owned_by_team_id);
        $val = $this->is_journeyman ? 0 : $this->value;
        
        if (!$team->dtreasury(-1 * $val * $rules['player_refund']))
            return false;

        if (!mysql_query("UPDATE players SET date_sold = NULL WHERE player_id = $this->player_id"))
            return false;

        $this->is_sold = false;
        SQLTriggers::run(T_SQLTRIG_PLAYER_DPROPS, array('id' => $this->player_id, 'obj' => $this)); # Update PV and TV.
        return true;        
    }

    public function unbuy() { # "Un-create"
    
        /**
         * Regret hirering/purchasing player (un-buy).
         **/
    
        if (!$this->is_unbuyable() || $this->is_sold)
            return false;
            
        $price = ($this->is_journeyman) ? 0 : self::price($this->f_pos_id);
        $team = new Team($this->owned_by_team_id);

        if (!$team->dtreasury($price))
            return false;

        if (!mysql_query("DELETE FROM players WHERE player_id = $this->player_id"))
            return false;
    
        SQLTriggers::run(T_SQLTRIG_PLAYER_DPROPS, array('id' => $this->player_id, 'obj' => $this)); # Update PV and TV.
        return true;
    }
    
    public function hireJourneyman() {

        /**
         * Permanently hire journeymen.
         **/

        if (!$this->is_journeyman || $this->is_sold || $this->is_dead)
            return false;
            
        $team = new Team($this->owned_by_team_id);
        $price = self::price($this->f_pos_id);
        
        if ($team->isFull() || !$team->isPlayerBuyable($this->f_pos_id) || $team->treasury < $price || !$team->dtreasury(-1 * $price))
            return false;

        $query = "UPDATE players SET type = ".PLAYER_TYPE_NORMAL." WHERE player_id = $this->player_id";
        
        if (mysql_query($query)) {
            return true;
        }
        // Return money.
        else {
            $team->dtreasury($price);
            return false;
        }
    }

    public function unhireJourneyman() {

        /**
         * Regret permanently hiring journeymen.
         **/

        if ($this->is_journeyman || $this->is_sold || $this->is_dead)
            return false;

        global $DEA;

        $team = new Team($this->owned_by_team_id);
        $price = self::price($this->f_pos_id);

        if ($this->qty != 16) # Journeymen are players from a 0-16 buyable position.
            return false;
       
        if (!$team->dtreasury($price))
            return false;

        $query = "UPDATE players SET type = ".PLAYER_TYPE_JOURNEY." WHERE player_id = $this->player_id";
        
        if (mysql_query($query)) {
            return true;
        }
        // Pull back money.
        else {
            $team->dtreasury(-1 * $price);
            return false;
        }        
    }
    
    public function removeNiggle() {
        if ($this->is_journeyman || $this->is_sold || $this->is_dead)
            return false;

        $query = "UPDATE players SET inj_ni = GREATEST(inj_ni - 1, 0) WHERE player_id = $this->player_id";
        
        return mysql_query($query);
    }

    public function rename($new_name) {
        return mysql_query("UPDATE players SET name = '" . mysql_real_escape_string($new_name) . "' WHERE player_id = $this->player_id");
    }
    
    public function renumber($number) {
        global $T_ALLOWED_PLAYER_NR;
        return (in_array($number, $T_ALLOWED_PLAYER_NR) && mysql_query("UPDATE players SET nr = $number WHERE player_id = $this->player_id"));
    }

    public function dspp($delta) {
        $query = "UPDATE players SET extra_spp = IF(extra_spp IS NULL, $delta, extra_spp + ($delta)) WHERE player_id = $this->player_id";
        return mysql_query($query);
    }

    public function dval($val = 0) {
        $query = "UPDATE players SET extra_val = $val WHERE player_id = $this->player_id";
        return mysql_query($query) && SQLTriggers::run(T_SQLTRIG_PLAYER_DPROPS, array('id' => $this->player_id, 'obj' => $this)); # Update PV and TV.
    }

    public function addSkill($type, $skill) {
    
        /**
         * Add new player skill.
         *
         *  $type may be:
         *  ------------- 
         *  "N" = Normal skill
         *  "D" = Double skill
         *  "E" = Extra skill
         *  "C" = Characteristics
         **/

        global $DEA, $skillididx, $skillcats, $CHR_CONV;
        
        $this->setSkills();        
        $this->setChoosableSkills();

        // Don't allow new skill if not enough SPP, unless it is an extra skill.
        if ($type != 'E' && !$this->mayHaveNewSkill())
            return false;

        // Statuses
        $IS_REGULAR = (in_array($type, array('N', 'D')) && in_array($skill, $this->choosable_skills[$skillcats[$type]['DEA_idx']]));
        $IS_EXTRA   = ($type == 'E' && in_array($skill, array_keys($skillididx)));

        // Determine skill type.
        $query = '';
        if ($type == "C" && in_array($skill, $this->choosable_skills['chr'])) {
            $fname = $CHR_CONV[$skill];
            $query = "UPDATE players SET ach_$fname = ach_$fname + 1 WHERE player_id = $this->player_id";
        }
        elseif ($IS_REGULAR || $IS_EXTRA) {
            $this->{$skillcats[$type]['obj_idx']}[] = $skill;
            $query = "INSERT INTO players_skills(f_pid, f_skill_id, type) VALUES ($this->player_id, $skill, '$type')";
        }

        return mysql_query($query) && SQLTriggers::run(T_SQLTRIG_PLAYER_DPROPS, array('id' => $this->player_id, 'obj' => $this)); # Update PV and TV.
    }

    public function rmSkill($type, $skill) {
        
        /**
         * Remove existing player skill.
         **/
         
        global $skillcats, $CHR_CONV;

        $query = '';
        if (in_array($type, array_keys($skillcats))) {
            $query = "DELETE FROM players_skills WHERE f_pid = $this->player_id AND type = '$type' AND f_skill_id = $skill";
        }
        elseif ($type == 'C') {
            $fname = $CHR_CONV[$skill];
            $query = '';
            if ($this->{"ach_$fname"} > 0) {
                #echo "yes! -- ".$this->{"ach_$fname"}; # DEBUG
                $query = "UPDATE players SET ach_$fname = ach_$fname - 1 WHERE player_id = $this->player_id";
            }
        }

        return mysql_query($query) && SQLTriggers::run(T_SQLTRIG_PLAYER_DPROPS, array('id' => $this->player_id, 'obj' => $this)); # Update PV and TV.
    }
    
    public function getStatus($match_id) {
        return self::getPlayerStatus($this->player_id, $match_id);
    }
    
    public function chrLimits($type, $char) {

        /**
         * Characteristics limit handler. Returns the number of characteristic injuries/achievements the player is further allowed.
         **/

        global $CHR_CONV;
        $char = $CHR_CONV[$char];
        $def = 'def_'.$char; # Default characteristic value.
        $ret = 0;

        if ($type == 'ach') {
            
            /* 
                Returns the number of increased/archived characteristics the player is allowed.
                Limits:
                    - Default + 2
                    - Max 10
            */

            if ($this->$def < 9)
                $ret = $this->$def + 2 - $this->$char;
            else
                $ret = 10 - $this->$char;                
        }
        elseif ($type == 'inj') {
            
            /* 
                Returns the number of characteristic injuries the player may sustain.
                Limits:
                    - Default - 2
                    - Min 1
            */
            
            if ($this->$def > 2)
                $ret = $this->$char - ($this->$def - 2);
            else
                $ret = $this->$char - 1;
        }
        
        return ($ret >= 0) ? $ret : 0; // Make sure we always get zero when no more injuries/ach. chars may be sustained/obtained.
    }
    
    public function getMatchMost($field) {
        
        /**
         * Returns an array structure with match data (and match obj.), for those matches, where $this player has the most of $field, 
         * compared to all other player in the same match.
         **/
        
        $matches = array();

        $matchesPlayed = "(SELECT DISTINCT f_match_id AS 'mid' FROM match_data WHERE f_player_id = $this->player_id) AS matchesPlayed";
        $max = "(SELECT f_match_id AS 'mid', MAX($field) AS 'maxVal' FROM match_data, $matchesPlayed WHERE f_match_id = mid GROUP BY f_match_id) AS max";
        $cntMax = "(SELECT f_match_id AS 'mid', COUNT(*) AS 'cnt', maxVal FROM match_data, $max WHERE f_match_id = mid AND ($field) = maxVal GROUP BY f_match_id) cntMax";
        $query = "
            SELECT 
                *
            FROM 
                match_data, $cntMax 
            WHERE 
                    f_match_id = mid 
                AND f_player_id = $this->player_id
                AND ($field) = maxVal 
                AND cnt = 1
        ";

        if (($result = mysql_query($query)) && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($matches, array_merge(array('match_obj' => new Match($row['f_match_id'])), $row));
            }
        }
        
        return $matches;
    }

    public function getAchEntries($type) {

        /**
         * Returns an array structure with match data (and match obj.), for those matches, where $this player has an achivement of type $type.
         **/

        $mdata = array();

        $query = "SELECT mvp, cp, td, intcpt, bh, ki, si, f_match_id FROM match_data, matches WHERE match_id > 0 AND f_match_id = match_id AND f_player_id = $this->player_id AND ($type) > 0 ORDER BY date_played DESC";
        if (($result = mysql_query($query)) && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($mdata, array_merge($row, array('match_obj' => new Match($row['f_match_id']))));
            }
        }
        
        return $mdata;
    }
    
    public function saveText($str) {
        $desc = new ObjDescriptions(T_TEXT_PLAYER, $this->player_id);
        return $desc->save($str);
    }

    public function getText() {
        $desc = new ObjDescriptions(T_TEXT_PLAYER, $this->player_id);
        return $desc->txt;
    }
    
    public function savePic($name = false) {
        $img = new ImageSubSys(IMGTYPE_PLAYER, $this->player_id);
        list($retstatus, $error) = $img->save($name);
        return $retstatus;
    }
    
    public function deletePic() {
        $img = new ImageSubSys(IMGTYPE_PLAYER, $this->player_id);
        return $img->delete();    
    }
    
    public function getSkillsStr($HTML = false) 
    {
        /**
         * Compiles skills string.
         **/
    
        $this->setSkills();
        $chrs = array();
        $extras = empty($this->extra_skills) ? array() : array_strpack(($HTML) ? '<u>%s</u>' : '%s*', skillsTrans($this->extra_skills));

        if ($this->ach_ma > 0) array_push($chrs, "+$this->ach_ma Ma");
        if ($this->ach_st > 0) array_push($chrs, "+$this->ach_st St");
        if ($this->ach_ag > 0) array_push($chrs, "+$this->ach_ag Ag");
        if ($this->ach_av > 0) array_push($chrs, "+$this->ach_av Av");

        $defs = skillsTrans($this->def_skills);
        if ($HTML) { $defs = array_strpack('<i>%s</i>', $defs); }
        $skillstr = array_merge($defs, skillsTrans(array_merge($this->ach_nor_skills, $this->ach_dob_skills)));
        return implode(', ', array_merge($skillstr, $extras, $chrs));
    }
    
    public function getInjsStr($HTML = false) 
    {
        /**
         * Compiles injuries string.
         **/
    
        $injs = array();
        
        if ($this->inj_ma > 0) array_push($injs, "-$this->inj_ma Ma");
        if ($this->inj_st > 0) array_push($injs, "-$this->inj_st St");
        if ($this->inj_ag > 0) array_push($injs, "-$this->inj_ag Ag");
        if ($this->inj_av > 0) array_push($injs, "-$this->inj_av Av");
        if ($HTML) {
            if ($this->inj_ni > 0) array_push($injs, "<font color='red'>$this->inj_ni Ni</font>");
        }
        else {
            if ($this->inj_ni > 0) array_push($injs, "$this->inj_ni Ni");
        }
        if ($this->is_mng)     array_push($injs, "MNG");
        
        return implode(', ', $injs);
    }
    
    private function _getInjHistory() 
    {
        $injs = array();
        $stats = array();
        $query = "SELECT inj, agn1, agn2, f_match_id AS 'mid',  mvp, cp, td, intcpt, bh, si, ki FROM match_data, matches WHERE f_match_id = match_id AND f_player_id = $this->player_id AND (inj != ".NONE." OR agn1 != ".NONE." OR agn2 != ".NONE.") ORDER BY date_played DESC";
        if (($result = mysql_query($query)) && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $stats[] = $row;
                $tmp = array();
                foreach (array('inj', 'agn1', 'agn2') as $inj) {
                    if ($row[$inj] != NONE) {
                        array_push($tmp, $row[$inj]);
                    }
                }
                $injs[] = $tmp;
            }
        }
        return array($injs, $stats);
    }
    public function getInjHistory() 
    {
        # This method wraps _getInjHistory() with extra information.
        list($injhist, $stats) = $this->_getInjHistory();
        $match_objs = array();
        foreach ($stats as $k => $v) {
            $match_objs[] = new Match($v['mid']);
        }
        return array($injhist, $stats, $match_objs);
    }
    
    /***************
     * Statics
     ***************/
     
    public static function exists($id) 
    {
        $result = mysql_query("SELECT COUNT(*) FROM players WHERE player_id = $id");
        list($CNT) = mysql_fetch_row($result);
        return ($CNT == 1);
    }

    public static function getPlayerStatus($player_id, $match_id) {

        /**
         * Returns player status for specific $match_id, or current status if $match_id == -1 (latest match).
         **/

        $query = "SELECT getPlayerStatus($player_id,$match_id) AS 'inj'";

        // Determine what status is.
        $result = mysql_query($query);
        if (is_resource($result) && mysql_num_rows($result) > 0) {
            $row = mysql_fetch_assoc($result);
            switch ($row['inj'])
            {
                case NONE: return NONE;
                case DEAD: return DEAD;
                default:   return MNG;
            }
        }
        else {
            return NONE;
        }
    }

    public static function price($pos_id) {
    
        /**
         * Get the price of a specific player.
         **/
        
        $result = mysql_query("SELECT cost FROM game_data_players WHERE pos_id = $pos_id");
        $row = mysql_fetch_row($result);
        return (int) $row[0];
    }
    
    const T_CREATE_SUCCESS = 0;
    const T_CREATE_ERROR__SQL_QUERY_FAIL = 1;
    const T_CREATE_ERROR__UNEXPECTED_INPUT = 2;
    const T_CREATE_ERROR__INVALID_TEAM = 3;
    const T_CREATE_ERROR__TEAM_FULL = 4;
    const T_CREATE_ERROR__INVALID_POS = 5;
    const T_CREATE_ERROR__POS_LIMIT_REACHED = 6;
    const T_CREATE_ERROR__INSUFFICIENT_FUNDS = 7;
    const T_CREATE_ERROR__INVALID_NUMBER = 8; # Player number.
    const T_CREATE_ERROR__NUMBER_OCCUPIED = 9;
    const T_CREATE_ERROR__JM_LIMIT_REACHED = 10;
    const T_CREATE_ERROR__INVALID_JM_POS = 11;
    

    public static $T_CREATE_ERROR_MSGS = array(
        self::T_CREATE_ERROR__SQL_QUERY_FAIL     => 'SQL query failed.',
        self::T_CREATE_ERROR__UNEXPECTED_INPUT   => 'Unexpected input.',
        self::T_CREATE_ERROR__INVALID_TEAM       => 'Illegal/invalid parent team ID.',
        self::T_CREATE_ERROR__TEAM_FULL          => 'Team is full.',
        self::T_CREATE_ERROR__INVALID_POS        => 'Illegal/invalid player position for parent team race.',
        self::T_CREATE_ERROR__POS_LIMIT_REACHED  => 'Maximum quantity of player position is reached.',
        self::T_CREATE_ERROR__INSUFFICIENT_FUNDS => 'Not enough gold.',
        self::T_CREATE_ERROR__INVALID_NUMBER     => 'The chosen player number is not within the allowed range.',
        self::T_CREATE_ERROR__NUMBER_OCCUPIED    => 'The chosen player number is already occupied by an active player.',
        self::T_CREATE_ERROR__JM_LIMIT_REACHED   => 'Journeymen limit is reached.',
        self::T_CREATE_ERROR__INVALID_JM_POS     => 'May not make a journeyman from that player position.',
    );
    
    public static $T_CREATE_SQL_ERROR = array(
        'query' => null, # mysql fail query.
        'error' => null, # mysql_error()
    );
    
    // Required passed fields (input) to create().
    public static $createEXPECTED = array(
        'name','team_id','nr','f_pos_id',
    );
    
    public static function create(array $input, array $opts) {

        /**
         * Creates a new player.
         *
         * Input: nr, f_pos_id, name, team_id
         **/

        global $rules, $DEA, $T_ALL_PLAYER_NR;
        $lid = get_alt_col('teams', 'team_id', $input['team_id'], 'f_lid');
        setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => (int) $lid)); // Load correct $rules for league.

        // Do these fixes because we can't define class statics using string interpolation for $rules.
        self::$T_CREATE_ERROR_MSGS[self::T_CREATE_ERROR__TEAM_FULL] .= " You have filled all $rules[max_team_players] available positions.";
        self::$T_CREATE_ERROR_MSGS[self::T_CREATE_ERROR__JM_LIMIT_REACHED] .= " Your team is now able to fill $rules[journeymen_limit] positions.";

        $JM = isset($opts['JM']) && $opts['JM'];
        $FREE = isset($opts['free']) && $opts['free'];
        $FORCE = isset($opts['force']) && $opts['force'];
        
        # When forcing ($FORCE is true) we ignore these errors:
        $ignoreableErrors = array(
            self::T_CREATE_ERROR__TEAM_FULL, self::T_CREATE_ERROR__POS_LIMIT_REACHED, self::T_CREATE_ERROR__INSUFFICIENT_FUNDS, 
            self::T_CREATE_ERROR__NUMBER_OCCUPIED, self::T_CREATE_ERROR__JM_LIMIT_REACHED, self::T_CREATE_ERROR__INVALID_JM_POS,
        );

        $EXPECTED = self::$createEXPECTED;
        sort($EXPECTED);
        ksort($input);

        // Input error handler
        if (!get_alt_col('teams', 'team_id', (int) $input['team_id'], 'team_id'))
            return array(self::T_CREATE_ERROR__INVALID_TEAM, null);
        else
            $team = new Team((int) $input['team_id']);

        $errors = array(
            self::T_CREATE_ERROR__UNEXPECTED_INPUT   => $EXPECTED !== array_keys($input),
            self::T_CREATE_ERROR__TEAM_FULL          => !$JM && $team->isFull(),
            self::T_CREATE_ERROR__INVALID_POS        => !$team->isPlayerPosValid((int) $input['f_pos_id']),
            self::T_CREATE_ERROR__POS_LIMIT_REACHED  => !$team->isPlayerBuyable((int) $input['f_pos_id']),
            self::T_CREATE_ERROR__INSUFFICIENT_FUNDS => $team->treasury - ($price = ($JM || $FREE) ? 0 : self::price((int) $input['f_pos_id'])) < 0,
            self::T_CREATE_ERROR__INVALID_NUMBER     => !in_array($input['nr'], $T_ALL_PLAYER_NR),
            self::T_CREATE_ERROR__NUMBER_OCCUPIED    => $team->isPlayerNumberOccupied((int) $input['nr']),
            self::T_CREATE_ERROR__JM_LIMIT_REACHED   => $JM && $team->isJMLimitReached(),
            // Is position valid to make a journeyman? 
            // Journeymen may be made from those positions, from which 16 players of the position is allowed on a team.
            self::T_CREATE_ERROR__INVALID_JM_POS     => $JM && $DEA[$team->f_rname]['players'][get_alt_col('game_data_players', 'pos_id', (int) $input['f_pos_id'], 'pos')]['qty'] < 12,
        );
        foreach ($errors as $exitStatus => $halt) {
            if ($halt && !($FORCE && in_array($exitStatus, $ignoreableErrors))) return array($exitStatus, null);
        }

        $input['owned_by_team_id'] = (int) $input['team_id']; unset($input['team_id']);
        $input['name'] = "'".mysql_real_escape_string($input['name'])."'"; 
        $input['date_bought'] = 'NOW()';
        $input['type'] = $JM ? PLAYER_TYPE_JOURNEY : PLAYER_TYPE_NORMAL;
        foreach (array('ach_ma', 'ach_st', 'ach_ag', 'ach_av', 'extra_spp') as $f) {$input[$f] = 0;}

        $query = "INSERT INTO players (".implode(',',array_keys($input)).") VALUES (".implode(',', array_values($input)).")";
        if (mysql_query($query)) {
            $pid = mysql_insert_id();
            $team->dtreasury(-1 * $price);
        }
        else {
            self::$T_CREATE_SQL_ERROR['query'] = $query;
            self::$T_CREATE_SQL_ERROR['error'] = mysql_error();
            return array(self::T_CREATE_ERROR__SQL_QUERY_FAIL, null);
        }

        SQLTriggers::run(T_SQLTRIG_PLAYER_NEW, array('id' => $pid, 'obj' => (object) array('player_id' => $pid, 'owned_by_team_id' => (int) $input['owned_by_team_id']))); # Update PV and TV.
        
        return array(self::T_CREATE_SUCCESS, $pid);
    }
}

