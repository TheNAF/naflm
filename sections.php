<?php

/*
 *  Copyright (c) Niels Orsleff Justesen <njustesen@gmail.com> and Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2007-2011. All Rights Reserved.
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
 *  Login
 *
 *************************/
 
function getFormAction($params) {
    $mobilePrefix =(strpos($params, '?') !== FALSE) ? '&' : '?';    
    return 'index.php' . $params . (Mobile::isMobile() ? ($mobilePrefix . 'mobile=1') : '');
}
 
function sec_login() {

    global $lng, $settings;
    
    $_URL_forgotpass = "index.php?section=login&amp;forgotpass=1";
    if (isset($_GET['forgotpass'])) {
        if (!isset($_POST['_retry'])) {
            title($lng->getTrn('login/forgotpass'));
        }
        if (isset($_GET['cid']) && isset($_GET['activation_code'])) {
            $c = new Coach((int) $_GET['cid']);
            status($new_passwd = $c->confirmActivation($_GET['activation_code']));
            echo "<br><br>";
            echo $lng->getTrn('login/temppasswd')." <b>$new_passwd</b><br>\n";
            echo '<a href="'.urlcompile(T_URL_PROFILE,T_OBJ_COACH,$_GET['cid'],false,false).'&amp;subsec=profile">'.$lng->getTrn('login/setnewpasswd').'.</a>';
        }
        else if (isset($_POST['coach_AC']) && isset($_POST['email'])) {
            $cid = get_alt_col('coaches', 'name', $_POST['coach_AC'], 'coach_id');
            $c = new Coach($cid);
            $correct_user = ($c->mail == $_POST['email']);
            status($correct_user, $correct_user ? '' : $lng->getTrn('login/mismatch'));
            if ($correct_user) {
                $c->requestPasswdReset();
                echo "<br><br>";
                echo $lng->getTrn('login/resetpasswdmail').'.';
            }
            else {
                // Return to same page.
                unset($_POST['coach']);
                unset($_POST['email']);
                $_POST['_retry'] = true;
                sec_login();
            }
        }
        else {
            ?>
            <div class='boxCommon'>
                <h3 class='boxTitle<?php echo T_HTMLBOX_COACH;?>'><?php echo $lng->getTrn('login/forgotpass');?></h3>
                <div class='boxBody'>
                <form method="POST" action="<?php echo $_URL_forgotpass;?>">
                    <?php echo $lng->getTrn('login/loginname');?><br>
                    <input type="text" name="coach_AC" size="20" maxlength="50">
                    <br><br>
                    Email<br>
                    <input type="text" name="email" size="20" maxlength="50">
                    <br><br>
                    <input type="submit" name="reqAC" value="<?php echo $lng->getTrn('common/submit');?>">
                </form>
                </div>
            </div>
            <?php 
        }       
    }
    else {
        title($lng->getTrn('menu/login'));
        ?>
        <script lang="text/javascript">
            $(document).ready(function() {
                $('#coach').focus();
            });
        </script>
        <div class='boxCommon'>
            <h3 class='boxTitle<?php echo T_HTMLBOX_COACH;?>'><?php echo $lng->getTrn('menu/login');?></h3>
            <div class='boxBody'>
            <form method="POST" action="<?php echo getFormAction(''); ?>">
                <?php echo $lng->getTrn('login/loginname');?><br>
                <input type="text" id="coach" name="coach" size="20" maxlength="50"><br><br>
                <?php echo $lng->getTrn('login/passwd');?><br>
                <input type="password" name="passwd" size="20" maxlength="50">
                <div style='display: none;'><input type='text' name='hackForHittingEnterToLogin' size='1'></div>
                <br><br>
                <?php echo $lng->getTrn('login/remember');?>
                <input type='checkbox' name='remember' value='1'>
                <br><br>
                <input type="submit" name="login" value="<?php echo $lng->getTrn('login/loginbutton');?>">
            </form>
            <br><br>
            <?php
            if(!Mobile::isMobile()) {
                if (Module::isRegistered('Registration') && $settings['allow_registration']) {
                    echo "<a href='handler.php?type=registration'><b>Register</b></a>";
                }  
                echo "<br><br>";
                echo "<a href='$_URL_forgotpass'><b>".$lng->getTrn('login/forgotpass').'</b></a>';
            }
            ?>
            </div>
        </div>
        <?php
    }
}

/*************************
 *
 *  MAIN
 *
 *************************/

function sec_main() {

    global $settings, $rules, $coach, $lng, $leagues;
    
    MTS('Main start');

    list($sel_lid, $HTML_LeagueSelector) = HTMLOUT::simpleLeagueSelector();
    $IS_GLOBAL_ADMIN = (is_object($coach) && $coach->ring == Coach::T_RING_GLOBAL_ADMIN);
    
    /*
     *  Was any main board actions made?
     */

    if (isset($_POST['type']) && is_object($coach) && $coach->isNodeCommish(T_NODE_LEAGUE, $sel_lid)) {
        if (get_magic_quotes_gpc()) {
            if (isset($_POST['title'])) $_POST['title'] = stripslashes($_POST['title']);
            if (isset($_POST['txt']))   $_POST['txt']   = stripslashes($_POST['txt']);
        }
        $msg = isset($_POST['msg_id']) ? new Message((int) $_POST['msg_id']) : null;
        switch ($_POST['type'])
        {
            case 'msgdel': status($msg->delete()); break;
            case 'msgnew':  
                status(Message::create(array(
                    'f_coach_id' => $coach->coach_id, 
                    'f_lid'      => ($IS_GLOBAL_ADMIN && isset($_POST['BC']) && $_POST['BC']) ? Message::T_BROADCAST : $sel_lid, 
                    'title'      => $_POST['title'], 
                    'msg'        => $_POST['txt'])
                )); break;
            case 'msgedit': status($msg->edit($_POST['title'], $_POST['txt'])); break;
            case 'pin':     status($msg->pin(1)); break;
            case 'unpin':   status($msg->pin(0)); break;

        }
    }

    /*
     *  Now we are ready to generate the HTML code.
     */

    ?>
    <div class="main_head"><?php echo $settings['league_name']; ?></div>
    <div class='main_leftColumn'>
        <div class="main_leftColumn_head">
            <?php
            echo "<div class='main_leftColumn_welcome'>\n";
            echo $settings['welcome'];
            echo "</div>\n";
            echo "<div class='main_leftColumn_left'>\n";
            if(count($leagues) > 1)
              echo $HTML_LeagueSelector;
            echo "</div>\n";
            echo "<div class='main_leftColumn_right'>\n";
            if (is_object($coach) && $coach->isNodeCommish(T_NODE_LEAGUE, $sel_lid)) {
                echo "<a href='javascript:void(0);' onClick=\"slideToggle('msgnew');\">".$lng->getTrn('main/newmsg')."</a>&nbsp;\n";
            }
            if (Module::isRegistered('RSSfeed')) {echo "<a href='handler.php?type=rss'>RSS</a>\n";}
            echo "</div>\n";
            ?>
            <div style="display:none; clear:both;" id="msgnew">
                <br><br>
                <form method="POST">
                    <textarea name="title" rows="1" cols="50"><?php echo $lng->getTrn('common/notitle');?></textarea><br><br>
                    <textarea name="txt" rows="15" cols="50"><?php echo $lng->getTrn('common/nobody');?></textarea><br><br>
                    <?php 
                    if ($IS_GLOBAL_ADMIN) {
                        echo $lng->getTrn('main/broadcast');
                        ?><input type="checkbox" name="BC"><br><br><?php
                    }
                    ?>
                    <input type="hidden" name="type" value="msgnew">
                    <input type="submit" value="<?php echo $lng->getTrn('common/submit');?>">
                </form>
            </div>
        </div>

        <?php
        /*
            Generate main board.

            Left column is the message board, consisting of both commissioner messages and game summaries/results.
            To generate this table we create a general array holding the content of both.
        */
        $j = 1; $prevPinned = 0;
        foreach (TextSubSys::getMainBoardMessages($settings['fp_messageboard']['length'], $sel_lid, $settings['fp_messageboard']['show_team_news'], $settings['fp_messageboard']['show_match_summaries']) as $e) {

            if ($prevPinned == 1 && !$e->pinned) { echo "<hr>\n"; }
            $prevPinned = $e->pinned;

            echo "<div class='boxWide'>\n";
                echo "<h3 class='boxTitle$e->cssidx'>$e->title</h3>\n";
                echo "<div class='boxBody'>\n";
                    $fmtMsg = fmtprint($e->message); # Basic supported syntax: linebreaks.
                    echo "
                    <div id='e$j' class='expandable'>$fmtMsg</div>
                    <script type='text/javascript'>
                      $('#e$j').expander({
                        slicePoint:       300,
                        expandText:       '".$lng->getTrn('main/more')."',
                        collapseTimer:    0,
                        userCollapseText: ''
                      });
                      </script>";
                    echo "<br><hr>\n";
                    echo "<table class='boxTable'><tr>\n";
                        switch ($e->type) 
                        {
                            case T_TEXT_MATCH_SUMMARY:
                                echo "<td align='left' width='100%'>".$lng->getTrn('main/posted')." ".textdate($e->date)." " . ((isset($e->date_mod) && $e->date_mod != $e->date) ? "(".$lng->getTrn('main/lastedit')." ".textdate($e->date_mod).") " : '') .$lng->getTrn('main/by')." $e->author</td>\n";
                                echo "<td align='right'><a href='index.php?section=matches&amp;type=report&amp;mid=$e->match_id'>".$lng->getTrn('common/view')."</a></td>\n";
                                break;
                            case  T_TEXT_MSG:
                                echo "<td align='left' width='100%'>".$lng->getTrn('main/posted')." ".textdate($e->date)." ".$lng->getTrn('main/by')." $e->author</td>\n";
                                if (is_object($coach) && ($IS_GLOBAL_ADMIN || $coach->coach_id == $e->author_id)) { // Only admins may delete messages, or if it's a commissioner's own message.
                                    echo "<td align='right'><a href='javascript:void(0);' onClick=\"slideToggle('msgedit$e->msg_id');\">".$lng->getTrn('common/edit')."</a></td>\n";
                                    echo "<td align='right'>";
                                    $fieldname = 'pin'; if ($e->pinned) {$fieldname = 'unpin';}
                                    echo inlineform(array('type' => "$fieldname", 'msg_id' => $e->msg_id), "${fieldname}$e->msg_id", $lng->getTrn("main/$fieldname"));
                                    echo "</td>";
                                    echo "<td align='right'>";
                                    echo inlineform(array('type' => 'msgdel', 'msg_id' => $e->msg_id), "msgdel$e->msg_id", $lng->getTrn('common/delete'));
                                    echo "</td>";
                                }
                                break;
                            case T_TEXT_TNEWS:
                                echo "<td align='left' width='100%'>".$lng->getTrn('main/posted')." ".textdate($e->date)."</td>\n";
                                break;
                        }
                        ?>
                    </tr></table>
                    <?php
                    if ($e->type == T_TEXT_MSG) {
                        echo "<div style='display:none;' id='msgedit$e->msg_id'>\n";
                        echo "<hr><br>\n";
                        echo '<form method="POST">
                            <textarea name="title" rows="1" cols="50">'.$e->title.'</textarea><br><br>
                            <textarea name="txt" rows="15" cols="50">'.$e->message.'</textarea><br><br>
                            <input type="hidden" name="type" value="msgedit">
                            <input type="hidden" name="msg_id" value="'.$e->msg_id.'">
                            <input type="submit" value="'.$lng->getTrn('common/submit').'">
                        </form>';
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
            <?php
            $j++;
        }
        ?>

    </div>
    <?php
    MTS('Board messages generated');
    
    /*
        The right hand side column optionally (depending on settings) contains standings, latest game results, touchdown and casualties stats.
        We will now generate the stats, so that they are ready to be printed in correct order.
    */
    
    echo "<div class='main_rightColumn'>\n";
    $boxes_all = array_merge($settings['fp_standings'], $settings['fp_leaders'], $settings['fp_events'], $settings['fp_latestgames']);
    usort($boxes_all, create_function('$a,$b', 'return (($a["box_ID"] > $b["box_ID"]) ? 1 : (($a["box_ID"] < $b["box_ID"]) ? -1 : 0) );')); 
    $boxes = array();
    foreach ($boxes_all as $box) {
        # These fields distinguishes the box types.
        if      (isset($box['fields'])) {$box['dispType'] = 'standings';}
        else if (isset($box['field']))  {$box['dispType'] = 'leaders';}
        else if (isset($box['content'])){$box['dispType'] = 'events';}
        else                            {$box['dispType'] = 'latestgames';}
        switch ($box['type']) {
            case 'league':     $_type = T_NODE_LEAGUE; break;
            case 'division':   $_type = T_NODE_DIVISION; break;
            case 'tournament': $_type = T_NODE_TOURNAMENT; break;
            default: $_type = T_NODE_LEAGUE;
        }
        $box['type'] = $_type;
        $boxes[] = $box;
    }

    // Used in the below standings dispType boxes.
    global $core_tables, $ES_fields;
    $_MV_COLS = array_merge(array_keys($core_tables['mv_teams']), array_keys($ES_fields));
    $_MV_RG_INTERSECT = array_intersect(array_keys($core_tables['teams']), array_keys($core_tables['mv_teams']));
    
    // Let's print those boxes!
    foreach ($boxes as $box) {
    
    switch ($box['dispType']) {
        
        case 'standings':
            $_BAD_COLS = array(); # Halt on these columns/fields.
            switch ($box['type']) {
                case T_NODE_TOURNAMENT:
                    if (!get_alt_col('tours', 'tour_id', $box['id'], 'tour_id')) {
                        break 2;
                    }
                    $tour = new Tour($box['id']);
                    $SR = array_map(create_function('$val', 'return $val[0]."mv_".substr($val,1);'), $tour->getRSSortRule());
                    break;
                    
                case T_NODE_DIVISION: 
                    $_BAD_COLS = array('elo', 'swon', 'slost', 'sdraw', 'win_pct'); # Divisions do not have pre-calculated, MV, values of these fields.
                    // Fall through!
                case T_NODE_LEAGUE:
                default:
                    global $hrs;
                    $SR = $hrs[$box['HRS']]['rule'];
                    foreach ($SR as &$f) {
                        $field = substr($f,1);
                        if (in_array($field, $_MV_RG_INTERSECT)) {
                            if (in_array($field, $_BAD_COLS)) { # E.g. divisions have no win_pct record for teams like the mv_teams table (for tours) has.
                                fatal("Sorry, the element '$field' in your specified house sortrule #$box[HRS] is not supported for your chosen type (ie. tournament/division/league).");
                            }
                            $f = $f[0]."rg_".substr($f,1);
                        }
                        else {
                            $f = $f[0]."mv_".substr($f,1);                            
                        }
                    }
                    break;
            }
            list($teams, ) = Stats::getRaw(T_OBJ_TEAM, array($box['type'] => $box['id']), array(1, $box['length']), $SR, false);
            ?>
            <div class='boxWide'>
                <h3 class='boxTitle<?php echo T_HTMLBOX_STATS;?>'><?php echo $box['title'];?></h3>
                <div class='boxBody'>
                    <table class="boxTable">
                        <?php
                        echo "<tr>\n";
                        foreach ($box['fields'] as $title => $f) {
                            echo "<td><i>$title</i></td>\n";
                        }
                        echo "</tr>\n";
                        foreach ($teams as $t) {
                            if (!$t['retired']) {
                                echo "<tr>\n";
                                foreach ($box['fields'] as $title => $f) {
                                    if (in_array($f, $_MV_COLS)) {
                                        $f = 'mv_'.$f;
                                    }
                                    echo "<td>";
                                    if ($settings['fp_links'] && $f == 'name')
                                        echo "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$t['team_id'],false,false)."'>$t[name]</a>";
                                    elseif (is_numeric($t[$f]) && !ctype_digit(($t[$f][0] == '-') ? substr($t[$f],1) : $t[$f]))
                                        echo sprintf('%1.2f', $t[$f]);
                                    else
                                        echo in_array($f, array('tv')) ? $t[$f]/1000 : $t[$f];
                                    echo "</td>\n";
                                }
                                echo "</tr>\n";
                            }
                        }
                        ?>
                    </table>
                    <?php
                    if (isset($box['infocus']) && $box['infocus']) {
                        echo "<hr>";
                        _infocus($teams);
                    }                    
                    ?>
                </div>
            </div>
            <?php
            MTS('Standings table generated');
            break;
            
        case 'latestgames':
    
            if ($box['length'] <= 0) {
                break;
            }
            $upcoming = isset($box['upcoming']) ? $box['upcoming'] : false;  
           ?>
          <div class="boxWide">
              <h3 class='boxTitle<?php echo T_HTMLBOX_MATCH;?>'><?php echo $box['title'];?></h3>
              <div class='boxBody'>
                  <table class="boxTable">
                      <tr>
                          <td style="text-align: right;" width="50%"><i><?php echo $lng->getTrn('common/home');?></i></td>
                          <td> </td>
                          <td style="text-align: left;" width="50%"><i><?php echo $lng->getTrn('common/away');?></i></td>
                          <?php if (!$upcoming) { ?>
                              <td><i><?php echo $lng->getTrn('common/date');?></i></td>
                          <?php } ?>
                          <td> </td>
                      </tr>
                        <?php
                        list($matches,$pages) = Match::getMatches(array(1, $box['length']), $box['type'], $box['id'], $upcoming); 
                        foreach ($matches as $m) {
                            echo "<tr valign='top'>\n";
                            $t1name = ($settings['fp_links']) ? "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team1_id,false,false)."'>$m->team1_name</a>" : $m->team1_name;
                            $t2name = ($settings['fp_links']) ? "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team2_id,false,false)."'>$m->team2_name</a>" : $m->team2_name;
                            echo "<td style='text-align: right;'>$t1name</td>\n";
                            if ($upcoming) {
                                echo "<td>&mdash;</td>\n";
                            } else {
                                echo "<td><nobr>$m->team1_score&mdash;$m->team2_score</nobr></td>\n";
                            }
                            echo "<td style='text-align: left;'>$t2name</td>\n";
                            if (!$upcoming) {
                                echo "<td>" . str_replace(' ', '&nbsp;', textdate($m->date_played,true)) . "</td>";
                            }
                            echo "<td><a href='index.php?section=matches&amp;type=report&amp;mid=$m->match_id'>Show</a></td>";
                            echo "</tr>";
                        }
                        ?>  
                    </table>
                </div>
            </div>
            <?php
            MTS('Latest matches table generated');
            break;
    
        case 'leaders':
        
            $f = 'mv_'.$box['field'];
            list($players, ) = Stats::getRaw(T_OBJ_PLAYER, array($box['type'] => $box['id']), array(1, $box['length']), array('-'.$f), false)
            ?>
            <div class="boxWide">
                <h3 class='boxTitle<?php echo T_HTMLBOX_STATS;?>'><?php echo $box['title'];?></h3>
                <div class='boxBody'>
                    <table class="boxTable">
                        <tr>
                            <td><i><?php echo $lng->getTrn('common/name');?></i></td>
                            <?php 
                            if ($box['show_team']) {
                                ?><td><i><?php echo $lng->getTrn('common/team');?></i></td><?php
                            }
                            ?>
                            <td><i>#</i></td>
                            <td><i><?php echo $lng->getTrn('common/value');?></i></td>
                        </tr>
                        <?php
                        foreach ($players as $p) {
                            echo "<tr>\n";
                            echo "<td>".(($settings['fp_links']) ? "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$p['player_id'],false,false)."'>$p[name]</a>" : $p['name'])."</td>\n";
                            if ($box['show_team']) {
                                echo "<td>".(($settings['fp_links']) ? "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$p['owned_by_team_id'],false,false)."'>$p[f_tname]</a>" : $p['f_tname'])."</td>\n";
                            }
                            echo "<td>".$p[$f]."</td>\n";
                            echo "<td>".$p['value']/1000 ."k</td>\n";
                            echo "</tr>";
                        }
                        ?>
                    </table>
                </div>
            </div>
            <?php
            MTS('Leaders standings generated');
            break;
            
        case 'events':
            $events = _events($box['content'], $box['type'], $box['id'], $box['length']);
            ?>
            <div class="boxWide">
                <h3 class='boxTitle<?php echo T_HTMLBOX_STATS;?>'><?php echo $box['title'];?></h3>
                <div class='boxBody'>
                    <table class="boxTable">
                        <?php
                        $head = array_pop($events);
                        echo "<tr>\n";
                        foreach ($head as $col => $name) {
                            echo "<td><i>$name</i></td>\n";
                        }
                        echo "</tr>\n";
                        foreach ($events as $e) {
                            echo "<tr>\n";
                            foreach ($head as $col => $name) {
                                switch ($col) {
                                    case 'date':
                                        $e->$col = str_replace(' ', '&nbsp;', textdate($e->$col,true));
                                        break;
                                    case 'name': 
                                        if ($settings['fp_links'])
                                            $e->$col = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$e->pid,false,false)."'>".$e->$col."</a>";
                                        break;
                                    case 'tname': 
                                        if ($settings['fp_links'])
                                            $e->$col = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$e->f_tid,false,false)."'>".$e->$col."</a>";
                                        break;
                                    case 'rname': 
                                        if ($settings['fp_links'])
                                            $e->$col = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_RACE,$e->f_rid,false,false)."'>".$e->$col."</a>";
                                        break;
                                    case 'f_pos_name': 
                                        if ($settings['fp_links'])
                                            $e->$col = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_RACE,$e->f_rid,false,false)."'>".$e->$col."</a>";
                                        break;
                                    case 'value': 
                                            $e->$col = $e->$col/1000 . 'k';
                                        break;
                                }
                                echo "<td>".$e->$col."</td>\n";
                            }
                            echo "</tr>\n";
                        }
                        ?>
                    </table>
                </div>
            </div>
            <?php
            MTS('Events box generated');
            break;
    }
    }
    ?>
    </div>
    <div class="main_foot">
        <?php
        HTMLOUT::dnt();
        ?>
        <br>
        <a TARGET="_blank" href="http://nicholasmr.dk/index.php?sec=obblm">OBBLM official website</a><br><br>
        This web site is completely unofficial and in no way endorsed by Games Workshop Limited.
        <br>
        Bloodquest, Blood Bowl, the Blood Bowl logo, The Blood Bowl Spike Device, Chaos, the Chaos device, the Chaos logo, Games Workshop, Games Workshop logo, Nurgle, the Nurgle device, Skaven, Tomb Kings, and all associated marks, names, races, race insignia, characters, vehicles, locations, units, illustrations and images from the Blood Bowl game, the Warhammer world are either (R), TM and/or (C) Games Workshop Ltd 2000-2006, variably registered in the UK and other countries around the world. Used without permission. No challenge to their status intended. All Rights Reserved to their respective owners.
        <br>
        FUMBBL icons are used with permission.  See 'about OBBLM' for credits.
    </div>
    <?php
}

$_INFOCUSCNT = 1; # HTML ID for _infocus()
function _infocus($teams) {
    
    //Create a new array of teams to display
    $ids = array();
    foreach ($teams as $team) {
        if (!$team['retired']) {
            $ids[] = $team['team_id'];
        }
    }

    if (empty($teams)) {
        return;
    }

    global $lng, $_INFOCUSCNT;

    //Select random team
    $teamKey = array_rand($ids);
    $teamId = $ids[$teamKey];
    $team = new Team($teamId);
    $teamLink =  "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$teamId,false,false)."'>$team->name</a>";

    //Create $logo_html
    $img = new ImageSubSys(IMGTYPE_TEAMLOGO, $team->team_id);
    $logo_html = "<img border='0px' height='60' alt='Team picture' src='".$img->getPath($team->f_race_id)."'>";

    //Create $starPlayers array used to display the three most experienced players on the team
    $starPlayers = array();
    foreach ($team->getPlayers() as $p) {
        if ($p->is_dead || $p->is_sold) {
            continue;
        }
        $starPlayers[] = array('name' => preg_replace('/\s/', '&nbsp;', $p->name), 'spp' => $p->mv_spp);
    }

    //Sort the array
    usort($starPlayers, create_function('$objA,$objB', 'return ($objA["spp"] < $objB["spp"]) ? +1 : -1;'));
    $starPlayers = array_slice($starPlayers, 0, 3); # Show only 3 Star players

    ?>
    <style type="text/css">
        /* InFocus Mod */
        #inFocusBox<?php echo $_INFOCUSCNT;?> .leftContentTd{
            font-weight: bold;
            padding-right: 1em;
        }

        #inFocusBox<?php echo $_INFOCUSCNT;?> .teamLogo {
            float: left;
            margin: 0 36px 0 20px;
        }

        #inFocusBox<?php echo $_INFOCUSCNT;?> .teamName {
            font-weight: bold;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> {
            position:relative;
            left: 160px;
            height: 80px;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> P {
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> DIV {
            position:absolute;
            top:0;
            left:0;
            z-index:8;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> DIV.invisible {
            display: none;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> DIV.inFocus {
            z-index:10;
            display: inline;
        }

        #inFocusContent<?php echo $_INFOCUSCNT;?> DIV.last-inFocus {
            z-index:9;redeclare compare_spp
        }
    </style>
    <div id="inFocusBox<?php echo $_INFOCUSCNT;?>" >
        <h3><?php echo $lng->getTrn('main/infocus').': '.$teamLink; ?></h3><br>
        <div style='clear:both;'>
            <div class='teamLogo'>
                <?php echo $logo_html; ?>
            </div>
            <div id="inFocusContent<?php echo $_INFOCUSCNT;?>">
                <div class="inFocus">
                    <table>
                        <tr><td class="leftContentTd"><?php echo $lng->getTrn('common/coach'); ?></td><td><?php echo $team->f_cname; ?></td></tr>
                        <tr><td class="leftContentTd"><?php echo $lng->getTrn('common/race'); ?></td><td><?php echo $team->f_rname; ?></td></tr>
                        <tr><td class="leftContentTd"><?php echo 'TV'; ?></td><td><?php echo (string)($team->tv / 1000); ?>k</td></tr>
                    </table>
                </div>
                <div class="invisible">
                    <p><?php echo $lng->getTrn('common/stars'); ?></p>
                    <table>
                        <?php
                        foreach($starPlayers as $player) {
                            echo "<tr><td class='leftContentTd'>".$player['name']."</td><td>".$player['spp']." spp</td></tr>";
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
    /* 
     * This script creates a slideshow of all <div>s in the "inFocusContent" div
     * 
     * Based on an example by Jon Raasch:
     *
     * http://jonraasch.com/blog/a-simple-jquery-slideshow
     */
    function nextContent<?php echo $_INFOCUSCNT;?>() {
        var $currentDiv = $('#inFocusContent<?php echo $_INFOCUSCNT;?> DIV.inFocus');
        var $nextDiv = $currentDiv.next().length ? $currentDiv.next() : $('#inFocusContent<?php echo $_INFOCUSCNT;?> DIV:first');
        $currentDiv.addClass('last-inFocus');

        //Fade current out
        $currentDiv.animate({opacity: 0.0}, 500, function() {
            $currentDiv.removeClass('inFocus last-inFocus');
            $currentDiv.addClass('invisible');
        });

        //Fade next in
        $nextDiv.css({opacity: 0.0})
            .addClass('inFocus')
            .animate({opacity: 1.0}, 500, function() {
            });
    }

    $(function() {
        setInterval( "nextContent<?php echo $_INFOCUSCNT;?>()", 5000 );
    });
    </script>
    
    <?php
    $_INFOCUSCNT++;
}

$_T_EVENTS = array('dead' => ObjEvent::T_PLAYER_DEAD, 'sold' => ObjEvent::T_PLAYER_SOLD, 'hired' => ObjEvent::T_PLAYER_HIRED, 'skills' => ObjEvent::T_PLAYER_SKILLS); # Allowed events
function _events($event, $node, $node_id, $N) {
    global $mv_keys, $_T_EVENTS, $lng;
    $dispColumns = array();
    switch ($event) {
        case 'dead':
        case 'sold':
        case 'hired':
            $dispColumns = array('name' => $lng->getTrn('common/player'), 'value' => $lng->getTrn('common/value'), 'tname' => $lng->getTrn('common/team'), 'date' => $lng->getTrn('common/date'));
            break;

        case 'skills':
            $dispColumns = array('skill' => $lng->getTrn('common/skill'), 'name' => $lng->getTrn('common/player'), 'f_pos_name' => $lng->getTrn('common/pos'), 'value' => $lng->getTrn('common/value'), 'tname' => $lng->getTrn('common/team'));
            break;

        // Event type not existing
        default:
          return array();
    }
    $events = ObjEvent::getRecentEvents($_T_EVENTS[$event], $node, $node_id, $N);
    $events[] = $dispColumns;
    return $events;
}

function sec_teamlist() {
    global $lng;
    title($lng->getTrn('menu/teams'));
    Team_HTMLOUT::dispList();
}

function sec_coachlist() {
    global $lng;
    title($lng->getTrn('menu/coaches'));
    Coach_HTMLOUT::dispList();
}

function sec_matcheshandler() {
    switch ($_GET['type'])
    {
        # Save all these subroutines in class_match_htmlout.php
        case 'tours':       Match_HTMLOUT::tours(); break;
        case 'tourmatches': Match_HTMLOUT::tourMatches(); break;
        case 'report':      Match_HTMLOUT::report(); break;
        case 'recent':      Match_HTMLOUT::recentMatches(); break;
        case 'upcoming':    Match_HTMLOUT::upcomingMatches(); break;
        case 'usersched':   Match_HTMLOUT::userSched(); break;
    }    
}

function sec_objhandler() {
    $types = array(T_OBJ_PLAYER => 'Player', T_OBJ_TEAM => 'Team', T_OBJ_COACH => 'Coach', T_OBJ_STAR => 'Star', T_OBJ_RACE => 'Race');
    foreach ($types as $t => $classPrefix) {
        if ($_GET['obj'] == $t) {
            switch ($_GET['type'])
            {
                case T_URL_STANDINGS:
                    call_user_func(
                        array("${classPrefix}_HTMLOUT", 'standings'), 
                        isset($_GET['node'])    ? (int) $_GET['node']    : false, 
                        isset($_GET['node_id']) ? (int) $_GET['node_id'] : false
                    );
                    break;
                case T_URL_PROFILE:
                    if (!call_user_func(array($classPrefix, 'exists'), (int) $_GET['obj_id'])) {
                        fatal("The specified ID does not exist.");
                    }
                    call_user_func(array("${classPrefix}_HTMLOUT", 'profile'), (int) $_GET['obj_id']);
                    break;
            }
        }
    }
}

/*************************
 *
 *  RULES
 *
 *************************/

function sec_rules() {
    global $lng, $settings, $leagues;
    title($lng->getTrn('menu/rules'));
    ?>
    <div class="boxWide">
        <?php
            if(count($leagues) > 1)
            {
                echo '<div class="boxTitle4">';
                list($sel_lid, $HTML_LeagueSelector) = HTMLOUT::simpleLeagueSelector();
                echo $HTML_LeagueSelector;
                echo '</div>';
            }
        ?>
        <div class="boxBody">
            <div>
                <?php echo $settings['rules']; ?>
            </div>
        </div>
    </div>
    <?php
}

/*************************
 *
 *  ABOUT
 *
 *************************/

function sec_about() {

    global $lng, $credits;
    title("About OBBLM");
    HTMLOUT::dnt();
    ?>
    <br>
    <p>
        <b>OBBLM version <?php echo OBBLM_VERSION; ?></b><br><br>
        Online Blood Bowl League Manager is an online game management system for Game Workshop's board game Blood Bowl.<br>
        <br>
        The authors of this program are
        <ul>
            <li> <a href="mailto:nicholas.rathmann@gmail.com">Nicholas Mossor Rathmann</a>
            <li> <a href="http://www.mercuryvps.com">William Leonard</a>
            <li> Niels Orsleff Justesen</a>
        </ul>
         <br>
        With special thanks to <?php $lc = array_pop($credits); echo implode(', ', $credits)." and $lc"; ?>.<br><br>
        Bugs reports and suggestions are welcome.
        <br><br>
        Further development carried out under the <a href="https://github.com/TheNAF/naflm">NAFLM fork</a> by
        <ul>
            <li> Steve Arthurs</a>
            <li> Craig Fleming</a>
        </ul>
        <br><br>With special thanks to Scott Bartel, Tom "Hutchinsfairy" and Michael Franchetti.
        <br><br>
        OBBLM consists of valid HTML 4.01 transitional document type pages.
        <br><br>
        <img src="http://www.w3.org/Icons/valid-html401" alt="Valid HTML 4.01 Transitional" height="31" width="88">
        <br><br>
        <b>Modules loaded:</b><br>
        <?php
        $mods = array();
        foreach (Module::getRegistered() as $modname) {
            list($author,$date,$moduleName) = Module::getInfo($modname);
            $mods[] = "<i>$moduleName</i> ($author, $date)";
        }
        echo implode(', ', $mods);
        ?>
    </p>

    <?php 
    title("OBBLM Hosting");
    echo 'Please visit <a href="http://www.mercuryvps.com">Mercury VPS</a> and click on the OBBLM tab to get started.';
    
    title("Documentation");
    echo "See the <a TARGET='_blank' href='".DOC_URL."'>OBBLM documentation wiki</a>";
    
    ?>

    <?php title("Disclaimer");?>
    <p>
        By installing and using this software you hereby accept and understand the following disclaimer
        <br><br>
        <b>This web site is completely unofficial and in no way endorsed by Games Workshop Limited.</b>
        <br><br>
        Bloodquest, Blood Bowl, the Blood Bowl logo, The Blood Bowl Spike Device, Chaos, the Chaos device, the Chaos logo, Games Workshop, Games Workshop logo, Nurgle, the Nurgle device, Skaven, Tomb Kings, 
        and all associated marks, names, races, race insignia, characters, vehicles, locations, units, illustrations and images from the Blood Bowl game, the Warhammer world are either ®, TM and/or © Games Workshop Ltd 2000-2006, 
        variably registered in the UK and other countries around the world. Used without permission. No challenge to their status intended. All Rights Reserved to their respective owners.
        <br><br>
        Fumbbl icons are used with permission.  Credits: harvestmouse, garion, christer, whatball.
    </p>

    <?php title("License");?>
    <p>
        Copyright (c) Niels Orsleff Justesen and Nicholas Mossor Rathmann 2007-2011. All Rights Reserved.
        <br><br>
        OBBLM is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation; either version 3 of the License, or
        (at your option) any later version.
        <br><br>
        OBBLM is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.
        <br><br>
        NAFLM is a fork of the original OBBLM programme and inherits all license and copyrights of the original.
        <br><br>
        You should have received a copy of the GNU General Public License
        along with this program.  If not, see http://www.gnu.org/licenses/.
    </p>
    <?php
}

function sec_requestleague() {
    global $coach, $settings, $db_user, $db_passwd;
    
    title("Request League");
    
    if (!isset($_SESSION['logged_in'])) {
        echo 'You must <a href="handler.php?type=registration"><b>register</b></a> as a League Commissioner before you can request a league.';
        return;
    }
    
    if(isset($_POST['requesting_league'])) {
        $to = Email::getAdministratorEmails();
        echo 'asdf' . $to . 'qwer';
        $subject = 'Request to create a league on TheNAF OBBLM.';
        $message = 'Commissioner Username: ' . $coach->name .
            '\n Full League Name: ' . $_POST['full_league_name'] .
            '\n Short League Name: ' . $_POST['short_league_name'] .
            '\n League City, State, Province: ' . $_POST['league_city_state_province'] .
            '\n League Country: ' . $_POST['league_country'];
        $headers = 'From: '.$_POST['email']. "\r\n" .
                   'Reply-To: '.$_POST['email']. "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        if (!mail($to, $subject, $message, $headers)) {
            ?>
            <div class="boxWide">
                <div class="boxTitle3">There was an error sending your message!</div>
                <div class="boxBody">
                    So you should try your favourite e-mail client instead.
                    <div class="quote">
                        <div><strong>To: </strong><?php echo $to; ?></div>
                        <div><strong>Subject: </strong><?php echo $subject; ?></div>
                        <div><strong>Body: </strong><?php echo $message; ?></div>
                    </div>
                </div>
            <?php
        } else {
            ?>
            Your message was sent successfully. An administrator will get back to you soon!
            <?php
        }
    }
    else {
        ?>
        <form method="POST" id="RequestLeagueForm">
            <input type="hidden" name="section" value="requestleague" />
            <input type="hidden" name="requesting_league" value="true" />
            <div class="input-item"><label>Your e-mail: </label><input type="text" name="email" /></div>
            <div class="input-item"><label>Full League Name: </label><input type="text" name="full_league_name" /></div>
            <div class="input-item"><label>Short League Name: </label><input type="text" name="short_league_name" /></div>
            <div class="input-item"><label>League City, State, Province: </label><input type="text" name="league_city_state_province" /></div>
            <div class="input-item"><label>League Country: </label><input type="text" name="league_country" /></div>
            <input type="submit" value="Send" />
        </form>
        <?php
    }
}
