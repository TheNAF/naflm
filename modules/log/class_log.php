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

class LogSubSys implements ModuleInterface
{

public static function main($argv)
{
    $func = array_shift($argv);
    return call_user_func_array(array(__CLASS__, $func), $argv);
}

public static function getModuleAttributes()
{
    return array(
        'author'     => 'Nicholas Mossor Rathmann',
        'moduleName' => 'Log subsystem',
        'date'       => '2009',
        'setCanvas'  => false,
    );
}

public static function getModuleTables()
{
    return array(
        # Table name => column definitions
        'log' => array(
            # Column name => column definition
            'log_id'   => 'MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
            'cid'      => 'MEDIUMINT UNSIGNED NOT NULL',
            'category' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
            'msg'      => 'TEXT',
            'date'     => 'DATETIME',
        ),
    );
}    

public static function getModuleUpgradeSQL()
{
    return array(
        '075-080' => array(
            'CREATE TABLE IF NOT EXISTS log (
                log_id   MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                cid      MEDIUMINT UNSIGNED NOT NULL,
                category TINYINT UNSIGNED NOT NULL DEFAULT 0,
                msg      TEXT,
                date     DATETIME
            )',
            'INSERT INTO log (cid, category, date, msg) SELECT f_id, \'1\', date, txt FROM texts WHERE type = 10 ORDER BY date ASC',
            'DELETE FROM texts WHERE type = 10',
        ),
    );
}

public static function triggerHandler($type, $argv) {}

// --------------------------------------------------

public static function createEntry($cat, $cid, $msg)
{
    return mysql_query("INSERT INTO log (date, cid, category, msg) VALUES (NOW(), $cid, $cat, '".mysql_real_escape_string($msg)."')");
}

public static function logViewPage()
{
    global $coach, $lng;
    
    if (!is_object($coach) || $coach->ring != Coach::T_RING_GLOBAL_ADMIN)
        fatal("Sorry. Only site administrators and commissioners are allowed to access this section.");

    title($lng->getTrn('name', 'LogSubSys'));
    echo "<table style='width:100%;'>\n";
    echo "<tr><td><i>Date</i></td><td><i>Message</i></td></tr><tr><td colspan='2'><hr></td></tr>\n";
    $query = "SELECT * FROM log WHERE date > SUBDATE(NOW(), INTERVAL ".LOG_HIST_LENGTH." MONTH) ORDER BY date DESC";
    $result = mysql_query($query);
    $logs = array();
    while ($l = mysql_fetch_object($result)) {
        echo "<tr><td>".textdate($l->date)."</td><td>$l->msg</td></tr>\n";
    }
    echo "</table>\n";
}

}
?>
