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

require_once('lib/game_data_lrb6x.php');

// Changes to present teams/positionals from LRB5b to LRB6.

$DEA['Necromantic']['players']['Flesh Golem']['cost'] = 110000;
if(isset($DEA['Necromantic']['players']['Werewolf']))
    $DEA['Necromantic']['players']['Necromantic Werewolf'] = $DEA['Necromantic']['players']['Werewolf']; unset($DEA['Necromantic']['players']['Werewolf']);
if(isset($DEA['Norse']['players']['Ulfwerener']))
    $DEA['Norse']['players']['Norse Werewolf'] = $DEA['Norse']['players']['Ulfwerener']; unset($DEA['Norse']['players']['Ulfwerener']);
if(isset($DEA['Norse']['players']['Snow Troll']))
    $DEA['Norse']['players']['Yhetee'] = $DEA['Norse']['players']['Snow Troll']; unset($DEA['Norse']['players']['Snow Troll']);
if(isset($DEA['Norse']['players']['Runner']))
    $DEA['Norse']['players']['Catcher'] = $DEA['Norse']['players']['Runner']; unset($DEA['Norse']['players']['Runner']);
if(isset($DEA['Norse']['players']['Berserker']))
    $DEA['Norse']['players']['Blitzer'] = $DEA['Norse']['players']['Berserker']; unset($DEA['Norse']['players']['Berserker']);

// Changes in star players from LRB5b to LRB6.

$stars['Bertha Bigfist']['cost']                      = 290000;
$stars['Crazy Igor']['cost']                          = 120000;
$stars['Dolfar Longstride']['cost']                   = 150000;
$stars['Fezglitch']['cost']                           = 100000;
$stars['Glart Smashrip Jr.']['cost']                  = 210000;
$stars['Morg \'n\' Thorg']['cost']                    = 430000;
$stars['Zzharg Madeye']['cost']                       = 90000;
$stars['Deeproot Strongbranch']['cost']               = 300000;
$stars['Eldril Sidewinder']['cost']                   = 200000;
$stars['Ramtut III']['cost']                          = 380000;

// Changes to inducements

$inducements['Halfling Master Chef']['reduced_cost'] = 100000; # Old LRB5 value.

