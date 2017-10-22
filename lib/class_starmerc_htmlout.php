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

class Star_HTMLOUT extends Star
{

public function profile($sid) 
{
    global $lng;
    $s = new self($sid);
    title($s->name);
    echo '<center><a href="'.urlcompile(T_URL_STANDINGS,T_OBJ_STAR,false,false,false).'">'.$lng->getTrn('common/back').'</a></center><br><br>';
    echo "<b>".$lng->getTrn('common/skills').":</b> ".skillsTrans($s->skills)."<br><br>";
    echo "<b>".$lng->getTrn('common/races').":</b> ".racesTrans($s->races)."<br><br>";
    self::starHireHistory(false, false, false, false, $s->star_id, array('url' => urlcompile(T_URL_PROFILE,T_OBJ_STAR, $s->star_id,false,false)));
}

public static function standings()
{
    global $lng;
    // All stars
    title($lng->getTrn('menu/statistics_menu/star_stn'));
    echo $lng->getTrn('common/notice_spp')."<br><br>\n";
    HTMLOUT::standings(STATS_STAR, false, false, array('url' => urlcompile(T_URL_STANDINGS,T_OBJ_STAR,false,false,false),));
}

public static function starHireHistory($obj, $obj_id, $node, $node_id, $star_id = false, $opts = array())
{
    global $lng;
    
    /* 
        If $star_id is false, then the HH from all stars of $obj = $obj_id will be displayed, instead of only the HH of star = $star_id 
    */

    if (!array_key_exists('GET_SS', $opts)) {$opts['GET_SS'] = '';}
    else {$extra['GETsuffix'] = $opts['GET_SS'];} # GET Sorting Suffix
    $extra['doNr'] = false;
    $extra['noHelp'] = true;
    if ($ANC = array_key_exists('anchor', $opts)) {$extra['anchor'] = $opts['anchor'];}

    $mdat = array();

    foreach ((($star_id) ? array(new Star($star_id)) : Star::getStars($obj, $obj_id, $node, $node_id)) as $s) {
        foreach ($s->getHireHistory($obj, $obj_id, $node, $node_id) as $m) {
            $o = (object) array();
            foreach (array('match_id', 'date_played', 'hiredBy', 'hiredAgainst', 'hiredByName', 'hiredAgainstName') as $k) {
                $o->$k = $m->$k;
            }
            foreach ($s->getStats(T_NODE_MATCH,$m->match_id) as $k => $v) {
                $o->$k = $v;
            }
            $o->match = $lng->getTrn('common/view');
            $o->tour = get_alt_col('tours', 'tour_id', $m->f_tour_id, 'name');
            $o->score = "$m->team1_score - $m->team2_score";
            $o->result = matchresult_icon(
                (
                ($m->team1_id == $m->hiredBy && $m->team1_score > $m->team2_score) ||
                ($m->team2_id == $m->hiredBy && $m->team1_score < $m->team2_score)
                )
                    ? 'W'
                    : (($m->team1_score == $m->team2_score) ? 'D' : 'L')
            );
            $o->star_id = $s->star_id;
            $o->name = $s->name;
            array_push($mdat, $o);
        }
    }
    $fields = array(
        'date_played'       => array('desc' => $lng->getTrn('common/dateplayed')),
        'name'              => array('desc' => $lng->getTrn('common/star'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_STAR,false,false,false), 'field' => 'obj_id', 'value' => 'star_id')),
        'tour'              => array('desc' => $lng->getTrn('common/tournament')),
        'hiredByName'       => array('desc' => $lng->getTrn('profile/star/hiredby'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'hiredBy')),
        'hiredAgainstName'  => array('desc' => $lng->getTrn('common/opponent'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false), 'field' => 'obj_id', 'value' => 'hiredAgainst')),
        'cp'     => array('desc' => 'Cp'),
        'td'     => array('desc' => 'Td'),
        'intcpt' => array('desc' => 'Int'),
        'cas'    => array('desc' => 'Cas'),
        'bh'     => array('desc' => 'BH'),
        'si'     => array('desc' => 'Si'),
        'ki'     => array('desc' => 'Ki'),
        'mvp'    => array('desc' => 'MVP'),
        'score'  => array('desc' => $lng->getTrn('common/score'), 'nosort' => true),
        'result' => array('desc' => $lng->getTrn('common/result'), 'nosort' => true),
        'match'  => array('desc' => $lng->getTrn('common/match'), 'href' => array('link' => 'index.php?section=matches&amp;type=report', 'field' => 'mid', 'value' => 'match_id'), 'nosort' => true),
    );
    if ($star_id) {unset($fields['name']);}
    if ($obj && $obj_id) {unset($fields['hiredByName']);}
    $title = $lng->getTrn('common/starhh');
    if ($ANC) {$title = "<a name='$opts[anchor]'>".$title.'<a>';}
    HTMLOUT::sort_table(
        $title,
        $opts['url'],
        $mdat,
        $fields,
        sort_rule('star_HH'),
        (isset($_GET["sort$opts[GET_SS]"])) ? array((($_GET["dir$opts[GET_SS]"] == 'a') ? '+' : '-') . $_GET["sort$opts[GET_SS]"]) : array(),
        $extra
    );
}

}

