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

class Table
{
    public static function createTable($name, $tblStruct)
    {
        $query = 'CREATE TABLE '.$name.' (
            '.implode(', ', array_map(create_function('$key,$val', 'return "$key\t $val";'), array_keys($tblStruct), array_values($tblStruct))).'
        )';
        return mysql_query("DROP TABLE IF EXISTS $name") && mysql_query($query);
    }
    
    public static function createTableIfNotExists($name, $tblStruct) {
        $query = 'CREATE TABLE IF NOT EXISTS '.$name.' (
            '.implode(', ', array_map(create_function('$key,$val', 'return "$key\t $val";'), array_keys($tblStruct), array_values($tblStruct))).'
        )';
        return mysql_query($query);
    }
}

