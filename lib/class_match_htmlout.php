<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2009-2012. All Rights Reserved.
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

// Fields used in match reports
# Stored name => display name
# Don't touch - order denpendant entries!!!
$T_MOUT_REL = array('nr' => '#', 'name' => 'Name', 'pos' => 'Position',);
$T_MOUT_ACH = array_combine($T_PMD_ACH, array('MVP','Cp','Td','Int','BH','SI','Ki',));
$T_MOUT_IR = array_combine($T_PMD_IR, array('IR1 D1','IR1 D2','IR2 D1','IR2 D2','IR3 D1','IR3 D2',));
$T_MOUT_INJ = array_combine($T_PMD_INJ, array('Inj','Ageing 1','Ageing 2'));

class Match_HTMLOUT extends Match
{

const T_HTML_MATCHES_PER_PAGE = 100;

public static function recentMatches() {

    global $lng;
    title($lng->getTrn('menu/matches_menu/recent'));
    list($node, $node_id) = HTMLOUT::nodeSelector(array());
    echo '<br>';
    HTMLOUT::recentGames(false,false,$node,$node_id, false,false,array('url' => 'index.php?section=matches&amp;type=recent', 'n' => MAX_RECENT_GAMES));
}

public static function upcomingMatches() {

    global $lng;
    title($lng->getTrn('menu/matches_menu/upcoming'));
    list($node, $node_id) = HTMLOUT::nodeSelector(array());
    echo '<br>';
    HTMLOUT::upcomingGames(false,false,$node,$node_id, false,false,array('url' => 'index.php?section=matches&amp;type=upcoming', 'n' => MAX_RECENT_GAMES));
}

public static function matchActions($IS_LOCAL_ADMIN) {
    // Admin actions made?
    if (isset($_GET['action']) && $IS_LOCAL_ADMIN) {
		$match = new Match((int) $_GET['mid']);
		switch ($_GET['action'])
		{
			case 'lock':   status($match->setLocked(true)); break;
			case 'unlock': status($match->setLocked(false)); break;
			case 'delete': status($match->delete()); break;
			case 'reset':  status($match->reset()); break;
		}
    }
    else if (isset($_GET['action'])) {
        status(false, 'Sorry, you do not have permission to do that.');
    }
}

public static function tourMatches()
{
    global $lng, $coach;
    global $leagues, $divisions, $tours;

    $trid = (int) $_GET['trid']; # Shortcut for string interpolation.
    if (!isset($trid) || !in_array($trid, array_keys($tours))) { # Not set or not viewable -> deny access.
        fatal('Invalid tournament ID.');
    }
    $IS_LOCAL_ADMIN = (is_object($coach) && $coach->isNodeCommish(T_NODE_TOURNAMENT, (int) $trid));
	self::matchActions($IS_LOCAL_ADMIN);

    $query = "SELECT COUNT(*) FROM matches WHERE f_tour_id = $trid";
    $result = mysql_query($query);
    list($cnt) = mysql_fetch_row($result);
    $pages = ($cnt == 0) ? 1 : ceil($cnt/self::T_HTML_MATCHES_PER_PAGE);
    global $page;
    $page = isset($_GET['page']) ? $_GET['page'] : 1; # Page 1 is default, of course.
    $_url = "?section=matches&amp;type=tourmatches&amp;trid=$trid&amp;";
    title(get_alt_col('tours', 'tour_id', $trid, 'name'));
    echo '<center><table>';
    echo '<tr><td>';
    echo 'Page: '.implode(', ', array_map(create_function('$nr', 'global $page; return ($nr == $page) ? $nr : "<a href=\''.$_url.'page=$nr\'>$nr</a>";'), range(1,$pages)));
    echo '</td></td>';
    echo "<tr><td>    Matches: $cnt</td></td>";
    echo '</table></center>';

    $rnd = 0; # Initial round number must be lower than possible round numbers.
    $cols = 7; # Common columns counter.
    $ROUND_SORT_DIR = (get_alt_col('tours', 'tour_id', $trid, 'type') == TT_RROBIN) ? 'ASC' : 'DESC'; # Sort differently depeding on tour type.
    $query = "SELECT t1.name AS 't1_name', t1.team_id AS 't1_id', t2.name AS 't2_name', t2.team_id AS 't2_id', match_id, date_played, locked, round, team1_score, team2_score, t1.owned_by_coach_id AS 'c1_id', t2.owned_by_coach_id AS 'c2_id',t1.f_cname AS 'c1_name', t2.f_cname AS 'c2_name'
        FROM matches, teams AS t1, teams AS t2 WHERE f_tour_id = $trid AND team1_id = t1.team_id AND team2_id = t2.team_id
        ORDER BY round $ROUND_SORT_DIR, date_played DESC, date_created ASC LIMIT ".(($page-1)*self::T_HTML_MATCHES_PER_PAGE).', '.(($page)*self::T_HTML_MATCHES_PER_PAGE);
    $result = mysql_query($query);
    echo "<table class='tours'>\n";
    while ($m = mysql_fetch_object($result)) {
        if ($m->round != $rnd) {
            $rnd = $m->round;
            $round = '';
            if     ($rnd == RT_FINAL)         $round = $lng->getTrn('matches/tourmatches/roundtypes/final');
            elseif ($rnd == RT_3RD_PLAYOFF)   $round = $lng->getTrn('matches/tourmatches/roundtypes/thirdPlayoff');
            elseif ($rnd == RT_SEMI)          $round = $lng->getTrn('matches/tourmatches/roundtypes/semi');
            elseif ($rnd == RT_QUARTER)       $round = $lng->getTrn('matches/tourmatches/roundtypes/quarter');
            elseif ($rnd == RT_ROUND16)       $round = $lng->getTrn('matches/tourmatches/roundtypes/rnd16');
            else                              $round = $lng->getTrn('matches/tourmatches/roundtypes/rnd').": $rnd";
            echo "<tr><td colspan='$cols' class='seperator'></td></tr>";
            echo "<tr><td colspan='$cols' class='round'><center><b>$round</b></center></td></tr>";
            echo "<tr><td colspan='$cols' class='seperator'></td></tr>";
        }
        ?>
        <tr>
            <td><?php echo !empty($m->date_played) ? textdate($m->date_played, true) : ''; ?></td>
            <td class="match" style="text-align: right;">
                <?php 
                echo "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->t1_id,false,false)."'>$m->t1_name</a>&nbsp;<i>(<a href='".urlcompile(T_URL_PROFILE,T_OBJ_COACH,$m->c1_id,false,false)."'>$m->c1_name</a>)</i>";
                ?>
            </td>
            <td class="match" style="text-align: center;"><?php echo !empty($m->date_played) ? $m->team1_score : '';?></td>
            <td class="match" style="text-align: center;">-</td>
            <td class="match" style="text-align: center;"><?php echo !empty($m->date_played) ? $m->team2_score : '';?></td>
            <td class="match" style="text-align: left;">
                <?php 
                echo "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->t2_id,false,false)."'>$m->t2_name</a>&nbsp;<i>(<a href='".urlcompile(T_URL_PROFILE,T_OBJ_COACH,$m->c2_id,false,false)."'>$m->c2_name</a>)</i>";
                ?>
            </td>
            <?php
            // Does the user have edit or view rights?
            $matchURL = "index.php?section=matches&amp;type=tourmatches&amp;trid=$trid&amp;mid=$m->match_id";
            ?>
            <td>
            <?php
            echo "&nbsp;<a href='index.php?section=matches&amp;type=report&amp;mid=$m->match_id'>".$lng->getTrn('common/view')."</a>&nbsp;\n";
            if ($IS_LOCAL_ADMIN) {
				?>
				<script language="JavaScript" type="text/javascript">
					function match_delete() {
						return confirm('<?php echo $lng->getTrn('matches/tourmatches/matchdelete'); ?>');
					}
					function match_reset() {
						return confirm('<?php echo $lng->getTrn('matches/tourmatches/reset_notice'); ?>');
					}
				</script>
				<?php
                echo "<a onclick=\"return match_reset();\" href='$matchURL&amp;action=reset'>".$lng->getTrn('common/reset')."</a>&nbsp;\n";
                echo "<a onclick=\"return match_delete();\" href='$matchURL&amp;action=delete' style='color:".(!empty($m->date_played) ? 'Red' : 'Blue').";'>".$lng->getTrn('common/delete')."</a>&nbsp;\n";
                echo "<a href='$matchURL&amp;action=".(($m->locked) ? 'unlock' : 'lock')."'>" . ($m->locked ? $lng->getTrn('common/unlock') : $lng->getTrn('common/lock')) . "</a>&nbsp;\n";
            }
            ?>
            </td>
        </tr>
        <?php
    }
    echo "</table>\n";
}

public static function tours()
{

    global $rules, $settings, $lng, $coach;

    title($lng->getTrn('menu/matches_menu/tours'));

    $flist = Coach::allowedNodeAccess(Coach::NODE_STRUCT__TREE, is_object($coach) ? $coach->coach_id : false);
    $tourObjs = array();
    $flist_JShides = array();
    $divsToFoldUp = array();

    // Run through the tours to see which nodes should be hidden.
    $ENABLE_LEAG_HIDING = in_array('league', $settings['tourlist_hide_nodes']);
    $ENABLE_DIV_HIDING  = in_array('division', $settings['tourlist_hide_nodes']);
    $ENABLE_TOUR_HIDING = in_array('tournament', $settings['tourlist_hide_nodes']);
    foreach ($flist as $lid => $divs) {
        $HIDE_LEAG = $ENABLE_LEAG_HIDING;
        foreach ($divs as $did => $tours) {
            if ($did == 'desc') continue;
            $HIDE_DIV = $ENABLE_DIV_HIDING;
            $FOLDUP_DIV = $settings['tourlist_foldup_fin_divs'];
            foreach ($tours as $trid => $desc) {
                if ($trid == 'desc') continue;
                $tourObjs[$trid] = new Tour($trid);
                if ($ENABLE_TOUR_HIDING && $tourObjs[$trid]->is_finished) $flist_JShides[] = "trid_$trid";
                $HIDE_DIV   &= $tourObjs[$trid]->is_finished;
                $FOLDUP_DIV &= $tourObjs[$trid]->is_finished;
            }
            if ($HIDE_DIV) $flist_JShides[] = "did_$did";
            if ($FOLDUP_DIV) $divsToFoldUp[] = $did;
            $HIDE_LEAG &= $HIDE_DIV;
        }
        if ($HIDE_LEAG) $flist_JShides[] = "lid_$lid";
    }

    // Print show hidden button.
    ?>
    <script language="JavaScript" type="text/javascript">
        function showFullTourList()
        {
            var hidden=[<?php echo array_strpack("'%s'", $flist_JShides, ',') ?>];
            for (var h in hidden) {
                slideToggleFast(hidden[h]+'_head');
                slideToggleFast(hidden[h]+'_cont');
            }
            return;
        }
    </script>
    <?php
    echo "<a href='javascript:void(0)' onClick='showFullTourList();'>".$lng->getTrn('matches/tours/showhidden')."</a><br>";

    // Print fixture list.
    foreach ($flist as $lid => $divs) {
        # Container
        echo "<div id='lid_${lid}_cont' class='leaguesNCont' style='".((in_array("lid_${lid}", $flist_JShides)) ? "display:none;" : '')."'>";
        # Title
        echo "<div class='leagues'><b><a href='javascript:void(0);' onClick=\"slideToggleFast('lid_$lid');\">[+/-]</a>&nbsp;".$flist[$lid]['desc']['lname']."</b></div>\n";
        # Body
        echo "<div id='lid_$lid'>";
    foreach ($divs as $did => $tours) {
        if ($did == 'desc') continue;
        # Container
        echo "<div id='did_${did}_cont' class='divisionsNCont' style='".((in_array("did_${did}", $flist_JShides)) ? "display:none;" : '')."'>";
        # Title
        echo "<div class='divisions'><b><a href='javascript:void(0);' onClick=\"slideToggleFast('did_$did');\">[+/-]</a>&nbsp;".$flist[$lid][$did]['desc']['dname']."</b></div>";
        # Body
        echo "<div id='did_$did' ".((in_array($did, $divsToFoldUp)) ? 'style="display:none;"' : '').">";
    foreach ($tours as $trid => $desc) {
        if ($trid == 'desc') continue;
        # Container
        echo "<div id='trid_${trid}_cont' class='toursNCont' style='".((in_array("trid_${trid}", $flist_JShides)) ? "display:none;" : '')."'>";
        # Title
        echo "<div class='tours'><a href='index.php?section=matches&amp;type=tourmatches&amp;trid=$trid'>".$flist[$lid][$did][$trid]['desc']['tname']."</a>";
        $tr = $tourObjs[$trid]; # We already have loaded these - reuse them!
        $suffix = '';
        if ($tr->is_finished) { $suffix .= '-&nbsp;&nbsp;<i>'.$lng->getTrn('common/finished').'</i>&nbsp;&nbsp;';}
        if ($tr->locked)      { $suffix .= '-&nbsp;&nbsp;<i>'.$lng->getTrn('common/locked').'</i>&nbsp;&nbsp;';}
        if (!empty($suffix)) { echo '&nbsp;&nbsp;'.$suffix;}
        echo "</div>\n"; # tour title container
        echo "</div>\n"; # tour container
    }
    echo "</div>\n"; # div body container
    echo "</div>\n"; # div container
    }
    echo "</div>\n"; # league body container
    echo "</div>\n"; # league container
    }

}

public static function report() {

    // Is $match_id valid?
    $match_id = (int) $_GET['mid'];
    if (!get_alt_col('matches', 'match_id', $match_id, 'match_id'))
        fatal("Invalid match ID.");

    global $lng, $stars, $rules, $settings, $coach, $racesHasNecromancer, $racesMayRaiseRotters, $DEA, $T_PMD__ENTRY_EXPECTED;
    global $T_MOUT_REL, $T_MOUT_ACH, $T_MOUT_IR, $T_MOUT_INJ;
    global $leagues,$divisions,$tours;
    $T_ROUNDS = Match::getRounds();

	// Perform actions (delete, lock/unlock and reset). Needs the
    $IS_LOCAL_ADMIN = (is_object($coach) && $coach->isNodeCommish(T_NODE_TOURNAMENT, get_alt_col('matches', 'match_id', $match_id, 'f_tour_id')));
	self::matchActions($IS_LOCAL_ADMIN);

    // Create objects
    $m = new Match($match_id);
    $team1 = new Team($m->team1_id);
    $team2 = new Team($m->team2_id);

    // Determine visitor privileges.
    $lid = $divisions[$tours[$m->f_tour_id]['f_did']]['f_lid'];
    $ALLOW_EDIT = (!$m->locked && is_object($coach) && ($coach->ring == Coach::T_RING_GLOBAL_ADMIN || $leagues[$lid]['ring'] == Coach::T_RING_LOCAL_ADMIN || $coach->isInMatch($m->match_id)));
    $DIS = ($ALLOW_EDIT) ? '' : 'DISABLED';

    // Lock page for other reasons? (Used journeys etc)
    $USED_JOURNEYMAN_PRESENT = false;
    foreach (array(1 => $team1, 2 => $team2) as $id => $t) {
        foreach ($t->getPlayers() as $p) {
            if (!self::player_validation($p, $m)) {continue;}
            if (!$m->is_played && $p->is_journeyman_used) {$USED_JOURNEYMAN_PRESENT = true;}
        }
    }
    if ($USED_JOURNEYMAN_PRESENT) {$DIS = 'DISABLED';}

    // Relay to ES report page?
    if (isset($_GET['es_report'])) { # Don't care what value the GET field has!
        self::report_ES($match_id, !$ALLOW_EDIT);
        return;
    }

    $easyconvert = new array_to_js();
    @$easyconvert->add_array($stars, 'phpStars'); // Load stars array into JavaScript array.
    echo $easyconvert->output_all();

    echo '<script language="JavaScript" type="text/javascript">
    var ID_MERCS = '.ID_MERCS.';
    var ID_STARS_BEGIN = '.ID_STARS_BEGIN.';
    </script>
    ';

    /*****************
     *
     * Submitted form?
     *
     *****************/

    if (isset($_POST['button']) && $ALLOW_EDIT) {

        if (get_magic_quotes_gpc())
            $_POST['summary'] =  stripslashes($_POST['summary']);

        MTS('Report submit STARTED');

        // FIRST, if any raised zombies are kept we need to create their player objects in order have the correct player- vs. match creation & played dates.
        foreach (array(1 => $team1, 2 => $team2) as $id => $t) {
            if (in_array($t->f_race_id, $racesHasNecromancer) && isset($_POST["t${id}zombie"])) {
                $pos_id = $DEA[$t->f_rname]['players']['Zombie']['pos_id'];
                list($exitStatus,$pid) = Player::create(
                    array(
                        'nr' => $t->getFreePlayerNr(),
                        'f_pos_id' => $pos_id,
                        'team_id' => $t->team_id,
                        'name' => 'RAISED ZOMBIE'
                    ),
                    array(
                        'free' => true,
                    ));
                /*
                    Knowing the new zombie's PID we relocate the zombie match data to regular player data - this allows us
                    to use the same loop for submitting the zombie's match data.
                */
                foreach ($T_PMD__ENTRY_EXPECTED as $f) {
                    $postName = "${f}_t${id}zombie";
                    $_POST["${f}_$pid"] = isset($_POST[$postName]) ? $_POST[$postName] : 0;
                    unset($_POST[$postName]);
                }
            }
        }

        // SECONDLY, look for raised rotters too, do same as above with zombies...
        foreach (array(1 => $team1, 2 => $team2) as $id => $t) {
            if (in_array($t->f_race_id, $racesMayRaiseRotters) && isset($_POST["t${id}rotterCnt"]) && ($N = (int) $_POST["t${id}rotterCnt"]) > 0) {
                foreach (range(1,$N) as $n) {
                    $pos_id = $DEA[$t->f_rname]['players']['Rotter']['pos_id'];
                    list($exitStatus,$pid) = Player::create(
                        array(
                            'nr' => $t->getFreePlayerNr(),
                            'f_pos_id' => $pos_id,
                            'team_id' => $t->team_id,
                            'name' => "RAISED ROTTER $n"
                        ),
                        array(
                            'free' => true,
                        ));

                    /*
                        Knowing the new rotter's PID we relocate the rotter match data to regular player data - this allows us
                        to use the same loop for submitting the rotter's match data.
                    */
                    foreach ($T_PMD__ENTRY_EXPECTED as $f) {
                        $postName = "${f}_t${id}rotter$n";
                        $_POST["${f}_$pid"] = isset($_POST[$postName]) ? $_POST[$postName] : 0;
                        unset($_POST[$postName]);
                    }
                }
            }
        }

        // Update general match data
        status($m->update(array(
            'submitter_id'  => (int) $_SESSION['coach_id'],
            'stadium'       => (int) $_POST['stadium'],
            'gate'          => (int) $_POST['gate']*1000,
            'fans'          => (int) $_POST['fans'],
            'ffactor1'      => (int) $_POST['ff1'],
            'ffactor2'      => (int) $_POST['ff2'],
            'income1'       => (int) $_POST['inc1']*1000,
            'income2'       => (int) $_POST['inc2']*1000,
            'team1_score'   => (int) $_POST['result1'],
            'team2_score'   => (int) $_POST['result2'],
            'smp1'          => (int) $_POST['smp1'],
            'smp2'          => (int) $_POST['smp2'],
            'tcas1'         => (int) $_POST['tcas1'],
            'tcas2'         => (int) $_POST['tcas2'],
            'fame1'         => (int) $_POST['fame1'],
            'fame2'         => (int) $_POST['fame2'],
            'tv1'           => (int) $_POST['tv1']*1000,
            'tv2'           => (int) $_POST['tv2']*1000,
        )), 'Saving match report');
        if (!empty($_POST['summary'])) {
            $m->saveText($_POST['summary']); # Save summery.
        }
        MTS('matches entry submitted');

        // Update match's player data
        foreach (array(1 => $team1, 2 => $team2) as $id => $t) {

            /* Save ordinary players */

            foreach ($t->getPlayers() as $p) {

                if (!self::player_validation($p, $m))
                    continue;

                // We create zero entries for MNG player(s). This is required!
                $pid = $p->player_id; # Shortcut
                if ($p->getStatus($m->match_id) == MNG) {
                    $_POST["mvp_$pid"]      = 0;
                    $_POST["cp_$pid"]       = 0;
                    $_POST["td_$pid"]       = 0;
                    $_POST["intcpt_$pid"]   = 0;
                    $_POST["bh_$pid"]       = 0;
                    $_POST["si_$pid"]       = 0;
                    $_POST["ki_$pid"]       = 0;
                    $_POST["ir1_d1_$pid"]   = 0;
                    $_POST["ir1_d2_$pid"]   = 0;
                    $_POST["ir2_d1_$pid"]   = 0;
                    $_POST["ir2_d2_$pid"]   = 0;
                    $_POST["ir3_d1_$pid"]   = 0;
                    $_POST["ir3_d2_$pid"]   = 0;
                    $_POST["inj_$pid"]      = NONE;
                    $_POST["agn1_$pid"]     = NONE;
                    $_POST["agn2_$pid"]     = NONE;
                }

                $m->entry($p->player_id, array(
                    'mvp'     => $_POST["mvp_$pid"], # NOT checkbox
                    'cp'      => $_POST["cp_$pid"],
                    'td'      => $_POST["td_$pid"],
                    'intcpt'  => $_POST["intcpt_$pid"],
                    'bh'      => $_POST["bh_$pid"],
                    'si'      => $_POST["si_$pid"],
                    'ki'      => $_POST["ki_$pid"],
                    'ir1_d1'  => $_POST["ir1_d1_$pid"],
                    'ir1_d2'  => $_POST["ir1_d2_$pid"],
                    'ir2_d1'  => $_POST["ir2_d1_$pid"],
                    'ir2_d2'  => $_POST["ir2_d2_$pid"],
                    'ir3_d1'  => $_POST["ir3_d1_$pid"],
                    'ir3_d2'  => $_POST["ir3_d2_$pid"],
                    'inj'     => $_POST["inj_$pid"],
                    'agn1'    => $_POST["agn1_$pid"],
                    'agn2'    => $_POST["agn2_$pid"],
                ));
            }
            MTS('Saved all REGULAR player entries in match_data for team '.$id);

            /*
                Save stars entries.
            */

            foreach ($stars as $star) {
                $s = new Star($star['id']);
                if (isset($_POST['team_'.$star['id']]) && $_POST['team_'.$star['id']] == $id) {
                    $sid = $s->star_id;

                    $m->entry($sid, array(
                        // Star required input
                        'f_team_id' => $t->team_id,
                        // Regular input
                        'mvp'     => (isset($_POST["mvp_$sid"]) && $_POST["mvp_$sid"]) ? 1 : 0, # Checkbox
                        'cp'      => $_POST["cp_$sid"],
                        'td'      => $_POST["td_$sid"],
                        'intcpt'  => $_POST["intcpt_$sid"],
                        'bh'      => $_POST["bh_$sid"],
                        'si'      => $_POST["si_$sid"],
                        'ki'      => $_POST["ki_$sid"],
                        'ir1_d1'  => 0,
                        'ir1_d2'  => 0,
                        'ir2_d1'  => 0,
                        'ir2_d2'  => 0,
                        'ir3_d1'  => 0,
                        'ir3_d2'  => 0,
                        'inj'     => NONE,
                        'agn1'    => NONE,
                        'agn2'    => NONE,
                    ));
                }
                else {
                    $s->rmMatchEntry($m->match_id, $t->team_id);
                }
            }
            MTS('Saved all STAR player entries in match_data for team '.$id);

            /*
                Save mercenary entries.
            */

            Mercenary::rmMatchEntries($m->match_id, $t->team_id); // Remove all previously saved mercs in this match.
            for ($i = 0; $i <= 20; $i++)  { # We don't expect over 20 mercs. This is just some large random number.
                $idm = '_'.ID_MERCS.'_'.$i;
                if (isset($_POST["team$idm"]) && $_POST["team$idm"] == $id) {
                    $m->entry(ID_MERCS, array(
                        // Merc required input
                        'f_team_id' => $t->team_id,
                        'nr'        => $i,
                        'skills'    => $_POST["skills$idm"],
                        // Regular input
                        'mvp'     => (isset($_POST["mvp$idm"]) && $_POST["mvp$idm"]) ? 1 : 0, # Checkbox
                        'cp'      => $_POST["cp$idm"],
                        'td'      => $_POST["td$idm"],
                        'intcpt'  => $_POST["intcpt$idm"],
                        'bh'      => $_POST["bh$idm"],
                        'si'      => $_POST["si$idm"],
                        'ki'      => $_POST["ki$idm"],
                        'ir1_d1'  => 0,
                        'ir1_d2'  => 0,
                        'ir2_d1'  => 0,
                        'ir2_d2'  => 0,
                        'ir3_d1'  => 0,
                        'ir3_d2'  => 0,
                        'inj'     => NONE,
                        'agn1'    => NONE,
                        'agn2'    => NONE,
                    ));
                }
            }
            MTS('Saved all MERC player entries in match_data for team '.$id);
        }

        $m->finalizeMatchSubmit(); # Required!
        MTS('Report submit ENDED');

        // Refresh objects used to display form.
        $m = new Match($match_id);
        $team1 = new Team($m->team1_id);
        $team2 = new Team($m->team2_id);
    }

    // Change round form submitted?
    if ($IS_LOCAL_ADMIN && isset($_POST['round'])) {
        status($m->chRound((int) $_POST['round']));
    }

    /****************
     *
     * Generate form
     *
     ****************/
    $teamUrl1 = "<a href=\"" . urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team1_id,false,false) . "\">" . $m->team1_name . "</a>";
    $teamUrl2 = "<a href=\"" . urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team2_id,false,false) . "\">" . $m->team2_name . "</a>";
    $coachUrl1 = "<a href=\"" . urlcompile(T_URL_PROFILE,T_OBJ_COACH,$team1->owned_by_coach_id,false,false) . "\">" . $team1->f_cname . "</a>";
    $coachUrl2 = "<a href=\"" . urlcompile(T_URL_PROFILE,T_OBJ_COACH,$team2->owned_by_coach_id,false,false) . "\">" . $team2->f_cname . "</a>";
    $raceUrl1 = "<a href=\"" . urlcompile(T_URL_PROFILE,T_OBJ_RACE,$team1->f_race_id,false,false) . "\">" .$lng->getTrn('race/'.strtolower(str_replace(' ','', $team1->f_rname))) . "</a>";
    $raceUrl2 = "<a href=\"" . urlcompile(T_URL_PROFILE,T_OBJ_RACE,$team2->f_race_id,false,false) . "\">" .$lng->getTrn('race/'.strtolower(str_replace(' ','', $team2->f_rname))). "</a>";

    $leagueUrl = League::getLeagueUrl(get_parent_id(T_NODE_MATCH, $m->match_id, T_NODE_LEAGUE));
    $divUrl = "<a href=\"" . urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,T_NODE_DIVISION,get_parent_id(T_NODE_MATCH, $m->match_id, T_NODE_DIVISION)) . "\">" . get_parent_name(T_NODE_MATCH, $m->match_id, T_NODE_DIVISION) . "</a>";
    $tourUrl = Tour::getTourUrl(get_parent_id(T_NODE_MATCH, $m->match_id, T_NODE_TOURNAMENT));

    title($teamUrl1 . " - " . $teamUrl2);
    $CP = 8; // Colspan.

    ?>
    <table>
    <tr><td></td><td style='text-align: right;'><i><?php echo $lng->getTrn('common/home');?></i></td><td>&mdash;</td><td style='text-align: left;'><i><?php echo $lng->getTrn('common/away');?></i></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/teams');?></b>:</td><td style='text-align: right;'><?php echo "$teamUrl1</td><td> &mdash; </td><td style='text-align: left;'>$teamUrl2";?></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/coaches');?></b>:</td><td style='text-align: right;'><?php echo "$coachUrl1</td><td> &mdash; </td><td style='text-align: left;'>$coachUrl2";?></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/races');?></b>:</td><td style='text-align: right;'><?php echo "$raceUrl1</td><td> &mdash; </td><td style='text-align: left;'>$raceUrl2";?></td></tr>
    <tr><td colspan="4"><hr></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/league');?></b>:</td><td colspan="3">    <?php   echo $leagueUrl; ?></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/division');?></b>:</td><td colspan="3">  <?php   echo $divUrl;?></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/tournament');?></b>:</td><td colspan="3"><?php   echo $tourUrl;?></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/round');?></b>:</td><td colspan="3">     <?php   echo $T_ROUNDS[$m->round];?></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/dateplayed');?></b>:</td><td colspan="3"><?php   echo ($m->is_played) ? textdate($m->date_played) : '<i>'.$lng->getTrn('matches/report/notplayed').'</i>';?></td></tr>
    <?php
    if (Module::isRegistered('PDFMatchReport')) {
        $str = '<a href="handler.php?type=pdfmatchreport&amp;tid1='.$team1->team_id.'&amp;tid2='.$team2->team_id.'&amp;mid='.$m->match_id.'" TARGET="_blank">Download PDF report</a>';
        echo "<tr><td><b>Match report</b>:</td><td>$str</td></tr>";
    }
    if (Module::isRegistered('UPLOAD_BOTOCS')) {
        echo "<tr><td><b>Replay</b>:</td><td colspan='3'><a href='handler.php?type=leegmgr&amp;replay=$m->match_id'>View replay</a></td></tr>";
    }
	if ($IS_LOCAL_ADMIN) {
		?>
		<script language="JavaScript" type="text/javascript">
			function match_delete() {
				return confirm('<?php echo $lng->getTrn('matches/tourmatches/matchdelete'); ?>');
			}
			function match_reset() {
				return confirm('<?php echo $lng->getTrn('matches/tourmatches/reset_notice'); ?>');
			}
		</script>
	    <?php
		$matchURL = "index.php?section=matches&type=report&amp;mid=$m->match_id";
		$deleteURL = "index.php?section=matches&amp;type=tourmatches&amp;trid=$m->f_tour_id&amp;mid=$m->match_id";

		echo "<tr><td><b>Admin:</b></td><td colspan='3'><b>";
		echo "<a onclick=\"return match_reset();\" href='$matchURL&amp;action=reset'>".$lng->getTrn('common/reset')."</a>&nbsp;\n";
		echo "<a onclick=\"return match_delete();\" href='$deleteURL&amp;action=delete' style='color:".(!empty($m->date_played) ? 'Red' : 'Blue').";'>".$lng->getTrn('common/delete')."</a>&nbsp;\n";
		echo "<a href='$matchURL&amp;action=".(($m->locked) ? 'unlock' : 'lock')."'>" . ($m->locked ? $lng->getTrn('common/unlock') : $lng->getTrn('common/lock')) . "</a>&nbsp;\n";
		echo "<br><a href='javascript:void(0);' onClick='slideToggleFast(\"chRound\");'>".$lng->getTrn('matches/report/chround')."</a><div id='chRound' style='display:none;'>
		<form method='POST'>
		<select name='round'>";
		foreach ($T_ROUNDS as $id => $desc ) {
		    echo "<option value='$id'>".$desc."</option>\n";
	    }
		echo "</select>
		<input type='submit' value='".$lng->getTrn('matches/report/chround')."'>
		</form>
		</div>";
		echo "</b></td></tr>";
	}
?>
    </table>
    <br>
    <?php echo "<b><a TARGET='_blank' href='".DOC_URL_GUIDE."'>".$lng->getTrn('common/needhelp')."</a></b><br><br>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <table class="common">
            <tr class='commonhead'><td colspan="<?php echo $CP;?>"><b><?php echo $lng->getTrn('matches/report/info');?></b></td></tr>
            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>
            <tr><td colspan='<?php echo $CP;?>'>
                <b><?php echo $lng->getTrn('matches/report/stadium');?></b>&nbsp;
                <select name="stadium" <?php echo $DIS;?>>
                    <?php
                    $stad = ($m->stadium) ? $m->stadium : $m->team1_id;
                    foreach (array($team1, $team2) as $_t) {
                        echo "<option value='$_t->team_id'".(($stad == $_t->team_id) ? 'SELECTED' : '').">$_t->name</option>\n";
                    }
                    ?>
                </select>
            </td></tr>
            <tr><td colspan='<?php echo $CP;?>'>
                <b><?php echo $lng->getTrn('common/gate');?></b>&nbsp;
                <input type="text" name="gate" onChange='numError(this);' value="<?php echo $m->gate ? $m->gate/1000 : 0;?>" size="4" maxlength="4" <?php echo $DIS;?>>k
            </td></tr>
            <tr><td colspan='<?php echo $CP;?>'>
                <b><?php echo $lng->getTrn('matches/report/fans');?></b>&nbsp;
                <input type="text" name="fans" onChange='numError(this);' value="<?php echo $m->fans;?>" size="7" maxlength="12" <?php echo $DIS;?>>
            </td></tr>
            <?php
            if (!$settings['hide_ES_extensions']) {
                ?>
                <tr><td colspan='<?php echo $CP;?>'>
                    <b>E</b>xtra player <b>S</b>tats (ES) <a href="index.php?section=matches&amp;type=report&amp;mid=<?php echo $m->match_id?>&amp;es_report=1">report page here</a>
                </td></tr>
                <?php
            }
            ?>
            <tr><td class="seperator" colspan='<?php echo $CP;?>'></td></tr>
            <tr class='commonhead'>
                <td><b><?php echo $lng->getTrn('common/teams');?></b></td>
                <td><b><?php echo $lng->getTrn('common/score');?></b></td>
                <td><b>&Delta; <?php echo $lng->getTrn('matches/report/treas');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/ff');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/smp');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/tcas');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/fame');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/tv');?></b></td>
            </tr>

            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>
            <?php
            foreach (array(1,2) as $N) {
                echo "<tr>\n";
                echo "<td>".${"teamUrl$N"}."</td>\n";
                echo "<td><input type='text' onChange='numError(this);' name='result$N' value='".((int) $m->{"team${N}_score"})."' size='1' maxlength='2' $DIS></td>\n";
                echo "<td><input type='text' onChange='numErrorAllowNegative(this);' name='inc$N' value='".(((int) $m->{"income$N"})/1000)."' size='4' maxlength='4' $DIS>k</td>\n";
                echo "<td>";
                foreach (array('1' => 'green', '0' => 'blue', '-1' => 'red') as $Nff => $color) {
                    echo "<input $DIS type='radio' name='ff$N' value='$Nff' ".(($m->{"ffactor$N"} == (int) $Nff) ? 'CHECKED' : '')."><font color='$color'><b>$Nff</b></font>";
                }
                echo "</td>\n";
                echo "<td><input type='text' onChange='numError(this);' name='smp$N' value='".($m->{"smp$N"})."' size='1' maxlength='2' $DIS>".$lng->getTrn('matches/report/pts')."</td>\n";
                echo "<td><input type='text' onChange='numError(this);' name='tcas$N' value='".($m->{"tcas$N"})."' size='1' maxlength='2' $DIS></td>\n";
                echo "<td><input type='text' onChange='numError(this);' name='fame$N' value='".($m->{"fame$N"})."' size='1' maxlength='2' $DIS></td>\n";
                echo "<td><input type='text' onChange='numError(this);' name='tv$N' value='".($m->is_played ? $m->{"tv$N"}/1000 : ${"team$N"}->value/1000)."' size='4' maxlength='10' $DIS>k</td>\n";
                echo "</tr>\n";
            }
            ?>
        </table>

        <?php
        $playerFields = array_merge($T_MOUT_REL, $T_MOUT_ACH, $T_MOUT_IR, $T_MOUT_INJ);
        $CPP = count($playerFields);
        foreach (array(1 => $team1, 2 => $team2) as $id => $t) {
            ?>
            <table class='common'>
            <tr><td class='seperator' colspan='<?php echo $CPP;?>'></td></tr>
            <tr class='commonhead'><td colspan='<?php echo $CPP;?>'>
                <b><a href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$t->team_id,false,false);?>"><?php echo $t->name;?></a> <?php echo $lng->getTrn('matches/report/report');?></b>
            </td></tr>
            <tr><td class='seperator' colspan='<?php echo $CPP;?>'></td></tr>
            <?php
            echo "<tr>\n";
            foreach (array_values($playerFields) as $f) {
               // We need to translate table headers
               switch(strtolower(str_replace(' ', '', $f)))
               {
                   case 'name': $header_text = $lng->getTrn('common/name'); break;
                   case 'mvp': $header_text = $lng->getTrn('matches/report/mvp'); break;
                   case 'cp': $header_text = $lng->getTrn('matches/report/cp'); break;
                   case 'bh': $header_text = $lng->getTrn('matches/report/bh'); break;
                   case 'si': $header_text = $lng->getTrn('matches/report/si'); break;
                   case 'ki': $header_text = $lng->getTrn('matches/report/ki'); break;
                   case 'ir1d1': $header_text = $lng->getTrn('matches/report/ir1')." D1"; break;
                   case 'ir1d2': $header_text = $lng->getTrn('matches/report/ir1')." D2"; break;
                   case 'ir2d1': $header_text = $lng->getTrn('matches/report/ir2')." D1"; break;
                   case 'ir2d2': $header_text = $lng->getTrn('matches/report/ir2')." D2"; break;
                   case 'ir3d1': $header_text = $lng->getTrn('matches/report/ir3')." D1"; break;
                   case 'ir3d2': $header_text = $lng->getTrn('matches/report/ir3')." D2"; break;
                   case 'inj': $header_text = $lng->getTrn('matches/report/inj'); break;
                   case 'ageing1': $header_text = $lng->getTrn('matches/report/ageing1'); break;
                   case 'ageing2': $header_text = $lng->getTrn('matches/report/ageing2'); break;
                   default: $header_text = $f;
                 }
                 echo "<td><i>$header_text</i></td>\n";
            }
            echo "</tr>\n";

            $NORMSTAT = true; // only normal player statuses
            foreach ($t->getPlayers() as $p) {

                if (!self::player_validation($p, $m))
                    continue;

                // Fetch player data from match
                $status = $p->getStatus($m->match_id);
                $mdat   = $m->getPlayerEntry($p->player_id);

                // Print player row
                if ($p->is_journeyman_used && !$m->is_played)   {$bgcolor = COLOR_HTML_JOURNEY_USED;    $NORMSTAT = false;}
                elseif ($p->is_journeyman)                      {$bgcolor = COLOR_HTML_JOURNEY;         $NORMSTAT = false;}
                elseif ($status == MNG)                         {$bgcolor = COLOR_HTML_MNG;             $NORMSTAT = false;}
                elseif ($p->mayHaveNewSkill())                  {$bgcolor = COLOR_HTML_NEWSKILL;        $NORMSTAT = false;}
                else {$bgcolor = false;}
                self::_print_player_row($p->player_id, '<a href="index.php?section=objhandler&type=1&obj=1&obj_id='.$p->player_id.'">'.$p->name.'</a>', $p->nr, $lng->getTrn('position/'.strtolower($lng->FilterPosition($p->position))).(($status == MNG) ? '&nbsp;[MNG]' : ''),$bgcolor, $mdat, $DIS || ($status == MNG));
            }
            echo "</table>\n";
            echo "<br>\n";
            if (!$NORMSTAT) {
            ?><table class="text"><tr><td style="width: 100%;"></td><?php
                if (1) {
                    ?>
                    <td style="background-color: <?php echo COLOR_HTML_MNG;     ?>;"><font color='black'><b>&nbsp;MNG&nbsp;</b></font></td>
                    <td style="background-color: <?php echo COLOR_HTML_JOURNEY; ?>;"><font color='black'><b>&nbsp;Journeyman&nbsp;</b></font></td>
                    <td style="background-color: <?php echo COLOR_HTML_JOURNEY_USED; ?>;"><font color='black'><b>&nbsp;Used&nbsp;journeyman&nbsp;</b></font></td>
                    <td style="background-color: <?php echo COLOR_HTML_NEWSKILL;?>;"><font color='black'><b>&nbsp;New&nbsp;skill&nbsp;available&nbsp;</b></font></td>
                    <?php
                }
            ?></tr></table><?php
            }

            // Add raised zombies
            global $racesHasNecromancer;
            if (in_array($t->f_race_id, $racesHasNecromancer)) {
                echo "<hr style='width:200px;float:left;'><br>
                <b>Raised zombie?:</b> <input type='checkbox' name='t${id}zombie' value='1' onclick='slideToggleFast(\"t${id}zombie\");'><br>\n";
                echo "<div id='t${id}zombie' style='display:none;'>\n";
                echo "<table class='common'>\n";
                self::_print_player_row("t${id}zombie", 'Raised zombie', '&mdash;', 'Zombie', false, array(), $DIS);
                echo "</table>\n";
                echo "</div>\n";
            }
            // Add raised rotters
            global $racesMayRaiseRotters;
            if (in_array($t->f_race_id, $racesMayRaiseRotters)) {
                $maxRotters = 6; # Note there is no real limit for raised rotters.
                echo "<hr style='width:200px;float:left;'><br>
                <b>Raised rotters?:</b>
                <select name='t${id}rotterCnt' onChange='var i = this.options[this.selectedIndex].value; var j=1; for (j=1; j<=$maxRotters; j++) {if (j<=i) {slideDownFast(\"t${id}rotter\"+j);} else {slideUpFast(\"t${id}rotter\"+j);}}' >";
                foreach (range(0,$maxRotters) as $n) {echo "<option value='$n'>$n</option>";}
                echo "</select>\n";
                foreach (range(0,$maxRotters) as $n) {
                    echo "<div id='t${id}rotter$n' style='display:none;'><table class='common'>\n";
                    self::_print_player_row("t${id}rotter$n", "Raised Rotter #$n", '&mdash;', 'Rotter', false, array(), $DIS);
                    echo "</table></div>\n";
                }
            }
            ?>

            <table style='border-spacing: 0px 10px;'>
                <tr><td align="left" valign="top">
                    <b>Star Players</b>:
                    <input type='button' id="addStarsBtn_<?php echo $id;?>" value="<?php echo $lng->getTrn('common/add');?>"
                    onClick="stars = document.getElementById('stars_<?php echo $id;?>'); addStarMerc(<?php echo $id;?>, stars.options[stars.selectedIndex].value);" <?php echo $DIS; ?>>
                    <select id="stars_<?php echo $id;?>" <?php echo $DIS; ?>>
                        <?php
                        foreach ($stars as $s => $d) {
                            echo "<option ".((in_array($t->f_race_id, $d['races'])) ? 'style="background-color: '.COLOR_HTML_READY.';"' : '')." value='$d[id]'>$s</option>\n";
                        }
                        ?>
                    </select>
                </td></tr>
                <tr><td align="left" valign="top">
                    <b>Mercenaries</b>: <input type='button' id="addMercsBtn_<?php echo $id;?>" value="<?php echo $lng->getTrn('common/add');?>" onClick="addStarMerc(<?php echo "$id, ".ID_MERCS;?>);" <?php echo $DIS; ?>>
                </td></tr>
            </table>

            <table class='common' id='<?php echo "starsmercs_$id";?>'>
            </table>
            <?php
        }
        ?>
        <table class='common'>
            <tr><td class='seperator' colspan='13'></td></tr>
            <tr class='commonhead'><td colspan='13'><b><?php echo $lng->getTrn('matches/report/summary');?></b></td></tr>
            <tr><td colspan='13'><textarea name='summary' rows='10' cols='100' <?php echo $DIS . ">" . $m->getText(); ?></textarea></td></tr>
        </table>
        <br>
        <center>
            <input type="submit" name='button' value="<?php echo $lng->getTrn('common/save');?>" <?php echo $DIS; ?>>
            <?php if ($USED_JOURNEYMAN_PRESENT) {echo "<br><br><b>".$lng->getTrn('matches/report/usedjourney')."</b>";} ?>
        </center>
    </form>
    <br><br>
    <?php

    /*
        Now, we call javascript routine(s) to fill out stars and mercs rows, if such entries exist in database.
    */

    $i = 0; // Counter. Used to pass PHP-data to Javascript.
    foreach (array(1 => $team1->team_id, 2 => $team2->team_id) as $id => $t) {
        foreach (Star::getStars(STATS_TEAM, $t, STATS_MATCH, $m->match_id) as $s) {
            echo "<script language='JavaScript' type='text/javascript'>\n";
            echo "var mdat$i = [];\n";
            $mdat = $s->getStats(T_NODE_MATCH,$m->match_id);
            foreach (array_keys($T_MOUT_ACH) as $f) {
                echo "mdat${i}['$f'] = ".$mdat[$f].";\n";
            }
            echo "existingStarMerc($id, $s->star_id, mdat$i);\n";
            echo "</script>\n";
            $i++;
        }

        foreach (Mercenary::getMercsHiredByTeam($t, $m->match_id) as $merc) {
            echo "<script language='JavaScript' type='text/javascript'>\n";
            echo "var mdat$i = [];\n";
            foreach (array_merge(array_keys($T_MOUT_ACH), array('skills')) as $f) {
                echo "mdat${i}['$f'] = ".$merc->$f.";\n";
            }
            echo "existingStarMerc($id, ".ID_MERCS.", mdat$i);\n";
            echo "</script>\n";
            $i++;
        }
    }
}


protected static function _print_player_row($FS, $name, $nr, $pos, $bgcolor, $mdat, $DISABLE) {

    global $T_MOUT_REL, $T_MOUT_ACH, $T_MOUT_IR, $T_MOUT_INJ;

    $DIS = ($DISABLE) ? 'DISABLED' : '';
    echo "<tr".(($bgcolor) ? " style='background-color: $bgcolor;'" : '').">\n";
    echo "<td>$nr</td>\n";
    echo "<td>$name</td>\n";
    echo "<td>$pos</td>\n";
    // MVP
    echo "<td><select $DIS name='mvp_$FS'>";
    foreach (range(0,2) as $n) {echo "<option value='$n' ".((isset($mdat['mvp']) && $mdat['mvp'] == $n) ? 'SELECTED' : '').">$n</option>";}
    echo "</select>\n";
    // Rest of ACH.
    foreach (array_diff(array_keys($T_MOUT_ACH), array('mvp')) as $f) {
        echo "<td><input $DIS type='text' onChange='numError(this);' size='1' maxlength='2' name='${f}_$FS' value='".(isset($mdat[$f]) ? $mdat[$f] : 0)."'></td>\n";
    }
    foreach (array_keys($T_MOUT_IR) as $irl) {
        echo "<td><select name='${irl}_$FS' $DIS>";
        foreach (range(0,6) as $N) {
            echo "<option value='$N' ".((isset($mdat[$irl]) && $mdat[$irl] == $N) ? 'SELECTED' : '').">$N</option>";
        }
        echo "</select></td>\n";
    }
    global $T_INJS;
    $T_INJS_AGN = array_diff_key($T_INJS, array(MNG => null, DEAD => null));
    foreach (array_combine(array_keys($T_MOUT_INJ), array($T_INJS, $T_INJS_AGN, $T_INJS_AGN)) as $f => $opts) {
        echo "<td><select name='${f}_$FS' $DIS>";
        foreach ($opts as $status => $name) {
            echo "<option value='$status' ".((isset($mdat[$f]) && $mdat[$f] == $status) ? 'SELECTED' : '').">$name</option>";
        }
        echo "</select></td>\n";
    }
    echo "</tr>\n";
}

public static function report_ES($mid, $DIS)
{
    global $lng, $ES_fields;
    $ES_grps = array();
    foreach ($ES_fields as $f) {
        if (!in_array($f['group'], $ES_grps)) {
            $ES_grps[] = $f['group'];
        }
    }
    $players = self::report_ES_loadPlayers($mid);

    // Update entries if requested.
    if (!$DIS && isset($_POST['ES_submitted'])) {
        $query = "SELECT tour_id AS 'trid', did, f_lid AS 'lid' FROM matches, tours, divisions WHERE match_id = $mid AND f_tour_id = tour_id AND f_did = did";
        $result = mysql_query($query);
        $NR = mysql_fetch_assoc($result); # Node Relations.
        $m = new Match($mid);
        global $p; # Dirty trick to make $p accessible within create_function() below.
        $status = true;
        foreach ($players as $teamPlayers) {
        foreach ($teamPlayers as $p) {
            $status &= Match::ESentry(
                array(
                    'f_pid' => $p['pid'], 'f_tid' => $p['f_tid'], 'f_cid' => $p['f_cid'], 'f_rid' => $p['f_rid'],
                    'f_mid' => $mid, 'f_trid' => $NR['trid'], 'f_did' => $NR['did'], 'f_lid' => $NR['lid']
                ),
                array_combine(array_keys($ES_fields), array_map(create_function('$f', 'global $p; return (int) $_POST["${f}_$p[pid]"];'), array_keys($ES_fields)))
            );
        }
        }
        status($status);
        $m->finalizeMatchSubmit(); // Run MySQL triggers
        $players = self::report_ES_loadPlayers($mid); # Reload!
    }

    // Create form
    title('ES submission');
    echo "<center><a href='index.php?section=matches&amp;type=report&amp;mid=$mid'>".$lng->getTrn('common/back')."</a></center>\n";
    HTMLOUT::helpBox('<b>Field explanations</b><br><table>'.implode("\n", array_map(create_function('$f,$def', 'return "<tr><td>$f</td><td>$def[desc]</td></tr>";'), array_keys($ES_fields), array_values($ES_fields))).'</table>', '<b>'.$lng->getTrn('common/needhelp').'</b>');
    echo "<form method='POST'>\n";
    foreach ($players as $teamPlayers) {
        echo "<br>\n";
        echo "<table style='font-size: small;'>\n";
        $COLSPAN = count($teamPlayers)+1; # +1 for field desc.
        $tid = $teamPlayers[0]['f_tid'];
        echo "<tr><td colspan='$COLSPAN'><b><a name='thead$tid'>".get_alt_col('teams', 'team_id', $tid, 'name')."</a></b></td></tr>";
        echo "<tr><td colspan='$COLSPAN'>Player number references:</td></tr>";
        echo implode('', array_map(create_function('$p', 'return "<tr><td colspan=\''.$COLSPAN.'\'>#$p[nr] $p[name]</td></tr>";'), $teamPlayers));
        echo "<tr><td colspan='$COLSPAN'>GOTO anchor ".implode(', ', array_map(create_function('$anc', 'return "<a href=\'#'.$tid.'$anc\'>$anc</a>";'), $ES_grps))."</td></tr>";
        $grp = null;
        foreach ($ES_fields as $f => $def) {
            if ($def['group'] != $grp) {
                $grp = $def['group'];
                echo "<tr><td colspan='$COLSPAN'>&nbsp;</td></tr>";
                echo "<tr style='font-style: italic;'><td><a name='$tid$grp'>$grp</a>&nbsp;|&nbsp;<a href='#thead$tid'>GOTO team head</a></td>".implode('', array_map(create_function('$p', 'return "<td>#$p[nr]</td>";'), $teamPlayers))."</tr>";
                echo "<tr><td colspan='$COLSPAN'><hr></td></tr>";
            }
            echo "<tr><td>$f</td>".implode('', array_map(
                create_function('$p', 'return "<td><input '.(($DIS) ? 'DISABLED' : '').' size=\'2\' maxlength=\'4\' name=\''.$f.'_$p[pid]\' value=\'".(($p[\''.$f.'\']) ? (int) $p[\''.$f.'\'] : 0)."\'></td>";'), $teamPlayers
            ))."</tr>\n";
        }
        echo "</table>\n";
    }
    echo "<br><br><input type='submit' name='submit' value='".$lng->getTrn('common/submit')."'>\n";
    echo "<input type='hidden' name='ES_submitted' value='1'>\n";
    echo "</form>\n";
}

protected static function report_ES_loadPlayers($mid)
{
    global $ES_fields;
    $query = "SELECT
            players.player_id AS 'pid', players.owned_by_team_id AS 'f_tid', players.f_cid AS 'f_cid', players.f_rid AS 'f_rid',
            players.name AS 'name', players.nr AS 'nr',
            ".implode(',', array_keys($ES_fields))."
        FROM matches, match_data, players LEFT JOIN match_data_es ON (match_data_es.f_mid = $mid AND players.player_id = match_data_es.f_pid)
        WHERE
            matches.match_id = $mid AND matches.match_id = match_data.f_match_id AND match_data.f_player_id = players.player_id AND (owned_by_team_id = team1_id OR owned_by_team_id = team2_id)
        ORDER BY f_tid ASC, nr ASC";
#    echo $query;
    $result = mysql_query($query);
    $players = array();
    while ($p = mysql_fetch_assoc($result)) {
        $players[$p['f_tid']][] = $p;
    }
    return $players;
}

public static function userSched() {
    global $lng, $coach, $settings, $leagues,$divisions,$tours;
    if (!is_object($coach)){
        status(false, "You must be logged in to schedule games");
        return;
    }

    if (isset($_POST['creategame'])) {
        // Test input
        $trid     = (int) $_POST['trid'];
        $round    = (int) $_POST['round'];
        $own_team = (int) $_POST['own_team'];
        $errmsg = '';
        // Logged in coach has access to the tour?
        if (!in_array($trid, array_keys($tours))) { 
            $errmsg = 'You do not have access to the tournament '.$tours[$trid]['tname'];
        }
        // Is the team is really owned by the logged in coach?
        if ($coach->coach_id != get_alt_col('teams', 'team_id', $own_team, 'owned_by_coach_id')) {
            $errmsg = 'The team '.get_alt_col('teams', 'team_id', $own_team, 'name').' is not owned by you';
        }
        // Create match
        if (!$errmsg) {
            list($exitStatus, $mid) = Match::create(array(
                'team1_id'  => $own_team,
                'team2_id'  => get_alt_col('teams', 'name', $_POST['opposing_team_autocomplete'], 'team_id'),
                'round'     => $round,
                'f_tour_id' => $trid,
            ));
            
            $backFromMatchLink = 
                Mobile::isMobile() 
                    ? "index.php?mobile=1"
                    : "index.php?section=matches&amp;type=report&amp;mid=$mid";
            
            status(!$exitStatus, 
                $exitStatus 
                    ? Match::$T_CREATE_ERROR_MSGS[$exitStatus] 
                    : "<a href='$backFromMatchLink'>Click here</a> to open the match report");
            if (!$exitStatus) {
                echo "<br>";
            }
        }
        else {
            status(false, $errmsg);
        }
    }
    $trid  = (isset($_GET['trid']) && is_numeric($_GET['trid'])) ? (int) $_GET['trid'] : 0;
    $lid   = $trid ? get_parent_id(T_NODE_TOURNAMENT, $trid, T_NODE_LEAGUE) : false;
    $lname = $lid ? get_parent_name(T_NODE_TOURNAMENT, $trid, T_NODE_LEAGUE) : '- N/A -';
    $did   = ($trid && get_alt_col('leagues', 'lid', $lid, 'tie_teams') == 1) ? get_parent_id(T_NODE_TOURNAMENT, $trid, T_NODE_DIVISION) : false;
    $dname = $did ? get_parent_name(T_NODE_TOURNAMENT, $trid, T_NODE_DIVISION) : false;
    
    $_DISABLED = (!$trid) ? 'DISABLED' : '';
    #print_r(array($trid, $lid, $lname, $did));

    title($lng->getTrn('menu/matches_menu/usersched'));
    $LOCK_FORMS = false;
    ?>
    <div class='boxCommon'>
        <h3 class='boxTitle<?php echo T_HTMLBOX_MATCH;?>'><?php echo $lng->getTrn('menu/matches_menu/usersched');?></h3>
        <div class='boxBody'>
            <form method="POST" id="usersched">
                <?php 
                echo "In tournament "; 
                echo HTMLOUT::nodeList(T_NODE_TOURNAMENT,'trid',array(T_NODE_TOURNAMENT => array('locked' => 0, 'type' => TT_FFA, 'allow_sched' => 1)), array(), array('sel_id' => $trid, 'extra_tags' => array('onChange="document.location.href = \'' . getFormAction('?section=matches&type=usersched') . '&trid=\' + $(this).val();"' ), 'init_option' => '<option value="0">- '.$lng->getTrn('matches/usersched/selecttour')." -</option>\n"));
                echo ' as ';
                echo '<select name="round" id="round" '.$_DISABLED.'>';
                $T_ROUNDS = Match::getRounds();
                foreach ($T_ROUNDS as $r => $d) {
                    echo "<option value='$r' ".(($r == 1) ? 'SELECTED' : '').">".$d."</option>\n";
                }
                ?>
                </select>
                <br><br>
                Your team
                <?php
                $teams = array();
                foreach ($coach->getTeams($lid, $did, array('sortby' => 'team_id DESC')) as $t) {
                    if (!$t->rdy || $t->is_retired)
                        continue;
                    $teams[] = $t;
                }
                ?>
                <select name='own_team' id='own_team' <?php echo $_DISABLED;?>>
                    <?php
                    echo "<optgroup class='leagues' label='$lname'>\n";
                    if ($dname) {
                        echo "<optgroup class='divisions' label='&nbsp;&nbsp;$dname'>\n";
                    }
                    foreach ($teams as $t) {
                        echo "<option style='background-color: white; margin-left: -1em;' value='$t->team_id'>&nbsp;&nbsp;&nbsp;$t->name</option>\n";
                    }
                    ?>
                </select>
                &nbsp;
                VS.
                <input type="text" id='opposing_team_autoselect' name="opposing_team_autocomplete" size="30" maxlength="50" <?php echo $_DISABLED;?>>
                <script>
                    $(document).ready(function(){
                        var options, b;

                        options = {
                            minChars:2,
                            serviceUrl:'handler.php?type=autocomplete&obj=<?php echo T_OBJ_TEAM;?>&trid=<?php echo $trid; ?>',
                        };
                        b = $('#opposing_team_autoselect').autocomplete(options);
                    });
                </script>
                <br><br><br>
                <input type="submit" name="creategame" value="<?php echo $lng->getTrn('menu/matches_menu/usersched');?>" <?php if (empty($teams) || $_DISABLED) echo 'DISABLED';?>>
                
                <?php if(Mobile::isMobile()) {
                    echo '<a href="' . getFormAction('') . '">' . $lng->getTrn('common/back') . '</a>';
                } ?>
            </form>
        </div>
    </div>
    <?php
}

}

