<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2012. All Rights Reserved.
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

define('T_RACE_DKHORNE', 25);

$DEA['Daemons of Khorne'] = array (
    'other'	=> array (
        'rr_cost' => 70000,
        'icon' => 'dkhorne.png',
        'race_id' => T_RACE_DKHORNE, # (Daemons of Khorne)
    ),
    'players'	=> array (
  			'Pit Fighter' => array (
    				'ma'        	=> 6,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 8,
    				'def'	    => array (5),
    				'norm'		=> array ('G', 'P'),
    				'doub'		=> array ('A', 'S'),
    				'qty'			  => 16,
    				'cost'			  => 60000,
    				'icon'			  => 'pitfighter',
    			    'pos_id'          => 250,
  			),
  			'Bloodletter Daemon' => array (
    				'ma'        	=> 6,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 7,
    				'def'	    => array (75,53,103),
    				'norm'		=> array ('G', 'A', 'S'),
    				'doub'		=> array ('P'),
    				'qty'			  => 4,
    				'cost'			  => 80000,
    				'icon'			  => 'bloodletter',
    			    'pos_id'          => 251,
  			),
  			'Khorne Herald'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 3,
    				'ag'        	=> 3,
    				'av'        	=> 8,
    				'def'	    => array (5,75,53),
    				'norm'		=> array ('G', 'S'),
    				'doub'		=> array ('A', 'P'),
    				'qty'			  => 2,
    				'cost'			  => 90000,
    				'icon'			  => 'herald',
    			    'pos_id'          => 252,
  			),
  			'Bloodthirster'	=> array (
    				'ma'        	=> 6,
    				'st'        	=> 5,
    				'ag'        	=> 1,
    				'av'        	=> 9,
    				'def'	    => array (99, 112, 71, 5, 75, 53, 103),
    				'norm'		=> array ('S'),
    				'doub'		=> array ('G', 'A', 'P'),
    				'qty'			  => 1,
    				'cost'			  => 180000,
    				'icon'			  => 'bloodthirster',
    			    'pos_id'          => 253,
  			),
    )
);

array_push($stars['Grashnak Blackhoof']['races'], T_RACE_DKHORNE);
array_push($stars['Morg \'n\' Thorg']['races'],   T_RACE_DKHORNE);

