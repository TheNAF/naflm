<?php

/*
 *  Copyright (c) Daniel Straalman <email is protected> 2008-2012. All Rights Reserved.
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

require('lib/game_data.php'); # LRB5

/*
 * Game data replacement for LRB6 experimental rules (LRB5b).
 */

//
// Changes to present teams/positionals in LRB5b.
//

$DEA['Dwarf']['players']['Deathroller']['def'] = array (99, 50, 3, 53, 54, 100, 105, 57);
$DEA['Dwarf']['other']['rr_cost'] = 50000;
$DEA['Goblin']['players']['Bombardier']['def'] = array (93, 23, 105, 108);
$DEA['Goblin']['players']['Looney']['def'] = array (95, 105, 108);
$DEA['Goblin']['players']['Pogoer']['def'] = array (23, 25, 108, 79);
$DEA['Goblin']['players']['Pogoer']['cost'] = 70000;
$DEA['Halfling']['players']['Treeman']['def'] = array (54, 57, 58, 109, 59, 110);
$DEA['Khemri']['players']['Skeleton']['cost'] = 40000;
$DEA['Khemri']['players']['Skeleton']['def'] = array (103, 59);
$DEA['Khemri']['players']['Tomb Guardian'] = $DEA['Khemri']['players']['Mummy'];
unset($DEA['Khemri']['players']['Mummy']);
$DEA['Khemri']['players']['Tomb Guardian']['cost'] = 100000;
$DEA['Khemri']['players']['Tomb Guardian']['ma'] = 4;
$DEA['Khemri']['players']['Tomb Guardian']['def'] = array (96, 103);
$DEA['Necromantic']['players']['Flesh Golem']['cost'] = 100000;
$DEA['Skaven']['players']['Rat Ogre']['cost'] = 150000;
$DEA['Undead']['players']['Skeleton']['cost'] = 40000;
$DEA['Undead']['players']['Skeleton']['def'] = array (103, 59);
$DEA['Undead']['players']['Mummy']['cost'] = 120000;
$DEA['Wood Elf']['players']['Catcher']['ma'] = 8;
$DEA['Wood Elf']['players']['Catcher']['def'] = array (20, 23, 28);

//
// One new skill in LRB5b - Animosity
// Only used in the three new teams
//

$skillarray['E'][113] = $skillididx[113] = 'Animosity';

//
// Three new teams in LRB5b.
//

define('T_RACE_CHAOS_PACT', 21);
define('T_RACE_SLANN', 22);
define('T_RACE_UNDERWORLD', 23);

$DEA['Chaos Pact'] = array (
    'other'	=> array (
        'rr_cost' => 70000,
        'icon' => 'chaos.png',
        'race_id' => T_RACE_CHAOS_PACT, # (Chaos Pact)
    ),
    'players'	=> array (
  			'Marauder'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 8,
    				'def'	    => array (),
    				'norm'		=> array ('G', 'S', 'P', 'M'),
    				'doub'		=> array ('A'),
    				'qty'			  => 12,
    				'cost'			  => 50000,
    				'icon'			  => 'marauder1',
    			    'pos_id'          => 210,
  			),
  			'Goblin Renegade'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 2,
    				'ag'        	=> 3,
    				'av'        	=> 7,
    				'def'	    => array (113, 23, 104, 108),
    				'norm'		=> array ('A', 'M'),
    				'doub'		=> array ('G', 'S', 'P'),
    				'qty'			  => 1,
    				'cost'			  => 40000,
    				'icon'			  => 'gobrenegade1',
    			    'pos_id'          => 211,
  			),
  			'Skaven Renegade'	=> array (
    				'ma'        	=> 7,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 7,
    				'def'	    => array (113),
    				'norm'		=> array ('G', 'M'),
    				'doub'		=> array ('A', 'S', 'P'),
    				'qty'			  => 1,
    				'cost'			  => 50000,
    				'icon'			  => 'skavenrenegade1',
    			    'pos_id'          => 212,
  			),
  			'Dark Elf Renegade'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 3,
    				'ag'        	=> 4,
    				'av'        	=> 8,
    				'def'	    => array (113),
    				'norm'		=> array ('G', 'A', 'M'),
    				'doub'		=> array ('S', 'P'),
    				'qty'			  => 1,
    				'cost'			  => 70000,
    				'icon'			  => 'delfrenegade1',
    			    'pos_id'          => 213,
  			),
			'Chaos Troll'	=> array (
    				'ma'        	=> 4,
    				'st'        	=> 5,
    				'ag'        	=> 1,
    				'av'        	=> 9,
    				'def'	    => array (99, 90, 54, 102, 103, 110),
    				'norm'		=> array ('S'),
    				'doub'		=> array ('G', 'A', 'M', 'P'),
    				'qty'			  => 1,
    				'cost'			  => 110000,
    				'icon'			  => 'chaostroll1',
    			    'pos_id'          => 214,
  			),
  			'Chaos Ogre'	=> array (
    				'ma'        	=> 5,
    				'st'        	=> 5,
    				'ag'        	=> 2,
    				'av'        	=> 9,
    				'def'	    => array (99, 94, 54, 59, 110),
    				'norm'		=> array ('S'),
    				'doub'		=> array ('G', 'A', 'M', 'P'),
    				'qty'			  => 1,
    				'cost'			  => 140000,
    				'icon'			  => 'pact_ogre1',
    			    'pos_id'          => 215,
  			),
  			'Minotaur'	=> array (
    				'ma'        	=> 5,
    				'st'        	=> 5,
    				'ag'        	=> 2,
    				'av'        	=> 8,
    				'def'	    => array (99, 5, 75, 54, 59, 112),
    				'norm'		=> array ('S'),
    				'doub'		=> array ('G', 'A', 'M', 'P'),
    				'qty'			  => 1,
    				'cost'			  => 150000,
    				'icon'			  => 'minotaur1',
    			    'pos_id'          => 216,
  			)
    )
);

$DEA['Slann'] = array (
    'other'	=> array (
        'rr_cost' => 50000,
        'icon' => 'slann.png',
        'race_id' => T_RACE_SLANN, # (Slann)
    ),
    'players'	=> array (
  			'Lineman'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 8,
    				'def'	    => array (25, 79),
    				'norm'		=> array ('G'),
    				'doub'		=> array ('A', 'S', 'P'),
    				'qty'			  => 16,
    				'cost'			  => 60000,
    				'icon'			  => 'slannlino1',
    			    'pos_id'          => 220,
  			),
  			'Catcher'	=> array (
    				'ma'        	=> 7,
    				'st'        	=> 2,
    				'ag'        	=> 4,
    				'av'        	=> 7,
    				'def'	    => array (21, 25, 79),
    				'norm'		=> array ('G', 'A'),
    				'doub'		=> array ('S', 'P'),
    				'qty'			  => 4,
    				'cost'			  => 80000,
    				'icon'			  => 'slanncatcher1',
    			    'pos_id'          => 221,
  			),
  			'Blitzer'	=> array (
    				'ma'        	=> 7,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 8,
    				'def'	    => array (22, 24, 25, 79),
    				'norm'		=> array ('G', 'A', 'S'),
    				'doub'		=> array ('P'),
    				'qty'			  => 4,
    				'cost'			  => 110000,
    				'icon'			  => 'slannblitzer1',
    			    'pos_id'          => 222,
  			),
  			'Kroxigor'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 5,
    				'ag'        	=> 1,
    				'av'        	=> 9,
    				'def'	    => array (99, 94, 54, 76, 59),
    				'norm'		=> array ('S'),
    				'doub'		=> array ('G', 'A', 'P'),
    				'qty'			  => 1,
    				'cost'			  => 140000,
    				'icon'			  => 'slannkrox1',
    			    'pos_id'          => 223,
  			)
    )
);

$DEA['Underworld'] = array (
    'other'	=> array (
        'rr_cost' => 70000,
        'icon' => 'underworld.png',
        'race_id' => T_RACE_UNDERWORLD, # (Underworld)
    ),
    'players'	=> array (
  			'Underworld Goblin'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 2,
    				'ag'        	=> 3,
    				'av'        	=> 7,
    				'def'	    => array (104, 23, 108),
    				'norm'		=> array ('A', 'M'),
    				'doub'		=> array ('G', 'S', 'P'),
    				'qty'			  => 12,
    				'cost'			  => 40000,
    				'icon'			  => 'uwgoblin1',
    			    'pos_id'          => 230,
  			),
  			'Skaven Lineman'	=> array (
    				'ma'        	=> 7,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 7,
    				'def'	    => array (113),
    				'norm'		=> array ('G', 'M'),
    				'doub'		=> array ('A', 'S', 'P'),
    				'qty'			  => 2,
    				'cost'			  => 50000,
    				'icon'			  => 'sklineman1',
    			    'pos_id'          => 231,
  			),
  			'Skaven Thrower'	=> array (
    				'ma'        	=> 7,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 7,
    				'def'	    => array (113, 45, 12),
    				'norm'		=> array ('G', 'P', 'M'),
    				'doub'		=> array ('A', 'S'),
    				'qty'			  => 2,
    				'cost'			  => 70000,
    				'icon'			  => 'skthrower1',
    			    'pos_id'          => 232,
  			),
  			'Skaven Blitzer'	=> array (
    				'ma'        	=> 7,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 8,
    				'def'	    => array (113, 1),
    				'norm'		=> array ('G', 'S', 'M'),
    				'doub'		=> array ('A', 'P'),
    				'qty'			  => 2,
    				'cost'			  => 90000,
    				'icon'			  => 'skstorm1',
    			    'pos_id'          => 233,
  			),
  			'Warpstone Troll'	=> array (
    				'ma'        	=> 4,
    				'st'        	=> 5,
    				'ag'        	=> 1,
    				'av'        	=> 9,
    				'def'	    => array (99, 90, 54, 102, 103, 110),
    				'norm'		=> array ('S', 'M'),
    				'doub'		=> array ('G', 'A', 'P'),
    				'qty'			  => 1,
    				'cost'			  => 110000,
    				'icon'			  => 'warptroll1',
    			    'pos_id'          => 234,
  			)
    )
);

//
// New star players in LRB5b.
//

$stars['Bertha Bigfist'] = array (
    'id'            => -47,
    'ma'            => 6,
    'st'            => 5,
    'ag'            => 3,
    'av'            => 9,
    'def'           => array (99, 94, 50, 23, 54, 59, 110),
    'cost'          => 260000,
    'icon'          => 'star',
    'races'         => array(0, 7, 16)
);
$stars['Crazy Igor'] = array (
    'id'            => -48,
    'ma'            => 6,
    'st'            => 3,
    'ag'            => 3,
    'av'            => 8,
    'def'           => array (99, 2, 103, 59),
    'cost'          => 130000,
    'icon'          => 'star',
    'races'         => array(18, 21)
);
$stars['Dolfar Longstride'] = array (
    'id'            => -49,
    'ma'            => 7,
    'st'            => 3,
    'ag'            => 4,
    'av'            => 7,
    'def'           => array (99, 21, 42, 6, 7, 8),
    'cost'          => 170000,
    'icon'          => 'star',
    'races'         => array(5, 8, 20)
);

$stars['Fezglitch'] = array (
    'id'            => -50,
    'ma'            => 4,
    'st'            => 7,
    'ag'            => 3,
    'av'            => 7,
    'def'           => array (99, 91, 72, 74, 100, 105),
    'cost'          => 80000,
    'icon'          => 'star',
    'races'         => array(19, 23)
);
$stars['Glart Smashrip Jr.'] = array (
    'id'            => -51,
    'ma'            => 7,
    'st'            => 4,
    'ag'            => 3,
    'av'            => 8,
    'def'           => array (99, 1, 71, 53),
    'cost'          => 200000,
    'icon'          => 'star',
    'races'         => array(19, 23)
);
$stars['Humerus Carpal'] = array (
    'id'            => -52,
    'ma'            => 7,
    'st'            => 2,
    'ag'            => 3,
    'av'            => 7,
    'def'           => array (99, 20, 23, 103, 44),
    'cost'          => 130000,
    'icon'          => 'star',
    'races'         => array(10)
);
$stars['Ithaca Benoin'] = array (
    'id'            => -53,
    'ma'            => 7,
    'st'            => 3,
    'ag'            => 3,
    'av'            => 7,
    'def'           => array (99, 40, 41, 44, 45, 103, 12),
    'cost'          => 220000,
    'icon'          => 'star',
    'races'         => array(3, 10)
);
$stars['J Earlice'] = array (
    'id'            => -54,
    'ma'            => 8,
    'st'            => 3,
    'ag'            => 3,
    'av'            => 7,
    'def'           => array (99, 20, 21, 23, 28),
    'cost'          => 180000,
    'icon'          => 'star',
    'races'         => array(13, 17, 18)
);
$stars['Lewdgrip Whiparm'] = array (
    'id'            => -55,
    'ma'            => 6,
    'st'            => 3,
    'ag'            => 3,
    'av'            => 9,
    'def'           => array (99, 45, 58, 12, 77),
    'cost'          => 150000,
    'icon'          => 'star',
    'races'         => array(1, 15, 21)
);
$stars['Lottabottol'] = array (
    'id'            => -56,
    'ma'            => 8,
    'st'            => 3,
    'ag'            => 3,
    'av'            => 8,
    'def'           => array (99, 20, 22, 24, 25, 8, 10, 79),
    'cost'          => 220000,
    'icon'          => 'star',
    'races'         => array(11, 22)
);
$stars['Quetzal Leap'] = array (
    'id'            => -57,
    'ma'            => 8,
    'st'            => 2,
    'ag'            => 4,
    'av'            => 7,
    'def'           => array (99, 20, 21, 4, 7, 25, 44, 79),
    'cost'          => 250000,
    'icon'          => 'star',
    'races'         => array(11, 22)
);
$stars['Roxanna Darknail'] = array (
    'id'            => -58,
    'ma'            => 8,
    'st'            => 3,
    'ag'            => 5,
    'av'            => 7,
    'def'           => array (99, 23, 5, 24, 53, 25),
    'cost'          => 250000,
    'icon'          => 'star',
    'races'         => array(0, 3)
);
$stars['Sinnedbad'] = array (
    'id'            => -59,
    'ma'            => 6,
    'st'            => 3,
    'ag'            => 2,
    'av'            => 7,
    'def'           => array (99, 1, 24, 8, 103, 105, 26, 106),
    'cost'          => 80000,
    'icon'          => 'star',
    'races'         => array(10, 17)
);
$stars['Soaren Hightower'] = array (
    'id'            => -60,
    'ma'            => 6,
    'st'            => 3,
    'ag'            => 4,
    'av'            => 8,
    'def'           => array (99, 4, 7, 45, 46, 12, 58),
    'cost'          => 180000,
    'icon'          => 'star',
    'races'         => array(8)
);
$stars['Willow Rosebark'] = array (
    'id'            => -61,
    'ma'            => 5,
    'st'            => 4,
    'ag'            => 3,
    'av'            => 8,
    'def'           => array (99, 2, 26, 59),
    'cost'          => 150000,
    'icon'          => 'star',
    'races'         => array(0, 7, 20)
);



//
// Changes to LRB5 star players in LRB5b
//

$stars['Bomber Dribblesnot']['def']          = array (99, 40, 93, 23, 104, 105, 108);
$stars['Bomber Dribblesnot']['races']        = array (6, 16, 12, 21, 23);
$stars['Boomer Eziasson']['def']             = array (99, 40, 1, 93, 105, 59);
$stars['Count Luthor Von Drakenborg']['def'] = array (99, 1, 98, 103, 26);
$stars['Flint Churnblade']['def']            = array (99, 1, 95, 105, 59);
$stars['Flint Churnblade']['cost']           = 130000;
$stars['Grim Ironjaw']['def']                = array (99, 1, 2, 5, 55, 59);
$stars['Hack Enslash']['def']                = array (99, 95, 103, 105, 26);
$stars['Hack Enslash']['cost']               = 120000;
$stars['Helmut Wulf']['def']                 = array (99, 95, 105, 57);
$stars['Helmut Wulf']['cost']                = 110000;
$stars['Helmut Wulf']['races']               = array (0, 9, 11, 14, 22, 18);
$stars['Hemlock']['races']                   = array (11, 22);
$stars['Hthark the Unstoppable']['cost']     = 330000;
$stars['Icepelt Hammerblow']['def']          = array (99, 71, 72, 5, 103, 59);
$stars['Jordell Freshbreeze']['cost']        = 260000;
$stars['Lord Borak the Despoiler']['cost']   = 300000;
$stars['Max Spleenripper']['def']            = array (99, 95, 105);
$stars['Max Spleenripper']['cost']           = 130000;
$stars['Mighty Zug']['cost']                 = 260000;
$stars['Morg \'n\' Thorg']['cost']           = 450000;
$stars['Morg \'n\' Thorg']['races']          = array (0, 1, 2, 21, 3, 4, 5, 6, 7, 8, 9, 11, 12, 14, 15, 16, 18, 19, 22, 23, 20);
$stars['Nobbla Blackwart']['def']            = array (99, 1, 23, 95, 105, 108);
$stars['Nobbla Blackwart']['cost']           = 130000;
$stars['Nobbla Blackwart']['races']          = array (2, 6, 16, 23);
$stars['Ripper']['races']                    = array (6, 12);
$stars['Scrappa Sorehead']['def']            = array (99, 3, 23, 25, 104, 28, 108, 29, 79);
$stars['Scrappa Sorehead']['cost']           = 150000;
$stars['Skitter Stab-Stab']['races']         = array (19, 23);
$stars['Slibli']['races']                    = array (11, 22);
$stars['Ugroth Bolgrot']['def']              = array (99, 95, 105);
$stars['Ugroth Bolgrot']['cost']             = 100000;
$stars['Ugroth Bolgrot']['races']            = array (12, 21);
$stars['Varag Ghoul-Chewer']['cost']         = 290000;
$stars['Zara the Slayer']['races']           = array (0, 4, 7, 8, 9, 14, 20);
$stars['Zzharg Madeye']['races']             = array (2, 21);

//
// Changes to inducements
//

$inducements['Halfling Master Chef']['reduced_cost'] = 50000;
$inducements['Bribes']['reduced_cost'] = 50000;
$inducements['Bribes']['reduced_cost_races'] = array(6);

