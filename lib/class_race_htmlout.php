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

class Race_HTMLOUT extends Race
{

public static function profile($rid) 
{
    global $lng, $DEA, $stars;
    
    $race = new Race($rid);
    $roster = $DEA[$race->name];
    title($lng->getTrn('race/'.strtolower(str_replace(' ','', $race->name))));
    ?>
    <center><img src="<?php echo RACE_ICONS.'/'.$roster['other']['icon'];?>" alt="Race icon"></center>
    <ul>
        <li><?php echo $lng->getTrn('common/reroll')?>: <?php echo $roster['other']['rr_cost']/1000;?>k</li>
    </ul><br>
    <?php

    //List available player positions for race
    $players = array();
    foreach ($roster['players'] as $player => $d) {
        $p = (object) array_merge(array('position' => $player), $d);
        $p->skills = implode(', ', skillsTrans($p->def));
        $p->N = implode('',$p->norm);
        $p->D = implode('',$p->doub);
        $p->position = $lng->getTrn("position/".strtolower(str_replace(' ','',$p->position)));
        $players[] = $p;
    }
    $fields = array(
        'qty'       => array('desc' => $lng->getTrn('common/maxqty')),
        'position'  => array('desc' => $lng->getTrn('common/pos')),
        'ma'        => array('desc' => $lng->getTrn('common/ma')),
        'st'        => array('desc' => $lng->getTrn('common/st')),
        'ag'        => array('desc' => $lng->getTrn('common/ag')),
        'av'        => array('desc' => $lng->getTrn('common/av')),
        'skills'    => array('desc' => $lng->getTrn('common/skills'), 'nosort' => true),
        'N'         => array('desc' => $lng->getTrn('common/normal'), 'nosort' => true),
        'D'         => array('desc' => $lng->getTrn('common/double'), 'nosort' => true),
        'cost'      => array('desc' => $lng->getTrn('common/price'), 'kilo' => true, 'suffix' => 'k'),
    );
    HTMLOUT::sort_table(
        $lng->getTrn('common/roster'),
        urlcompile(T_URL_PROFILE,T_OBJ_RACE,$race->race_id,false,false),
        $players,
        $fields,
        sort_rule('race_page'),
        (isset($_GET['sortpl'])) ? array((($_GET['dirpl'] == 'a') ? '+' : '-') . $_GET['sortpl']) : array(),
        array('GETsuffix' => 'pl', 'noHelp' => true, 'doNr' => false)
    );

    //List available star players for race
    $racestars = array(); 
 
    foreach ($stars as $s => $d) {  
        if (in_array($race->race_id, $d['races'])) {
            $tmp = new Star($d['id']);
            $tmp->skills = skillsTrans($tmp->skills);            
            $racestars[] = $tmp;
        }
    }
        
    $fields = array(
		'name'   => array('desc' => $lng->getTrn('common/star'), 'href' => array('link' => urlcompile(T_URL_PROFILE,T_OBJ_STAR,false,false,false), 'field' => 'obj_id', 'value' => 'star_id')),
        'ma'     => array('desc' => $lng->getTrn('common/ma')),
        'st'     => array('desc' => $lng->getTrn('common/st')),
        'ag'     => array('desc' => $lng->getTrn('common/ag')),
        'av'     => array('desc' => $lng->getTrn('common/av')),
        'skills' => array('desc' => $lng->getTrn('common/skills'), 'nosort' => true),
        'cost'   => array('desc' => $lng->getTrn('common/price'), 'kilo' => true, 'suffix' => 'k'),
    );
        
    HTMLOUT::sort_table(
        $lng->getTrn('common/availablestars'),
        urlcompile(T_URL_PROFILE,T_OBJ_RACE,$race->race_id,false,false),
        $racestars,
        $fields,
        sort_rule('star'),
        (isset($_GET['sort'])) ? array((($_GET['dir'] == 'a') ? '+' : '-') . $_GET['sort']) : array(),
        array('anchor' => 's2', 'doNr' => false, 'noHelp' => true)
    );

    // Teams of the chosen race.
    $url = urlcompile(T_URL_PROFILE,T_OBJ_RACE,$race->race_id,false,false);
    HTMLOUT::standings(STATS_TEAM,false,false,array('url' => $url, 'teams_from' => STATS_RACE, 'teams_from_id' => $race->race_id));
    echo '<br>';
    HTMLOUT::recentGames(STATS_RACE, $race->race_id, false, false, false, false, array('url' => $url, 'n' => MAX_RECENT_GAMES, 'GET_SS' => 'gp'));
}

public static function standings()
{  
    global $lng;
    title($lng->getTrn('menu/statistics_menu/race_stn'));
    HTMLOUT::standings(STATS_RACE,false,false,array('url' => urlcompile(T_URL_STANDINGS,T_OBJ_RACE,false,false,false)));
}

}

