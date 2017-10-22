<?php

/*
 *  Copyright (c) Ian Williams <email is protected> 2011-2012. All Rights Reserved.
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

// Additional data for Bretonnians see  - http://www.plasmoids.dk/bbowl/BBBretonnians.htm

define('T_RACE_BRETONNIA', 24);

$DEA['Bretonnia'] = array (
    'other'	=> array (
        'rr_cost' => 60000,
        'icon' => 'bretonnian.jpg',
        'race_id' => T_RACE_BRETONNIA, # (Bretonnia)
    ),
    'players'	=> array (
  			'Lineman'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 3,
    				'ag'        	=> 2,
    				'av'        	=> 7,
    				'def'	    => array (4),
    				'norm'		=> array ('G'),
    				'doub'		=> array ('A', 'S', 'P'),
    				'qty'			  => 16,
    				'cost'			  => 40000,
    				'icon'			  => 'bretlineman',
    			    'pos_id'          => 235,
  			),
  			'Blocker'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 8,
    				'def'	    => array (14),
    				'norm'		=> array ('G', 'S'),
    				'doub'		=> array ('A', 'P'),
    				'qty'			  => 4,
    				'cost'			  => 70000,
    				'icon'			  => 'bretblocker',
    			    'pos_id'          => 236,
  			),
  			'Blitzer'	=> array (
    				'ma'        	=> 7,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 8,
    				'def'	    => array (1, 20, 2),
    				'norm'		=> array ('G', 'P'),
    				'doub'		=> array ('A', 'S'),
    				'qty'			  => 4,
    				'cost'			  => 110000,
    				'icon'			  => 'bretblitzer',
    			    'pos_id'          => 237,
  			),
    )
);

// Stars allowed
foreach (array('Dolfar Longstride', 'Griff Oberwald', 'Mighty Zug', 'Morg \'n\' Thorg', 'Willow Rosebark', 'Zara the Slayer') as $s) {
    array_push($stars[$s]['races'], T_RACE_BRETONNIA);
}

