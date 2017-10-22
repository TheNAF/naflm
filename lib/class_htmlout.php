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
/*
 THIS FILE is used for HTML-helper routines.
 */
// Special dropdown states for nodeSelector().
define('T_STATE_ALLTIME', 1);
define('T_STATE_ACTIVE', 2);
define('T_NODE_ALL', -1);  # All nodes.
define('T_RACE_ALL', -1);  # All races.
class HTMLOUT
{
public static function recentGames($obj, $obj_id, $node, $node_id, $opp_obj, $opp_obj_id, array $opts)
{
    /*
        Make recent games table.
         $opts = array(
            'url' => The URL of the page on which this table is to be printed.
            'n' => (int) Fetch the n most recent games. If not specified all matches are displayed.
            'GET_SS' => GET Sorting suffix
         );
    */
    global $lng;
    $T_ROUNDS = Match::getRounds();
    $extra = array('doNr' => false, 'noHelp' => true);
    if (!array_key_exists('GET_SS', $opts)) {$opts['GET_SS'] = '';}
    else {$extra['GETsuffix'] = $opts['GET_SS'];} # GET Sorting Suffix
    if (!(array_key_exists('n', $opts) && $opts['n'])) {$opts['n'] = false;}
    if (!$opts['n']) {
        $N = array();
    }
    else {
        $N = isset($_GET["page"]) 
            ? array((int) $_GET["page"],$opts['n'])
            : array(1,$opts['n']);
    }
    
    $FOR_OBJ = $obj;
    if ($obj && $obj_id)
        list($matches, $pages) = Stats::getMatches($obj, $obj_id, $node, $node_id, $opp_obj, $opp_obj_id, $N, true, false);
    else
        list($matches, $pages) = Match::getMatches($N, ($node) ? $node : false, ($node) ? $node_id : false, false);
    $extra['page'] = $N[0];
    $extra['pages'] = $pages;
    foreach ($matches as $m) {
        $m->date_played_disp = textdate($m->date_played, false, false);
        $m->score = "$m->team1_score&mdash;$m->team2_score";
        $m->mlink = "<a href='index.php?section=matches&amp;type=report&amp;mid=$m->match_id'>".$lng->getTrn('common/view')."</a>";
        $m->tour_name = Tour::getTourUrl($m->f_tour_id);
        $m->league_name = League::getLeagueUrl(get_parent_id(T_NODE_TOURNAMENT, $m->f_tour_id, T_NODE_LEAGUE));
        if ($FOR_OBJ) {
            $m->result = matchresult_icon($m->result);
        }
        if (in_array($m->round,array_keys($T_ROUNDS))) {
            $m->round = $T_ROUNDS[$m->round];
        }
        $m->team1_name = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team1_id,false,false)."'>$m->team1_name</a>&nbsp;<i>(<a href='".urlcompile(T_URL_PROFILE,T_OBJ_COACH,$m->coach1_id,false,false)."'>$m->coach1_name</a>)</i>";
        $m->team2_name = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team2_id,false,false)."'>$m->team2_name</a>&nbsp;<i>(<a href='".urlcompile(T_URL_PROFILE,T_OBJ_COACH,$m->coach2_id,false,false)."'>$m->coach2_name</a>)</i>";
    }
    $fields = array(
        'date_played_disp' => array('desc' => $lng->getTrn('common/dateplayed'), 'nosort' => true),
        'league_name' => array('desc' => $lng->getTrn('common/league'), 'nosort' => true),
        'tour_name'   => array('desc' => $lng->getTrn('common/tournament'), 'nosort' => true),
        'round'       => array('desc' => $lng->getTrn('common/round'), 'nosort' => true),
        'team1_name'    => array('desc' => $lng->getTrn('common/home'), 'nosort' => true),
        'team2_name'    => array('desc' => $lng->getTrn('common/away'), 'nosort' => true),
// Old solution
#        'team1_name'  => array('desc' => $lng->getTrn('common/home'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team1_id'), 'nosort' => true),
#        'team2_name'  => array('desc' => $lng->getTrn('common/away'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team2_id'), 'nosort' => true),
        'gate'        => array('desc' => $lng->getTrn('common/gate'), 'kilo' => true, 'suffix' => 'k', 'href' => false, 'nosort' => true),
        'score'       => array('desc' => $lng->getTrn('common/score'), 'nosort' => true),
    );
    if ($FOR_OBJ) {$fields['result'] = array('desc' => $lng->getTrn('common/result'), 'nosort' => true);}
    $fields['mlink'] = array('desc' => $lng->getTrn('common/match'), 'nosort' => true); # Must be last!
    HTMLOUT::sort_table(
        $lng->getTrn('common/recentmatches'),
        $opts['url'],
        $matches,
        $fields,
        sort_rule('match'),
        (isset($_GET["sort$opts[GET_SS]"])) ? array((($_GET["dir$opts[GET_SS]"] == 'a') ? '+' : '-') . $_GET["sort$opts[GET_SS]"]) : array(),
        $extra
    );
}
public static function upcomingGames($obj, $obj_id, $node, $node_id, $opp_obj, $opp_obj_id, array $opts)
{
    /*
        Make upcoming games table.
         $opts = array(
            'url' => The URL of the page on which this table is to be printed.
            'n' => (int) Fetch the n most recent games. If not specified all matches are displayed.
            'GET_SS' => GET Sorting suffix
         );
    */
    global $lng;
    $T_ROUNDS = Match::getRounds();
    $extra = array('doNr' => false, 'noHelp' => true);
    if (!array_key_exists('GET_SS', $opts)) {$opts['GET_SS'] = '';}
    else {$extra['GETsuffix'] = $opts['GET_SS'];} # GET Sorting Suffix
    if (!(array_key_exists('n', $opts) && $opts['n'])) {$opts['n'] = false;}
    if (!$opts['n']) {
        $N = array();
    }
    else {
        $N = isset($_GET["page"]) 
            ? array((int) $_GET["page"],$opts['n'])
            : array(1,$opts['n']);
    }
    if ($obj && $obj_id)
        list($matches, $pages) = Stats::getMatches($obj, $obj_id, $node, $node_id, $opp_obj, $opp_obj_id, $N, true, true);
    else
        list($matches, $pages) = Match::getMatches($N, ($node) ? $node : false, ($node) ? $node_id : false, true);
    $extra['page'] = $N[0];
    $extra['pages'] = $pages;
    foreach ($matches as $m) {
        $m->date_created_disp = textdate($m->date_created, true);
        $m->mlink = "<a href='index.php?section=matches&amp;type=report&amp;mid=$m->match_id'>".$lng->getTrn('common/view')."</a>";
        $m->tour_name = Tour::getTourUrl($m->f_tour_id);
        $m->league_name = League::getLeagueUrl(get_parent_id(T_NODE_TOURNAMENT, $m->f_tour_id, T_NODE_LEAGUE));
        if (in_array($m->round,array_keys($T_ROUNDS))) {
            $m->round = $T_ROUNDS[$m->round];
        }
        $m->team1_name = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team1_id,false,false)."'>$m->team1_name</a>&nbsp;<i>(<a href='".urlcompile(T_URL_PROFILE,T_OBJ_COACH,$m->coach1_id,false,false)."'>$m->coach1_name</a>)</i>";
        $m->team2_name = "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$m->team2_id,false,false)."'>$m->team2_name</a>&nbsp;<i>(<a href='".urlcompile(T_URL_PROFILE,T_OBJ_COACH,$m->coach2_id,false,false)."'>$m->coach2_name</a>)</i>";
    }
    $fields = array(
        'date_created_disp'  => array('desc' => $lng->getTrn('common/datecreated'), 'nosort' => true),
        'league_name'   => array('desc' => $lng->getTrn('common/league'), 'nosort' => true),
        'tour_name'     => array('desc' => $lng->getTrn('common/tournament'), 'nosort' => true),
        'round'         => array('desc' => $lng->getTrn('common/round'), 'nosort' => true),
        'team1_name'    => array('desc' => $lng->getTrn('common/home'), 'nosort' => true),
        'team2_name'    => array('desc' => $lng->getTrn('common/away'), 'nosort' => true),
// Old solution
#        'team1_name'    => array('desc' => $lng->getTrn('common/home'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team1_id'), 'nosort' => true),
#        'team2_name'    => array('desc' => $lng->getTrn('common/away'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team2_id'), 'nosort' => true),
        'mlink'         => array('desc' => $lng->getTrn('common/match'), 'nosort' => true),
    );
    HTMLOUT::sort_table(
        $lng->getTrn('common/upcomingmatches'),
        $opts['url'],
        $matches,
        $fields,
        array('+round','+date_created'),
        (isset($_GET["sort$opts[GET_SS]"])) ? array((($_GET["dir$opts[GET_SS]"] == 'a') ? '+' : '-') . $_GET["sort$opts[GET_SS]"]) : array(),
        $extra
    );
}
private static function _getDefFields($obj, $node, $node_id)
{
    /*
        Shared use by standings() and nodeSelector().
        These are the default fields (general/regular stats) in standings() and the "having" filters of nodeSelector().
        mv_ fields are accumulated stats from MV tables. rg_ (regular) are regular/static/all-time fields from non mv-tables (players, teams, coaches etc.)
    */
    global $lng;
    $fields = array(
        'mv_won'     => array('desc' => 'W'),
        'mv_draw'    => array('desc' => 'D'),
        'mv_lost'    => array('desc' => 'L'),
        'mv_played'  => array('desc' => 'GP'),
        'rg_win_pct' => array('desc' => 'WIN%'),
        'rg_swon'    => array('desc' => 'SW'),
        'rg_sdraw'   => array('desc' => 'SD'),
        'rg_slost'   => array('desc' => 'SL'),
        'mv_gf'      => array('desc' => 'GF'),
        'mv_ga'      => array('desc' => 'GA'),
        'mv_td'      => array('desc' => 'Td'),
        'mv_cp'      => array('desc' => 'Cp'),
        'mv_intcpt'  => array('desc' => 'Int'),
        'mv_cas'     => array('desc' => 'Cas'),
        'mv_bh'      => array('desc' => 'BH'),
        'mv_si'      => array('desc' => 'Si'),
        'mv_ki'      => array('desc' => 'Ki'),
    );
    // These fields are not summable!!!
    //ie. you dont get the division/league value of these fields by summing over the related/underlying tournaments field's values.
    global $objFields_notsum;
        # Look non-summable field and remove them.
    $ALL_TIME = self::_isNodeAllTime($obj, $node, $node_id);
    if (!$ALL_TIME) {
        if ($node == T_NODE_TOURNAMENT) {
            $new_fields = array();
            foreach ($fields as $fname => $fcont) {
                $f = preg_replace('/^\w\w\_/', '', $fname);
                $new_fields[in_array($f, $objFields_notsum) ? "mv_$f" : $fname] = $fcont;
            }
            $fields = $new_fields;
        }
        foreach ($objFields_notsum as $f) {
            unset($fields["rg_$f"]);
        }
    }
    $fields_before = $fields_after = array();
    switch ($obj)
    {
        case STATS_PLAYER:
            $fields_after = array(
                'mv_mvp' => array('desc' => 'MVP'),
                'mv_spp' => array('desc' => 'SPP'),
                'value'  => array('desc' => $lng->getTrn('common/value'), 'nosort' => !$ALL_TIME, 'kilo' => true, 'suffix' => 'k'),
            );
            foreach (array('won', 'lost', 'draw') as $f) {
                unset($fields["rg_s$f"]);
                unset($fields["mv_s$f"]);
            }
            break;
        case STATS_TEAM:
            $fields_before = array(
                'tv' => array('desc' => $lng->getTrn('common/value'), 'kilo' => true, 'suffix' => 'k'),
            );
            $fields_after = array(
                'mv_tcasf' => array('desc' => 'tcasf'),
                'mv_tcasa' => array('desc' => 'tcasa'),
                'mv_tcdiff' => array('desc' => 'tcdiff'),
                'mv_smp' => array('desc' => 'SMP'),
            );
            if ($ALL_TIME) {
                $fields_after['wt_cnt'] = array('desc' => 'WT');
                $fields_after['rg_elo'] = array('desc' => 'ELO');
            }
            else if ($node == T_NODE_TOURNAMENT) {
                $fields_after['mv_elo'] = array('desc' => 'ELO');
                $tr = new Tour($node_id);
                if ($tr->isRSWithPoints()) {
                    $fields_after['mv_pts'] = array('desc' => 'PTS');
                }
                unset($fields_after['tv']);
            }
            break;
        case STATS_RACE:
            if ($node == T_NODE_TOURNAMENT) {
                $fields_before['mv_team_cnt'] = array('desc' => $lng->getTrn('common/teams'));
            }
            else if ($ALL_TIME) {
                $fields_before['rg_team_cnt'] = array('desc' => $lng->getTrn('common/teams'));
            }
            foreach (array('won', 'lost', 'draw') as $f) {
                unset($fields["rg_s$f"]);
                unset($fields["mv_s$f"]);
            }
            break;
        case STATS_COACH:
            $fields_after = array(
                'mv_tcasf' => array('desc' => 'tcasf'),
                'mv_tcasa' => array('desc' => 'tcasa'),
                'mv_tcdiff' => array('desc' => 'tcdiff'),
                'mv_smp' => array('desc' => 'SMP'),
            );
            if ($node == T_NODE_TOURNAMENT) {
                $fields_before['mv_team_cnt'] = array('desc' => $lng->getTrn('common/teams'));
                $fields_after['mv_elo'] = array('desc' => 'ELO');
            }
            else if ($ALL_TIME) {
                $fields_before['rg_team_cnt'] = array('desc' => $lng->getTrn('common/teams'));
                $fields_after['rg_elo'] = array('desc' => 'ELO');
            }
            break;
        case STATS_STAR:
            $fields_after = array(
                'mv_mvp' => array('desc' => 'MVP'),
                'mv_spp' => array('desc' => 'SPP'),
            );
            foreach (array('won', 'lost', 'draw', 'ga', 'gf') as $f) {
                unset($fields["rg_s$f"]);
                unset($fields["mv_s$f"]);
            }
            unset($fields["rg_win_pct"]);
            break;
    }
    return array_merge($fields_before, $fields, $fields_after);
}
private static function _isNodeAllTime($obj, $node, $node_id)
{
    # Teams may not cross leagues, so a team's league stats is equal to its all-time stats.
    return (!$node || !$node_id || ($node == T_NODE_LEAGUE && $node_id == T_NODE_ALL) || ($obj == T_OBJ_TEAM && $node == T_NODE_LEAGUE));
}
public static function standings($obj, $node, $node_id, array $opts)
{
    /*
         Makes various kinds of standings tables.
         $obj and $node types are STATS_* types.
         $opts = array(
            'url' => page URL on which table is to be displayed (required!)
            'GET_SS' => GET Sorting suffix
            'return_objects' => bool
            'teams_from' => [T_OBJ_COACH|T_OBJ_RACE] when $obj = T_OBJ_TEAM and this is set, only teams related to this object type (teams_from), of ID = $opts[teams_from_id] are fetched.
            'teams_from_id' => ID (int) see "teams_from" for details.
         );
     */
    global $lng, $settings, $objFields_avg;
    $tblTitle = '';
    $objs = $fields = $extra = array();
    $fields_before = $fields_after = array(); // To be merged with $fields.
    if (!array_key_exists('GET_SS', $opts)) {$opts['GET_SS'] = '';}
    else {$extra['GETsuffix'] = $opts['GET_SS'];} # GET Sorting Suffix
    $PAGE = isset($_GET["page"]) 
            ? (int) $_GET["page"]
            : 1;
    $PAGELENGTH = 0; # Infinite, is overrided in below switch/case..
    $extra['noHelp'] = false;
    $W_TEAMS_FROM = array_key_exists('teams_from', $opts);
    $enableRaceSelector = ($obj == T_OBJ_PLAYER || $obj == T_OBJ_TEAM && (!isset($opts['teams_from']) || $opts['teams_from'] != T_OBJ_RACE));
    # NO filters for teams of a coach on the coach's teams list.
    $_COACH_TEAM_LIST = ($W_TEAMS_FROM && $opts['teams_from'] == T_OBJ_COACH);
    if ($_COACH_TEAM_LIST) {
        list(,,$T_STATE) = HTMLOUT::nodeSelector(array('nonodes' => true, 'state' => true)); # Produces a state selector.
        $_SELECTOR = array(false,false,$T_STATE,T_RACE_ALL,'GENERAL','mv_played',self::T_NS__ffilter_ineq_gt,0);
    }
    else {
        $_SELECTOR = HTMLOUT::nodeSelector(array('force_node' => array($node,$node_id), 'race' => $enableRaceSelector, 'sgrp' => true, 'ffilter' => true, 'obj' => $obj));
    }
    list($sel_node, $sel_node_id, $sel_state, $sel_race, $sel_sgrp, $sel_ff_field, $sel_ff_ineq, $sel_ff_limit) = $_SELECTOR;
    $filter_node = array($sel_node => $sel_node_id);
    $filter_race = ($sel_race != T_RACE_ALL) ? array(T_OBJ_RACE => $sel_race) : array();
    $filter_having = array('having' => array($sel_ff_field.(($sel_ff_ineq == self::T_NS__ffilter_ineq_gt) ? '>=' : '<=').$sel_ff_limit));
    if ($_COACH_TEAM_LIST && $sel_state != T_STATE_ALLTIME) {
        $filter_having['having'][] = 'rdy IS TRUE';
        $filter_having['having'][] = 'retired IS FALSE';
    }
    $SGRP_GEN = ($sel_sgrp == 'GENERAL');
    $ALL_TIME = self::_isNodeAllTime($obj, $sel_node, $sel_node_id);
    $manualSort = isset($_GET["sort$opts[GET_SS]"]);
    $sortRule = array_merge(
        ($manualSort) ? array((($_GET["dir$opts[GET_SS]"] == 'a') ? '+' : '-') . $_GET["sort$opts[GET_SS]"]) : array(),
        ($obj == T_OBJ_TEAM && $sel_node == T_NODE_TOURNAMENT && is_object($tr = new Tour($sel_node_id)))
            ? array_map(create_function('$val', 'return $val[0]."mv_".substr($val,1);'), $tr->getRSSortRule())
            : sort_rule($obj)
    );
    $set_avg = (isset($_GET['pms']) && $_GET['pms']); // Per match stats?
    echo '<br><a href="'.$opts['url'].'&amp;pms='.(($set_avg) ? 0 : 1).'"><b>'.$lng->getTrn('common/'.(($set_avg) ? 'ats' : 'pms'))."</b></a><br><br>\n";
    // Common $obj type fields.
    $fields = self::_getDefFields($obj, $sel_node, $sel_node_id);
    // Was a different (non-general) stats group selected?
    if (!$SGRP_GEN) {
        $grps_short = getESGroups(true,true);
        $grps_long = getESGroups(true,false);
        $fields_short = $grps_short[$sel_sgrp];
        $fields_long = $grps_long[$sel_sgrp];
        $fields = array_combine(
            array_strpack('mv_%s', $fields_long),
            array_map(create_function('$f', 'return array("desc" => $f);'), $fields_short)
        );
        $objFields_avg = array_merge($objFields_avg, array_map(create_function('$k', 'return substr($k, 3);'), array_keys($fields)));
    }
    switch ($obj)
    {
        case STATS_PLAYER:
            $tblTitle = $lng->getTrn('menu/statistics_menu/player_stn');
            $fields_before = array(
                'name'    => array('desc' => $lng->getTrn('common/player'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,false,false,false), 'field' => 'obj_id', 'value' => 'player_id')),
                'f_tname' => array('desc' => $lng->getTrn('common/team'),   'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'owned_by_team_id')),
            );
            $PAGELENGTH = $settings['standings']['length_players'];
            list($objs, $PAGES) = Stats::getRaw(T_OBJ_PLAYER, $filter_node+$filter_having+$filter_race, array($PAGE, $PAGELENGTH), $sortRule, $set_avg);
            break;
        case STATS_TEAM:
            $tblTitle = $lng->getTrn('menu/statistics_menu/team_stn');
            $fields_before = array(
                'name' => array('desc' => $lng->getTrn('common/name'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'team_id')),
            );
            // Show teams standings list only for teams owned by... ?
            switch ($W_TEAMS_FROM ? $opts['teams_from'] : false)
            {
                case T_OBJ_COACH:
                    $fields_before['f_rname'] = array('desc' => $lng->getTrn('common/race'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_RACE,false,false,false), 'field' => 'obj_id', 'value' => 'f_race_id'));
                    list($objs, $PAGES) = Stats::getRaw(T_OBJ_TEAM, $filter_node+$filter_having+$filter_race+array(T_OBJ_COACH => (int) $opts['teams_from_id']), false, $sortRule, $set_avg);
                    break;
                case T_OBJ_RACE:
                    $fields_before['f_cname'] = array('desc' => $lng->getTrn('common/coach'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false), 'field' => 'obj_id', 'value' => 'owned_by_coach_id'));
                    $PAGELENGTH = $settings['standings']['length_teams'];
                    list($objs, $PAGES) = Stats::getRaw(T_OBJ_TEAM, $filter_node+$filter_having+array(T_OBJ_RACE => (int) $opts['teams_from_id']), array($PAGE, $PAGELENGTH), $sortRule, $set_avg);
                    break;
                // All teams
                default:
                    $PAGELENGTH = $settings['standings']['length_teams'];
                    list($objs, $PAGES) = Stats::getRaw(T_OBJ_TEAM, $filter_node+$filter_having+$filter_race, array($PAGE, $PAGELENGTH), $sortRule, $set_avg);
            }
            // Translating race name
            foreach($objs as &$o)
              $o['f_rname'] = $lng->getTrn('race/'.strtolower(str_replace(' ','', $o['f_rname'])));
            break;
        case STATS_RACE:
            $tblTitle = $lng->getTrn('menu/statistics_menu/race_stn');
            $fields_before = array(
                'name' => array('desc' => $lng->getTrn('common/race'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_RACE,false,false,false), 'field' => 'obj_id', 'value' => 'race_id')),
            );
            $dash_empty = false;
            if ($sel_node == T_NODE_TOURNAMENT) {
                $dash_empty = 'mv_team_cnt';
            }
            else if ($ALL_TIME) {
                $dash_empty = 'rg_team_cnt';
            }
            if ($dash_empty) {
                $extra['dashed'] = array('condField' => $dash_empty, 'fieldVal' => 0, 'noDashFields' => array('name'));
            }
            list($objs, $PAGES) = Stats::getRaw(T_OBJ_RACE, $filter_node+$filter_having, false, $sortRule, $set_avg);
            // Translating race name
            foreach($objs as &$o)
              $o['name'] = $lng->getTrn('race/'.strtolower(str_replace(' ','', $o['name'])));
            break;
        case STATS_COACH:
            $tblTitle = $lng->getTrn('menu/statistics_menu/coach_stn');
            $fields_before = array(
                'name' => array('desc' => $lng->getTrn('common/coach'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false), 'field' => 'obj_id', 'value' => 'coach_id')),
            );
            $PAGELENGTH = $settings['standings']['length_coaches'];
            list($objs, $PAGES) = Stats::getRaw(T_OBJ_COACH, $filter_node+$filter_having, array($PAGE, $PAGELENGTH), $sortRule, $set_avg);
            break;
        case STATS_STAR:
            $tblTitle = $lng->getTrn('menu/statistics_menu/star_stn');
            $fields_before = array(
                'name' => array('desc' => $lng->getTrn('common/star'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_STAR,false,false,false), 'field' => 'obj_id', 'value' => 'star_id')),
                // Statics
                'cost'   => array('desc' => 'Price', 'kilo' => true, 'suffix' => 'k'),
                'ma'     => array('desc' => 'Ma'),
                'st'     => array('desc' => 'St'),
                'ag'     => array('desc' => 'Ag'),
                'av'     => array('desc' => 'Av'),
            );
            $extra['dashed'] = array('condField' => 'mv_played', 'fieldVal' => 0, 'noDashFields' => array('name'));
            list($objs, $PAGES) = Stats::getRaw(T_OBJ_STAR, $filter_node+$filter_having, false, $sortRule, $set_avg);
            break;
    }
    foreach ($objs as $idx => $obj) {$objs[$idx] = (object) $obj;}
    if (!$SGRP_GEN) {
        $tmp = $fields_before['name'];
        $fields_before = $fields_after = array();
        $fields_before['name'] = $tmp;
    }
    $fields = array_merge($fields_before, $fields, $fields_after);
    // Add average marker on fields (*).
    if ($set_avg) {
        foreach (array_keys($fields) as $f) {
            $f_cut = preg_replace('/^\w\w\_/', '', $f);
            if (in_array($f_cut, $objFields_avg)) {
                $fields[$f]['desc'] .= '*';
            }
        }
    }
    $extra['page'] = $PAGE;
    $extra['pages'] = $PAGES;
    $extra['pagelength'] = $PAGELENGTH;
    HTMLOUT::sort_table(
       $tblTitle,
       $opts['url'].(($set_avg) ? '&amp;pms=1' : ''),
       $objs,
       $fields,
       $sortRule,
       array(),
       $extra
    );
    return (array_key_exists('return_objects', $opts) && $opts['return_objects']) ? array($objs, $sortRule) : true;
}
// We need this so that a new league's settings gets loaded on next page reload when set in the node selector.
public static function updateNodeSelectorLeagueVars()
{
    global $leagues;
    /* Simple league selector (SLS) */
    $lids = array_keys($leagues);
    if (isset($_REQUEST['SLS_lid']) && in_array($_REQUEST['SLS_lid'], $lids)) {
        $_SESSION[self::T_NSStr__node]    = T_NODE_LEAGUE;
        $_SESSION[self::T_NSStr__node_id] = (int) $_REQUEST['SLS_lid'];
    }
    /* Advanced node selector (ANS) */
    if (isset($_REQUEST['ANS'])) {
        $_SESSION[self::T_NSStr__node] = (int) $_POST['node'];
        $rel = array(T_NODE_TOURNAMENT => 'tour', T_NODE_DIVISION => 'division', T_NODE_LEAGUE => 'league');
        $_SESSION[self::T_NSStr__node_id] = (int) $_POST[$rel[$_SESSION[self::T_NSStr__node]].'_in'];
    }
}

public static function getSelectedNodeLidOrDefault() {
    global $leagues, $coach, $settings;
    
    if(!isset($leagues))
        return 1;
    
    $lids = array_keys($leagues); # Used multiple times below to determine selected FP league.
    $sel_lid = (is_object($coach) && isset($coach->settings['home_lid']) && in_array($coach->settings['home_lid'], $lids)) ? $coach->settings['home_lid'] : $settings['default_visitor_league'];
    
    if ($_lid = self::getSelectedNodeLid())
        $sel_lid = $_lid;
    
    return $sel_lid;
}

const T_NSStr__node    = 'NS_node';
const T_NSStr__node_id = 'NS_node_id';
public static function simpleLeagueSelector()
{
    global $lng;

    $sel_lid = self::getSelectedNodeLidOrDefault();
    
    # Save league view.
    $_SESSION[self::T_NSStr__node]    = T_NODE_LEAGUE;
    $_SESSION[self::T_NSStr__node_id] = (int) $sel_lid;
    $HTMLselector = $lng->getTrn('common/league').'&nbsp;';
    $HTMLselector .= "<form name='SLS' method='POST' style='display:inline; margin:0px;'>";
    $HTMLselector .= self::nodeList(T_NODE_LEAGUE, 'SLS_lid',array(),array(),array('sel_id' => $sel_lid, 'extra_tags' => array("onChange='document.SLS.submit();'")));
    $HTMLselector .= "</form>\n";
    return array($sel_lid, $HTMLselector);
}
public static function getSelectedNodeLid()
{
    global $leagues;
    $lids = array_keys($leagues);
    $_lid = false;
    if (isset($_SESSION[self::T_NSStr__node_id]) && (int) $_SESSION[self::T_NSStr__node_id] > 0) {
        $_lid = ((int) $_SESSION[self::T_NSStr__node] != T_NODE_LEAGUE)
            ? get_parent_id((int) $_SESSION[self::T_NSStr__node], (int) $_SESSION[self::T_NSStr__node_id], T_NODE_LEAGUE)
            : (int) $_SESSION[self::T_NSStr__node_id];
        if (!in_array($_lid, $lids)) {
            $_lid = false;
        }
    }
    return $_lid;
}
// Node Selector constants
const T_NS__ffilter_ineq_gt = 1; # Greater than.
const T_NS__ffilter_ineq_lt = 2; # Less than.
public static function nodeSelector(array $opts)
{
    global $lng, $settings, $raceididx, $coach;
    // Set defaults
    $s_node     = self::T_NSStr__node;
    $s_node_id  = self::T_NSStr__node_id;
    $s_state    = "NS_state";
    $s_race     = "NS_race";
    $s_sgrp     = "NS_sgrp";
        # Field filter
    $s_ffilter_field = "NS_ffilter__field"; # Field name, e.g. "mv_played".
    $s_ffilter_ineq  = "NS_ffilter__ineq"; # inequality direction (">=" or "<="), self::T_NS__ffilter_ineq_* values.
    $s_ffilter_limit = "NS_ffilter__limit"; # RHS of ineq, e.g. "20" in the expression mv_played > 20
    // Options
    $hideNodes = (array_key_exists('nonodes', $opts) && $opts['nonodes']); # This is not a "set" option because it was implemented later, and for backwards compabillity (not changing the caller's syntax) we therefore do it this way.
    $setState = (array_key_exists('state', $opts) && $opts['state']);
    $setRace = (array_key_exists('race', $opts) && $opts['race']);
    $setSGrp = (array_key_exists('sgrp', $opts) && $opts['sgrp']);
    $setFFilter = (array_key_exists('ffilter', $opts) && $opts['ffilter']);
    $obj = ($setFFilter) ? $opts['obj'] : null;
    // Defaults
    $def_node    = T_NODE_LEAGUE;
    $def_node_id = (is_object($coach) && isset($coach->settings['home_lid'])) ? $coach->settings['home_lid'] : $settings['default_visitor_league'];
    $def_state   = T_STATE_ACTIVE; # No longer T_STATE_ALLTIME;
    $def_race    = T_RACE_ALL;
    $def_sgrp    = 'GENERAL';
    $def_ffilter_field = 'mv_played';
    $def_ffilter_ineq  = self::T_NS__ffilter_ineq_gt;
    $def_ffilter_limit = '0';
    // Forcings
    $force_node = $force_node_id = false;
    if (isset($opts['force_node']) && is_numeric($opts['force_node'][0]) && is_numeric($opts['force_node'][1])) {
        list($force_node,$force_node_id) = $opts['force_node'];
    }
    $NEW = isset($_POST['ANS']);
    $_SESSION[$s_state] = ($NEW && $setState) ? (int) $_POST['state_in'] : (isset($_SESSION[$s_state]) ? $_SESSION[$s_state] : $def_state);
    $_SESSION[$s_race]  = ($NEW && $setRace)  ? (int) $_POST['race_in']  : (isset($_SESSION[$s_race])  ? $_SESSION[$s_race]  : $def_race);
    $_SESSION[$s_sgrp]  = ($NEW && $setSGrp)  ? $_POST['sgrp_in']        : (isset($_SESSION[$s_sgrp])  ? $_SESSION[$s_sgrp]  : $def_sgrp);
    # NOTE: Form selections updates of $_SESSION node vars are done via self::updateNodeSelectorLeagueVars().
    $_SESSION[$s_node]    = $force_node    ? $force_node    : (isset($_SESSION[$s_node])     ? $_SESSION[$s_node]    : $def_node);
    $_SESSION[$s_node_id] = $force_node_id ? $force_node_id : (isset($_SESSION[$s_node_id])  ? $_SESSION[$s_node_id] : $def_node_id);
    $_SESSION[$s_ffilter_field] = ($NEW && $setFFilter) ? $_POST['ffilter_field_in'] : (isset($_SESSION[$s_ffilter_field]) ? $_SESSION[$s_ffilter_field] : $def_ffilter_field);
    $_SESSION[$s_ffilter_ineq]  = ($NEW && $setFFilter) ? $_POST['ffilter_ineq_in']  : (isset($_SESSION[$s_ffilter_ineq])  ? $_SESSION[$s_ffilter_ineq]  : $def_ffilter_ineq);
    $_SESSION[$s_ffilter_limit] = ($NEW && $setFFilter) ? $_POST['ffilter_limit_in'] : (isset($_SESSION[$s_ffilter_limit]) ? $_SESSION[$s_ffilter_limit] : $def_ffilter_limit);
    // Fetch contents of node selector
#    $leagues = Coach::allowedNodeAccess(Coach::NODE_STRUCT__TREE, is_object($coach) ? $coach->coach_id : false);
    ?>
    <form method="POST">
    <?php 
    echo $lng->getTrn('common/displayfrom');
    ?>
    <select <?php if ($hideNodes) {echo "style='display:none;'";}?> name="node" onChange="
        selConst = Number(this.options[this.selectedIndex].value);
        disableall();
        switch(selConst)
        {
            case <?php echo T_NODE_TOURNAMENT;?>: document.getElementById('tour_in').style.display = 'inline'; break;
            case <?php echo T_NODE_DIVISION;?>:   document.getElementById('division_in').style.display = 'inline'; break;
            case <?php echo T_NODE_LEAGUE;?>:     document.getElementById('league_in').style.display = 'inline'; break;
        }
    ">
        <?php
        foreach (array(T_NODE_LEAGUE => $lng->getTrn('common/league'), T_NODE_DIVISION => $lng->getTrn('common/division'), T_NODE_TOURNAMENT => $lng->getTrn('common/tournament')) as $const => $name) {
            echo "<option value='$const' ".(($_SESSION[$s_node] == $const) ? 'SELECTED' : '').">$name</option>\n";
        }
        ?>
    </select>
    <?php
    if (!$hideNodes) {echo ":";}
    echo self::nodeList(T_NODE_TOURNAMENT, 'tour_in',     array(), array(), array('all' => true, 'sel_id' => ($_SESSION[$s_node] == T_NODE_TOURNAMENT) ? $_SESSION[$s_node_id] : null, 'extra_tags' => array('style="display:none;"'),  'hide_empty' => array(T_NODE_DIVISION)));
    echo self::nodeList(T_NODE_DIVISION,   'division_in', array(), array(), array('all' => true, 'sel_id' => ($_SESSION[$s_node] == T_NODE_DIVISION)   ? $_SESSION[$s_node_id] : null, 'extra_tags' => array('style="display:none;"'),  'empty_str' => array(T_NODE_LEAGUE => '')));
    echo self::nodeList(T_NODE_LEAGUE,     'league_in',   array(), array(), array('all' => true, 'sel_id' => ($_SESSION[$s_node] == T_NODE_LEAGUE)     ? $_SESSION[$s_node_id] : null, 'extra_tags' => array('style="display:none;"'),  'empty_str' => array(T_NODE_LEAGUE => ''), 'allow_all' => true));
    if ($setState) {
        echo $lng->getTrn('common/type');
        ?>
        <select name="state_in" id="state_in">
            <?php
            echo "<option value='".T_STATE_ALLTIME."' ".(($_SESSION[$s_state] == T_STATE_ALLTIME) ? 'SELECTED' : '').">".$lng->getTrn('common/alltime')."</option>\n";
            echo "<option value='".T_STATE_ACTIVE."'  ".(($_SESSION[$s_state] == T_STATE_ACTIVE) ? 'SELECTED' : '').">".$lng->getTrn('common/active')."</option>\n";
            ?>
        </select>
        <?php
    }
    if ($setRace) {
        echo $lng->getTrn('common/race');
        ?>
        <select name="race_in" id="race_in">
            <?php
            echo "<option style='font-weight: bold;' value='".T_RACE_ALL."'>-".$lng->getTrn('common/all')."-</option>\n";
            foreach ($raceididx as $rid => $rname) {
                echo "<option value='$rid'".(($_SESSION[$s_race] == $rid) ? 'SELECTED' : '').">$rname</option>\n";
            }
            ?>
        </select>
        <?php
    }
    if ($setSGrp) {
        echo $lng->getTrn('common/sgrp');
        ?>
        <select name="sgrp_in" id="sgrp_in">
            <?php
            echo "<option value='GENERAL'>".$lng->getTrn('common/general')."</option>\n";
            foreach (($settings['hide_ES_extensions']) ? array() : getESGroups(false) as $f) {
                echo "<option value='$f'".(($_SESSION[$s_sgrp] == $f) ? 'SELECTED' : '').">$f</option>\n";
            }
            ?>
        </select>
        <?php
    }
    if ($setFFilter) {
        echo $lng->getTrn('common/having');
        $FFilterFields = self::_getDefFields($obj, $_SESSION[$s_node], $_SESSION[$s_node_id]);
        if (!in_array($_SESSION[$s_ffilter_field], array_keys($FFilterFields))) {
            $_SESSION[$s_ffilter_field] = $def_ffilter_field;
            $_SESSION[$s_ffilter_ineq]  = $def_ffilter_ineq;
            $_SESSION[$s_ffilter_limit] = $def_ffilter_limit;
        }
        ?>
        <select name="ffilter_field_in" id="ffilter_field_in">
            <?php
            foreach ($FFilterFields as $f => $desc) {
                echo "<option value='$f'".(($_SESSION[$s_ffilter_field] == $f) ? 'SELECTED' : '').">$desc[desc]</option>\n";
            }
            ?>
        </select>
        <select name="ffilter_ineq_in" id="ffilter_ineq_in">
            <option value="<?php echo self::T_NS__ffilter_ineq_gt;?>" <?php echo ($_SESSION[$s_ffilter_ineq] == self::T_NS__ffilter_ineq_gt) ? 'SELECTED' : '';?>>>=</option>
            <option value="<?php echo self::T_NS__ffilter_ineq_lt;?>" <?php echo ($_SESSION[$s_ffilter_ineq] == self::T_NS__ffilter_ineq_lt) ? 'SELECTED' : '';?>><=</option>
        </select>
        <input type='text' name="ffilter_limit_in" id="ffilter_limit_in" size='2' value="<?php echo $_SESSION[$s_ffilter_limit];?>">
        <?php
    }
    ?>
    &nbsp;
    <input type="hidden" name="ANS" value="1">
    <input type="submit" name="select" value="<?php echo $lng->getTrn('common/select');?>">
    </form>
    <script language="JavaScript" type="text/javascript">
        var open;
        <?php
        echo '
            switch('.$_SESSION[$s_node].')
            {
                case '.T_NODE_TOURNAMENT.': open = "tour"; break;
                case '.T_NODE_DIVISION.':   open = "division"; break;
                case '.T_NODE_LEAGUE.':     open = "league"; break;
            }
        ';
        if (!$hideNodes) echo "document.getElementById(open+'_in').style.display = 'inline'\n";
        ?>
        function disableall()
        {
            document.getElementById('tour_in').style.display = 'none';
            document.getElementById('division_in').style.display = 'none';
            document.getElementById('league_in').style.display = 'none';
            return true;
        }
    </script>
    <?php
    $allNodes = ($_SESSION[$s_node] == T_NODE_LEAGUE && $_SESSION[$s_node_id] == T_NODE_ALL);
    return array(
        ($allNodes) ? false : $_SESSION[$s_node],
        ($allNodes) ? false : $_SESSION[$s_node_id],
        ($setState) ? $_SESSION[$s_state] : false,
        ($setRace) ? $_SESSION[$s_race] : false,
        ($setSGrp) ? $_SESSION[$s_sgrp] : false,
        ($setFFilter) ? $_SESSION[$s_ffilter_field] : false,
        ($setFFilter) ? $_SESSION[$s_ffilter_ineq]  : false,
        ($setFFilter) ? $_SESSION[$s_ffilter_limit] : false,
    );
}
public static function quoteEsc($str) {
    return str_replace("'",'&#39;',$str);
}
private static function _nodeList_filter($filters, $desc)
{
    foreach ($filters as $key => $val) {
        $not = false;
#        if ($key[0] == '!') {
#            $not = true;
#            $key = substr($key, 1);
#        }
        if (!isset($desc[$key]) || ($not) ? ($desc[$key] == $val) : ($desc[$key] != $val)) {
            return false;
        }
    }
    return true;
}
#public static function nodeList($node, $nameid, $selected_id, $style = '', $no_all = false, $filter = array(), $disCond = array())
public static function nodeList($node, $nameid, $filter = array(), $disCond = array(), $opts = array())
{
    global $coach, $lng;
    // Inputs
    $selected_id = isset($opts['sel_id']) ? $opts['sel_id'] : null;
    $extra_tags = isset($opts['extra_tags']) ? $opts['extra_tags'] : array();
    $allow_all = (isset($opts['allow_all']) && $opts['allow_all']);
    $hide_empty = isset($opts['hide_empty']) ? $opts['hide_empty'] : array();
    $empty_str = isset($opts['empty_str']) ? $opts['empty_str'] : array(); # e.g. "%name (EMPTY)" where %name will be substituted like $DISSTR
    $init_option = isset($opts['init_option']) ? $opts['init_option'] : false;
    # Default empty strings
    foreach ($empty_str as $idx => $str) {
        if (empty($str)) { # If $emprty_str = array(T_NODE_* => ''); then we convert it to the below.
            $empty_str[$idx] = strtoupper($lng->getTrn('common/empty')).' &mdash; %name';
        }
    }
    // Preprocessing
    $additionalFields = array();
    # Init filters to empty if not set
    foreach (array(T_NODE_LEAGUE,T_NODE_DIVISION,T_NODE_TOURNAMENT) as $n) {
        if (!isset($filter[$n])) $filter[$n] = array();
    }
    # "OTHER" filters may contain non-node field (ie. membership fields): ring
    $OTHER_FILTER = isset($filter['OTHER']) ? $filter['OTHER'] : array();
    unset($filter['OTHER']); # Don't pass to allowedNodeAccess() - add to $filter again later...
    foreach ($filter as $n => $filters) {
        $_filter_keys = array_keys($filters);
        # Recombine for allowedNodeAccess() => the returned fields from allowedNodeAccess() the same names as the keys from $filters.
        $additionalFields[$n] = empty($_filter_keys) ? array() : array_combine($_filter_keys,$_filter_keys); # Don't rereference the field names, make them the same.
    }
    $DISSTR = isset($disCond['DISSTR']) ? $disCond['DISSTR'] : '';
    unset($disCond['DISSTR']);
    $_filter_keys = array_keys($disCond);
    $disCond2 = empty($_filter_keys) ? array() : array_combine($_filter_keys,$_filter_keys); # Don't rereference the field names, make them the same.
    $additionalFields[$node] = array_merge($additionalFields[$node], $disCond2);
    $leagues = Coach::allowedNodeAccess(Coach::NODE_STRUCT__TREE, is_object($coach) ? $coach->coach_id : false, $additionalFields);
    # This needs only to be added to the league node filter, since it's always evaluated independant of the $node value.
    $filter[T_NODE_LEAGUE] = array_merge($filter[T_NODE_LEAGUE], $OTHER_FILTER);
#    print_r($leagues);'
    # Mark nodse to hide in their desc element
    foreach ($leagues as $lid => $divs) {
        # I.e. only the 'desc' element exists => no divs in league
        $leagues[$lid]['desc']['_empty'] = $empty = (count($divs) == 1);
        $leagues[$lid]['desc']['_hide'] = ($empty && in_array(T_NODE_LEAGUE, $hide_empty));
        foreach ($divs as $did => $tours) {
            if ($did == 'desc') continue;
            # I.e. only the 'desc' element exists => no tours in division
            $leagues[$lid][$did]['desc']['_empty'] = $empty = (count($tours) == 1);
            $leagues[$lid][$did]['desc']['_hide'] = ($empty && in_array(T_NODE_DIVISION, $hide_empty));
        }
    }
    // Done preprocessing...
    $NL = '';
    $NL .= "<select name='$nameid' id='$nameid' ".implode(' ', $extra_tags).">\n";
    if ($init_option) {
        $NL .= $init_option;
    }
    switch ($node) {
        case T_NODE_TOURNAMENT:
            foreach ($leagues as $lid => $divs) {
                if (!self::_nodeList_filter($filter[T_NODE_LEAGUE], $divs['desc']) || $divs['desc']['_hide']) continue;
                $optname = self::quoteEsc($divs['desc']['lname']);
                if ($divs['desc']['_empty'] && isset($empty_str[T_NODE_LEAGUE])) {
                    $optname = str_replace('%name', $optname, $empty_str[T_NODE_LEAGUE]);
                }
                $NL .= "<optgroup class='leagues' label='$optname'>\n";
                foreach ($divs as $did => $tours) {
                    if (!is_numeric($did)) continue; # skip "desc" entry for division
                    if (!self::_nodeList_filter($filter[T_NODE_DIVISION], $tours['desc']) || $tours['desc']['_hide']) continue;
                    $optname = self::quoteEsc($tours['desc']['dname']);
                    if ($tours['desc']['_empty'] && isset($empty_str[T_NODE_DIVISION])) {
                        $optname = str_replace('%name', $optname, $empty_str[T_NODE_DIVISION]);
                    }
                    $NL .= "<optgroup class='divisions' style='padding-left: 1em;' label='$optname'>\n";
                    foreach ($tours as $trid => $desc) {
                        if (!is_numeric($trid)) continue; # skip "desc" entry for division
                        if (!self::_nodeList_filter($filter[T_NODE_TOURNAMENT], $desc['desc'])) continue;
                        $dis = false;
                        foreach ($disCond as $key => $val) {
                            if ($desc['desc'][$key] == $val) {$dis = true; break;};
                        }
                        $name = self::quoteEsc($desc['desc']['tname']);
                        $NL .= "<option ".(($dis) ? 'DISABLED' : '')." style='background-color: white; margin-left: -1em;' value='$trid' ".(($selected_id == $trid) ? 'SELECTED' : '').">".(($dis) ? str_replace('%name', $name, $DISSTR) : $name)."</option>\n";
                    }
                    $NL .= "</optgroup>\n";
                }
                $NL .= "</optgroup>\n";
            }
            break;
        case T_NODE_DIVISION:
            foreach ($leagues as $lid => $divs) {
                if (!self::_nodeList_filter($filter[T_NODE_LEAGUE], $divs['desc']) || $divs['desc']['_hide']) continue;
                $optname = self::quoteEsc($divs['desc']['lname']);
                if ($divs['desc']['_empty'] && isset($empty_str[T_NODE_LEAGUE])) {
                    $optname = str_replace('%name', $optname, $empty_str[T_NODE_LEAGUE]);
                }
                $NL .= "<optgroup class='leagues' label='$optname'>\n";
                foreach ($divs as $did => $tours) {
                    if (!is_numeric($did)) continue; # skip "desc" entry for division
                    if (!self::_nodeList_filter($filter[T_NODE_DIVISION], $tours['desc'])) continue;
                    $dis = false;
                    foreach ($disCond as $key => $val) {
                        if ($tours['desc'][$key] == $val) {$dis = true; break;};
                    }
                    $name = self::quoteEsc($tours['desc']['dname']);
                    if ($dis) {
                        $name = str_replace('%name', $name, $DISSTR);
                    }
                    if ($tours['desc']['_empty'] && isset($empty_str[T_NODE_DIVISION])) {
                        $name = str_replace('%name', $name, $empty_str[T_NODE_DIVISION]);
                    }
                    $NL .= "<option ".(($dis) ? 'DISABLED' : '')." style='background-color: white;' value='$did' ".(($selected_id == $did) ? 'SELECTED' : '').">$name</option>\n";
                }
                $NL .= "</optgroup>\n";
            }
            break;
        case T_NODE_LEAGUE:
            if ($allow_all) {
                $NL .= "<option style='font-weight: bold;' value='".T_NODE_ALL."'>-".$lng->getTrn('common/all')."-</option>\n";
            }
            foreach ($leagues as $lid => $divs) {
                if (!self::_nodeList_filter($filter[T_NODE_LEAGUE], $divs['desc'])) continue;
                $dis = false;
                foreach ($disCond as $key => $val) {
                    if ($divs['desc'][$key] == $val) {$dis = true; break;};
                }
                $name = self::quoteEsc($divs['desc']['lname']);
                if ($dis) {
                    $name = str_replace('%name', $name, $DISSTR);
                }
                if ($divs['desc']['_empty'] && isset($empty_str[T_NODE_LEAGUE])) {
                    $name = str_replace('%name', $name, $empty_str[T_NODE_LEAGUE]);
                }
                $NL .= "<option ".(($dis) ? 'DISABLED' : '')." value='$lid' ".(($selected_id == $lid) ? 'SELECTED' : '').">$name</option>\n";
            }
            break;
    }
    $NL .= "</select>\n";
    return $NL;
}
public static function frame_begin($menu = true)
{
    global $settings;
    
    Module::runTriggers(T_TRIGGER_BEFORE_PAGE_RENDER);
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        <title><?php echo $settings['site_name']; ?></title>
        <link type="text/css" href="css/stylesheet_default.css" rel="stylesheet">
        <link type="text/css" href="css/stylesheet<?php echo $settings['stylesheet']; ?>.css" rel="stylesheet">
        <link type="text/css" href="css/league_override_<?php echo self::getSelectedNodeLidOrDefault(); ?>.css" rel="stylesheet">
        <link rel="alternate" type="application/rss+xml" title="RSS Feed"href="rss.xml">
        <script type="text/javascript" src="lib/misc_functions.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/jquery-ui.min.js"></script>
        <script type="text/javascript" src="js/lib/underscore-1.8.3.min.js"></script>
        <script type="text/javascript" src="js/lib/knockout-3.3.0.min.js"></script>
        <script type="text/javascript" src="lib/jquery.autocomplete-min.js"></script>
        <script type="text/javascript" src="lib/jquery.expander.js"></script>
        <script src="//cdn.tinymce.com/4/tinymce.min.js"></script>
        <script>tinymce.init({ 
            selector:'.html_edit',
            menubar: false,
            width: 860,
            height: 150
        });</script>
        
        <script type="text/javascript" src="js/app/ViewModel/Common/RegistrationViewModel.js"></script>
        <script type="text/javascript" src="js/app/ViewModel/Common/PageViewModel.js"></script>
        <script type="text/javascript" src="js/app/CustomBinders/EditableCustomBinder.js"></script>
        
        <script type="text/javascript">
            $(document).ready(function() {
                var leaguesJson = <?php echo json_encode(League::getLeaguesWithLocation()); ?>;
                ko.applyBindings(new PageViewModel(leaguesJson));
            });
        </script>
    </head>
    <body>
        <div class="everything">
            <div class="banner">
            <f0> </f0><br><br><br>
            <f1><?php echo $settings['banner_title']; ?></f1><br>
            <f2><?php echo $settings['banner_subtitle']; ?></f2></div>
            <div class="menu">
                <?php if ($menu) {HTMLOUT::make_menu();} ?>
            </div> <!-- Menu div end -->
            <div class="section"> <!-- This container holds the section specific content -->
    <?php
    
}public static function mobile_frame_begin($menu = true)
{
    global $settings;
    
    Module::runTriggers(T_TRIGGER_BEFORE_PAGE_RENDER);
    ?>
    <!DOCTYPE HTML>
    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        <title><?php echo $settings['site_name']; ?></title>
        <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.24/themes/smoothness/jquery-ui.css">
        <link type="text/css" href="css/stylesheet_default.css" rel="stylesheet">
        <link type="text/css" href="css/stylesheet<?php echo $settings['stylesheet']; ?>.css" rel="stylesheet">
        <link type="text/css" href="css/league_override_<?php echo self::getSelectedNodeLidOrDefault(); ?>.css" rel="stylesheet">
        <script type="text/javascript" src="lib/misc_functions.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
        <script type="text/javascript" src="js/lib/underscore-1.8.3.min.js"></script>
        <script type="text/javascript" src="js/lib/knockout-3.3.0.min.js"></script>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.24/jquery-ui.min.js"></script>
        <script type="text/javascript" src="lib/jquery.autocomplete-min.js"></script>

        <script type="text/javascript" src="js/app/Model/Match.js"></script>
        <script type="text/javascript" src="js/app/ViewModel/Mobile/MobileViewModel.js"></script>
        <script type="text/javascript" src="js/app/ViewModel/Mobile/TeamViewModel.js"></script>
        <script type="text/javascript" src="js/app/ViewModel/Mobile/PlayerViewModel.js"></script>
        <script type="text/javascript" src="js/app/ViewModel/Mobile/PlayerDialogViewModel.js"></script>
        <script type="text/javascript" src="js/app/ViewModel/Mobile/MatchDialogViewModel.js"></script>
        <script type="text/javascript" src="js/app/ViewModel/Mobile/SelectedPlayerViewModel.js"></script>
        <script type="text/javascript" src="js/app/ViewModel/Common/RegistrationViewModel.js"></script>
        
    </head>
    <body>
        <div class="everything">
            <div class="section"> <!-- This container holds the section specific content -->
    <?php
}
public static function frame_end()
{
    ?>
                <!-- Pseudo container to force parent container to have the correct height for (potential) floating children -->
                <div style="clear: both;"></div>
            </div> <!-- End of section div -->
        </div> <!-- End of everything div -->
    </body>
    </html>
    <?php
    return true;
}

private static function make_menu()
{
    global $lng, $coach, $settings, $rules, $admin_menu;
    ?>
    <ul class="css3menu1 topmenu">
        <li class="topfirst"><a href="index.php?section=main"><?php echo $lng->getTrn('menu/home');?></a>
		 <ul>
            <?php 
            if(Settings::getValueOrDefault('show-regional-menu', false)) { 
                foreach(League::getLeaguesByLocation() as $locationName => $leagues) {
                    echo '<li><a href="#">' . $locationName . ' ></a><ul>';
                    
                    foreach($leagues as $league) {
                        echo '<li><a href="index.php?SLS_lid=' . $league->lid . '">' . $league->name . '</a></li>';
                    }
                    
                    echo '</ul></li>';
                }
                if (isset($_SESSION['logged_in'])) {
                    echo '<li><a href="index.php?section=requestleague">Request a League</a></li>';
                } 
                echo '<li><a href="http://www.thenaf.net/leagues/leagues-locator/" >TheNAF.net League Locator</a></li>';
                echo '<li><a href="index.php?SLS_lid=1" >League Hosting Home</a></li>';
            } ?>
            <li><a href="index.php?section=about">About OBBLM</a></li>
		</ul>
    </li>
<?php
        if (isset($_SESSION['logged_in'])) {
?>

<li class="topfirst"><a href="#">User Menu</a>
        <ul>
            <li class="subfirst"><a href="handler.php?type=teamcreator">Create a New Team</a></li>
            <li><a href="index.php?section=matches&amp;type=usersched" >Schedule Match</a></li>
             <li><a rel="nofollow" href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_COACH,$coach->coach_id,false,false).'&amp;subsec=teams';?>"><?php echo $lng->getTrn('cc/coach_teams');?></a></li>
            <li><a rel="nofollow" href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_COACH,$coach->coach_id,false,false).'&amp;subsec=profile';?>"><?php echo $lng->getTrn('cc/profile');?></a></li>
            <li><a rel="nofollow" href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_COACH,$coach->coach_id,false,false).'&amp;subsec=stats';?>"><?php echo $lng->getTrn('common/stats');?></a></li>
           <li><a rel="nofollow" href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_COACH,$coach->coach_id,false,false).'&amp;subsec=recentmatches';?>"><?php echo $lng->getTrn('common/recentmatches');?></a></li>
            <li><a rel="nofollow"href="index.php?logout=1"><?php echo $lng->getTrn('menu/logout');?></a></li>
            </ul><?php 
}        else      { 
?><li class="topfirst"><a rel="nofollow" href="index.php?section=login" style="height:20px;line-height:20px;"><?php echo $lng->getTrn('menu/login');?></a></li><?php }
?>

<?php if (!empty($admin_menu)) { ?>
    <li class="topmenu">
        <a href="#"><?php echo $lng->getTrn('menu/admin_menu/name');?></a>
        <ul>
            <li class="subfirst">
                <a href="handler.php?type=leaguepref"><?php echo $lng->getTrn('name', 'LeaguePref');?></a>
            </li>
            <?php 
                if (Module::isRegistered('Conference'))
                    echo '<li><a href="handler.php?type=conference">' . $lng->getTrn('name', 'Conference') . '</a></li>';
                
                if (Module::isRegistered('Scheduler'))
                    echo '<li><a href="handler.php?type=scheduler">' . $lng->getTrn('menu/admin_menu/schedule') . '</a></li>';

                foreach ($admin_menu as $lnk => $desc) {
                    if (!is_array($desc)) {
                        echo "<li><a href='index.php?section=admin&amp;subsec=$lnk'>$desc</a></li>\n";
                    }
                    else { 
                        echo '<li><a href="#">' . $desc['title'] . '<ul>';
                        foreach ($desc['sub'] as $sub) {
                            echo "<li><a href='index.php?section=admin&amp;subsec=$lnk&amp;$sub[href]'>$sub[title]</a></li>\n";
                        }
                        echo '</ul></li>';
                    }
                }
            ?>
        </ul>
    </li>
<?php } ?>        

<li class="topmenu">
    <a href="#">League Menu</a>
    <ul>
        <li class="subfirst"><a href="index.php?section=rules"><?php echo $lng->getTrn('menu/rules');?></a></li>
        <li><a href="handler.php?type=leaguetables"><?php echo $lng->getTrn('name', 'LeagueTables');?></a></li>
        <li><a href="index.php?section=teamlist"><?php echo $lng->getTrn('menu/teams');?></a></li>
        <li><a href="index.php?section=coachlist"><?php echo $lng->getTrn('menu/coaches');?></a></li>
        <li><a href="index.php?section=matches&amp;type=recent"><?php echo $lng->getTrn('menu/matches_menu/recent');?></a></li>
        <li><a href="index.php?section=matches&amp;type=upcoming"><?php echo $lng->getTrn('menu/matches_menu/upcoming');?></a>
        <?php
            if (!empty($settings['league_url'])) {
                $leagueUrl = $settings['league_url'];
                $leagueUrl = !strpos($leagueUrl, 'http') ? 'http://' . $leagueUrl : $leagueUrl;
                
                ?>  <li><a href="<?php echo $leagueUrl;?>"><?php echo $settings['league_url_name'];?></a></li><?php
            }
        ?>
    </ul>
</li>
        
<li class="topmenu"><a rel="nofollow" href="index.php?section=rules">League History</a>
    <ul>   
        <?php if (Module::isRegistered('Gallery'))        { ?><li><a href="handler.php?type=gallery"><?php echo $lng->getTrn('name', 'Gallery');?></a></li><?php } ?>
        <?php if (Module::isRegistered('Wanted'))        { ?><li><a href="handler.php?type=wanted"><?php echo $lng->getTrn('name', 'Wanted');?></a></li><?php } ?>
        <?php if (Module::isRegistered('Prize'))        { ?><li><a href="handler.php?type=prize"><?php echo $lng->getTrn('name', 'Prize');?></a></li><?php } ?>
        <?php if (Module::isRegistered('HOF'))        { ?><li><a href="handler.php?type=hof"><?php echo $lng->getTrn('name', 'HOF');?></a></li><?php } ?>
        <?php if (Module::isRegistered('FamousTeams'))        { ?><li><a href="handler.php?type=famousteams"><?php echo $lng->getTrn('name', 'FamousTeams');?></a></li><?php } ?>
        <?php if (Module::isRegistered('Memmatches'))        { ?><li><a href="handler.php?type=memmatches"><?php echo $lng->getTrn('name', 'Memmatches');?></a></li><?php } ?>
        <li><a href="index.php?section=matches&amp;type=tours"><?php echo $lng->getTrn('menu/matches_menu/tours');?></a></li>
    </ul>
</li>
        
<li class="topmenu"><a rel="nofollow" href="#">Statistics</a>
    <ul>
        <li class="subfirst"><a rel="nofollow" href="<?php echo urlcompile(T_URL_STANDINGS,T_OBJ_TEAM,false,false,false);?>"><?php echo $lng->getTrn('menu/statistics_menu/team_stn');?></a></li>
        <li><a rel="nofollow" href="<?php echo urlcompile(T_URL_STANDINGS,T_OBJ_PLAYER,false,false,false);?>"><?php echo $lng->getTrn('menu/statistics_menu/player_stn');?></a></li>
        <li><a rel="nofollow" href="<?php echo urlcompile(T_URL_STANDINGS,T_OBJ_COACH,false,false,false);?>"><?php echo $lng->getTrn('menu/statistics_menu/coach_stn');?></a></li>
        <li><a rel="nofollow" href="<?php echo urlcompile(T_URL_STANDINGS,T_OBJ_RACE,false,false,false);?>"><?php echo $lng->getTrn('menu/statistics_menu/race_stn');?></a></li>
        <li><a rel="nofollow" href="<?php echo urlcompile(T_URL_STANDINGS,T_OBJ_STAR,false,false,false);?>"><?php echo $lng->getTrn('menu/statistics_menu/star_stn');?></a></li>
    </ul>
</li>
        
<li class="topmenu"><a rel="nofollow" href="#">Game Rules</a>
    <ul> 
        <li class="subfirst"><a rel="nofollow" href="#">CRP Rosters ></a>
            <ul><li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=0" style="height:10px;line-height:10px;">Amazon</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=1" style="height:10px;line-height:10px;">Chaos</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=2" style="height:10px;line-height:10px;">Chaos Dwarf</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=3" style="height:10px;line-height:10px;">Dark Elf</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=4" style="height:10px;line-height:10px;">Dwarf</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=5" style="height:10px;line-height:10px;">Elf</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=6" style="height:10px;line-height:10px;">Goblin</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=7" style="height:10px;line-height:10px;">Halfling</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=8" style="height:10px;line-height:10px;">High Elf</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=9" style="height:10px;line-height:10px;">Human</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=10" style="height:10px;line-height:10px;">Khemri</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=11" style="height:10px;line-height:10px;">Lizardman</a></li>
           <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=13" style="height:10px;line-height:10px;">Necromantic</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=14" style="height:10px;line-height:10px;">Norse</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=15" style="height:10px;line-height:10px;">Nurgle</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=16" style="height:10px;line-height:10px;">Ogre</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=12" style="height:10px;line-height:10px;">Orc</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=19" style="height:10px;line-height:10px;">Skaven</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=17" style="height:10px;line-height:10px;">Undead</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=18" style="height:10px;line-height:10px;">Vampire</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=20" style="height:10px;line-height:10px;">Wood Elf</a></li>
        </ul></li>
        <li class="subfirst"><a rel="nofollow" href="#">LRB6 Rosters ></a>
            <ul><li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=21" style="height:10px;line-height:10px;">Chaos Pact</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=22" style="height:10px;line-height:10px;">Slann</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=23" style="height:10px;line-height:10px;">Underworld</a></li>
        </ul></li>
        <li class="subfirst"><a rel="nofollow" href="#">(Optional) Community Rosters ></a>
            <ul><li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=24" style="height:10px;line-height:10px;">Bretonnian</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=25" style="height:10px;line-height:10px;">Khorne</a></li>
            <li><a href="index.php?section=objhandler&type=1&obj=4&obj_id=26" style="height:10px;line-height:10px;">Simyin</a></li>
        </ul></li>
        <li><a href="http://www.thenaf.net/wp-content/uploads/2013/06/CRP1.pdf">The CRP (Full Blood Bowl Rules)</a></li>
        <li><a href="http://the-outcast.com/bloodbowl/blood%20bowl%20crp%20lite.pdf">A5 Rules Summary</a></li>  
    </ul>
</li>  

<?php if (Module::isRegistered('Search'))            { ?><li><a href="handler.php?type=search"><?php echo $lng->getTrn('name', 'Search');?></a></li><?php } ?>
    
    <?php
}
// Prints an advanced sort table.
public static function sort_table($title, $lnk, array $objs, array $fields, array $std_sort, $sort = array(), $extra = array())
{
    /*
        extra fields:
            tableWidth  => CSS style width value
            dashed => array(
                'condField' => field name,                    // When an object has this field's (condField) = fieldVal, then a "-" is put in the place of all values.
                'fieldVal'  => field value,
                'noDashFields' => array('field 1', 'field 2') // ...unless the field name is one of those specified in the array 'noDashFields'.
            );
            remove => array(
                'condField' => field name,  // When an object has this field's (condField) = fieldVal, then the entry/row is not printed in the html table.
                'fieldVal'  => field value,
            );
            GETsuffix => suffix to paste into "dir" and "sort" GET strings.
            color => true/false. Boolean telling wheter or not we should look into each object for the field "HTMLfcolor" and "HTMLbcolor", and use these color codes to color the obj's row. Note: the object must contain the two previously stated fields, or else black-on-white is used as default.
            doNr => true/false. Boolean telling wheter or not to print the "Nr." column.
            limit => int. Stop printing rows when this row number is reached.
            anchor => string. Will create table sorting links, that include this identifier as an anchor.
            noHelp => true/false. Will enable/disable help link [?].
            noSRdisp => true/false. Will force not to show the table sort rule used/parsed.
            
            page => current page being viewed
            pages => total number of pages
    */
    global $settings, $lng, $coach;
    if (array_key_exists('remove', $extra)) {
        $objs = array_filter($objs, create_function('$obj', 'return ($obj->'.$extra['remove']['condField'].' != '.$extra['remove']['fieldVal'].');'));
    }
    $MASTER_SORT = array_merge($sort, $std_sort);
    if (!empty($MASTER_SORT)) {
        objsort($objs, $MASTER_SORT);
    }
    $no_print_fields = array();
    $DONR = (!array_key_exists('doNr', $extra) || $extra['doNr']) ? true : false;
    $LIMIT = (array_key_exists('limit', $extra)) ? $extra['limit'] : -1;
    $ANCHOR = (array_key_exists('anchor', $extra)) ? $extra['anchor'] : false;
    $NOSRDISP = (array_key_exists('noSRdisp', $extra)) ? $extra['noSRdisp'] : false;
    $GETSUFX = (array_key_exists('GETsuffix', $extra)) ? $extra['GETsuffix'] : '';
    $PAGES = (array_key_exists('pages', $extra)) ? $extra['pages'] : false;
    $PAGE  = (array_key_exists('page', $extra))  ? $extra['page']  : 1;
    $PAGELENGTH = (array_key_exists('pagelength', $extra))  ? $extra['pagelength'] : 0;
    if ($DONR) {
        $fields = array_merge(array('nr' => array('desc' => '#')), $fields);
        array_push($no_print_fields, 'nr');
    }
    $CP = count($fields);
    ?>
    <table class="common" <?php echo (array_key_exists('tableWidth', $extra)) ? "style='width: $extra[tableWidth];'" : '';?>>
        <tr class="commonhead">
            <td colspan="<?php echo $CP;?>"><b>
            <?php echo $title;?>&nbsp;
            <?php
            if (!array_key_exists('noHelp', $extra) || !$extra['noHelp']) {
                ?><a TARGET="_blank" href="html/table_desc.html">[?]</a><?php
            }
            ?>
            </b></td>
        </tr>
        <tr>
            <?php
            foreach ($fields as $f => $attr)
                echo "<td><i>$attr[desc]</i></td>";
            ?>
        </tr>
        <tr>
        <?php
        foreach ($fields as $f => $attr) {
            if (in_array($f, $no_print_fields) || (array_key_exists('nosort', $attr) && $attr['nosort'])) {
                echo "<td></td>";
                continue;
            }
            $sort = 'sort'.$GETSUFX;
            $dir = 'dir'.$GETSUFX;
            $anc = '';
            if ($ANCHOR) {
                $anc = "#$ANCHOR";
            }
            echo "<td><b><a href='$lnk&amp;page=1&amp;$sort=$f&amp;$dir=a$anc' title='Sort ascending'>+</a>/<a href='$lnk&amp;page=1&amp;$sort=$f&amp;$dir=d$anc' title='Sort descending'>-</a></b></td>";
        }
        ?>
        </tr>
        <tr><td colspan="<?php echo $CP;?>"><hr></td></tr>
        <?php
        $i = 1 + (($PAGE && $PAGELENGTH) ? ($PAGE-1)*$PAGELENGTH : 0);
        foreach ($objs as $o) {
            $DASH = (array_key_exists('dashed', $extra) && $o->{$extra['dashed']['condField']} == $extra['dashed']['fieldVal']) ? true : false;
            if (array_key_exists('color', $extra)) {
                $td = "<td style='background-color: ".(isset($o->HTMLbcolor) ? $o->HTMLbcolor : 'white')."; color: ".(isset($o->HTMLfcolor) ? $o->HTMLfcolor : 'black').";'>";
            }
            else {
                $td = '<td>';
            }
            echo "<tr>";
            if ($DONR) {
                echo $td.$i."</td>";
            }
            foreach ($fields as $f => $a) { // Field => attributes
                if (!in_array($f, $no_print_fields)) {
                    if ($DASH && !in_array($f, $extra['dashed']['noDashFields'])) {
                        echo $td."-</td>";
                        continue;
                    }
                    $cpy = $o->$f; // Don't change the objects themselves! Make copies!
                    if (array_key_exists('kilo', $a) && $a['kilo'])
                        $cpy /= 1000;
                        $cpy = (string) $cpy;
                    if (is_numeric($cpy) && !ctype_digit(($cpy[0] == '-') ? substr($cpy,1) : $cpy))
                        $cpy = sprintf("%1.2f", $cpy);
                    if (array_key_exists('suffix', $a) && $a['suffix'])
                        $cpy .= $a['suffix'];
                    if (array_key_exists('color', $a) && $a['color'])
                        $cpy = "<font color='$a[color]'>".$cpy."</font>";
                    if (array_key_exists('href', $a) && $a['href']) {
                        $href = (isset($o->href)) ? $o->href : $a['href'];
                        $cpy  = "<a " 
                            . "href='$href[link]" . ((isset($href['field'])) ? "&amp;$href[field]=".$o->{$href['value']} : '') . "'"
                            . "class='" . ((array_key_exists('icon', $a) && $a['icon']) ? "icon-link" : "") . "'"
                            . ">"
                            . $cpy
                            . "</a>";
                    }
                    if (array_key_exists('editable', $a) && $a['editable']) {
                        if($a['allowEdit']) {
                            $args = "";
                            if(array_key_exists('javaScriptArgs', $a) && $a['javaScriptArgs']) {
                                $args = '';
                                $prefix = '';
                                foreach($a['javaScriptArgs'] as $propertyKey) {
                                    $args .= ($prefix . $o->$propertyKey);
                                    $prefix = ',';
                                }
                            }
                            if(!isset($a['editableClass']))
                                $a['editableClass'] = '';
                            
                            $cpy = '<div data-bind="editable: {update: ' . $a['editable'] . ', args: [' . $args . '], cssClass: \'' . $a['editableClass'] . '\'}">'
                                . $cpy
                                . '</div>';
                        }
                    }
                    if (isset($o->{"${f}_color"})) {
                        echo "<td style='background-color: ".$o->{"${f}_color"}."; color: black;'>".$cpy."</td>";
                    }
                    else {
                        echo $td.$cpy."</td>";
                    }
                }
            }
            echo "</tr>\n";
            if ($i++ == $LIMIT) {
                break;
            }
        }
        if (!$NOSRDISP) {
        ?>
        <tr>
            <td colspan="<?php echo $CP;?>">
            <hr>
            </td>
        </tr>
        <tr>
            <td align="right" colspan="<?php echo $CP;?>">
            <?php
            if ($PAGES) {
            ?>
            <div style='float:left;'><?php 
                echo $lng->getTrn('common/page')."&nbsp;";
                $primary_sort = isset($_GET["sort$GETSUFX"])
                    ? "&amp;sort$GETSUFX=".$_GET["sort$GETSUFX"]."&amp;dir$GETSUFX=".$_GET["dir$GETSUFX"]
                    : '';
                $pageslist = array_strpack('<a href=\"'.$lnk.'&amp;page=%s'.$primary_sort.'\">%s</a>', range(1,$PAGES));
                $pageslist[$PAGE-1] = "<b>$PAGE</b>";
                echo implode(', ', $pageslist);
            ?></div>
            <?php
            }
            ?>
            <div style='float:right;'><i><?php echo $lng->getTrn('common/sortedagainst');?>: <?php echo implode(', ', rule_dict($MASTER_SORT));?></i></div>
            </td>
        </tr>
        <?php
        }
    echo "</table>\n";
}
public static function generateEStable($obj)
{
    global $ES_fields, $lng;
    echo "<table>\n";
    echo "<tr><td><i>".$lng->getTrn('common/stat')."</i></td>
        <td><i>".$lng->getTrn('common/alltime')."</i></td>
        <td>&nbsp;<i>".$lng->getTrn('common/matchavg')."</i>&nbsp;</td>
        <td><i>".$lng->getTrn('common/desc')."</i></td></tr>\n";
            echo "<tr><td colspan='4'><hr></td></tr>\n";
    $grp = null;
    $objAVG = clone $obj;
    $objAVG->setStats(false,false,true);
    # Require that fields are already sorted against group type!!!
    foreach ($ES_fields as $ESf => $def) {
        if ($grp != $def['group']) {
            echo "<tr><td colspan='4'><br><b>$def[group]</b></td></tr>\n";
            $grp = $def['group'];
        }
        echo "<tr valign='top'><td>$ESf</td><td align='right'>".$obj->{"mv_$ESf"}."</td><td align='right'>".sprintf("%1.2f",$objAVG->{"mv_$ESf"})."</td><td style='padding-left:10px;'>".$def['desc']."</td></tr>\n";
    }
    echo "</table>";
}
private static $helpBoxIdx = 0;
public static function helpBox($body, $link = '', $style = '')
{
    $ID = 'helpBox'.(++self::$helpBoxIdx);
    if (!empty($link)) {
        echo "<a href='javascript:void(0);' onClick='slideToggle(\"$ID\");'>$link</a><br><br>";
    }
    echo "<div id='$ID' class='helpBox' style='".(empty($link) ? '' : 'display:none').";$style'>".$body.'</div>';
    return $ID;
}
# DELETE THIS, IT'S NO LONGER USED (was used on schedule page)!
private static $assistantBoxIdx = 0;
public static function assistantBox($title, $body, $style = '')
{
    $ID = 'assistantBox'.(++self::$assistantBoxIdx);
    echo "<div class='boxCommon' style='$style' id='$ID'>\n";
    echo "<h3 class='boxTitle".T_HTMLBOX_ADMIN."'>".$title."</h3>";
    echo "<div class='boxBody'>\n$body</div>\n</div>\n";
#    echo "<div id='$ID' class='assistantBox' style='$style'>".$body.'</div>';
    return $ID;
}
public static function dnt()
{
    ?>
    If you enjoy this software please support the further development of it by donating.<br>
    <br>
    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
    <input type="hidden" name="cmd" value="_s-xclick">
    <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHJwYJKoZIhvcNAQcEoIIHGDCCBxQCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYDAXl4ZznrQUskTlm4uZpyxI37sonv+BFdn4QsGv7GUzMGSR3WB/+Goi/rJytZwkE/71QLowqRZUVNWo52go7XKXkt/lE1Vh5en4FnGQzT2XLmQQeoP7EPuX8zmr6TYcSQ/QxHYcHgyNYhCDFRDEUy4kYUoU8WNNAxXagT8PbBQzTELMAkGBSsOAwIaBQAwgaQGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIoGFhfGVhqbyAgYArgtT6R30i19D1LExCFC6d4XKxaewWJYJFM4eCmkCIv+eUWRXxphelweB7+uzyvgQMeZOvZgPJAF/7EqDNakMvmlqWvvUVeCQIT8WeQMPP2y5Eybh8oRQMS0PvlVkrGj4BsUfTKvw/sz9Pg4xZVZ7YEKbNR+awZVPgd5wtaKLTqqCCA4cwggODMIIC7KADAgECAgEAMA0GCSqGSIb3DQEBBQUAMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbTAeFw0wNDAyMTMxMDEzMTVaFw0zNTAyMTMxMDEzMTVaMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEAwUdO3fxEzEtcnI7ZKZL412XvZPugoni7i7D7prCe0AtaHTc97CYgm7NsAtJyxNLixmhLV8pyIEaiHXWAh8fPKW+R017+EmXrr9EaquPmsVvTywAAE1PMNOKqo2kl4Gxiz9zZqIajOm1fZGWcGS0f5JQ2kBqNbvbg2/Za+GJ/qwUCAwEAAaOB7jCB6zAdBgNVHQ4EFgQUlp98u8ZvF71ZP1LXChvsENZklGswgbsGA1UdIwSBszCBsIAUlp98u8ZvF71ZP1LXChvsENZklGuhgZSkgZEwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tggEAMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADgYEAgV86VpqAWuXvX6Oro4qJ1tYVIT5DgWpE692Ag422H7yRIr/9j/iKG4Thia/Oflx4TdL+IFJBAyPK9v6zZNZtBgPBynXb048hsP16l2vi0k5Q2JKiPDsEfBhGI+HnxLXEaUWAcVfCsQFvd2A1sxRr67ip5y2wwBelUecP3AjJ+YcxggGaMIIBlgIBATCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwCQYFKw4DAhoFAKBdMBgGCSqGSIb3DQEJAzELBgkqhkiG9w0BBwEwHAYJKoZIhvcNAQkFMQ8XDTEwMDMwMTIyMTQzMVowIwYJKoZIhvcNAQkEMRYEFN3mB1myNwGotEQV1MTNvFfRxOphMA0GCSqGSIb3DQEBAQUABIGAYnSeuLskvPZtw4HKYmhNUukMYVtZshxI1ebO9llut+PExFBdkPE7Ox0c0LfFmN+GBAntt1qE5ocKWB9WdKtjKSn3tpekXne1NUaNzq7YzQpKWornj79zhkrOEa8XjmKpV5mSN7bPaZ1AbzI1gvvXjP95lusjFCwe27npnuaSaYQ=-----END PKCS7-----
    ">
    <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
    <img alt="" border="0" src="https://www.paypal.com/da_DK/i/scr/pixel.gif" width="1" height="1">
    </form>
    <?php
}
}
?>