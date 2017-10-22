<?php
/*
 *  Copyright (c) Daniel Straalman <email is protected> 2009-2012. All Rights Reserved.
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
require_once('lib/game_data_lrb6.php');
// New skills added in BB16.
$skillarray['E'][114] = $skillididx[114] = 'Monstrous Mouth';
$skillarray['E'][115] = $skillididx[115] = 'Timmmber';
$skillarray['E'][116] = $skillididx[116] = 'Weeping Blades';
$skillarray['E'][117] = $skillididx[117] = 'Swoop';

// Changes to present teams/positionals from LRB6 to BB16.
$DEA['Human']['players']['Catcher']['cost'] = 60000;
$DEA['Skaven']['players']['Gutter Runner']['def'] = array (23, 116);
$DEA['Halfling']['players']['Treeman']['def'] = array (54, 57, 58, 109, 59, 110, 115);

$DEA['Goblin']['players']['deathdiver'] = array (
	'ma'        	=> 6,
	'st'        	=> 2,
	'ag'        	=> 3,
	'av'        	=> 7,
	'def'	    => array (104, 108, 117 ),
	'norm'		=> array ('A'),
	'doub'		=> array ('G', 'S', 'P'),
	'qty'			=> 1,
	'cost'			=> 60000,
	'icon'			=> 'goblin1',
	'pos_id'        => 235,
);
$DEA['Goblin']['players']['ooligan'] = array (
	'ma'        	=> 6,
	'st'        	=> 2,
	'ag'        	=> 3,
	'av'        	=> 7,
	'def'	    => array (104, 23, 108, 72, 97 ),
	'norm'		=> array ('A'),
	'doub'		=> array ('G', 'S', 'P'),
	'qty'			=> 1,
	'cost'			=> 70000,
	'icon'			=> 'goblin1',
	'pos_id'        => 236,
);

$DEA['Chaos Pact']['players']['Orc Renegade'] = array (
    'ma'        	=> 5,
	'st'        	=> 3,
	'ag'        	=> 3,
	'av'        	=> 9,
	'def'	    => array (113),
	'norm'		=> array ('G', 'M'),
	'doub'		=> array ('S', 'P'),
	'qty'			  => 1,
	'cost'			  => 50000,
	'icon'			  => 'olineman1',
	'pos_id'          => 237
);

// New star players in BB16.
$stars['Rasta Tailspike'] = array (
       'id'            => -62,
       'ma'            => 8,
       'st'            => 3,
       'ag'            => 3,
       'av'            => 7,
       'def'    => array (99, 20, 73),
       'cost'          => 110000,
       'icon'          => 'star',
       'races'         => array(19),
   );
   
$stars['Frank N Stein'] = array (
       'id'            => -63,
       'ma'            => 4,
       'st'            => 5,
       'ag'            => 1,
       'av'            => 9,
       'def'    => array (99, 50, 54, 103, 57, 59),
       'cost'          => 210000,
       'icon'          => 'star',
       'races'         => array(9, 13, 17),
   );
   
$stars['Bilerot Vomitflesh'] = array (
       'id'            => -64,
       'ma'            => 4,
       'st'            => 5,
       'ag'            => 2,
       'av'            => 9,
       'def'    => array (99, 3, 72, 74),
       'cost'          => 180000,
       'icon'          => 'star',
       'races'         => array(15, 1),
   );
   
$stars['Guffle Pusmaw'] = array (
       'id'            => -65,
       'ma'            => 5,
       'st'            => 3,
       'ag'            => 4,
       'av'            => 9,
       'def'    => array (99, 74, 101, 114),
       'cost'          => 220000,
       'icon'          => 'star',
       'races'         => array(15),
   );
