<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2009-2011. All Rights Reserved.
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

define('T_HTML_COACHES_PER_PAGE', 50);

class Coach_HTMLOUT extends Coach
{

public static function dispList()
{
    global $lng;

    /*
        NOTE: We do NOT show coaches not having played any matches for nodes = {T_NODE_TOURNAMENT, T_NODE_DIVISION}.
    */

    list($sel_node, $sel_node_id, $sel_state, $sel_race) = HTMLOUT::nodeSelector(array('state' => true));
    $ALL_TIME = ($sel_node === false && $sel_node_id === false);
    
    $fields = 'coach_id AS "coach_id", coaches.name AS "cname", coaches.retired AS "retired", team_cnt AS "team_cnt"';
    $where = array();
    if ($sel_state == T_STATE_ACTIVE) $where[] = 'coaches.retired IS FALSE';

    if ($ALL_TIME) {
        $where = (count($where) > 0) ? 'WHERE '.implode(' AND ', $where) : '';
	    $queryCnt = "SELECT COUNT(*) FROM coaches $where";
	    $queryGet = "SELECT $fields FROM coaches $where ORDER BY cname ASC";
	}
	else if ($sel_node == T_NODE_LEAGUE) {
        $where = (count($where) > 0) ? ' AND '.implode(' AND ', $where) : '';
        # In case of duplicate records in memberships table we search for distinct/"grouped by" values.
	    $queryCnt = "SELECT COUNT(DISTINCT cid, lid) FROM coaches,memberships WHERE cid = coach_id AND lid = $sel_node_id $where";
	    $queryGet = "SELECT $fields FROM coaches,memberships WHERE cid = coach_id AND lid = $sel_node_id $where GROUP BY cid, lid ORDER BY cname ASC";
	}
    else {
        $q = "SELECT $fields FROM matches, teams, coaches, tours, divisions 
              WHERE teams.owned_by_coach_id = coaches.coach_id AND matches._RRP = teams.team_id AND matches.f_tour_id = tours.tour_id AND tours.f_did = divisions.did ";
	    switch ($sel_node)
	    {
	        case false: break;
	        case T_NODE_TOURNAMENT: $q .= "AND tours.tour_id = $sel_node_id";   break;
	        case T_NODE_DIVISION:   $q .= "AND divisions.did = $sel_node_id";   break;
	        case T_NODE_LEAGUE:     $q .= "AND divisions.f_lid = $sel_node_id"; break;
	    }
	    $q .= (count($where) > 0) ? ' AND '.implode(' AND ', $where).' ' : '';
	    $_subt1 = '('.preg_replace('/\_RRP/', 'team1_id', $q).')';
	    $_subt2 = '('.preg_replace('/\_RRP/', 'team2_id', $q).')';
	    $queryCnt = "SELECT COUNT(*) FROM (($_subt1) UNION DISTINCT ($_subt2)) AS tmp";
	    $queryGet = '('.$_subt1.') UNION DISTINCT ('.$_subt2.') ORDER BY cname ASC';
    }    
    
    $result = mysql_query($queryCnt);
    list($cnt) = mysql_fetch_row($result);
    $pages = ($cnt == 0) ? 1 : ceil($cnt/T_HTML_COACHES_PER_PAGE);
    global $page;
    $page = (isset($_GET['page']) && $_GET['page'] <= $pages) ? $_GET['page'] : 1; # Page 1 is default, of course.
    $_url = "?section=coachlist&amp;";
    echo '<br><center><table>';
    echo '<tr><td>';
    echo $lng->getTrn('common/page').': '.implode(', ', array_map(create_function('$nr', 'global $page; return ($nr == $page) ? $nr : "<a href=\''.$_url.'page=$nr\'>$nr</a>";'), range(1,$pages)));
    echo '</td></td>';
    echo "<tr><td>".$lng->getTrn('common/coaches').": $cnt</td></td>";
    echo '</table></center><br>';
    $queryGet .= ' LIMIT '.(($page-1)*T_HTML_COACHES_PER_PAGE).', '.(($page)*T_HTML_COACHES_PER_PAGE);
    
    $coaches = array();
    $result = mysql_query($queryGet);
    while ($c = mysql_fetch_object($result)) {
        $c->retired = ($c->retired) ? '<b>'.$lng->getTrn('common/yes').'</b>' : $lng->getTrn('common/no');
        $coaches[] = $c;
    }

    $fields = array(
        'cname'    => array('desc' => $lng->getTrn('common/name'), 'nosort' => true, 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false), 'field' => 'obj_id', 'value' => 'coach_id')),
        'team_cnt' => array('desc' => $lng->getTrn('common/teams'), 'nosort' => true),
        'retired'  => array('desc' => $lng->getTrn('common/retired'), 'nosort' => true),
    );

    HTMLOUT::sort_table(
        $lng->getTrn('common/coaches'),
        "index.php$_url",
        $coaches,
        $fields,
        array(),
        array(),
        array('doNr' => false, 'noHelp' => true, 'noSRdisp' => true)
    );
}

public static function standings()
{
    global $lng;
    title($lng->getTrn('menu/statistics_menu/coach_stn'));
    HTMLOUT::standings(STATS_COACH, false, false, array('url' => urlcompile(T_URL_STANDINGS,T_OBJ_COACH,false,false,false)));
}

public static function profile($cid) {

    global $lng, $coach;
    $c = new self($cid);
    $ALLOW_EDIT = (is_object($coach) && ($c->coach_id == $coach->coach_id || $coach->mayManageObj(T_OBJ_COACH, $c->coach_id))); # Coach (or admin) visting own profile?

    title($c->name);
    echo "<hr>";
    $c->_menu($ALLOW_EDIT);
    switch (isset($_GET['subsec']) ? $_GET['subsec'] : 'teams')
    {
        case 'profile': $c->_CCprofile($ALLOW_EDIT); break;
        case 'stats': $c->_stats(); break;
        case 'newteam': $c->_newTeam($ALLOW_EDIT); break;
        case 'teams': $c->_teams($ALLOW_EDIT); break;
        case 'recentmatches': $c->_recentGames(); break;
    }
}

private function _menu($ALLOW_EDIT)
{
    global $lng, $settings, $rules;
    $url = urlcompile(T_URL_PROFILE,T_OBJ_COACH,$this->coach_id,false,false);
    ?>
    <ul class="css3menu1 menu" style="z-index:0">
        <li><a href="<?php echo $url.'&amp;subsec=teams';?>"><?php echo $lng->getTrn('cc/coach_teams');?></a></li>
        <li><a href="<?php echo $url.'&amp;subsec=profile';?>"><?php echo $lng->getTrn('cc/profile');?></a></li>
        <li><a href="<?php echo $url.'&amp;subsec=stats';?>"><?php echo $lng->getTrn('common/stats');?></a></li>
        <li><a href="<?php echo $url.'&amp;subsec=recentmatches';?>"><?php echo $lng->getTrn('common/recentmatches');?></a></li>
        <?php
        if ($ALLOW_EDIT) {
            ?><li><a href="handler.php?type=teamcreator"><?php echo $lng->getTrn('cc/new_team');?></a></li><?php
        }
        if (Module::isRegistered('SGraph')) {
            echo "<li><a href='handler.php?type=graph&amp;gtype=".SG_T_COACH."&amp;id=$this->coach_id'>Vis. stats</a></li>\n";
        }
        ?>
    </ul>
    <?php
}

private function _newTeam($ALLOW_EDIT)
{
    global $lng, $settings, $raceididx, $rules;
    global $leagues, $divisions;

    // Form posted? -> Create team.
    if (isset($_POST['type']) && $_POST['type'] == 'newteam' && $ALLOW_EDIT) {
        if (get_magic_quotes_gpc()) {
            $_POST['name'] = stripslashes($_POST['name']);
        }
        @list($lid,$did) = explode(',',$_POST['lid_did']);
        setupGlobalVars(T_SETUP_GLOBAL_VARS__LOAD_LEAGUE_SETTINGS, array('lid' => (int) $lid)); // Load correct $rules for league.
        list($exitStatus, $tid) = Team::create(array(
            'name' => $_POST['name'], 
            'owned_by_coach_id' => (int) $this->coach_id, 
            'f_race_id' => (int) $_POST['rid'], 
            'treasury' => $rules['initial_treasury'], 
            'apothecary' => 0, 
            'rerolls' => $rules['initial_rerolls'], 
            'ff_bought' => $rules['initial_fan_factor'], 
            'ass_coaches' => $rules['initial_ass_coaches'], 
            'cheerleaders' => $rules['initial_cheerleaders'],
            'won_0' => 0, 'lost_0' => 0, 'draw_0' => 0, 'played_0' => 0, 'wt_0' => 0, 'gf_0' => 0, 'ga_0' => 0,
            'imported' => 0,
            'f_lid' => (int) $lid,
            'f_did' => isset($did) ? (int) $did : Team::T_NO_DIVISION_TIE,
            ));
        status(!$exitStatus, $exitStatus ? Team::$T_CREATE_ERROR_MSGS[$exitStatus] : null);
    }

    // Show new team form.
    ?>
    <br><br>
    <div class='boxCommon'>
        <h3 class='boxTitle<?php echo T_HTMLBOX_COACH;?>'><?php echo $lng->getTrn('cc/new_team');?></h3>
        <div class='boxBody'>
        
    <form method="POST">
    <?php echo $lng->getTrn('common/name');?><br>
    <input type="text" name="name" size="20" maxlength="50">
    <br><br>
    <?php echo $lng->getTrn('common/race');?><br>
    <select name="rid">
        <?php
        // We need to sort the array manually because of the translation
        foreach ($raceididx as $rid => &$rname)
            $rname = $lng->getTrn('race/'.strtolower(str_replace(' ','', $rname)));
        unset($rname); // very important !
        asort($raceididx);
        // The, we display as usual
        foreach ($raceididx as $rid => $rname)
            echo "<option value='$rid'>$rname</option>\n";
        ?>
    </select>
    <br><br>
    <?php echo $lng->getTrn('common/league').'/'.$lng->getTrn('common/division');?><br>
    <select name="lid_did">
        <?php
        foreach ($leagues = Coach::allowedNodeAccess(Coach::NODE_STRUCT__TREE, $this->coach_id, array(T_NODE_LEAGUE => array('tie_teams' => 'tie_teams'))) as $lid => $lstruct) {
            if ($lstruct['desc']['tie_teams']) {
                echo "<OPTGROUP LABEL='".$lng->getTrn('common/league').": ".$lstruct['desc']['lname']."'>\n";
                foreach ($lstruct as $did => $dstruct) {
                    if ($did != 'desc') {
                        echo "<option value='$lid,$did'>".$lng->getTrn('common/division').": ".$dstruct['desc']['dname']."</option>";
                    }
                }
                echo "</OPTGROUP>\n";
            }
            else {
                echo "<option value='$lid'>".$lng->getTrn('common/league').": ".$lstruct['desc']['lname']."</option>";
            }
        }
        ?>
    </select>
    <br><br>    
    <input type='hidden' name='type' value='newteam'>
    <input type="submit" name="new_team" value="<?php echo $lng->getTrn('common/create');?>" <?php echo (count($leagues) == 0) ? 'DISABLED' : '';?>>
    </form>
        </div>
    </div>
    <?php
}

private function _teams($ALLOW_EDIT)
{
    global $lng;
    echo "<br>";
    $url = urlcompile(T_URL_PROFILE,T_OBJ_COACH,$this->coach_id,false,false).'&amp;subsec=teams';
    HTMLOUT::standings(T_OBJ_TEAM,false,false,array('url' => $url, 'teams_from' => T_OBJ_COACH, 'teams_from_id' => $this->coach_id));
}

private function _CCprofile($ALLOW_EDIT) 
{
    global $lng, $coach, $leagues;

    // Was new password/email request made?
    if (isset($_POST['type']) && $ALLOW_EDIT) {

        if (get_magic_quotes_gpc()) {
            $_POST['new_passwd']   = isset($_POST['new_passwd'])     ? stripslashes($_POST['new_passwd']) : '';
            $_POST['new_phone']    = isset($_POST['new_phone'])      ? stripslashes($_POST['new_phone']) : '';
            $_POST['new_email']    = isset($_POST['new_email'])      ? stripslashes($_POST['new_email']) : '';
            $_POST['new_name']     = isset($_POST['new_name'])       ? stripslashes($_POST['new_name']) : '';
            $_POST['new_realname'] = isset($_POST['new_realname'])   ? stripslashes($_POST['new_realname']) : '';
        }

        switch ($_POST['type'])
        {
            case 'chpasswd':    status(Coach::checkPasswd($this->coach_id, $_POST['old_passwd']) && $this->setPasswd($_POST['new_passwd'])); break;
            case 'chphone':     status($this->setPhone($_POST['new_phone'])); break;
            case 'chmail':      status($this->setMail($_POST['new_email'])); break;
            case 'chlogin':     status($this->setName($_POST['new_name'])); break;
            case 'chname':      status($this->setRealName($_POST['new_realname'])); break;
            case 'chlang':      status($this->setSetting('lang', $_POST['new_lang'])); break;
            case 'chhomelid':   status(isset($_POST['new_homelid']) && get_alt_col('leagues', 'lid', (int) $_POST['new_homelid'], 'lid') && $this->setSetting('home_lid', $_POST['new_homelid'])); break;

            case 'pic':         status(($_POST['add_del'] == 'add') ? $this->savePic(false) : $this->deletePic()); break;
            case 'coachtext':
                if (get_magic_quotes_gpc()) {
                    $_POST['coachtext'] = stripslashes($_POST['coachtext']);
                }
                status($this->saveText($_POST['coachtext']));
                break;
        }
    }

    // New team and change coach settings.
    echo "<br><br>";
    ?>
    <table class="common"><tr class="commonhead"><td><b><?php echo $lng->getTrn('cc/coach_info');?></b></td></tr></table>
    <br>
    <?php
    echo $lng->getTrn('cc/note_persinfo');
    echo "<br><br>";
    
    if (is_object($coach) && !$ALLOW_EDIT) { # Logged in but not viewing own coach page.
        ?>
        <table>
            <tr>
                <td>ID:</td>
                <td><?php echo $this->coach_id;?></td>
            </tr>
            <tr>
                <td>Name (login):</td>
                <td><?php echo $this->name;?></td>
            </tr>
            <tr>
                <td>Full name:</td>
                <td><?php echo empty($this->realname) ? '<i>'.$lng->getTrn('common/none').'</i>' : $this->realname;?></td>
            </tr>
            <tr>
                <td>Phone:</td>
                <td><?php echo empty($this->phone) ? '<i>'.$lng->getTrn('common/none').'</i>' : $this->phone?></td>
            </tr>
            <tr>
                <td>Mail:</td>
                <td><?php echo empty($this->mail) ? '<i>'.$lng->getTrn('common/none').'</i>' : $this->mail?></td>
            </tr>
        </table>
        <br>
        <?php
    }
    if ($ALLOW_EDIT) {
        ?>
        <table class="common" style="border-spacing:5px; padding:20px;">
            <tr><td colspan='4'>ID: <?php echo $this->coach_id;?></td></tr>
            <tr>
                <form method="POST">
                <td><?php echo $lng->getTrn('cc/chpasswd');?>:</td>
                <td><?php echo $lng->getTrn('cc/old');?>:<input type='password' name='old_passwd' size="20" maxlength="50"></td>
                <td><?php echo $lng->getTrn('cc/new');?>:<input type='password' name='new_passwd' size="20" maxlength="50"></td>
                <td><input type="submit" name="button" value="<?php echo $lng->getTrn('cc/chpasswd');?>"></td>
                <input type='hidden' name='type' value='chpasswd'>
                </form>
            </tr>
            <tr>
                <form method="POST">
                <td><?php echo $lng->getTrn('cc/chphone');?>:</td>
                <td><?php echo $lng->getTrn('cc/old');?>:<input type='text' name='old_phone' readonly value="<?php echo $this->phone; ?>" size="20" maxlength="129"></td>
                <td><?php echo $lng->getTrn('cc/new');?>:<input type='text' name='new_phone' size="20" maxlength="25"></td>
                <td><input type="submit" name="button" value="<?php echo $lng->getTrn('cc/chphone');?>"></td>
                <input type='hidden' name='type' value='chphone'>
                </form>
            </tr>
            <tr>
                <form method="POST">
                <td><?php echo $lng->getTrn('cc/chmail');?>:</td>
                <td><?php echo $lng->getTrn('cc/old');?>:<input type='text' name='old_email' readonly value="<?php echo $this->mail; ?>" size="20" maxlength="129"></td>
                <td><?php echo $lng->getTrn('cc/new');?>:<input type='text' name='new_email' size="20" maxlength="129"></td>
                <td><input type="submit" name="button" value="<?php echo $lng->getTrn('cc/chmail');?>"></td>
                <input type='hidden' name='type' value='chmail'>
                </form>
            </tr>
            <tr>
                <form method="POST">
                <td><?php echo $lng->getTrn('cc/chlogin');?>:</td>
                <td><?php echo $lng->getTrn('cc/old');?>:<input type='text' name='old_name' readonly value="<?php echo $this->name; ?>" size="20" maxlength="50"></td>
                <td><?php echo $lng->getTrn('cc/new');?>:<input type='text' name='new_name' size="20" maxlength="50"></td>
                <td><input type="submit" name="button" value="<?php echo $lng->getTrn('cc/chlogin');?>"></td>
                <input type='hidden' name='type' value='chlogin'>
                </form>
            </tr>
            <tr>
                <form method="POST">
                <td><?php echo $lng->getTrn('cc/chname');?>:</td>
                <td><?php echo $lng->getTrn('cc/old');?>:<input type='text' name='old_realname' readonly value="<?php echo $this->realname; ?>" size="20" maxlength="50"></td>
                <td><?php echo $lng->getTrn('cc/new');?>:<input type='text' name='new_realname' size="20" maxlength="50"></td>
                <td><input type="submit" name="button" value="<?php echo $lng->getTrn('cc/chname');?>"></td>
                <input type='hidden' name='type' value='chname'>
                </form>
            </tr>
            <tr>
                <form method="POST">
                <td><?php echo $lng->getTrn('cc/chlang');?>:</td>
                <td><?php echo $lng->getTrn('cc/current');?>: <?php echo $this->settings['lang'];?></td>
                <td>
                    <?php echo $lng->getTrn('cc/new');?>:
                    <select name='new_lang'>
                        <?php
                        foreach (Translations::$registeredLanguages as $lang) {
                            echo "<option value='$lang'>$lang</option>\n";
                        }
                        ?>
                    </select>
                </td>
                <td><input type="submit" name="button" value="<?php echo $lng->getTrn('cc/chlang');?>"></td>
                <input type='hidden' name='type' value='chlang'>
                </form>
            </tr>
            <tr>
                <form method="POST">
                <td><?php echo $lng->getTrn('cc/chhomelid');?>:</td>
                <td><?php echo $lng->getTrn('cc/current');?>: <?php echo (isset($leagues[$this->settings['home_lid']])) ? $leagues[$this->settings['home_lid']]['lname'] : '<i>'.$lng->getTrn('common/none').'</i>';?></td>
                <td>
                    <?php echo $lng->getTrn('cc/new');?>:
                    <select name='new_homelid'>
                        <?php
                        foreach ($leagues as $lid => $desc) {
                            echo "<option value='$lid'>$desc[lname]</option>\n";
                        }
                        ?>
                    </select>
                </td>
                <td><input type="submit" name="button" value="<?php echo $lng->getTrn('cc/chhomelid');?>" <?php echo (count($leagues) == 0) ? 'DISABLED' : '';?>></td>
                <input type='hidden' name='type' value='chhomelid'>
                </form>
            </tr>
        </table>
        <?php
    }
    ?>

    <table class='common'>
        <tr class='commonhead'>
            <td><b><?php echo $lng->getTrn('cc/photo');?></b></td>
            <td><b><?php echo $lng->getTrn('common/about');?></b></td>
        </tr>
        <tr>
            <td>
                <?php
                ImageSubSys::makeBox(IMGTYPE_COACH, $this->coach_id, $ALLOW_EDIT, false);
                ?>
            </td>
            <td valign='top'>
                <?php
                $txt = $this->getText();
                if (empty($txt)) {
                    $txt = $lng->getTrn('common/nobody');
                }
                if ($ALLOW_EDIT) {
                    ?>
                    <form method='POST'>
                        <textarea name='coachtext' rows='15' cols='70'><?php echo $txt;?></textarea>
                        <br><br>
                        <input type="hidden" name="type" value="coachtext">
                        <input type="submit" name='Save' value="<?php echo $lng->getTrn('common/save');?>">
                    </form>
                    <?php
                }
                else {
                    echo '<p>'.fmtprint($txt)."</p>\n";
                }
                ?>
            </td>
        </tr>
    </table>
    <?php
}

private function _stats()
{
    global $lng, $settings;
    ?>
    <div class="row">
        <div class="boxCoachPage">
            <h3 class='boxTitle1'><?php echo $lng->getTrn('common/general'); ?></h3>
            <div class='boxBody'>
                <table class="boxTable">
                <?php
                echo "<tr><td>Played</td><td>$this->mv_played</td></tr>\n";
                echo "<tr><td>WIN%</td><td>".(sprintf("%1.1f", $this->rg_win_pct).'%')."</td></tr>\n";
                echo "<tr><td>ELO</td><td>".(($this->rg_elo) ? sprintf("%1.2f", $this->rg_elo) : '<i>N/A</i>')."</td></tr>\n";
                echo "<tr><td>W/L/D</td><td>$this->mv_won/$this->mv_lost/$this->mv_draw</td></tr>\n";
                echo "<tr><td>W/L/D ".$lng->getTrn('common/streaks')."</td><td>$this->mv_swon/$this->mv_slost/$this->mv_sdraw</td></tr>\n";
                echo "<tr><td>".$lng->getTrn('common/wontours')."</td><td>$this->wt_cnt</td></tr>\n";            
                if (Module::isRegistered('Prize')) {
                    echo "<tr><td>".$lng->getTrn('name', 'Prize')."</td><td><small>".Module::run('Prize', array('getPrizesString', T_OBJ_COACH, $this->coach_id))."</small></td></tr>\n";
                }
                echo "<tr><td colspan='2'><hr></td></tr>";
                $result = mysql_query("
                    SELECT 
                        COUNT(*) AS 'teams_total', 
                        IFNULL(SUM(IF(rdy IS TRUE AND retired IS FALSE,1,0)),0) AS 'teams_active', 
                        IFNULL(SUM(IF(rdy IS FALSE,1,0)),0) AS 'teams_notready',
                        IFNULL(SUM(IF(retired IS TRUE,1,0)),0) AS 'teams_retired',
                        IFNULL(AVG(elo),0) AS 'avg_elo',
                        IFNULL(CAST(AVG(ff) AS SIGNED INT),0) AS 'avg_ff',
                        IFNULL(CAST(AVG(tv)/1000 AS SIGNED INT),0) AS 'avg_tv'
                    FROM teams WHERE owned_by_coach_id = $this->coach_id");
                $row = mysql_fetch_assoc($result);
                echo "<tr><td>".$lng->getTrn('profile/coach/teams_total')."</td><td>$row[teams_total]</td></tr>\n";
                echo "<tr><td>".$lng->getTrn('profile/coach/teams_active')."</td><td>$row[teams_active]</td></tr>\n";
                echo "<tr><td>".$lng->getTrn('profile/coach/teams_notready')."</td><td>$row[teams_notready]</td></tr>\n";
                echo "<tr><td>".$lng->getTrn('profile/coach/teams_retired')."</td><td>$row[teams_retired]</td></tr>\n";
                echo "<tr><td>".$lng->getTrn('profile/coach/avgteam_elo')."</td><td>".(($row['avg_elo']) ? sprintf("%1.2f", $row['avg_elo']) : '<i>N/A</i>')."</td></tr>\n";
                echo "<tr><td>".$lng->getTrn('profile/coach/avgteam_tv')."</td><td>$row[avg_tv]</td></tr>\n";
                echo "<tr><td>".$lng->getTrn('profile/coach/avgteam_ff')."</td><td>$row[avg_ff]</td></tr>\n";
                ?>
                </table>
            </div>
        </div>
        <div class="boxCoachPage">
            <h3 class='boxTitle1'><?php echo $lng->getTrn('common/ach'); ?></h3>
            <div class='boxBody'>
                <table class="boxTable">
                <?php
                $stats = array(
                    # Display name => array(field (int or false) sprintf() precision)
                    'CAS' => array('cas', 2),
                    'BH'  => array('bh', 2),
                    'Ki'  => array('ki', 2),
                    'Si'  => array('si', 2),
                    'TD'  => array('td', 2),
                    'Int' => array('intcpt', 2),
                    'Cp'  => array('cp', 2),
                    'GF'  => array('gf', 2),
                    'GA'  => array('ga', 2),
                    'SMP' => array('smp', 2),                
                );
                $thisAVG = clone $this;
                $thisAVG->setStats(false, false, true);
                echo "<tr><td>".$lng->getTrn('common/stat')."</td> <td>".$lng->getTrn('common/amount')."</td> <td>".$lng->getTrn('common/matchavg')."</td></tr>\n";
                echo "<tr><td colspan='5'><hr></td></tr>\n";
                foreach ($stats as $name => $d) {
                    echo "<tr><td><i>$name</i></td>";
                    echo "<td>".($this->{"mv_$d[0]"})."</td>";
                    echo "<td>".sprintf("%1.$d[1]f", $thisAVG->{"mv_$d[0]"})."</td>";
                    echo "</tr>\n";
                }
                ?>
                </table>
            </div>
        </div>
    </div>
    <br>
    <div class="row"></div>
    <br>
    <?php
    if (!$settings['hide_ES_extensions']) {
        ?>
        <div class="row">
            <div class="boxWide">
                <div class="boxTitle<?php echo T_HTMLBOX_STATS;?>"><a href='javascript:void(0);' onClick="slideToggleFast('ES');"><b>[+/-]</b></a> &nbsp;<?php echo $lng->getTrn('common/extrastats'); ?></div>
                <div class="boxBody" id="ES">
                    <?php
                    HTMLOUT::generateEStable($this);
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}

private function _recentGames()
{
    echo "<div id='cc_recentmatches' style='clear:both;'>\n";
    echo "<br>";
    $url = urlcompile(T_URL_PROFILE,T_OBJ_COACH,$this->coach_id,false,false).'&amp;subsec=recentmatches';
    HTMLOUT::recentGames(T_OBJ_COACH, $this->coach_id, false, false, false, false, array('url' => $url, 'n' => MAX_RECENT_GAMES, 'GET_SS' => 'gp'));
    echo "</div>";
}

}
