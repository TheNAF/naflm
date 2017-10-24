<?php

/**************************
 * MySQL database settings 
 **************************/

$db_name   = 'obblmdb';
$db_user   = 'root';
$db_passwd = '';
$db_host   = 'localhost';

/*************************
 * Global settings 
 *************************/

/* 
    For help visit 
        - http://www.nicholasmr.dk/obblmwiki/index.php?title=Customization#Global_settings
        - http://www.nicholasmr.dk/obblmwiki/index.php?title=Setup
        
    For LOCAL settings, ie. per league settings, edit the localsettings/settings_<LEAGUE ID>.php files.
*/

$settings['site_name'] = 'My OBBLM portal';                         // Site name.
$settings['default_visitor_league'] = 1;                            // ID of default league to show on front page when not logged in OR coach has not selected a home league.
$settings['default_leagues'] = array();                             // When creating a coach the coach will automatically become a regular coach in leagues with these IDs.
$settings['hide_ES_extensions'] = false;                            // Default is false. Hides ES (Extra Stats) tables and ES references.
$settings['league_coordinator_email'] = 'webmaster@example.com';    // Sent "Request League" e-mails

$rules['bank_threshold'] = 0; // Default is 0 (banking rule disabled). Amount of team treasury in kilos (k) above which it will count towards the team value (TV). NOTE: 1) This feature is not yet available on a per-league basis, it works across all leagues! 2) When changing this value run "Re-install DB back-end procedures and functions" under "DB maintenance" from the "Admin -> Core panel" menu.
$rules['force_IR'] = false; // Default is false. Setting this to true will remove the ability of selecting 0-0 as 2D6 injury rolls (IR) in match reports (of all leagues).

/*
    Game data - additional races
        OBBLM uses LRB6 by default.
        In addition you can include the custom races below. Simply change the keywords "false" to "true" (without apostrophes).
*/

$settings['custom_races'] = array(
    'Bretonnia'         => true,
    'Daemons of khorne' => true,
    'Apes of wrath'     => true,
);


/*****************
 * House ranking systems
 *****************/

/* 
    Please visit 
        - http://www.nicholasmr.dk/obblmwiki/index.php?title=Customization#Global_settings
    before you edit the below settings.
*/

// Rule #1
$hrs[1]['rule']   = array('-pts', '-td', '+smp');    // Sort teams against: most points, then most TDs and then least sportsmanship points.
$hrs[1]['points'] = '3*[won] + 2*[draw] + 1*[lost]'; // The definition of points.

// Rule #2
$hrs[2]['rule']   = array('-pts', '-ki', '-mvp'); // Sort teams against: most points, then most killed and then by most MVPs.
$hrs[2]['points'] = '2*[gf] - 1*[ga]';            // The definition of points.

// Rule #3
$hrs[3]['rule']   = array('-sdiff', '-smp'); // Sort teams against: largest score difference, then most sportsmanship points.
$hrs[3]['points'] = '';                      // Points not used.

// Rule #4
$hrs[4]['rule']   = array('-pts', '-sdiff', '-tcdiff');    // Sort teams against: most points, then net TDs and then least sportsmanship points.
$hrs[4]['points'] = '6*[won] + 3*[draw] + 1*[lost]'; // The definition of points.

// Rule #4
$hrs[5]['rule']   = array('-pts', '-sdiff', '-tcdiff');    // Sort teams against: most points, then net TDs and then least sportsmanship points.
$hrs[5]['points'] = '3*[won] + 1*[draw] + 0*[lost]'; // The definition of points.

// Add you own rules here...