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

 /*************************
 *
 *  ADMINISTRATION
 *
 *************************/

function sec_admin() {

    global $rules, $settings, $DEA, $coach, $lng, $admin_menu;
    global $leagues, $divisions, $tours;
    
    if (!is_object($coach))
        fatal('Please login.');
    if (!isset($_GET['subsec']))
        $_GET['subsec'] = '_NONE_';

    $IS_GLOBAL_ADMIN = ($coach->ring == Coach::T_RING_GLOBAL_ADMIN);
    $ONLY_FOR_GLOBAL_ADMIN = "Note: This feature may only be used by <i>global</i> administrators."; # Used string in a few common feature/action boxes.

    // Deny un-authorized users.
    if (!in_array($_GET['subsec'], array_keys($admin_menu)))
        fatal("Sorry. Your access level does not allow you opening the requested page.");

    switch ($_GET['subsec']) 
    {
        case 'usr_man':     include('admin/admin_usr_man.php'); break;
        case 'ct_man':      include('admin/admin_ct_man.php'); break;
        case 'nodes':       include('admin/admin_nodes.php'); break;
        case 'schedule':    include('admin/admin_schedule.php'); break;
        case 'import':      include('admin/admin_import.php'); break;
        case 'log':         Module::run('LogSubSys', array('logViewPage')); break;
        case 'cpanel':      include('admin/admin_cpanel.php'); break;
        default:            fatal('The requested admin page does not exist.');
    }
    echo "<br><br>";
    HTMLOUT::dnt();
}

?>
