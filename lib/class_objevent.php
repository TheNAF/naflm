<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2011. All Rights Reserved.
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

class ObjEvent
{

const T_PLAYER_DEAD   = 1;
const T_PLAYER_SOLD   = 2;
const T_PLAYER_HIRED  = 3;
const T_PLAYER_SKILLS = 4;

public static $EVENTS = array(
    T_OBJ_PLAYER => array(
        self::T_PLAYER_DEAD, 
        self::T_PLAYER_SOLD,
        self::T_PLAYER_HIRED,
        self::T_PLAYER_SKILLS,
    ),
    T_OBJ_TEAM   => array(
    ),
    T_OBJ_COACH  => array(
    ),
);

public static function getRecentEvents($event, $node, $node_id, $N) 
{
    global $mv_keys;
    $events = array();
    $_COMMON_COLS__T_PLAYER = "players.name, players.value, players.f_pos_name, players.owned_by_team_id AS 'f_tid', players.f_tname AS 'tname', players.f_rid, f_rname AS 'rname'";
    switch ($event) 
    {
    case self::T_PLAYER_DEAD:
        if (!isset($col)) $col = 'date_died';
    case self::T_PLAYER_SOLD:
        if (!isset($col)) $col = 'date_sold';
    case self::T_PLAYER_HIRED:
        if (!isset($col)) $col = 'date_bought';
        $_query = "SELECT DISTINCT player_id AS 'pid', %COL AS 'date', $_COMMON_COLS__T_PLAYER FROM players, mv_players WHERE players.player_id = mv_players.f_pid AND players.type = ".PLAYER_TYPE_NORMAL." AND %NODE = $node_id AND %COL IS NOT NULL ORDER BY %COL DESC LIMIT $N";
        $query = str_replace(array('%COL', '%NODE'), array($col, $mv_keys[$node]), $_query);
        $result = mysql_query($query);
        while ($row = mysql_fetch_object($result)) {
            $events[] = $row;
        }
        break;

    case self::T_PLAYER_SKILLS:
        $query = "SELECT player_id AS 'pid', game_data_skills.skill_id AS 'skill_id', game_data_skills.cat AS 'cat', game_data_skills.name AS 'skill_name', CONCAT(game_data_skills.name,' (',game_data_skills.cat,')') AS 'skill', $_COMMON_COLS__T_PLAYER FROM players, mv_players, players_skills, game_data_skills WHERE 
            players.player_id = mv_players.f_pid AND 
            mv_players.f_pid = players_skills.f_pid AND
            players_skills.f_skill_id = game_data_skills.skill_id AND 
            ".$mv_keys[$node]." = $node_id 
        GROUP BY player_id, f_skill_id ORDER BY players_skills.id DESC LIMIT $N";
        $result = mysql_query($query);
        while ($row = mysql_fetch_object($result)) {
            $events[] = $row;
        }
        break;
    }
    return $events;
}
}

