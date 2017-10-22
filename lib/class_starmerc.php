<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2008-2010. All Rights Reserved.
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

class Star 
{
    /***************
     * Attributes
     ***************/

    // General
    public $star_id = 0;
    public $icon    = '';
    public $skills = array();
    
    /***************
     * Methods
     ***************/

    public function __construct($sid)
    {
        /* 
            Creates a star object with up-to-date fields/stats reflecting the star's participation in every match.
        */

        $this->star_id = $sid;
        $this->setStats(false, false, false);
        global $stars;
        $this->icon = PLAYER_ICONS.'/'.$stars[$this->name]['icon'].'.png';
    }
    
    public function setStats($node, $node_id, $setAvg = false)
    {
        foreach (Stats::getAllStats(T_OBJ_STAR, $this->star_id, $node, $node_id, $setAvg) as $key => $val) {
            $this->$key = $val;
        }
    }
    
    public function getStats($type, $type_id) 
    {
        global $STATS_TRANS;
        $fields = array('cp','td','intcpt','mvp','bh+ki+si', 'bh','si','ki');
        $query = "SELECT ".implode(',', array_map(create_function('$f', 'return "SUM($f) AS \'$f\'";'), $fields))." FROM match_data WHERE f_player_id = $this->star_id AND ".$STATS_TRANS[$type].'='.$type_id;
        $result = mysql_query($query);
        $ret = array();
        foreach (mysql_fetch_assoc($result) as $col => $val) {
            $ret[$col] = ($val) ? $val : 0;
        }
        $ret['cas'] = $ret['bh+ki+si'];
        unset($ret['bh+ki+si']);
        return $ret;
    }
    
    public function setSkills($makeString = false)
    {
        $query = "SELECT skills FROM game_data_stars WHERE star_id = $this->star_id";
        $result = mysql_query($query);
        list($skillsstr) = mysql_fetch_row($result);
        $this->skills = ($makeString) ? skillsTrans($skillsstr) : (empty($skillsstr) ? array() : explode(',', $skillsstr));
    }
    
    public function getHireHistory($obj, $obj_id, $node, $node_id)
    {
        /* 
            Returns an array of match objects for those matches which this star has participated in.
            New "fake" match fields are created for each match object with respect to this star object:
                - hiredBy           (team id)
                - hiredAgainst      (team id)
                - hiredByName       (team name)
                - hiredAgainstName  (team name)
        */
        
        global $STATS_TRANS;
        $matches = array();
        
        $query = "SELECT DISTINCT f_match_id, f_team_id FROM match_data, matches WHERE 
            f_match_id = match_id 
            AND f_player_id = $this->star_id 
                ".(($obj)  ? ' AND '.$STATS_TRANS[$obj]. " = $obj_id "  : '').'
                '.(($node) ? ' AND '.$STATS_TRANS[$node]." = $node_id " : '').'
            ORDER BY date_played DESC LIMIT '.MAX_RECENT_GAMES;
            
        if (($result = mysql_query($query)) && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $m = new Match($row['f_match_id']);
                // Make fake match fields for this star's values.
                $m->hiredBy          = $row['f_team_id'];
                $m->hiredAgainst     = ($m->team1_id == $m->hiredBy) ? $m->team2_id : $m->team1_id;
                $m->hiredByName      = get_alt_col('teams', 'team_id', $m->hiredBy, 'name');
                $m->hiredAgainstName = get_alt_col('teams', 'team_id', $m->hiredAgainst, 'name');
                array_push($matches, $m);
            }
        }
        
        return $matches;
    }
    
    public function rmMatchEntry($match_id, $team_id = false)
    {
        /*
            Deletes a star's entry in a match.
        */
        # DO NOT CHANGE THE ORDER OF EXPRESSIONS!
        $result = mysql_query("SELECT * FROM match_data WHERE f_player_id = $this->star_id AND f_match_id = $match_id");
        $CNT = mysql_num_rows($result);
        $query = "DELETE FROM match_data WHERE f_player_id = $this->star_id AND f_match_id = $match_id" . (($team_id) ? " AND f_team_id = $team_id" : '');
        mysql_query($query);
        if ($CNT > 0) {
            $trid = get_alt_col('matches', 'match_id', $match_id, 'f_tour_id');
            $q = "SELECT syncMVplayer($this->star_id, $trid)";
            mysql_query($q);
        }
        return true;
    }
    
    public static function getStars($obj, $obj_id, $node, $node_id)
    {
        /*
            Returns an array of star objs for each (depending on arguments) star player.
        */
        
        global $stars, $STATS_TRANS;
        $starObjs = array();
        
        if (!($obj || $node)) {
            $starObjs = array_map(create_function('$s', 'return (new Star($s[\'id\']));'), $stars);
        }
        else {
            $query = "SELECT DISTINCT f_player_id FROM match_data, matches WHERE 
                f_match_id = match_id AND f_player_id <= ".ID_STARS_BEGIN." 
                ".(($obj)  ? ' AND '.$STATS_TRANS[$obj]. " = $obj_id "  : '').'
                '.(($node) ? ' AND '.$STATS_TRANS[$node]." = $node_id " : '');
            if (($result = mysql_query($query)) && mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_assoc($result)) {
                    array_push($starObjs, new Star($row['f_player_id']));
                }
            }
        }
        
        return $starObjs;
    }
    
    public static function exists($id)
    {
        return get_alt_col('game_data_stars', 'star_id', $id, 'star_id');
    }
}

class Mercenary
{
    /*
        About mercenaries data stored in MySQL:
        Mercenary number is stored in the "inj" field, and the number of extra skills bought in "agn1".
    */

    /***************
     * Attributes
     ***************/

    public $nr       = 0;
    public $match_id = 0;
    public $skills   = 0;

    public $mvp     = 0;
    public $cp      = 0;
    public $td      = 0;
    public $intcpt  = 0;
    public $bh      = 0;
    public $ki      = 0;
    public $si      = 0;
    public $cas     = 0;

    /***************
     * Methods
     ***************/

    public function __construct($match_id, $nr)
    {
        /*
            Make new merc obj.
            
            The field values of a merc obj consists of the accomplishments of the merc in a specific match.
            No two mercenaries are the same therefore mercs have no ID. 
            This means that to operate on a specific merc from a match, we must know the match ID and some kind of in-match-merc-id so that we 
            may distinguish two mercs from each other in the same match (ie. the merc $nr).
        */
        
        $this->nr = $nr;
        $this->match_id = $match_id;
        
        $query = "SELECT * FROM match_data WHERE f_match_id = $match_id AND f_player_id = ".ID_MERCS." AND inj = $nr";
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);

        $this->mvp = $row['mvp'];
        $this->cp = $row['cp'];
        $this->td = $row['td'];
        $this->intcpt = $row['intcpt'];
        $this->bh = $row['bh'];
        $this->ki = $row['ki'];
        $this->si = $row['si'];
        $this->cas = $this->bh + $this->ki + $this->si;
        $this->skills = $row['agn1'];
        
        return true;
    }

    /***************
     * Statics
     ***************/

    public static function rmMatchEntries($match_id, $team_id = false)
    {
        /*
            Deletes a merc's entry in a match.
        */
        
        $query = "DELETE FROM match_data WHERE f_player_id = ".ID_MERCS." AND f_match_id = $match_id".(($team_id) ? " AND f_team_id = $team_id" : '');
        return mysql_query($query);
    }
    
    public static function getMercsHiredByTeam($team_id, $f_match_id = false)
    {
        /*
            Returns an array of merc objects, which have in common that they are from the same team ID and match.
            By not specifying the match ID this methods returns all merc objects hired by the team in question.
        */
        
        $mercs = array();
        
        $query = "SELECT inj, f_match_id FROM match_data, matches WHERE f_match_id = match_id AND f_team_id = $team_id AND f_player_id = ".ID_MERCS.(($f_match_id) ? " AND f_match_id = $f_match_id" : '') . ' ORDER BY date_played DESC, inj ASC';
        $result = mysql_query($query);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($mercs, new Mercenary($row['f_match_id'], $row['inj']));
            }
        }
        
        return $mercs;
    }
}

