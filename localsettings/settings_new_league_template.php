<?php

/*************************
 * Local settings for League with ID = X, as per settings_X.php
 *************************/
preg_match('/settings_(.*?)\.php/', __FILE__, $match);
$get_lid = $match[1];
$settings['stylesheet'] = 1; 
$settings['lang']            = 'en-GB';

/*********************
 *   General
 *********************/

// Change the Title after the = sign.  Do not change things before the = sign.
$settings['banner_subtitle'] = 'New here?  Visit theNAF.net/Leagues for more information';
// Button text for league URL.
$settings['league_url_name'] = 'League Forum'; 
// Stylesheet for text etc. Currently stylesheet 1 is the only existing stylesheet, so don't change it!  
// Default language. Existing: en-GB, es-ES, de-DE, fr-FR, it-IT. 
// Default is true. Generate coach, team and player links on the front page?       
$settings['fp_links']        = true;
$settings['league_name']     = get_alt_col('league_prefs','f_lid',$get_lid,'league_name'); 
$settings['banner_title']    = get_alt_col('league_prefs','f_lid',$get_lid,'league_name');
// URL of league home page, if you have one. If not then leave this empty, that is = '' (two quotes only), which will disable the button.
$settings['league_url']      = get_alt_col('league_prefs','f_lid',$get_lid,'forum_url');    
// The welcome text appears below the title.           
$settings['welcome']         = get_alt_col('league_prefs','f_lid',$get_lid,'welcome'); 
// The next text appears when you click the rules button.
$settings['rules']           = get_alt_col('league_prefs','f_lid',$get_lid,'rules'); 
$get_prime = get_alt_col('league_prefs','f_lid',$get_lid,'prime_tid');
$get_second = get_alt_col('league_prefs','f_lid',$get_lid,'second_tid');

// Keep the following the same.
$settings['tourlist_foldup_fin_divs'] = false; // Default is false. If true the division nodes in the tournament lists section will automatically be folded up if all child tournaments in that division are marked as finished.
$settings['tourlist_hide_nodes'] = array('league', 'division', 'tournament'); // Default is array('league', 'division', 'tournament'). In the section tournament lists these nodes will be hidden if their contents (children) are finished. Example: If 'division' is chosen here, and all tours in a given division are finished, then the division entry will be hidden.

/*********************
 *   Rules
 *********************/

// Please use the boolean values "true" and "false" wherever default values are boolean.

$rules['max_team_players']      = 16;       // Default is 16.
$rules['static_rerolls_prices'] = false;    // Default is "false". "true" forces re-roll prices to their un-doubled values.
$rules['player_refund']         = 0;        // Player sell value percentage. Default is 0 = 0%, 0.5 = 50%, and so on.
$rules['journeymen_limit']      = 11;       // Until a team can field this number of players, it may fill team positions with journeymen.
$rules['post_game_ff']          = false;    // Default is false. Allows teams to buy and drop fan factor even though their first game has been played.

$rules['initial_treasury']      = 1000000;  // Default is 1000000.
$rules['initial_rerolls']       = 0;        // Default is 0.
$rules['initial_fan_factor']    = 0;        // Default is 0.
$rules['initial_ass_coaches']   = 0;        // Default is 0.
$rules['initial_cheerleaders']  = 0;        // Default is 0.

// For the below limits, the following applies: -1 = unlimited. 0 = disabled.
$rules['max_rerolls']           = -1;       // Default is -1.
$rules['max_fan_factor']        = 9;        // Default is 9.
$rules['max_ass_coaches']       = -1;       // Default is -1.
$rules['max_cheerleaders']      = -1;       // Default is -1.

// Remove double backslashes in front of team number to enable team specific starting treasuries.
$rules['initial_team_treasury'] = array(	//	0			=>	1000000,	// Amazon
											//	1			=>	1000000,	// Chaos
											//	2			=>	1000000,	// Chaos Dwarf
											//	3			=>	1000000,	// Dark Elf
											//	4			=>	1000000,	// Dwarf
											//	5			=>	1000000,	// Elf
											//	6			=>	1000000,	// Goblin
											//	7			=>	1000000,	// Halfling
											//	8			=>	1000000,	// High Elf
											//	9			=>	1000000,	// Human
											//	10			=>	1000000,	// Khemri
											//	11			=>	1000000,	// Lizardman
											//	12			=>	1000000,	// Orc
											//	13			=>	1000000,	// Necromantic
											//	14			=>	1000000,	// Norse
											//	15			=>	1000000,	// Nurgle
											//	16			=>	1000000,	// Ogre
											//	17			=>	1000000,	// undead
											//	18			=>	1000000,	// Vampire
											//	19			=>	1000000,	// Skaven
											//	20			=>	1000000,	// Wood Elf
											//	21			=>	1000000,	// Chaos Pact
											//	22			=>	1000000,	// Slann
											//	23			=>	1000000,	// Underworld
											//	24			=>	1000000,	// Bretonnia
											//	25			=>	1000000,	// Daemons of Khorne
											//	26			=>	1000000,	// Apes of Wrath
										);	

/*********************
 *   Standings pages
 *********************/

$settings['standings']['length_players'] = 30;  // Number of entries on the general players standings table.
$settings['standings']['length_teams']   = 30;  // Number of entries on the general teams   standings table.
$settings['standings']['length_coaches'] = 30;  // Number of entries on the general coaches standings table.

/*********************
 *   Front page messageboard
 *********************/

$settings['fp_messageboard']['length']               = 10;    // Number of entries on the front page message board.
$settings['fp_messageboard']['show_team_news']       = true; // Default is true. Show team news on the front page message board.
$settings['fp_messageboard']['show_match_summaries'] = true; // Default is true. Show match summaries on the front page message board.

/*********************
 *   Front page boxes
 *********************/

/*
    The below settings define which boxes to show on the right side of the front page.

    Note, every box MUST have a UNIQUE 'box_ID' number.
    The box IDs are used to determine the order in which the boxes are shown on the front page.
    The box with 'box_ID' = 1 is shown at the top of the page, the box with 'box_ID' = 2 is displayed underneath it and so forth.
*/

/*********************
 *   Front page: tournament standings boxes
 *********************/
$settings['fp_standings'] = array(
# This will display a standings box of the top 6 teams in node (league, division or tournament) with ID = 1
array(
'id' => $get_prime, # Node ID
'box_ID' => 1,
// Please note: 'type' may be either one of: 'league', 'division' or 'tournament'
'type' => 'tournament', # This sets the node to be a tournament. I.e. this will make a standings box for the tournament with ID = 1
'infocus' => true, # If true a random team from the standings will be selected and its top players displayed.
/*
The house ranking system (HRS) NUMBER to sort the table against.
Note, this is ignored for "type = tournament", since tours have an assigned HRS.
Also note that using HRSs with fields such as points (pts) for leagues/divisions standings makes no sense as they are tournament specific fields (i.e. it makes no sense to sum the points for teams across different tours to get the teams' "league/division points", as the points definitions for tours may vary).
*/
'HRS' => get_alt_col('tours','tour_id',$get_prime,'rs'), # Note: this must be a existing and valid HRS number from the main settings.php file.
'title' => get_alt_col('tours','tour_id',$get_prime,'name'), # Table title
'length' => 40, # Number of entries in table
# Format: "Displayed table column name" => "OBBLM field name". For the OBBLM fields available see http://nicholasmr.dk/obblmwiki/index.ph ... tomization
'fields' => array('Name' => 'name', 'PTS' => 'pts', 'TV' => 'tv', 'CAS' => 'cas', 'W' => 'won', 'L' => 'lost', 'D' => 'draw', 'GF' => 'gf', 'GA' => 'ga',),
),
    
);

/*********************
 *   Front page: leaders boxes
 *********************/

$settings['fp_leaders'] = array(
    # Please note: You can NOT make expressions out of leader fields e.g.: 'field' => 'cas+td'
    # This will display a 'most CAS' player leaders box for the node (league, division or tournament) with ID = 1
    array(
        'id'        => get_alt_col('tours','tour_id',$get_prime,'f_did'), # Node ID
        'box_ID'    => 3,
        // Please note: 'type' may be either one of: 'league', 'division' or 'tournament'
        'type'      => 'division', # This sets the node to be a tournament. I.e. this will make a leaders box for the tournament with ID = 1
        'title'     => 'Most Individual Casualties (Division)', # Table title
        'field'     => 'cas', # For the OBBLM fields available see http://nicholasmr.dk/obblmwiki/index.php?title=Customization
        'length'    => 5, # Number of entries in table
        'show_team' => true, # Show player's team name?
    ),
    # This will display a 'most TD' player leaders box for the node (league, division or tournament) with ID = 2
    array(
        'id'        => get_alt_col('tours','tour_id',$get_prime,'f_did'), # Node ID
        'box_ID'    => 4,
        // Please note: 'type' may be either one of: 'league', 'division' or 'tournament'
        'type'      => 'division', # This sets the node to be a tournament. I.e. this will make a leaders box for the tournament with ID = 1
        'title'     => 'Most Individual Touchdowns (Division)', # Table title
        'field'     => 'td', # For the OBBLM fields available see http://nicholasmr.dk/obblmwiki/index.php?title=Customization
        'length'    => 5, # Number of entries in table
        'show_team' => true, # Show player's team name?
    ),
);

/*********************
 *   Front page: event boxes
 *********************/

$settings['fp_events'] = array(
    /*
        Event boxes can show for any league, division or tournament the following:
            dead        - recent dead players
            sold        - recent sold players
            hired       - recent hired players
            skills      - recent player skill picks
    */
    array(
        'id'        => $get_lid, # Node ID
        'box_ID'    => 5,
        // Please note: 'type' may be either one of: 'league', 'division' or 'tournament'
        'type'      => 'league', # This sets the node to be a tournament. I.e. this will make an event box for the tournament with ID = 1
        'title'     => 'Latest Dead Players (League)', # Table title
        'content'   => 'dead', # Event type
        'length'    => 5, # Number of entries in table
    ),

array(
        'id'        => $get_lid, # Node ID
        'box_ID'    => 6,
        // Please note: 'type' may be either one of: 'league', 'division' or 'tournament'
        'type'      => 'league', # This sets the node to be a tournament. I.e. this will make an event box for the tournament with ID = 1
        'title'     => 'Latest Skills (League)', # Table title
        'content'   => 'skills', # Event type
        'length'    => 5, # Number of entries in table
    ),
);

/*********************
 *   Front page: latest games boxes
 *********************/

$settings['fp_latestgames'] = array(
    # This will display a latest games box for the node (league, division or tournament) with ID = 1
    array(
        'id'     => $get_lid, # Node ID
        'box_ID' => 7,
        // Please note: 'type' may be either one of: 'league', 'division' or 'tournament'
        'type'   => 'league', # This sets the node to be a league. I.e. this will make a latest games box for the league with ID = 1
        'title'  => 'Recent Games (League)', # Table title
        'length' => 5, # Number of entries in table
    ),
);
