<?php

// Startup checks
if (version_compare(PHP_VERSION, '5.2.0') == -1)
    die('OBBLM requires PHP version 5.2.0, you are running version '.PHP_VERSION);
if (strtolower($iniRG = ini_get('register_globals')) == 'on' || $iniRG == 1)
    die('OBBLM requires the PHP configuration directive <i>register_globals</i> set <b>off</b> in the <i>php.ini</i> configuration file. Please contact your web host.');
if (!defined('T_NO_STARTUP') && file_exists('install.php'))
    die('Please remove <i>install.php</i> before using OBBLM.');

// Error reporting
// error_reporting(E_ALL);
error_reporting((E_ALL | E_STRICT) & ~E_DEPRECATED);
session_start();

// MySQL wrapper to use mysqli for PHP7 support
require_once('lib/mysqli_wrapper.php');

/*********************
 *   General
 *********************/
define('OBBLM_VERSION', '0.97 SVN');
$credits = array(	'Pierluigi Masia',
					'Mag Merli',
					'Lars Scharrenberg',
					'Tim Haini',
					'Daniel Straalman',
					'Juergen Unfried',
					'Sune Radich Christensen',
					'Michael Bielec',
					'Grégory Romé',
					'Goiz Ruiz de Gopegui',
					'Ryan Williams',
					'Ian Williams');
define('NAFLM_VERSION', '2.0');
define('NAFLM_BUILD_DATE', '1st of September 2020');
define('CONTENT_VERSION', 'Spike 10.0');
define('CONTENT_DETAIL', 'Blood Bowl 2016, including Deathzone 1 and 2 and Spike 1 through 10');
define('CONTENT_DATE', '1st of September 2020');
$naflmcredits = array(	
						'Anthony Baez',
						'byrnesvictim',
						'Craig Fleming',
						'dannyuk1982',
						'Derek Hall',
						'doubleskulls',
						'drd0dger',	
						'hutchinsfary',
						'juergen69',
						'kossy',
						'mfranchetti',
						'rythos42',
						'Shteve0',
						'snotlingorc',
						'thefloppy1',
						'vanhu42',
						'williamleonard (funnyfingers)'
					  );
define('MAX_RECENT_GAMES', 15); // This limits the number of rows shown in the "recent/upcoming games" tables.
define('MAX_TNEWS', 3); // This number of entries are shown on the team news board.
define('DOC_URL', 'http://github.com/nicholasmr/obblm/wiki');
define('DOC_URL_GUIDE', 'http://github.com/nicholasmr/obblm/wiki/User-guide');
define('DOC_URL_CUSTOM', 'http://github.com/nicholasmr/obblm/wiki/Customization');

/*********************
 *   Node and object types.
 *********************/
// DO NOT CHANGE THESE EVER!!!
define('T_OBJ_PLAYER',  1);
define('T_OBJ_TEAM',    2);
define('T_OBJ_COACH',   3);

define('T_OBJ_RACE',   4);
define('T_OBJ_STAR',   5);

define('T_NODE_MATCH',      11);
define('T_NODE_TOURNAMENT', 12);
define('T_NODE_DIVISION',   13);
define('T_NODE_LEAGUE',     14);

/*********************
 *   Images
 *********************/
define('IMG', 'images');
define('RACE_ICONS', IMG.'/race_icons');
define('PLAYER_ICONS', IMG.'/player_icons');

/*********************
 *   HTML BOX types
 *********************/
define('T_HTMLBOX_INFO',  1);
define('T_HTMLBOX_COACH', 2);
define('T_HTMLBOX_ADMIN', 3);
define('T_HTMLBOX_STATS', 4);
define('T_HTMLBOX_MATCH', 5);

/********************
 *  Dependencies
 ********************/
// General OBBLM routines and data structures.
# General settings
require_once('lib/settings_default.php'); 			# Defaults
require_once('settings.php');             			# Overrides
require_once('localsettings/settings_none.php'); 	# Defaults. Overrides are league dependant and are not loaded here - see setupGlobalVars()
# Load game data --- Module settings might depend on game data, so we include it first
require_once('lib/game_data_bb2016.php'); # GAME_DATA_BB2016 MUST be loaded.
if ($settings['custom_races']['Bretonnia'])         {require_once('lib/game_data_bretonnia.php');}
if ($settings['custom_races']['Daemons of khorne']) {require_once('lib/game_data_daemonsofkhorne.php');}
if ($settings['custom_races']['Apes of wrath'])     {require_once('lib/game_data_apesofwrath.php');}
# Module settings
require_once('lib/settings_modules_default.php'); 	# Defaults
require_once('settings_modules.php');             	# Overrides
require_once('settings_css.php');

// OBBLM libraries.
require_once('lib/class_settings.php');
require_once('lib/mysql.php');
require_once('lib/misc_functions.php');
require_once('lib/class_email.php');
require_once('lib/class_mobile.php');
require_once('lib/class_sqltriggers.php');
require_once('lib/class_match.php');
require_once('lib/class_tournament.php');
require_once('lib/class_division.php');
require_once('lib/class_league.php');
require_once('lib/class_player.php');
require_once('lib/class_starmerc.php');
require_once('lib/class_team.php');
require_once('lib/class_coach.php');
require_once('lib/class_race.php');
require_once('lib/class_stats.php');
require_once('lib/class_text.php');
require_once('lib/class_rrobin.php');
require_once('lib/class_module.php');
require_once('lib/class_tablehandler.php');
require_once('lib/class_image.php');
require_once('lib/class_translations.php');
require_once('lib/class_objevent.php');
require_once('lib/class_filemanager.php');

// External libraries.
require_once('lib/class_arraytojs.php');

// HTML interface routines.
require_once('sections.php'); # Main file. Some of the subroutines in this file are quite large and are therefore split into the files below.
require_once('admin/admin.php');
require_once('lib/class_htmlout.php');
require_once('lib/class_coach_htmlout.php');
require_once('lib/class_team_htmlout.php');
require_once('lib/class_player_htmlout.php');
require_once('lib/class_starmerc_htmlout.php');
require_once('lib/class_race_htmlout.php');
require_once('lib/class_match_htmlout.php');
require_once('lib/class_mobile_htmlout.php');

/********************
 *   Final setup
 ********************/
if (!is_writable(IMG)) {
    die('OBBLM needs to be able to write to the <i>images</i> directory in order to work properly. Please check the directory permissions.');
}
sortgamedata(); # Game data files are unsorted, make them pretty for display purposes.

/********************
 *   Globals/Startup
 ********************/

if (defined('T_NO_STARTUP')) {
	Coach::logout();
	setupGlobalVars(T_SETUP_GLOBAL_VARS__COMMON);
    require_once('modules/modsheader.php'); # Registration of modules.
} else {
    $conn = mysql_up(defined('T_NO_TBL_CHK') ? !T_NO_TBL_CHK : true); # MySQL connect.
    require_once('modules/modsheader.php'); # Registration of modules.
    setupGlobalVars(T_SETUP_GLOBAL_VARS__POST_LOAD_MODULES);
	
	/******************************
	   Translate skills globally
	******************************/
	global $lng;
	$lng->TranslateSkills();
}
