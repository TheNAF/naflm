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

class Division
{
/***************
 * Properties 
 ***************/
 
public $did = 0;    // Division ID.
public $f_lid = 0;  // From this league ID.
public $name = '';

public $league_name = '';
 
/***************
 * Methods 
 ***************/

public function __construct($did) 
{
    $result = mysql_query("SELECT * FROM divisions WHERE did = $did");
    $row = mysql_fetch_assoc($result);
    foreach ($row as $col => $val) {
        $this->$col = ($val) ? $val : 0;
    }
    
    if (!$this->name) {$this->name = '';} # Make $name empty string and not zero when empty in mysql.
    
    $this->league_name = get_alt_col('leagues', 'lid', $this->f_lid, 'name');
}

public function delete()
{
    return mysql_query("DELETE FROM divisions WHERE did = $this->did");
}

public function setName($name)
{
    $query = "UPDATE divisions SET name = '".mysql_real_escape_string($name)."' WHERE did = $this->did";
    return (get_alt_col('divisions', 'name', $name, 'did')) ? false : mysql_query($query);
}

public function getTours($onlyIds = false)
{
    $tours = array();
    $result = mysql_query("SELECT tour_id FROM tours WHERE f_did = $this->did");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            array_push($tours, ($onlyIds) ? $row['tour_id'] : new Tour($row['tour_id']));
        }
    }
    return $tours;    
}

public static function getDivisions($onlyIds = false)
{
    $divisions = array();
    $result = mysql_query("SELECT did FROM divisions");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            array_push($divisions, ($onlyIds) ? $row['did'] : new Division($row['did']));
        }
    }
    return $divisions;
}

public static function create($f_lid, $name)
{
    $query = "INSERT INTO divisions (f_lid, name) VALUES ($f_lid, '".mysql_real_escape_string($name)."')";
    return (get_alt_col('divisions', 'name', $name, 'did')) ? false : mysql_query($query);
}

}
