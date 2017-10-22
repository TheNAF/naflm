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

include("jpgraph/jpgraph.php");
include("jpgraph/jpgraph_mgraph.php");
include("jpgraph/jpgraph_bar.php");
include("jpgraph/jpgraph_pie.php");

class SGraph implements ModuleInterface
{
    public static function getModuleAttributes()
    {
        return array(
            'author'     => 'Nicholas Mossor Rathmann',
            'moduleName' => 'Graphical statistics',
            'date'       => '2008-2009',
            'setCanvas'  => false,
        );
    }

    public static function getModuleTables()
    {
        return array();
    }

    public static function getModuleUpgradeSQL()
    {
        return array();
    }

    public static function triggerHandler($type, $argv){}

    public static function main($argv)
    {
        list($type, $id, $cmp_id) = $argv;

        // General.
        $o           = null;
        $opts        = array('xdim' => SG_DIM_X, 'ydim' => SG_DIM_Y, 'retObj' => true); // Options to pass to mbars().
        $count_horiz = SG_CNT_HORIZ; // Number og graphs to place in each horizontal multi graph "row".
        $graphs      = array();

        if     ($type == SG_T_TEAM)   {$o = new Team($_GET['id']);  $where = "f_team_id   = $o->team_id";}
        elseif ($type == SG_T_COACH)  {$o = new Coach($_GET['id']); $where = "f_coach_id  = $o->coach_id";}
        elseif ($type == SG_T_PLAYER) {$o = new Player($_GET['id']);$where = "f_player_id = $o->player_id";}

        if ($type != SG_T_LEAGUE && !is_object($o))
            return false;

        // Make graphs components for multi graph plot.
        if ($type == SG_T_LEAGUE) {

            /*
                Played matches.
            */
            $queries = array();
            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                $range = "(
                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    AND
                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                )";
                # m$i = minus/negative $i months from present month.
                array_push($queries, "SUM(IF($range, 1, 0)) AS 'Games_m$i'");
                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
            }

            $query  = "SELECT ".implode(', ', $queries)." FROM matches";
            $result = mysql_query($query);
            $row    = mysql_fetch_assoc($result);

            $lengends = array('Games' => 'royalblue');
            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Games played", "Months", "Games", $opts));

            /*
                Active teams
            */

            $queries = array();
            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                $range = "(
                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    AND
                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                )";

                array_push($queries,
                    "(SELECT COUNT(DISTINCT(tid)) FROM
                    (
                    SELECT team1_id AS 'tid', date_played FROM matches WHERE $range
                    UNION
                    SELECT team2_id AS 'tid', date_played FROM matches WHERE $range
                    ) AS tmptbl) AS 'Active teams_m$i'"
                );
                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'\n");
            }

            $query  = "SELECT ".implode(', ', $queries);
            $result = mysql_query($query);
            $row    = mysql_fetch_assoc($result);

            $lengends = array('Active teams' => 'olivedrab');
            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Active teams", "Months", "Teams", $opts));

            /*
                Active coaches
            */

            $queries = array();
            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                $range = "(
                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    AND
                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                )";

                array_push($queries,
                    "(SELECT COUNT(DISTINCT(cid)) FROM
                    (
                    SELECT owned_by_coach_id AS 'cid', date_played FROM matches, teams WHERE team1_id = team_id AND $range
                    UNION
                    SELECT owned_by_coach_id AS 'cid', date_played FROM matches, teams WHERE team2_id = team_id AND $range
                    ) AS tmptbl) AS 'Active coaches_m$i'"
                );
                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'\n");
            }

            $query  = "SELECT ".implode(', ', $queries);
            $result = mysql_query($query);
            $row    = mysql_fetch_assoc($result);

            $lengends = array('Active coaches' => 'darkorange1');
            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Active coaches", "Months", "Coaches", $opts));

            /*
                Played matches.
            */
            $queries = array();
            foreach (range(0, SG_MULTIBAR_HIST_LENGTH_DAYS) as $i) {
                # Hack (for display purposes) set month -> year, day -> month
                $range = "(
                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i DAY)))
                    AND
                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i DAY)))
                    AND
                    (DAY(date_played) = DAY(SUBDATE(DATE(NOW()), INTERVAL $i DAY)))
                )";
                # m$i = minus/negative $i months from present month.
                array_push($queries, "SUM(IF($range, 1, 0)) AS 'Games_m$i'");
                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i DAY)) AS 'yr_m$i'");
                array_push($queries, "DAY(SUBDATE(DATE(NOW()), INTERVAL $i DAY)) AS 'mn_m$i'");
            }

            $query  = "SELECT ".implode(', ', $queries)." FROM matches";
            $result = mysql_query($query);
            $row    = mysql_fetch_assoc($result);

            $lengends = array('Games' => 'royalblue');
            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row, SG_MULTIBAR_HIST_LENGTH_DAYS);
            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Games played", "Days", "Games", $opts));

            /*
                td & cp.
            */
            $queries = array();
            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                $range = "(
                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    AND
                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                )";
                # m$i = minus/negative $i months from present month.
                array_push($queries, "SUM(IF($range, cp, 0))     AS 'CP_m$i'");
                array_push($queries, "SUM(IF($range, td, 0))     AS 'TD_m$i'");
#                array_push($queries, "SUM(IF($range, intcpt, 0)) AS 'int_m$i'");
                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
            }

            $query  = "SELECT ".implode(', ', $queries)." FROM matches, match_data WHERE f_match_id = match_id";
            $result = mysql_query($query);
            $row    = mysql_fetch_assoc($result);

            $lengends = array('CP' => 'seagreen', 'TD' => 'indianred');
            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "CP & TD history", "Months", "Amount", $opts));

            /*
                CAS.
            */
            $queries = array();
            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                $range = "(
                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    AND
                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                )";
                # m$i = minus/negative $i months from present month.
                array_push($queries, "SUM(IF($range, bh+si+ki, 0)) AS 'CAS_m$i'");
                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
            }

            $query  = "SELECT ".implode(', ', $queries)." FROM matches, match_data WHERE f_match_id = match_id";
            $result = mysql_query($query);
            $row    = mysql_fetch_assoc($result);

            $lengends = array('CAS' => 'firebrick1');
            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "CAS history", "Months", "Amount", $opts));

            /*
                SMP.
            */
#            $queries = array();
#            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
#                $range = "(
#                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                    AND
#                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                )";
#                # m$i = minus/negative $i months from present month.
#                array_push($queries, "SUM(IF($range, smp1+smp2, 0))     AS 'smp_m$i'");
#                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
#                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
#            }

#            $query  = "SELECT ".implode(', ', $queries)." FROM matches";
#            $result = mysql_query($query);
#            $row    = mysql_fetch_assoc($result);
#
#            $lengends = array('smp' => 'blue');
#            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
#            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Total given sportsmanship points (smp)", "Months", "Points", $opts));

            /*
                Avg. gate per match.
            */

#            $queries = array();
#            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
#                $range = "(
#                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                    AND
#                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                )";
#                # m$i = minus/negative $i months from present month.
#                array_push($queries, "AVG(IF($range, gate/1000, NULL)) AS 'avg_gate_m$i'");
#                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
#                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
#            }
#
#            $query  = "SELECT ".implode(', ', $queries)." FROM matches";
#            $result = mysql_query($query);
#            $row    = mysql_fetch_assoc($result);
#
#            $lengends = array('avg_gate' => 'blue');
#            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
#            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Average gate per match (kilo)", "Months", "Gate", array_merge($opts, array('scale' => 'textlin'))));

            /*
                average absolute score diff.
            */
#            $queries = array();
#            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
#                $range = "(
#                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                    AND
#                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                )";
#                # m$i = minus/negative $i months from present month.
#                array_push($queries, "AVG(IF($range, ABS(CONVERT(team1_score,SIGNED) - CONVERT(team2_score,SIGNED)), NULL)) AS 'avg_abs_diff_m$i'");
#                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
#                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
#            }

#            $query  = "SELECT ".implode(', ', $queries)." FROM matches";
#            $result = mysql_query($query);
#            $row    = mysql_fetch_assoc($result);
#
#            $lengends = array('avg_abs_diff' => 'blue');
#            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
#            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Average absolute score difference history", "Months", "Avg. abs. score diff.", array_merge($opts, array('scale' => 'textlin'))));

            /*
                Average deta treasury
            */
#            $queries = array();
#            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
#                $range = "(
#                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                    AND
#                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                )";
#                # m$i = minus/negative $i months from present month.
#                array_push($queries, "AVG(IF($range, ((income1+income2)/2)/1000, NULL))     AS 'avg_dtreasury_m$i'");
#                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
#                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
#            }

#            $query  = "SELECT ".implode(', ', $queries)." FROM matches";
#            $result = mysql_query($query);
#            $row    = mysql_fetch_assoc($result);
#
#            $lengends = array('avg_dtreasury' => 'blue');
#            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
#            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Avg. change in team's treasury per match (kilo)", "Months", "Average change", array_merge($opts, array('scale' => 'textlin'))));

            /*
                Average fans at match.
            */
#            $queries = array();
#            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
#                $range = "(
#                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                    AND
#                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                )";
#                # m$i = minus/negative $i months from present month.
#                array_push($queries, "AVG(IF($range, fans, NULL))     AS 'fans_m$i'");
#                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
#                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
#            }

#            $query  = "SELECT ".implode(', ', $queries)." FROM matches";
#            $result = mysql_query($query);
#            $row    = mysql_fetch_assoc($result);
#
#            $lengends = array('fans' => 'blue');
#            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
#            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Average fans per match", "Months", "Average fans", array_merge($opts, array('scale' => 'textlin'))));

            /*
                Average stars and mercs hirings per match
            */
#            $queries = array();
#            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
#                $range = "(
#                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                    AND
#                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                )";
#                # m$i = minus/negative $i months from present month.
#                array_push($queries, "AVG(IF($range, stars, NULL)) AS 'avg_stars_m$i'");
#                array_push($queries, "AVG(IF($range, mercs, NULL)) AS 'avg_mercs_m$i'");
#                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
#                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
#            }
#            $tableMercs = "(
#                SELECT f_match_id, SUM(IF(f_player_id = ".ID_MERCS.", 1, 0)) AS mercs FROM match_data GROUP BY f_match_id
#            ) AS mercsTbl";
#            $tableStars = "(
#                SELECT f_match_id, SUM(IF(f_player_id <= ".ID_STARS_BEGIN.", 1, 0)) AS stars FROM match_data GROUP BY f_match_id
#            ) AS starsTbl";
#            $query  = "SELECT ".implode(', ', $queries)." FROM matches, $tableMercs, $tableStars WHERE mercsTbl.f_match_id = matches.match_id AND starsTbl.f_match_id = matches.match_id";
#            $result = mysql_query($query);
#            $row    = mysql_fetch_assoc($result);
#
#            $lengends = array('avg_stars' => 'red', 'avg_mercs' => 'green');
#            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
#            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Avg. stars and mercs per match", "Months", "Average hirings", array_merge($opts, array('scale' => 'textlin'))));

            /*
                Injuries
            */
#            $queries = array();
#            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
#                $range = "(
#                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                    AND
#                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
#                )";
#                # m$i = minus/negative $i months from present month.
#                array_push($queries, "SUM(IF($range, IF(inj = ".MNG.",  1, 0)+IF(agn1 = ".MNG.",  1, 0)+IF(agn2 = ".MNG.",  1, 0), 0)) AS 'mng_m$i'");
#                array_push($queries, "SUM(IF($range, IF(inj = ".NI.",   1, 0)+IF(agn1 = ".NI.",   1, 0)+IF(agn2 = ".NI.",   1, 0), 0)) AS 'ni_m$i'");
#                array_push($queries, "SUM(IF($range, IF(inj = ".MA.",   1, 0)+IF(agn1 = ".MA.",   1, 0)+IF(agn2 = ".MA.",   1, 0), 0)) AS 'ma_m$i'");
#                array_push($queries, "SUM(IF($range, IF(inj = ".AV.",   1, 0)+IF(agn1 = ".AV.",   1, 0)+IF(agn2 = ".AV.",   1, 0), 0)) AS 'av_m$i'");
#                array_push($queries, "SUM(IF($range, IF(inj = ".AG.",   1, 0)+IF(agn1 = ".AG.",   1, 0)+IF(agn2 = ".AG.",   1, 0), 0)) AS 'ag_m$i'");
#                array_push($queries, "SUM(IF($range, IF(inj = ".ST.",   1, 0)+IF(agn1 = ".ST.",   1, 0)+IF(agn2 = ".ST.",   1, 0), 0)) AS 'st_m$i'");
#                array_push($queries, "SUM(IF($range, IF(inj = ".DEAD.", 1, 0)+IF(agn1 = ".DEAD.", 1, 0)+IF(agn2 = ".DEAD.", 1, 0), 0)) AS 'dead_m$i'");
#                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
#                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
#            }

#            $query  = "SELECT ".implode(', ', $queries)." FROM matches, match_data WHERE f_match_id = match_id";
#            $result = mysql_query($query);
#            $row    = mysql_fetch_assoc($result);
#
#            $lengends = array('mng' => 'green', 'ni' => 'red', 'ma' => 'blue', 'av' => 'aqua', 'ag' => 'brown', 'st' => 'purple', 'dead' => 'slategray');
#            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
#            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Types of sustained player injuries/statuses", "Months", "Amount", $opts));

            /*
                Race distribution
            */
#            global $raceididx;
#            $query  = "SELECT DISTINCT(f_race_id) AS 'race', COUNT(f_race_id) 'cnt' FROM teams GROUP BY f_race_id";
#            $result = mysql_query($query);
#            $data = array();
#            while ($row = mysql_fetch_assoc($result)) {
#                $data[$raceididx[$row['race']]." ($row[cnt])"] = $row['cnt'];
#            }
#            $graph = new PieGraph($opts['xdim'],$opts['ydim'],"auto");
#            $graph->SetShadow();
#            $graph->title->Set('Current race distribution');
#            $graph->title->SetFont(FF_FONT1,FS_BOLD);
#            $p1 = new PiePlot(array_values($data));
#            $p1->SetLegends(array_keys($data));
#            $p1->SetCenter(0.4);
#            $graph->Add($p1);
#            array_push($graphs, $graph);

            /*
                CAS distribution
            */
#            $query  = "SELECT SUM(bh) AS 'bh', SUM(si) AS 'si', SUM(ki) AS 'ki' FROM match_data";
#            $result = mysql_query($query);
#            $o = (object) mysql_fetch_assoc($result);
#            $data = array("BH ($o->bh)" => $o->bh, "SI ($o->si)" => $o->si, "Ki ($o->ki)" => $o->ki);
#            $graph = new PieGraph($opts['xdim'],$opts['ydim'],"auto");
#            $graph->SetShadow();
#            $graph->title->Set('Current CAS distribution');
#            $graph->title->SetFont(FF_FONT1,FS_BOLD);
#            $p1 = new PiePlot(array_values($data));
#            $p1->SetLegends(array_keys($data));
#            $p1->SetCenter(0.4);
#            $graph->Add($p1);
#            array_push($graphs, $graph);
        }
        else {

            /********************
             *  Current CAS
             ********************/

            if (!$cmp_id && $o->mv_cas != 0) {
                $data = array("BH ($o->mv_bh)" => $o->mv_bh, "SI ($o->mv_si)" => $o->mv_si, "Ki ($o->mv_ki)" => $o->mv_ki);
                $graph = new PieGraph($opts['xdim'],$opts['ydim'],"auto");
                $graph->SetShadow();
                $graph->title->Set('Current CAS distribution');
                $graph->title->SetFont(FF_FONT1,FS_BOLD);
                $p1 = new PiePlot(array_values($data));
                $p1->SetLegends(array_keys($data));
                $p1->SetCenter(0.4);
                $graph->Add($p1);
                array_push($graphs, $graph);
            }

            /********************
             *  BH, SI and Ki
             ********************/

            $queries = array();
            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                $range = "(
                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    AND
                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                )";
                # m$i = minus/negative $i months from present month.
                array_push($queries, "SUM(IF($range, bh, 0)) AS 'BH_m$i'");
                array_push($queries, "SUM(IF($range, si, 0)) AS 'SI_m$i'");
                array_push($queries, "SUM(IF($range, ki, 0)) AS 'Ki_m$i'");
                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
            }

            $query  = "SELECT ".implode(', ', $queries)." FROM matches, match_data WHERE f_match_id = match_id AND $where";
            $result = mysql_query($query);
            $row    = mysql_fetch_assoc($result);

            $lengends = array('BH' => 'forestgreen', 'SI' => 'firebrick', 'Ki' => 'blue');
            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "BH, SI and Ki distribution history", "Months", "Amount", $opts));


            /********************
             *  CP & TD
             ********************/

            $queries = array();
            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                $range = "(
                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    AND
                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                )";
                # m$i = minus/negative $i months from present month.
                array_push($queries, "SUM(IF($range, cp, 0))     AS 'CP_m$i'");
                array_push($queries, "SUM(IF($range, td, 0))     AS 'TD_m$i'");
#                array_push($queries, "SUM(IF($range, intcpt, 0)) AS 'int_m$i'");
                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
            }

            $query  = "SELECT ".implode(', ', $queries)." FROM matches, match_data WHERE f_match_id = match_id AND $where";
            $result = mysql_query($query);
            $row    = mysql_fetch_assoc($result);

            $lengends = array('CP' => 'forestgreen', 'TD' => 'firebrick');
            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "CP & TD distribution history", "Months", "Amount", $opts));

            /********************
             *  Injuries
             ********************/

            $queries = array();
            foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                $range = "(
                    (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    AND
                    (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                )";
                # m$i = minus/negative $i months from present month.
                array_push($queries, "SUM(IF($range, IF(inj = ".MNG.",  1, 0)+IF(agn1 = ".MNG.",  1, 0)+IF(agn2 = ".MNG.",  1, 0), 0)) AS 'MNG_m$i'");
                array_push($queries, "SUM(IF($range, IF(inj = ".NI.",   1, 0)+IF(agn1 = ".NI.",   1, 0)+IF(agn2 = ".NI.",   1, 0), 0)) AS 'Ni_m$i'");
                array_push($queries, "SUM(IF($range, IF(inj = ".MA.",   1, 0)+IF(agn1 = ".MA.",   1, 0)+IF(agn2 = ".MA.",   1, 0), 0)) AS 'MA_m$i'");
                array_push($queries, "SUM(IF($range, IF(inj = ".AV.",   1, 0)+IF(agn1 = ".AV.",   1, 0)+IF(agn2 = ".AV.",   1, 0), 0)) AS 'AV_m$i'");
                array_push($queries, "SUM(IF($range, IF(inj = ".AG.",   1, 0)+IF(agn1 = ".AG.",   1, 0)+IF(agn2 = ".AG.",   1, 0), 0)) AS 'AG_m$i'");
                array_push($queries, "SUM(IF($range, IF(inj = ".ST.",   1, 0)+IF(agn1 = ".ST.",   1, 0)+IF(agn2 = ".ST.",   1, 0), 0)) AS 'ST_m$i'");
                array_push($queries, "SUM(IF($range, IF(inj = ".DEAD.", 1, 0)+IF(agn1 = ".DEAD.", 1, 0)+IF(agn2 = ".DEAD.", 1, 0), 0)) AS 'Dead_m$i'");
                array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
            }

            $query  = "SELECT ".implode(', ', $queries)." FROM matches, match_data WHERE f_match_id = match_id AND $where";
            $result = mysql_query($query);
            $row    = mysql_fetch_assoc($result);

            $lengends = array('MNG' => 'green', 'Ni' => 'red', 'MA' => 'blue', 'AV' => 'aqua', 'AG' => 'brown', 'ST' => 'purple', 'Dead' => 'slategray');
            list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
            array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Types of sustained player injuries/statuses", "Months", "Amount", $opts));

            // Only if type = team.

            if ($type == SG_T_TEAM) {

                /********************
                 *  Won, lost and draw
                 ********************/

                $queries = array();
                foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                    $range = "(
                        (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                        AND
                        (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    )";
                    # m$i = minus/negative $i months from present month.
                    array_push($queries, "SUM(IF((team1_score > team2_score AND team1_id = $o->team_id OR team1_score < team2_score AND team2_id = $o->team_id) AND $range, 1, 0)) AS 'W_m$i'");
                    array_push($queries, "SUM(IF((team1_score < team2_score AND team1_id = $o->team_id OR team1_score > team2_score AND team2_id = $o->team_id) AND $range, 1, 0)) AS 'L_m$i'");
                    array_push($queries, "SUM(IF(team1_score = team2_score AND $range, 1, 0)) AS 'D_m$i'");
                    array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                    array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
                }

                $query  = "SELECT ".implode(', ', $queries)." FROM matches WHERE team1_id = $o->team_id OR team2_id = $o->team_id";
                $result = mysql_query($query);
                $row    = mysql_fetch_assoc($result);

                $lengends = array('W' => 'forestgreen', 'L' => 'firebrick', 'D' => 'blue');
                list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
                array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Won, lost and draw distribution history", "Months", "Matches", $opts));

                /*
                    Average deta treasury
                */
                $queries = array();
                foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                    $range = "(
                        (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                        AND
                        (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    )";
                    # m$i = minus/negative $i months from present month.
                    array_push($queries, "AVG(IF($range, IF(team1_id = $o->team_id, income1, income2)/1000, NULL)) AS 'Avg. change_m$i'");
                    array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                    array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
                }

                $query  = "SELECT ".implode(', ', $queries)." FROM matches WHERE team1_id = $o->team_id OR team2_id = $o->team_id";
                $result = mysql_query($query);
                $row    = mysql_fetch_assoc($result);

                $lengends = array('Avg. change' => 'darkolivegreen4');
                list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
                array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Avg. change in team's treasury per match (kilo)", "Months", "Average change", array_merge($opts, array('scale' => 'textlin'))));

                /*
                    SMP.
                */
                $queries = array();
                foreach (range(0, SG_MULTIBAR_HIST_LENGTH) as $i) {
                    $range = "(
                        (YEAR(date_played) = YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                        AND
                        (MONTH(date_played) = MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)))
                    )";
                    # m$i = minus/negative $i months from present month.
                    array_push($queries, "SUM(IF($range, IF(team1_id = $o->team_id, smp1, smp2), 0))     AS 'SMP_m$i'");
                    array_push($queries, "YEAR(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'yr_m$i'");
                    array_push($queries, "MONTH(SUBDATE(DATE(NOW()), INTERVAL $i MONTH)) AS 'mn_m$i'");
                }

                $query  = "SELECT ".implode(', ', $queries)." FROM matches WHERE team1_id = $o->team_id OR team2_id = $o->team_id";
                $result = mysql_query($query);
                $row    = mysql_fetch_assoc($result);

                $lengends = array('SMP' => 'blue');
                list($datasets, $labels) = SGraph::mbarsInputFormatter($lengends, $row);
                array_push($graphs, SGraph::mbars($datasets, $labels, $lengends, "Total given sportsmanship points (smp)", "Months", "Points", $opts));
            }
        }

        // Multi plot, baby!
        $mgraph = new MGraph();
        $count = count($graphs);
        for ($i = $j = 1; ($i + ($j-1)*$count_horiz) <= $count; $j += (($i == $count_horiz) ? 1 : 0), $i = (($i == $count_horiz) ? 1 : $i+1)) { // i is horiz, j is vert.
            $mgraph->Add(array_shift($graphs), ($i-1)*$opts['xdim'], ($j-1)*$opts['ydim']);
        }
        return $mgraph->Stroke();
    }

    private static function mbars($datasets, $labels, $lengends, $title, $xlabel, $ylabel, $opts = array())
    {
        /*
            Types:
                datasets: array('KEY1' => array(1,2,3,4),   'KEY2' => array(1,2,3,4),   ...)
                lengends: array('KEY1' => 'color1',         'KEY2' => 'color2',         ...)
                labels:   array('Jan', '02', 'whatever', ...)
                opts:     array('xdim' => int, 'ydim' => int, 'retObj' => bool, 'scale' => 'scale string')
        */

        // Options
        $retObj = (array_key_exists('retObj', $opts) && $opts['retObj']);
        $dim['x'] = (array_key_exists('xdim', $opts)) ? $opts['xdim'] : 800;
        $dim['y'] = (array_key_exists('ydim', $opts)) ? $opts['ydim'] : 600;
        $scale = (array_key_exists('scale', $opts)) ? $opts['scale'] : "textint";
        $format = (preg_match('/lin/', $scale)) ? '%.1f' : '%d';

        // Ready graph object.
        $graph = new Graph($dim['x'],$dim['y'],"auto");
        $graph->SetScale($scale);
        $graph->SetShadow();
        $graph->img->SetMargin(40,30,20,40);
        $graph->xaxis->SetTickLabels($labels);

        // Create the bar plots
        $bplots = array();
        foreach ($lengends as $key => $color) {
            $bplot = new BarPlot($datasets[$key]);
            $bplot->SetFillColor($color);
            $bplot->value->Show();
            $bplot->value->SetFormat($format);
            $bplot->value->SetFont(FF_FONT1,FS_BOLD);
            $bplot->value->SetColor('white');
            $bplot->SetValuePos('center');
            $bplot->SetLegend($key);
            $bplots[] = $bplot;
        }

        // Create the grouped bar plot
        $plot = new GroupBarPlot($bplots);
        $graph->Add($plot);
        $graph->title->Set($title);
        $graph->xaxis->title->Set($xlabel);
        $graph->yaxis->title->Set($ylabel);
        return ($retObj) ? $graph : $graph->Stroke();
    }

    private static function mbarsInputFormatter($lengends, $row, $cnt_entries = SG_MULTIBAR_HIST_LENGTH)
    {
        // $row is sql data.

        $datasets = array();
        $labels   = array();

        foreach (array_keys($lengends) as $key) {
            $ds = array();
            foreach (range(0, $cnt_entries) as $i) {
                $ds[] = $row["${key}_m$i"];
            }
            $datasets[$key] = $ds;
        }
        foreach (range(0, $cnt_entries) as $i) {
            $labels[] = $row["yr_m$i"].'/'.$row["mn_m$i"];
        }

        return array($datasets, $labels);
    }
}

