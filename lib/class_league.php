<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2009. All Rights Reserved.
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

class League
{
/***************
 * Properties
 ***************/

public $lid = 0; // League ID.
public $tie_teams = true;
public $name = '';
public $date = '';
public $location = ''; // Physical location of league.

/***************
 * Methods
 ***************/

function __construct($lid) {
    $result = mysql_query("SELECT * FROM leagues WHERE lid = $lid");
    $row = mysql_fetch_assoc($result);
    foreach ($row as $col => $val) {
        $this->$col = ($val) ? $val : 0;
    }

    if (!$this->name) {$this->name = '';} # Make $name empty string and not zero when empty in mysql.
    if (!$this->location) {$this->location = '';}
    if (!$this->date) {$this->date = '';}
}

public function delete()
{
    $status = true;
    foreach ($this->getDivisions() as $d) {
        $status &= $d->delete();
    }
    return ($status && mysql_query("DELETE FROM leagues WHERE lid = $this->lid"));
}

public function setName($name)
{
    $query = "UPDATE leagues SET name = '".mysql_real_escape_string($name)."' WHERE lid = $this->lid";
    return (get_alt_col('leagues', 'name', $name, 'lid')) ? false : mysql_query($query);
}

public function setLocation($location)
{
    $query = "UPDATE leagues SET location = '".mysql_real_escape_string($location)."' WHERE lid = $this->lid";
    return mysql_query($query);
}

public function setTeamDivisionTies($bool)
{
    $query = "UPDATE leagues SET tie_teams = ".($bool ? 'TRUE' : 'FALSE')." WHERE lid = $this->lid";
    return mysql_query($query);
}

public function getDivisions($onlyIds = false)
{
    $divisions = array();
    $result = mysql_query("SELECT did FROM divisions WHERE f_lid = $this->lid");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            array_push($divisions, ($onlyIds) ? $row['did'] : new Division($row['did']));
        }
    }
    return $divisions;
}

public static function getLeagues($onlyIds = false)
{
    $leagues = array();
    $result = mysql_query("SELECT lid FROM leagues");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            array_push($leagues, ($onlyIds) ? $row['lid'] : new League($row['lid']));
        }
    }
    return $leagues;
}

public static function getLeaguesWithLocation() {
    $leagues = array();
    $result = mysql_query("SELECT lid FROM leagues where location <> '' and location is not null");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            array_push($leagues, new League($row['lid']));
        }
    }
    return $leagues;
}

public static function getLeaguesByLocation() {
    $locations = array();
    $result = mysql_query("SELECT lid FROM leagues");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $league = new League($row['lid']);
            
            if($league->location) {
                if(!isset($locations[$league->location]))
                    $locations[$league->location] = array();
                
                $locations[$league->location][] = $league;
            }
         }
    }
    
    ksort($locations);
        
    return $locations;
}

public static function create($name, $location, $tie_teams)
{
    global $lng;
    
    $query = "INSERT INTO leagues (date, location, name, tie_teams) VALUES (NOW(), '".mysql_real_escape_string($location)."', '".mysql_real_escape_string($name)."', ".((int) $tie_teams).")";
    if(get_alt_col('leagues', 'name', $name, 'lid'))
        return $lng->getTrn('admin/nodes/errors/league_already_exists');
    
    // Create the league
    mysql_query($query);
    
    // Make a new settings file for that league.
    $new_lid = get_alt_col('leagues', 'name', $name, 'lid');
    $settings_new_filename = FileManager::getSettingsDirectoryName() . "/settings_$new_lid.php";
    $settings_template_filename = FileManager::getSettingsDirectoryName() . "/settings_new_league_template.php";
    
    if(!FileManager::copyFile($settings_template_filename, $settings_new_filename))
        return $lng->getTrn('admin/nodes/errors/settings_file_copy_failed');
    
    return false;
}

public static function getLeagueUrl($lid, $l_name = null) {
	if(!isset($l_name)) {
		$l_name = get_alt_col('leagues', 'lid', $lid, 'name');
	}
	return "<a href=\"" . urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,T_NODE_LEAGUE,$lid) . "\">" . $l_name . "</a>";
}

public function getUrl() {
	return getLeagueUrl($this->lid, $this->name);
}
}

