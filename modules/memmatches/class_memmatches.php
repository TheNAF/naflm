<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2008-2011. All Rights Reserved.
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

define('MAX_MEM_MATCHES', 3); // For each mem. match category: If the number of matches with equal records exceed this value, no matches are shown at all.
define('MMATCHES_BREAK', 5); // Break after this number of entries in HTML column.

class Memmatches implements ModuleInterface
{

public static function getModuleAttributes()
{
    return array(
        'author'     => 'Nicholas Mossor Rathmann',
        'moduleName' => 'Memorable matches',
        'date'       => '2008-2009',
        'setCanvas'  => true,
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

public static function main($argv) {

    global $lng;
    title($lng->getTrn('name', __CLASS__));
    echo $lng->getTrn('desc', __CLASS__)."<br><br>\n";
    list($sel_node, $sel_node_id) = HTMLOUT::nodeSelector(array());
    echo "<br>";
    $_rows = 0;
    foreach (self::getMemMatches($sel_node, $sel_node_id) as $d => $matches) {
        $_container_style = 'float:left; width:50%;';
        if ($_rows == 0) {
            echo "<div style='$_container_style'>";
        }
        ?>
        <div class="boxWide">
            <div class="boxTitle<?php echo T_HTMLBOX_INFO;?>"><?php echo $lng->getTrn($d, __CLASS__); ?></div>
            <div class="boxBody">
                <table class="common">
                <?php
                if (empty($matches)) {
                    ?><tr><td align="center"><br><br><?php echo preg_replace('/\sX\s/', ' '.MAX_MEM_MATCHES.' ', $lng->getTrn('filled', __CLASS__));?><br><br></td></tr><?php
                }
                else {
                $i = count($matches);
                foreach ($matches as $m) {
                    $t1 = new Team($m->team1_id);
                    $t2 = new Team($m->team2_id);
                    $img1 = new ImageSubSys(IMGTYPE_TEAMLOGO, $t1->team_id);
                    $img2 = new ImageSubSys(IMGTYPE_TEAMLOGO, $t2->team_id);
                    ?>
                    <tr>
                        <td align="left" style="width:40%;"><img border='0px' height='30' width='30' alt='team picture' src='<?php echo $img1->getPath();?>'><?php echo $t1->name;?></td>
                        <td align="center">
                        <?php
                        switch ($d)
                        {
                            case 'td':
                            case 'cp':
                            case 'intcpt':
                            case 'ki':
                            case 'cas':
                                $v = array();
                                $s = ($d == 'cas') ? 'bh+ki+si' : $d;
                                foreach (array(1,2) as $j) {
                                    $query = "SELECT SUM($s) as '$s' FROM matches, match_data WHERE f_match_id = match_id AND match_id = $m->match_id AND f_team_id = team${j}_id";
                                    $result = mysql_query($query);
                                    $row = mysql_fetch_assoc($result);
                                    $v[$j] = ($row[$s]) ? $row[$s] : 0;
                                }
                                echo "<b>$v[1] &nbsp;-&nbsp; $v[2]</b>";
                                break;

                            case 'svic': echo "<b>$m->team1_score &nbsp;-&nbsp; $m->team2_score</b>"; break;
                            case 'inc': echo '<b>'.($m->income1/1000).'k - '.($m->income2/1000).'k</b>'; break;
                            case 'gate': echo '<b>'.($m->gate/1000).'k</b>'; break;
                            case 'mfans': echo "<b>$m->fans</b>"; break;
                            case 'tvdiff': echo '<b>'.($m->tv1/1000).'k - '.($m->tv2/1000).'k</b>'; break;
                        }
                        ?>
                        </td>
                        <td align="right" style="width:40%;"><?php echo $t2->name;?><img border='0px' height='30' width='30' alt='team picture' src='<?php echo $img2->getPath();?>'></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="3">
                        <small>
                        <i><?php echo get_alt_col('tours', 'tour_id', $m->f_tour_id, 'name');?>, <?php echo textdate($m->date_played, true);?></i>,
                        <a href="index.php?section=matches&amp;type=report&amp;mid=<?php echo $m->match_id;?>"><?php echo $lng->getTrn('common/view');?></a>
                        </small>
                        </td>
                    </tr>
                    <?php
                    if (--$i > 0) {
                        echo '<tr><td colspan="3"><hr></td></tr>';
                    }
                }
                }
                ?>
                </table>
            </div>
        </div>
        <?php
        if (is_int(($_rows+1)/MMATCHES_BREAK)) {
            echo "</div>";
            echo "<div style='$_container_style'>";
        }
        $_rows++;
    }
    echo "</div>";
}

private static function getMemMatches($node = false, $node_id = false) {

    /*
         Creates an array of matches for those matches which:

         - Most TDs (sum of both teams)
         - most cp
         - most int
         - Most killed
         - Most CAS
         - Largest score-wise victory
         - Largest match income
         - Largest gate
         - Most fans
         - Largest TV difference in which underdog won
     */

    $m = array(
       'td'        => array(),
       'cp'        => array(),
       'intcpt'    => array(),
       'ki'        => array(),
       'bh+ki+si'  => array(), // array key is renamed 'cas' before returning.

       'svic'      => array(),
       'inc'       => array(),
       'gate'      => array(),
       'mfans'     => array(), // most fans
       'tvdiff'    => array(),
    );

    /* Queries for finding the matches holding records. */

    // For all-time.
    /*
    $ach = "SELECT f_match_id AS 'match_id', SUM(REPLACE_BY_ACH) as 'sumA' FROM match_data WHERE f_match_id > 0 GROUP BY f_match_id HAVING sumA > 0 AND sumA = (
        SELECT MAX(sumB) FROM (SELECT SUM(REPLACE_BY_ACH) AS 'sumB' FROM match_data WHERE f_match_id > 0 GROUP BY f_match_id) AS tmpTable
    )";
    $str1 = 'ABS(CONVERT(team1_score,SIGNED) - CONVERT(team2_score,SIGNED))';
    $str2 = '(SELECT MAX(IF(income1>income2, income1, income2)) FROM matches)';
    $svic = "SELECT match_id, $str1 FROM matches WHERE $str1 != 0 AND $str1 = (SELECT MAX($str1) AS 'mdiff' FROM matches HAVING mdiff IS NOT NULL)";
    $inc = "SELECT match_id, income1, income2 FROM matches WHERE (income1 != 0 OR income2 != 0) AND IF(income1>income2, income1 = $str2, income2 = $str2)";
    $gate = "SELECT match_id, gate FROM matches WHERE gate = (SELECT MAX(gate) FROM matches)";
    $mfans = "SELECT match_id, fans FROM matches WHERE fans = (SELECT MAX(fans) FROM matches)";
    $str3 = '((tv1 > tv2 AND team1_score < team2_score) OR (tv1 < tv2 AND team1_score > team2_score))';
    $str4 = 'ABS(CONVERT(tv1,SIGNED) - CONVERT(tv2,SIGNED))';
    $tvdiff = "SELECT match_id, $str4 AS tvdiff FROM matches WHERE $str3 AND $str4 = (SELECT MAX($str4) FROM matches WHERE $str3)";
    */

    // Node filter
    $ref = array(
        STATS_TOUR      => "f_tour_id",
        STATS_DIVISION  => "f_did",
        STATS_LEAGUE    => "f_lid",
    );
    $tables         = ($node) ? ',tours,divisions' : ''; # For matches references.
    $tables_wKey    = ($node) ? "$tables WHERE" : '';
    $where1         = "matches.match_id > 0 AND ";
    $where1        .= ($node) ? 'matches.f_tour_id = tours.tour_id AND tours.f_did = divisions.did AND '.$ref[$node]." = $node_id AND " : ''; # For matches table.
    $where2         = ($node) ? $ref[$node]." = $node_id AND " : ''; # For match_data table.
    $where1_noAnd   = ($node) ? substr($where1, 0, -4) : '';
    $where2_noAnd   = ($node) ? substr($where2, 0, -4) : '';

    // Queries
    $ach = "SELECT f_match_id AS 'match_id', SUM(REPLACE_BY_ACH) as 'sumA' FROM match_data WHERE $where2 f_match_id > 0 GROUP BY f_match_id HAVING sumA > 0 AND sumA = (
        SELECT MAX(sumB) FROM (SELECT SUM(REPLACE_BY_ACH) AS 'sumB' FROM match_data WHERE $where2 f_match_id > 0 GROUP BY f_match_id) AS tmpTable
    )";
    $str1 = 'ABS(CONVERT(team1_score,SIGNED) - CONVERT(team2_score,SIGNED))';
    $str2 = "(SELECT MAX(IF(income1>income2, income1, income2)) FROM matches $tables_wKey $where1_noAnd)";
    $svic = "SELECT match_id, $str1 FROM matches $tables WHERE $where1 $str1 != 0 AND $str1 = (SELECT MAX($str1) AS 'mdiff' FROM matches $tables_wKey $where1_noAnd HAVING mdiff IS NOT NULL)";
    $inc = "SELECT match_id, income1, income2 FROM matches $tables WHERE $where1 (income1 != 0 OR income2 != 0) AND IF(income1>income2, income1 = $str2, income2 = $str2)";
    $gate = "SELECT match_id, gate FROM matches $tables WHERE $where1 gate = (SELECT MAX(gate) FROM matches $tables_wKey $where1_noAnd)";
    $mfans = "SELECT match_id, fans FROM matches $tables WHERE $where1 fans = (SELECT MAX(fans) FROM matches $tables_wKey $where1_noAnd)";
    $str3 = '((tv1 > tv2 AND team1_score < team2_score) OR (tv1 < tv2 AND team1_score > team2_score))';
    $str4 = 'ABS(CONVERT(tv1,SIGNED) - CONVERT(tv2,SIGNED))';
    $tvdiff = "SELECT match_id, $str4 AS tvdiff FROM matches $tables WHERE $where1 $str3 AND $str4 = (SELECT MAX($str4) FROM matches $tables WHERE $where1 $str3)";

    /* Create an array to loop through containing the queries to throw at mysql. */

    $qryarr = array();
    foreach (array_keys(array_slice($m, 0, 5)) as $k) {
        $qryarr[$k] = preg_replace('/REPLACE_BY_ACH/', $k, $ach);
    }
    $qryarr['svic'] = $svic;
    $qryarr['inc'] = $inc;
    $qryarr['gate'] = $gate;
    $qryarr['mfans'] = $mfans;
    $qryarr['tvdiff'] = $tvdiff;

    /* Store match objects for record holding matches. */

    foreach ($qryarr as $k => $query) {
        $mObjs = array();
        if (($result = mysql_query($query)) && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($mObjs, new Match($row['match_id']));
            }
        }
        elseif (mysql_errno() != 0) {
            die("<b>Query:</b><br>\n$query<br>\n<br>\n<b>Error:</b><br>\n".mysql_error()."<br>\n");
        }
        objsort($mObjs, array('+date_played'));
        $m[$k] = (count($mObjs) > MAX_MEM_MATCHES) ? array() : $mObjs;
    }

    /* Rename CAS key */

    $m['cas'] = $m['bh+ki+si'];
    unset($m['bh+ki+si']);

    /* Return, baby. */
    return $m;
}

}
?>
