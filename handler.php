<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2008-2009. All Rights Reserved.
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

require('header.php'); // Includes and constants.

if (!isset($_GET['type']))
    fatal("Sorry. Don't know what to do. Please specify 'type' via GET.");

switch ($_GET['type'])
{
    /* PDF-roster */
    case 'roster':
        Module::run('PDFroster', array());
        break;

    /* RSS feed */
    case 'rss':
        Module::run('RSSfeed', array());
        break;

    /* Visual stats */
    case 'graph':
        Module::run('SGraph', array($_GET['gtype'], $_GET['id'], false));
        break;

    /* Inducements */
    case 'inducements':
        Module::run('IndcPage', array());
        break;

    /* BOTOCS match import */
    case 'leegmgr':
        Module::run('UPLOAD_BOTOCS', array());
        break;

    /* Team BOTOCS XML export */
    case 'botocsxml':
        Module::run('XML_BOTOCS', array());
        break;

    /* Mem. matches */
   	case 'memmatches':
   		Module::run('Memmatches', array());
   		break;

    /* Register */
   	case 'registration':
   		Module::run('Registration', array());
   		break;

    /* Hall of fame */
    case 'hof':
        Module::run('HOF', array('makeList'));
        break;

    /* Famous Teams */
    case 'famousteams':
        Module::run('FamousTeams', array('makeList'));
        break;

    /* Wanted */
    case 'wanted':
        Module::run('Wanted', array('makeList'));
        break;

    /* Prizes */
    case 'prize':
        Module::run('Prize', array('makeList'));
        break;

    /* Gallery */
    case 'gallery':
        Module::run('Gallery', array());
        break;

    /* Search */
    case 'search':
        Module::run('Search', array());
        break;

    /* League Tables */
    case 'leaguetables':
        Module::run('LeagueTables', array('showTables'));
        break;


    /* Conference */
    case 'conference':
        Module::run('Conference', array('conferenceAdmin'));
        break;


    /* Team Creator */
    case 'teamcreator':
        Module::run('TeamCreator', array('teamCreator'));
        break;

    /* League Preferences */
    case 'leaguepref':
        Module::run('LeaguePref', array('showLeaguePreferences'));
        break;

    /* Name autocompletion - AJAX */
    case 'confcomplete':

        $objs = array();
        switch ($_GET['obj']) {

            case T_OBJ_COACH:
                $query = "SELECT coach_id AS 'id', name FROM coaches WHERE name LIKE '$_GET[query]%' ORDER BY name ASC";
                $result = mysql_query($query);
                while($row = mysql_fetch_assoc($result)) {
                    $objs[$row['id']] = $row['name'];
                }
                break;

            case T_OBJ_TEAM:
                $query = "SELECT team_id AS 'id', name FROM teams WHERE name LIKE '%$_GET[query]%' ORDER BY name ASC";
                $result = mysql_query($query);
                while($row = mysql_fetch_assoc($result)) {
                    $objs[$row['id']] = $row['name'];
                }
                break;

        }
        echo json_encode(array('query' => $_GET['query'], 'suggestions' => array_values($objs), 'data' => array_keys($objs)));
        break;

    /* Team strength compare */
    
    case 'teamcompare':
        Module::run('TeamCompare', array());
        break;

    /* Cemetery */
    
    case 'cemetery':
        if (isset($_GET['tid'])) {
            Module::run('Cemetery', array((int) $_GET['tid']));
        }
        else {
            fatal('Invalid parameter "tid".');
        }
        break;

    /* PDF Match Report */
    case 'pdfmatchreport':
          $argv = array();
          if ( isset($_GET['tid1']) && isset($_GET['tid2']) && isset($_GET['mid']) && 
            is_numeric($_GET['tid1']) && is_numeric($_GET['tid2']) && is_numeric($_GET['mid'])) {
            $argv = array((int) $_GET['tid1'], (int) $_GET['tid2'], (int) $_GET['mid']);
          }
          Module::run('PDFMatchReport', $argv);
          break;

    /* Veridy team name - AJAX use */
    case 'verifyteam':
        if (isset($_POST['tname'])) {
            if (get_magic_quotes_gpc()) {
                $_POST['tname'] = stripslashes($_POST['tname']);
            }
            echo is_numeric($tid = get_alt_col('teams', 'name', $_POST['tname'], 'team_id')) ? $tid : '0';
        }
        break;

    /* Name autocompletion - AJAX */
    case 'autocomplete':

        $objs = array();
        switch ($_GET['obj']) {

            case T_OBJ_COACH:
                $query = "SELECT coach_id AS 'id', name FROM coaches WHERE name LIKE '$_GET[query]%' ORDER BY name ASC";
                $result = mysql_query($query);
                while($row = mysql_fetch_assoc($result)) {
                    $objs[$row['id']] = $row['name'];
                }
                break;

            case T_OBJ_TEAM:
                $lid = isset($_GET['trid']) ? get_parent_id(T_NODE_TOURNAMENT, (int) $_GET['trid'], T_NODE_LEAGUE) : false;
                $did = ($lid && get_alt_col('leagues', 'lid', $lid, 'tie_teams') == 1) ? get_parent_id(T_NODE_TOURNAMENT, (int) $_GET['trid'], T_NODE_DIVISION) : false;
                $FROM_lid = ($lid) ? "f_lid = $lid AND" : '';
                $FROM_did = ($did) ? "f_did = $did AND" : '';
                $query = "SELECT team_id AS 'id', name, rdy FROM teams WHERE $FROM_lid $FROM_did name LIKE '%$_GET[query]%' ORDER BY name ASC";
                $result = mysql_query($query);
                while($row = mysql_fetch_assoc($result)) {
                    $objs[$row['id']] = $row['name'];
                }
                break;

        }
        echo json_encode(array('query' => $_GET['query'], 'suggestions' => array_values($objs), 'data' => array_keys($objs)));
        break;

	/* League Tables */
    case 'scheduler':
		$subtype = '';
		if (isset($_POST['subtype'])) {
			$subtype = $_POST['subtype'];
		}
		
		if ($subtype != '') {
			switch($subtype) {
				case 'apa_schedule_available': {
					Scheduler::apa_schedule_available();
					break;
				}
				case 'apa_generate_draw': {
					Scheduler::apa_generate_draw();
					break;
				}
				case 'manual_draw': {
					Scheduler::show_manual_draw();
					break;
				}
				case 'manual_schedule': {
					$teams = json_decode($_POST['teams']);

					$draw = array();
					foreach($teams->teams as $team) {
						$draw[] = str_replace("pool","", $team);
					}
					Scheduler::apa_generate_schedule($draw);
					break;
				}
				case 'custom_draw': {
					Scheduler::show_custom_draw();
					break;
				}
				case 'custom_game': {
					Scheduler::schedule_custom_game();
					break;
				}
			}
		} else {
			Module::run('Scheduler', array());
		}
		
        break;
	case 'scheduler_apa_schedule_available':
		
		break;
    default:
        fatal("Sorry. I don't know what the type '$_GET[type]' means.\n");
}

mysql_close($conn);

