<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2007-2011. All Rights Reserved.
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

/* THIS FILE is used for MySQL-helper routines */


// These are the OBBLM core tables.

# Commonly used col. defs.
$CT_cols = array(
    T_OBJ_PLAYER => 'MEDIUMINT SIGNED', # Negative IDs are stars.
    T_OBJ_TEAM   => 'MEDIUMINT UNSIGNED',
    T_OBJ_COACH  => 'MEDIUMINT UNSIGNED',
    T_OBJ_RACE   => 'TINYINT UNSIGNED',
    T_OBJ_STAR   => 'SMALLINT SIGNED', # All star IDs are negative.
    'pos_id'     => 'SMALLINT UNSIGNED', # Position ID/"name ID" of player on race roster.
    'skill_id'   => 'SMALLINT UNSIGNED',
    T_NODE_MATCH      => 'MEDIUMINT SIGNED',
    T_NODE_TOURNAMENT => 'MEDIUMINT UNSIGNED',
    T_NODE_DIVISION   => 'MEDIUMINT UNSIGNED',
    T_NODE_LEAGUE     => 'MEDIUMINT UNSIGNED',

    'name' => 'VARCHAR(60)', # Widely used for name fields etc.
    'tv' => 'MEDIUMINT UNSIGNED', # Team value
    'pv' => 'MEDIUMINT SIGNED', # Player value
    'chr' => 'TINYINT UNSIGNED', # ma, st, ag, av (inj, def and ach)
    'chr_ua' => 'TINYINT SIGNED', # Unadjusted ma, st, ag, av
    'elo' => 'FLOAT',
    'team_cnt' => 'TINYINT UNSIGNED', # Teams count for races and coaches.
    'wt_cnt'   => 'SMALLINT UNSIGNED', # Won tours count.
    'win_pct'  => 'FLOAT UNSIGNED',
    'streak' => 'SMALLINT UNSIGNED',
    'skills' => 'VARCHAR('.(19+20*3).')', # Set limit to 20 skills, ie. chars = 19 commas + 20*3 (max 20 integers of 3 decimals (assumed upper limit)).
    'pts'   => 'FLOAT SIGNED',
);

$core_tables = array(
    'coaches' => array(
        'coach_id'  => $CT_cols[T_OBJ_COACH].' NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'name'      => $CT_cols['name'],
        'realname'  => $CT_cols['name'],
        'passwd'    => 'VARCHAR(32)',
        'mail'      => 'VARCHAR(129)',
        'phone'     => 'VARCHAR(25) NOT NULL',
        'ring'      => 'TINYINT UNSIGNED NOT NULL DEFAULT 0', # Global access level
        'settings'  => 'VARCHAR(320) NOT NULL',
        'retired'   => 'BOOLEAN NOT NULL DEFAULT 0',
        'activation_code' => 'VARCHAR(32) DEFAULT NULL',
        // Dynamic properties (DPROPS)
        'elo'   => $CT_cols['elo'].' DEFAULT NULL', # All-time ELO (across all matches).
        'swon'  => $CT_cols['streak'].' DEFAULT 0',
        'sdraw' => $CT_cols['streak'].' DEFAULT 0',
        'slost' => $CT_cols['streak'].' DEFAULT 0',
        'team_cnt' => $CT_cols['team_cnt'].' DEFAULT 0',
        'wt_cnt' => $CT_cols['wt_cnt'].' DEFAULT 0',
        'win_pct' => $CT_cols['win_pct'].' DEFAULT 0',
    ),
    'teams' => array(
        'team_id'           => $CT_cols[T_OBJ_TEAM].' NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'name'              => $CT_cols['name'],
        'owned_by_coach_id' => $CT_cols[T_OBJ_COACH],
        'f_race_id'         => $CT_cols[T_OBJ_RACE].' NOT NULL DEFAULT 0',
        'f_did'             => $CT_cols[T_NODE_DIVISION].' NOT NULL DEFAULT 0',
        'f_lid'             => $CT_cols[T_NODE_LEAGUE].' NOT NULL DEFAULT 0',
        'treasury'          => 'BIGINT SIGNED',
        'apothecary'        => 'BOOLEAN',
        'rerolls'           => 'MEDIUMINT UNSIGNED',
        'ff_bought'         => 'TINYINT UNSIGNED',
        'ass_coaches'       => 'MEDIUMINT UNSIGNED',
        'cheerleaders'      => 'MEDIUMINT UNSIGNED',
        'rdy'               => 'BOOLEAN NOT NULL DEFAULT 1',
        'imported'          => 'BOOLEAN NOT NULL DEFAULT 0',
        'retired'           => 'BOOLEAN NOT NULL DEFAULT 0',
        'won_0'     => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
        'lost_0'    => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
        'draw_0'    => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
        'played_0'  => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
        'wt_0'      => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
        'gf_0'      => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
        'ga_0'      => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
        // Relations
        'f_rname' => $CT_cols['name'],
        'f_cname' => $CT_cols['name'],
        // Dynamic properties (DPROPS)
        'ff'      => 'TINYINT UNSIGNED', # Total: sum of match FF (ff, from MV table) and bought (ff_bought).
        'tv'      => $CT_cols['tv'],
        'elo'     => $CT_cols['elo'].' DEFAULT NULL', # All-time ELO (across all matches).
        'swon'    => $CT_cols['streak'].' DEFAULT 0',
        'sdraw'   => $CT_cols['streak'].' DEFAULT 0',
        'slost'   => $CT_cols['streak'].' DEFAULT 0',
        'wt_cnt'  => $CT_cols['wt_cnt'].' DEFAULT 0',
        'win_pct' => $CT_cols['win_pct'].' DEFAULT 0', # All-time win pct (across all matches).
    ),
    'players' => array(
        'player_id'         => $CT_cols[T_OBJ_PLAYER].' NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'type'              => 'TINYINT UNSIGNED DEFAULT 1',
        'name'              => $CT_cols['name'],
        'owned_by_team_id'  => $CT_cols[T_OBJ_TEAM],
        'nr'                => 'MEDIUMINT UNSIGNED',
        'f_pos_id'          => $CT_cols['pos_id'],
        'date_bought'       => 'DATETIME',
        'date_sold'         => 'DATETIME',
        'ach_ma'            => $CT_cols['chr'],
        'ach_st'            => $CT_cols['chr'],
        'ach_ag'            => $CT_cols['chr'],
        'ach_av'            => $CT_cols['chr'],
        'extra_spp'         => 'MEDIUMINT SIGNED',
        'extra_val'         => $CT_cols['pv'].' NOT NULL DEFAULT 0',
        // Relations
        'f_rid' => $CT_cols[T_OBJ_RACE],
        'f_cid' => $CT_cols[T_OBJ_COACH],
        'f_tname' => $CT_cols['name'],
        'f_rname' => $CT_cols['name'],
        'f_cname' => $CT_cols['name'],
        'f_pos_name' => $CT_cols['name'],
        // Dynamic properties (DPROPS)
        'value'     => $CT_cols['pv'],
        'status'    => 'TINYINT UNSIGNED',
        'date_died' => 'DATETIME',
        'ma'        => $CT_cols['chr'].' DEFAULT 0',
        'st'        => $CT_cols['chr'].' DEFAULT 0',
        'ag'        => $CT_cols['chr'].' DEFAULT 0',
        'av'        => $CT_cols['chr'].' DEFAULT 0',
        'ma_ua'     => $CT_cols['chr_ua'].' DEFAULT 0',
        'st_ua'     => $CT_cols['chr_ua'].' DEFAULT 0',
        'ag_ua'     => $CT_cols['chr_ua'].' DEFAULT 0',
        'av_ua'     => $CT_cols['chr_ua'].' DEFAULT 0',
        'inj_ma'    => $CT_cols['chr'].' DEFAULT 0',
        'inj_st'    => $CT_cols['chr'].' DEFAULT 0',
        'inj_ag'    => $CT_cols['chr'].' DEFAULT 0',
        'inj_av'    => $CT_cols['chr'].' DEFAULT 0',
        'inj_ni'    => $CT_cols['chr'].' DEFAULT 0',
        'win_pct' => $CT_cols['win_pct'].' DEFAULT 0', # All-time win pct (across all matches).
    ),
    'memberships' => array(
        'cid'   => $CT_cols[T_OBJ_COACH].' NOT NULL',
        'lid'   => $CT_cols[T_NODE_LEAGUE].' NOT NULL',
        'ring'  => 'TINYINT UNSIGNED NOT NULL DEFAULT 0', # Local access level
    ),
    'players_skills' => array(
        'id'         => 'INT NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'f_pid'      => $CT_cols[T_OBJ_PLAYER].' NOT NULL',
        'f_skill_id' => $CT_cols['skill_id'].' NOT NULL',
        'type'       => 'VARCHAR(1)', # N, D or E
    ),
    'races' => array(
        'race_id' => $CT_cols[T_OBJ_RACE].' NOT NULL PRIMARY KEY',
        'name'    => $CT_cols['name'],
        'cost_rr' => 'MEDIUMINT UNSIGNED',
        // Dynamic properties (DPROPS)
        'team_cnt' => $CT_cols['team_cnt'].' DEFAULT 0',
        'wt_cnt' => $CT_cols['wt_cnt'].' DEFAULT 0',
        'win_pct' => $CT_cols['win_pct'].' DEFAULT 0', # All-time win pct (across all matches).
    ),
    'leagues' => array(
        'lid'       => $CT_cols[T_NODE_LEAGUE].' NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'name'      => $CT_cols['name'],
        'location'  => $CT_cols['name'],
        'date'      => 'DATETIME',
        'tie_teams' => 'BOOLEAN DEFAULT TRUE',
    ),
    'divisions' => array(
        'did'   => $CT_cols[T_NODE_DIVISION].' NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'f_lid' => $CT_cols[T_NODE_LEAGUE],
        'name'  => $CT_cols['name'],
    ),
    'tours' => array(
        'tour_id'       => $CT_cols[T_NODE_TOURNAMENT].' NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'f_did'         => $CT_cols[T_NODE_DIVISION],
        'name'          => $CT_cols['name'],
        'type'          => 'TINYINT UNSIGNED',
        'date_created'  => 'DATETIME',
        'rs'            => 'TINYINT UNSIGNED DEFAULT 1',
        'locked'        => 'BOOLEAN NOT NULL DEFAULT 0',
        // Dynamic properties (DPROPS)
        'empty'    => 'BOOLEAN DEFAULT TRUE',
        'begun'    => 'BOOLEAN DEFAULT FALSE',
        'finished' => 'BOOLEAN DEFAULT FALSE',
        'winner'   => $CT_cols[T_OBJ_TEAM],
        'allow_sched' => 'BOOLEAN DEFAULT FALSE',
    ),
    'matches' => array(
        'match_id'      => $CT_cols[T_NODE_MATCH].' NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'round'         => 'TINYINT UNSIGNED',
        'f_tour_id'     => $CT_cols[T_NODE_TOURNAMENT],
        'locked'        => 'BOOLEAN',
        'submitter_id'  => $CT_cols[T_OBJ_COACH],
        'stadium'       => $CT_cols[T_OBJ_TEAM],
        'gate'          => 'MEDIUMINT UNSIGNED',
        'fans'          => 'MEDIUMINT UNSIGNED NOT NULL DEFAULT 0',
        'ffactor1'      => 'TINYINT SIGNED',
        'ffactor2'      => 'TINYINT SIGNED',
        'income1'       => 'MEDIUMINT SIGNED',
        'income2'       => 'MEDIUMINT SIGNED',
        'team1_id'      => $CT_cols[T_OBJ_TEAM],
        'team2_id'      => $CT_cols[T_OBJ_TEAM],
        'date_created'  => 'DATETIME',
        'date_played'   => 'DATETIME',
        'date_modified' => 'DATETIME',
        'team1_score'   => 'TINYINT UNSIGNED',
        'team2_score'   => 'TINYINT UNSIGNED',
        'smp1'          => 'TINYINT SIGNED NOT NULL DEFAULT 0',
        'smp2'          => 'TINYINT SIGNED NOT NULL DEFAULT 0',
        'tcas1'         => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'tcas2'         => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'fame1'         => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'fame2'         => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'tv1'           => $CT_cols['tv'].' NOT NULL DEFAULT 0',
        'tv2'           => $CT_cols['tv'].' NOT NULL DEFAULT 0',
    ),
    'match_data' => array(
        'f_coach_id'    => $CT_cols[T_OBJ_COACH],
        'f_team_id'     => $CT_cols[T_OBJ_TEAM],
        'f_player_id'   => $CT_cols[T_OBJ_PLAYER],
        'f_race_id'     => $CT_cols[T_OBJ_RACE],
        'f_match_id'    => $CT_cols[T_NODE_MATCH],
        'f_tour_id'     => $CT_cols[T_NODE_TOURNAMENT],
        'f_did'         => $CT_cols[T_NODE_DIVISION],
        'f_lid'         => $CT_cols[T_NODE_LEAGUE],
        'mvp'           => 'TINYINT UNSIGNED',
        'cp'            => 'TINYINT UNSIGNED',
        'td'            => 'TINYINT UNSIGNED',
        'intcpt'        => 'TINYINT UNSIGNED',
        'bh'            => 'TINYINT UNSIGNED',
        'si'            => 'TINYINT UNSIGNED',
        'ki'            => 'TINYINT UNSIGNED',
        'inj'           => 'TINYINT UNSIGNED',
        'ir1_d1'        => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'ir1_d2'        => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'ir2_d1'        => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'ir2_d2'        => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'ir3_d1'        => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'ir3_d2'        => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
        'agn1'          => 'TINYINT UNSIGNED',
        'agn2'          => 'TINYINT UNSIGNED',
        'mg'            => 'BOOLEAN NOT NULL DEFAULT FALSE',
    ),
    'texts' => array(
        'txt_id'    => 'MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'type'      => 'TINYINT UNSIGNED',
        'f_id'      => 'MEDIUMINT UNSIGNED',
        'f_id2'     => 'MEDIUMINT UNSIGNED NOT NULL DEFAULT 0',
        'date'      => 'DATETIME',
        'txt2'      => 'TEXT',
        'txt'       => 'TEXT',
    ),
    // Game data tables. These are synced with the PHP-stored game data.
    'game_data_players' => array(
        'pos_id'    => $CT_cols['pos_id'].' NOT NULL PRIMARY KEY',
        'f_race_id' => $CT_cols[T_OBJ_RACE],
        'pos'    => $CT_cols['name'],
        'cost'   => 'MEDIUMINT UNSIGNED',
        'qty'    => 'TINYINT UNSIGNED',
        'ma'     => $CT_cols['chr'],
        'st'     => $CT_cols['chr'],
        'ag'     => $CT_cols['chr'],
        'av'     => $CT_cols['chr'],
        'skills' => $CT_cols['skills'],
        'norm'   => 'VARCHAR(6)', # Max used is 4 chars, but set to 6 to be sure.
        'doub'   => 'VARCHAR(6)', # Max used is 4 chars, but set to 6 to be sure.
    ),
    'game_data_stars' => array(
        'star_id'=> $CT_cols[T_OBJ_STAR].' NOT NULL PRIMARY KEY',
        'name'   => $CT_cols['name'],
        'cost'   => 'MEDIUMINT UNSIGNED',
        'races'  => 'VARCHAR('.(29+30*2).')', # Race IDs that may hire star. Total of (less than) 30 races of each two digit race ID + 29 commas = 29+30*2
        'ma'     => $CT_cols['chr'],
        'st'     => $CT_cols['chr'],
        'ag'     => $CT_cols['chr'],
        'av'     => $CT_cols['chr'],
        'skills' => $CT_cols['skills'],
    ),
    'game_data_skills' => array(
        'skill_id' => $CT_cols['skill_id'].' NOT NULL PRIMARY KEY',
        'name' => $CT_cols['name'],
        'cat' => 'VARCHAR(1)',
    ),
);

/*
    MV tables
*/

$mv_keys = array(
    T_OBJ_PLAYER => 'f_pid',
    T_OBJ_STAR   => 'f_pid',
    T_OBJ_TEAM   => 'f_tid',
    T_OBJ_COACH  => 'f_cid',
    T_OBJ_RACE   => 'f_rid',
    T_NODE_TOURNAMENT => 'f_trid',
    T_NODE_DIVISION   => 'f_did',
    T_NODE_LEAGUE     => 'f_lid',
);

// Common:
$mv_commoncols = array(
    # Node references
    'f_trid' => $CT_cols[T_NODE_TOURNAMENT],
    'f_did'  => $CT_cols[T_NODE_DIVISION],
    'f_lid'  => $CT_cols[T_NODE_LEAGUE],
    # Stats
    'mvp'       => 'SMALLINT UNSIGNED',
    'cp'        => 'SMALLINT UNSIGNED',
    'td'        => 'SMALLINT UNSIGNED',
    'intcpt'    => 'SMALLINT UNSIGNED',
    'bh'        => 'SMALLINT UNSIGNED',
    'ki'        => 'SMALLINT UNSIGNED',
    'si'        => 'SMALLINT UNSIGNED',
    'cas'       => 'SMALLINT UNSIGNED',
    'tdcas'     => 'SMALLINT UNSIGNED',
    'smp'       => 'SMALLINT SIGNED',
    'spp'       => 'SMALLINT UNSIGNED',
    'ff'        => 'SMALLINT SIGNED',
    'won'       => 'SMALLINT UNSIGNED',
    'lost'      => 'SMALLINT UNSIGNED',
    'draw'      => 'SMALLINT UNSIGNED',
    'played'    => 'SMALLINT UNSIGNED',
    'win_pct'   => $CT_cols['win_pct'],
    'ga'        => 'SMALLINT UNSIGNED',
    'gf'        => 'SMALLINT UNSIGNED',
    'sdiff'     => 'SMALLINT SIGNED',
    'tcasa'     => 'SMALLINT UNSIGNED',
    'tcasf'     => 'SMALLINT UNSIGNED',
    'tcdiff'    => 'SMALLINT SIGNED',
);
$core_tables['mv_players'] = array(
    'f_pid' => $CT_cols[T_OBJ_PLAYER].' NOT NULL',
    'f_tid' => $CT_cols[T_OBJ_TEAM],
    'f_cid' => $CT_cols[T_OBJ_COACH],
    'f_rid' => $CT_cols[T_OBJ_RACE],
);
$core_tables['mv_teams'] = array(
    'f_tid' => $CT_cols[T_OBJ_TEAM].' NOT NULL',
    'f_cid' => $CT_cols[T_OBJ_COACH],
    'f_rid' => $CT_cols[T_OBJ_RACE],

    'elo'   => $CT_cols['elo'],
    'swon'  => $CT_cols['streak'],
    'sdraw' => $CT_cols['streak'],
    'slost' => $CT_cols['streak'],
    'pts'   => $CT_cols['pts'],
);
$core_tables['mv_coaches'] = array(
    'f_cid' => $CT_cols[T_OBJ_COACH].' NOT NULL',

    'elo'   => $CT_cols['elo'],
    'swon'  => $CT_cols['streak'],
    'sdraw' => $CT_cols['streak'],
    'slost' => $CT_cols['streak'],
    'team_cnt' => $CT_cols['team_cnt'],
);
$core_tables['mv_races'] = array(
    'f_rid' => $CT_cols[T_OBJ_RACE].' NOT NULL',

    'team_cnt' => $CT_cols['team_cnt'],
);

$ES_fields = array(
        # cat fs.txt | awk '/==/ {grp = $0} /\|\|/ {printf("%s%s\n", $0, grp);}' | perl -ne 's/^\|\|(\w*)\|\|(\w*)\|\|([^|]*)\|\|===([^|]*)===\s*$/"$1" => array("short" => "$2","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "$4", "desc" => "$3"),\n/ && print'
"pass_attempts" => array("short" => "cp_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of pass throw attempts of the ball."),
"interceptions_thrown" => array("short" => "cp_int","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of times the thrower has been intercepted."),
"safe_throws" => array("short" => "cp_st","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of times an interception was canceled by safe throw."),
"pass_distance" => array("short" => "cp_dist","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of squares progression the ball was thrown towards the endzone (this should be multiplied up to give number of paces (x5?)"),
"dumpoff_attempts" => array("short" => "dmp_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of passes thrown which have been dumpoffs (this is informational, pass_attempts includes dump offs)."),
"dumpoff_completions" => array("short" => "dmp","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of completions from dump offs (as above this is for informational purposes, pass_completions includes dump off completions)."),
"catch_attempts" => array("short" => "catch_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of catch attempts made my a player from a throw."),
"catches" => array("short" => "catch","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of catches made (including re-rolled)."),
"handoffs" => array("short" => "hnd","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of hand offs this player has made"),
"handoffs_received" => array("short" => "hnd_r_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of times this player has been handed off to."),
"handoff_catches" => array("short" => "hnd_r","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number times this player caught a hand off (including re-rolled)."),
"pickup_attempts" => array("short" => "pick_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of times attempting to pick the ball up."),
"pickups" => array("short" => "pick","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Offensive stats", "desc" => "Number of successful pick ups (including re-rolled)."),
"rushing_distance_leap" => array("short" => "rush_dist_lp","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Squares of progression towards the end zone leaping with the ball."),
"rushing_distance_push" => array("short" => "rush_dist_p","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "quares of progression towards the end zone from pushes."),
"rushing_distance_move" => array("short" => "rush_dist_m","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Squares of progression with the ball running towards the end zone in a normal move."),
"rushing_distance_block" => array("short" => "rush_dist_b","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Squares of progression towards the end zone from blocks/blitzes."),
"rushing_distance_shadowing" => array("short" => "rush_dist_sh","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Squares of progression towards the end zone from shadowing."),
"leap_attempts" => array("short" => "lp_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Number of leap attempts."),
"leaps" => array("short" => "lp","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Number of successful leaps (including re-rolled)."),
"dodge_attempts" => array("short" => "dg_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Number of dodge attempts"),
"dodges" => array("short" => "dg","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Number of successful dodges (including re-rolled)"),
"blitz_actions" => array("short" => "blz","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Number of times this player has blitzed."),
"gfi_attempts" => array("short" => "gfi_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Go for it attempts"),
"gfis" => array("short" => "gfi","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Movement stats", "desc" => "Successful go for its."),
"inflicted_blocks" => array("short" => "blk_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player tried to throw a block."),
"inflicted_defender_downs" => array("short" => "pow_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times defender down was the selected result."),
"inflicted_defender_stumbles" => array("short" => "stmbl_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times defender stumbles was the selected result."),
"inflicted_pushes" => array("short" => "psh_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times push was the selected result."),
"inflicted_both_downs" => array("short" => "both_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times both down was the selected result."),
"inflicted_attacker_downs" => array("short" => "skul_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times attacker down was the selected result."),
"inflicted_knock_downs" => array("short" => "dwns_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player knocked the other player down blocking (as the attacker, the defends ends up down or as the defender the attacker ends up down)."),
"inflicted_strip_balls" => array("short" => "strp_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times strip ball has been used by this player."),
"inflicted_sacks" => array("short" => "sack_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player knocked the other player down blocking (as the attacker, the defends ends up down or as the defender the attacker ends up down), when that player was carrying the ball."),
"inflicted_crowd_surfs" => array("short" => "surf_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times the push result has ended up in as an injury roll (presuming from being crowd surfed)."),
"inflicted_stuns" => array("short" => "st_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player knocked the other player down blocking (as the attacker, the defends ends up down or as the defender the attacker ends up down) and that player ended up stunned."),
"inflicted_kos" => array("short" => "ko_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player knocked the other player down blocking (as the attacker, the defends ends up down or as the defender the attacker ends up down) and that player ended up KOed."),
"inflicted_bhs" => array("short" => "bh_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player knocked the other player down blocking (as the attacker, the defends ends up down or as the defender the attacker ends up down) and that player ended up badly hurt (after apoth)."),
"inflicted_sis" => array("short" => "si_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player knocked the other player down blocking (as the attacker, the defends ends up down or as the defender the attacker ends up down) and that player ended up seriously injured (after apoth)."),
"inflicted_kills" => array("short" => "ki_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player knocked the other player down blocking (as the attacker, the defends ends up down or as the defender the attacker ends up down) and that player ended up dead (after apoth)"),
"sustained_blocks" => array("short" => "blk_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player has been blocked."),
"sustained_knocked_downs" => array("short" => "dwn_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number this this player was knocked down while blocking either from sustaining a block or when throwing a block."),
"sustained_sacks" => array("short" => "sack_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number this this player was knocked down while blocking either from sustaining a block or when throwing a block when carrying the ball."),
"sustained_crowd_surfs" => array("short" => "surf_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Number of times this player has been pushed and required been required to make an injury roll (from crowd surfs)."),
"sustained_stuns" => array("short" => "st_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Total number of times this player has been stunned (from any means). All these stats check player status at the end of the turn."),
"sustained_kos" => array("short" => "ko_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Total number of times this player has been KOed (from any means)."),
"sustained_bhs" => array("short" => "bh_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Total number of times this player has been badly hurt (from any means)."),
"sustained_sis" => array("short" => "si_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Total number of times this player has been seriously injured (from any means)."),
"sustained_kill" => array("short" => "ki_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Blocking stats", "desc" => "Total number of times this player has been killed (from any means)... this would only ever be 1!"),
"inflicted_fouls" => array("short" => "fl_i","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Fouling stats", "desc" => "Number of times this player has fouled another."),
"inflicted_foul_stuns" => array("short" => "st_fi","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Fouling stats", "desc" => "Number of times this player stunned another through fouling"),
"inflicted_foul_kos" => array("short" => "ko_fi","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Fouling stats", "desc" => "Number of times this player knocked out another through fouling"),
"inflicted_foul_bhs" => array("short" => "bh_fi","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Fouling stats", "desc" => "Number of times this player badly hurt another through fouling"),
"inflicted_foul_sis" => array("short" => "si_fi","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Fouling stats", "desc" => "Number of times this player seriously injured another through fouling"),
"inflicted_foul_kills" => array("short" => "ki_fi","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Fouling stats", "desc" => "Number of times this player killed another through fouling"),
"sustained_fouls" => array("short" => "fl_s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Fouling stats", "desc" => "Number of times this player has been fouled."),
"sustained_ejections" => array("short" => "ejct","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Fouling stats", "desc" => "Number of times this player was ejected for fouling."),
"apothecary_used" => array("short" => "ap","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Healing stats", "desc" => "Number of times the apoth has been used on this player"),
"ko_recovery_attempts" => array("short" => "ko_ra","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Healing stats", "desc" => "Number of recovery rolls from KOs"),
"ko_recoveries" => array("short" => "ko_r","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Healing stats", "desc" => "Number of successful KOs recoveries"),
"thickskull_used" => array("short" => "thk","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Healing stats", "desc" => "Number of times thick skull was used by this player."),
"regeneration_attempts" => array("short" => "rgn_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Healing stats", "desc" => "Number of time this player attempted to regenerate."),
"regenerations" => array("short" => "rgn","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Healing stats", "desc" => "Number of times the regenerate roll succeeded."),
"kickoffs" => array("short" => "kck","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Kicking stats", "desc" => "Number of times this player kicked off"),
"kick_distance" => array("short" => "kck_dist","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Kicking stats", "desc" => "Distance the ball was kicked in squares."),
"dice_rolls" => array("short" => "dice","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Dice stats", "desc" => "Number of times this player rolled a simple roll or skill roll."),
"dice_natural_ones" => array("short" => "1s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Dice stats", "desc" => "Number of natural ones rolled."),
"dice_natural_sixes" => array("short" => "6s","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Dice stats", "desc" => "Number of natural sixes rolled."),
"dice_target_sum" => array("short" => "dice_trg","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Dice stats", "desc" => "Sum of the total targets required."),
"dice_roll_sum" => array("short" => "dice_sum","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Dice stats", "desc" => "Sum of what was actually rolled (with above would be used to show averages)."),
"big_guy_stupidity_attempts" => array("short" => "big_stp_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Big Guy stats", "desc" => "Number of rolls for really stupid, bonehead, take root and wild animal."),
"big_guy_stupidity_successes" => array("short" => "big_stp","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Big Guy stats", "desc" => "Number of times the really stupid, bonehead, take root and wild animal roll succeeded."),
"big_guy_stupidity_blitz_attempts" => array("short" => "big_bltz_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Big Guy stats", "desc" => "Number of times this big guy declared a blitz"),
"big_guy_stupidity_blitz_successes" => array("short" => "big_bltz","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Big Guy stats", "desc" => "Number of times this big guy was able to blitz"),
"throw_team_mate_attempts" => array("short" => "TTM_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Big Guy stats", "desc" => "Number of attempts to throw a team mate by this player"),
"throw_team_mate_successes" => array("short" => "TTM","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Big Guy stats", "desc" => "Number of times this player successfully threw a team mate."),
"throw_team_mate_distance" => array("short" => "TTM_dist","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Big Guy stats", "desc" => "How far this player has thrown team mates in squares."),
"throw_team_mate_to_safe_landing" => array("short" => "TTM_landed","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Big Guy stats", "desc" => "Number of times this player successfully threw a team mate and the thrown player landed."),
"times_thrown" => array("short" => "RS_thrn","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Right Stuff stats", "desc" => "Number of times this player has been thrown"),
"landing_attempts" => array("short" => "RS_land_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Right Stuff stats", "desc" => "Number of times this player has attempted to land"),
"landings" => array("short" => "RS_land","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Right Stuff stats", "desc" => "Number of times this player successfully landed."),
"distance_thrown" => array("short" => "RS_dist","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Right Stuff stats", "desc" => "The distance this player has been thrown"),
"rushing_distance_thrown" => array("short" => "RS_rush_dist","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Right Stuff stats", "desc" => "The distance the ball progressed towards the end zone when this player was thrown (should be added to rushing distance total stat)"),
"bloodlust_rolls" => array("short" => "bldlst_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Vampire stats", "desc" => "Number of blood lust rolls"),
"bloodlust_successes" => array("short" => "bldlst","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Vampire stats", "desc" => "Number of times this player didn't succumb to blood lust."),
"bloodfeeds" => array("short" => "bldfed","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Vampire stats", "desc" => "Number of blood feeds by this vampire"),
"hypnoze_rolls" => array("short" => "hyp_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Vampire stats", "desc" => "Number of times hypnotic gaze was used"),
"hypnoze_successes" => array("short" => "hyp","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Vampire stats", "desc" => "Number of times hypnotic gaze was successful"),
"tentacles_rolls" => array("short" => "tent_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Skill stats", "desc" => "Number of times this player used his tentacles"),
"tentacles_successes" => array("short" => "tent","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Skill stats", "desc" => "Number of times this players successfully held another"),
"foul_appearance_rolls" => array("short" => "foul_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Skill stats", "desc" => "Number of times foul appearance was rolled"),
"foul_appearance_successes" => array("short" => "foul","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Skill stats", "desc" => "Number of times foul appearance succeeded"),
"dauntless_rolls" => array("short" => "dau_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Skill stats", "desc" => "Number of times dauntless was rolled"),
"dauntless_successes" => array("short" => "dau","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Skill stats", "desc" => "Number of times dauntless succeeded"),
"shadowing_rolls" => array("short" => "shad_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Skill stats", "desc" => "Number of times shadowing was attempted"),
"shadowing_successes" => array("short" => "shad","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Skill stats", "desc" => "Number of times shadowing succeeded"),
"bombs_throw_attempts" => array("short" => "bomb_a","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Bomb Throwing stats", "desc" => "Number of times a bomb throw attempts"),
"bombs_thrown" => array("short" => "bomb_t","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Bomb Throwing stats", "desc" => "Number of times a bomb was thrown"),
"sustained_bomb_effect" => array("short" => "sbo_ef","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Bomb Throwing stats", "desc" => "Number of times effected by a bomb"),
"sustained_bomb_stun" => array("short" => "sbo_st","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Bomb Throwing stats", "desc" => "Number of times stunned by a bomb"),
"sustained_bomb_ko" => array("short" => "sbo_ko","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Bomb Throwing stats", "desc" => "Number of times knocked out by a bomb"),
"sustained_bomb_bh" => array("short" => "sbo_bh","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Bomb Throwing stats", "desc" => "Number of times badly hurt by a bomb"),
"sustained_bomb_si" => array("short" => "sbo_si","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Bomb Throwing stats", "desc" => "Number of times seriously hurt by a bomb"),
"sustained_bomb_kill" => array("short" => "sbo_ki","type" => "MEDIUMINT SIGNED NOT NULL DEFAULT 0", "group" => "Bomb Throwing stats", "desc" => "Number of times killed by a bomb"),
);
$ES_commoncols = array_merge(array(
    # Node references
        # array() for compatibility.
    'f_trid' => array('type' => $CT_cols[T_NODE_TOURNAMENT]),
    'f_did'  => array('type' => $CT_cols[T_NODE_DIVISION]),
    'f_lid'  => array('type' => $CT_cols[T_NODE_LEAGUE]),
), $ES_fields);
$core_tables['mv_es_players'] = array(
    'f_pid' => $CT_cols[T_OBJ_PLAYER].' NOT NULL',
    'f_tid' => $CT_cols[T_OBJ_TEAM],
    'f_cid' => $CT_cols[T_OBJ_COACH],
    'f_rid' => $CT_cols[T_OBJ_RACE],
);
$core_tables['mv_es_teams'] = array(
    'f_tid' => $CT_cols[T_OBJ_TEAM].' NOT NULL',
    'f_cid' => $CT_cols[T_OBJ_COACH],
    'f_rid' => $CT_cols[T_OBJ_RACE],
);
$core_tables['mv_es_coaches'] = array(
    'f_cid' => $CT_cols[T_OBJ_COACH].' NOT NULL',
);
$core_tables['mv_es_races'] = array(
    'f_rid' => $CT_cols[T_OBJ_RACE].' NOT NULL',
);

$core_tables['match_data_es'] = array(
    'f_pid' => $CT_cols[T_OBJ_PLAYER].' NOT NULL',
    'f_tid' => $CT_cols[T_OBJ_TEAM],
    'f_cid' => $CT_cols[T_OBJ_COACH],
    'f_rid' => $CT_cols[T_OBJ_RACE],

    'f_mid' => $CT_cols[T_NODE_MATCH].' NOT NULL',
);

foreach (array('players', 'teams', 'coaches', 'races') as $tbl) {
    $idx = "mv_$tbl";
    $core_tables[$idx] = array_merge($core_tables[$idx], $mv_commoncols);
    $idx = "mv_es_$tbl";
    $core_tables[$idx] = array_merge($core_tables[$idx], array_map(create_function('$c', 'return $c["type"];'), $ES_commoncols));
}
// The ES equivalent to match_data.
$core_tables['match_data_es'] = array_merge($core_tables['match_data_es'], array_map(create_function('$c', 'return $c["type"];'), $ES_commoncols));


// Table structure references.
$relations_node = array(
    T_NODE_MATCH        => array('id' => 'match_id', 'parent_id' => 'f_tour_id', 'tbl' => 'matches'),
    T_NODE_TOURNAMENT   => array('id' => 'tour_id',  'parent_id' => 'f_did',     'tbl' => 'tours'),
    T_NODE_DIVISION     => array('id' => 'did',      'parent_id' => 'f_lid',     'tbl' => 'divisions'),
    T_NODE_LEAGUE       => array('id' => 'lid',      'parent_id' => null,        'tbl' => 'leagues'),
);
$relations_obj = array(
    T_OBJ_PLAYER => array('id' => 'player_id', 'parent_id' => 'owned_by_team_id',   'tbl' => 'players'),
    T_OBJ_STAR   => array('id' => 'star_id',   'parent_id' => null,                 'tbl' => 'game_data_stars'),
    T_OBJ_TEAM   => array('id' => 'team_id',   'parent_id' => 'owned_by_coach_id',  'tbl' => 'teams'),
    T_OBJ_COACH  => array('id' => 'coach_id',  'parent_id' => null,                 'tbl' => 'coaches'),
    T_OBJ_RACE   => array('id' => 'race_id',   'parent_id' => null,                 'tbl' => 'races'),
);

// Initial values of object properties
$objFields_init = array(
    T_OBJ_TEAM => array(
        'won' => 'won_0', 'lost' => 'lost_0', 'draw' => 'draw_0', 'played' => 'played_0',
        'wt_cnt' => 'wt_0', 'gf' => 'gf_0', 'ga' => 'ga_0',
    ),
);
// Object property extra (addition) fields
$objFields_extra = array(
    /*
        We don't include extra_skills since it's a string field.
        Neither extra_value since it's added to normal value field in the SQL core.
    */
    T_OBJ_PLAYER => array('spp' => 'extra_spp'),
);

// These object fields are averageable
$objFields_avg = array_keys(array_diff_key($mv_commoncols, array('f_trid'=>null,'f_did'=>null,'f_lid'=>null,'won'=>null,'lost'=>null,'draw'=>null,'played'=>null,'win_pct'=>null)));

// These fields are not summable!
// ie. you dont get the division/league value of these fields by summing over the related/underlying tournaments field's values.
$objFields_notsum = array('win_pct', 'swon', 'sdraw', 'slost');

function mysql_up($do_table_check = false) {

    // Brings up MySQL for use in PHP execution.

    global $db_host, $db_user, $db_passwd, $db_name; // From settings.php

    $conn = mysql_connect($db_host, $db_user, $db_passwd);

    if (!$conn)
        die("<font color='red'><b>Could not connect to the MySQL server.
            <ul>
                <li>Is the MySQL server running?</li>
                <li>Are the settings in <i>settings.php</i> correct?</li>
                <li>Is PHP set up correctly?</li>
            </ul></b></font>");

    if (!mysql_select_db($db_name))
        die("<font color='red'><b>Could not select the database '$db_name'</b><br><br>
            Please make sure that:
            <ul>
                <li>The database '$db_name' exists &mdash; you have to create it yourself!</li>
                <li>The database user '$db_user' can use the database '$db_name'.</li>
            </ul>
            Please confirm the settings in the <i>settings.php</i> file and try running the install script again.</font>");

    // Test if all tables exist.
    if ($do_table_check) {
        global $core_tables;
        $tables_expected = array_keys($core_tables);
        $tables_found = array();
        $query = "SHOW TABLES";
        $result = mysql_query($query);
        while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
            array_push($tables_found, $row[0]);
        }
        $tables_diff = array_diff($tables_expected, $tables_found);
        if (count($tables_diff) > 0) {
            die("<font color='red'><b>Could not find all the expected tables in database '$db_name'</b>.<br><br>Did you run the install/upgrade script?<br><br>
                Tables missing:<br><br><i>". implode('<br>', $tables_diff) ."</i>
                </font>");
        }
    }

    return $conn;
}

function get_alt_col($V, $X, $Y, $Z) {

    /*
     *  Get Alternative Column
     *
     *  $V = table
     *  $X = look-up column
     *  $Y = look-up value
     *  $Z = column to return value from.
     */

    $result = mysql_query("SELECT $Z FROM $V WHERE $X = '" . mysql_real_escape_string($Y) . "'");
    return (mysql_num_rows($result) > 0 && ($r = mysql_fetch_row($result))) ? $r[0] : null;
}

function get_alt_col_int($V, $X, $Y, $Z) {

    /*
     *  Get Alternative Column
     *
     *  $V = table
     *  $X = look-up column
     *  $Y = look-up value
     *  $Z = column to return value from.
     */

    $result = mysql_query("SELECT $Z FROM $V WHERE $X = $Y");
    return (mysql_num_rows($result) > 0 && ($r = mysql_fetch_row($result))) ? $r[0] : null;
}

function get_rows($tbl, array $getFields, $where = array()) {
    /*
        Useful for when wanting to quickly make objects with basic fields.

        Ex: Get all teams' name and ID:
            get_rows('teams', array('team_id', 'name'));
        ...will return an (unsorted) array of objects with the attributes 'team_id' and 'name', found in the teams table.
    */
    $query = 'SELECT '.(empty($getFields) ? '*' : implode(',', $getFields))." FROM $tbl ".(empty($where) ? '' : 'WHERE '.implode(' AND ', $where));
    $ret = array();
    if ($result = mysql_query($query)) {
        while ($row = mysql_fetch_object($result)) {
            $ret[] = $row;
        }
    }
    return $ret;
}

function get_parent_id($type, $id, $parent_type) {
    global $relations_node, $relations_obj;
    $relations = in_array($type, array_keys($relations_node)) ? $relations_node : $relations_obj;
    if ($type >= $parent_type)
        return null;
    # Don't include tables below $node OR above $parent_node OR parent_node table itself!
    list($zeroEntry) = array_keys($relations);
    $REL_trimmed = array_slice($relations, $type-$zeroEntry, $parent_type-$type);
    $REL_trimmed_padded = $REL_trimmed;
    $tables = array_map(create_function('$rl', 'return $rl["tbl"];'), $REL_trimmed);
    array_pop($REL_trimmed);
    array_shift($REL_trimmed_padded);
    $wheres = array_map(create_function('$rl,$rl_next', 'return "$rl[tbl].$rl[parent_id] = $rl_next[tbl].$rl_next[id]";'), $REL_trimmed, $REL_trimmed_padded);
    $query = 'SELECT '.$relations[$parent_type-1]['parent_id'].' AS "parent_id" FROM '.implode(',', $tables).' WHERE '.$relations[$type]['id']."=$id".((!empty($wheres)) ? ' AND '.implode(' AND ', $wheres) : '');
    $result = mysql_query($query);
    $row = mysql_fetch_assoc($result);
    return $row['parent_id'];
}

function get_parent_name($type, $id, $parent_type) {
    global $relations_node, $relations_obj;
    $relations = in_array($type, array_keys($relations_node)) ? $relations_node : $relations_obj;
    return get_alt_col($relations[$parent_type]['tbl'], $relations[$parent_type]['id'], get_parent_id($type,$id,$parent_type), 'name');
}

function get_list($table, $col, $val, $new_col) {
    $result = mysql_query("SELECT $new_col FROM $table WHERE $col = '$val'");
    if (mysql_num_rows($result) <= 0)
        return array();

    $row = mysql_fetch_assoc($result);
    return (empty($row[$new_col])) ? array() : explode(',', $row[$new_col]);
}

function set_list($table, $col, $val, $new_col, $new_val = array()) {
    $new_val = implode(',', $new_val);
    if (mysql_query("UPDATE $table SET $new_col = '$new_val' WHERE $col = '$val'"))
        return true;
    else
        return false;
}

function SQLFetchField($query) {
    $result = mysql_query($query);
    list($field) = ($result) ? mysql_fetch_row($result) : array(null);
    return $field;
}

function SQLBoolEval($query) {
    return (bool) SQLFetchField($query);
}

function setup_database() {

    global $core_tables;
    $conn = mysql_up();
    require_once('lib/class_sqlcore.php');

    // Create core tables.
    echo "<b>Creating core tables...</b><br>\n";
    foreach ($core_tables as $tblName => $def) {
        echo (Table::createTable($tblName, $def))
            ? "<font color='green'>OK &mdash; $tblName</font><br>\n"
            : "<font color='red'>FAILED &mdash; $tblName</font><br>\n";
    }

    // Create tables used by modules.
    echo "<b>Creating module tables...</b><br>\n";
    foreach (Module::createAllRequiredTables() as $module => $tables) {
        foreach ($tables as $name => $tblStat) {
            echo ($tblStat)
                ? "<font color='green'>OK &mdash; $name</font><br>\n"
                : "<font color='red'>FAILED &mdash; $name</font><br>\n";
        }
    }

    echo "<b>Other tasks...</b><br>\n";

    echo (SQLCore::syncGameData())
        ? "<font color='green'>OK &mdash; Synchronize game data with database</font><br>\n"
        : "<font color='red'>FAILED &mdash; Error whilst synchronizing game data with database</font><br>\n";

    echo (SQLCore::installTableIndexes())
        ? "<font color='green'>OK &mdash; applied table indexes</font><br>\n"
        : "<font color='red'>FAILED &mdash; could not apply one more more table indexes</font><br>\n";

    echo (SQLCore::installProcsAndFuncs(true))
        ? "<font color='green'>OK &mdash; created MySQL functions/procedures</font><br>\n"
        : "<font color='red'>FAILED &mdash; could not create MySQL functions/procedures</font><br>\n";

    // Create root user and leave welcome message on messageboard
	global $rootpass;
	$rootpass = isset($rootpass) ? $rootpass : 'root';
    echo (Coach::create(array('name' => 'root', 'realname' => 'root', 'passwd' => $rootpass, 'ring' => Coach::T_RING_GLOBAL_ADMIN, 'mail' => '', 'phone' => '', 'settings' => array(), 'def_leagues' => array())))
        ? "<font color=green>OK &mdash; root user created.</font><br>\n"
        : "<font color=red>FAILED &mdash; root user was not created.</font><br>\n";

    Message::create(array(
        'f_coach_id' => 1,
        'f_lid'      => Message::T_BROADCAST,
        'title'      => 'OBBLM installed!',
        'msg'        => 'Congratulations! You have successfully installed Online Blood Bowl League Manager. See "about" and "introduction" for more information.'));

    // Done!
    mysql_close($conn);
    return true;
}

function upgrade_database_to_version($toVersion, $fromVersion) {
    global $db_upgrade_options;
    
    require_once('lib/class_sqlcore.php');
    require_once('lib/mysql_upgrade_queries.php');
    
    $reversedUpgradeSqls = array_reverse($upgradeSQLs, true);
    $upgrading = false;
    
    foreach($reversedUpgradeSqls as $versionNumber => $sqlList) {
        if($versionNumber == $fromVersion || $versionNumber > $fromVersion)
            $upgrading = true;
        
        if($upgrading) {
            echo '<div>Upgrading to version ' . $versionNumber . '.</div>';
            upgrade_database($versionNumber, $db_upgrade_options, $upgradeSQLs);
        }

        if($versionNumber == $toVersion)
            $upgrading = false;
    }
}

function upgrade_database($version, $opts, $upgradeSQLs)
{
    switch ($version) {
        case '075-080':
            # Migrating position IDs correctly requires having loaded the correct LRB used in the v0.75 league.
            global $DEA, $stars, $skillarray; # Make global so that below include()s will overwrite their values.
            switch ($opts['lrb']) {
                case '5':  require('lib/game_data.php'); break; # Load LRB5
                case '6x': require('lib/game_data_lrb6x.php'); break; # Load LRB6x
                default: break; # LRB6 already loaded by default.
            }
            break;

        default:
            break;
    }

    // Modules
    echo "<b>Running SQLs for modules upgrade...</b><br>\n";
    foreach (Module::getAllUpgradeSQLs($version) as $modname => $SQLs) {
        if (empty($SQLs))
            continue;
        $status = true;
        foreach ($SQLs as $query) {
            $status &= (mysql_query($query) or die(mysql_error()));
        }
        echo ($status) ? "<font color='green'>OK &mdash; SQLs of $modname</font><br>\n" : "<font color='red'>FAILED &mdash; SQLs of $modname</font><br>\n";
    }

    // Core
    echo "<b>Running tasks for core system upgrade...</b><br>\n";

    if (isset($upgradeSettings[$version]) && $upgradeSettings[$version]['sync_gamedata']) {
        echo (SQLCore::syncGameData())
            ? "<font color='green'>OK &mdash; Synchronized game data with database</font><br>\n"
            : "<font color='red'>FAILED &mdash; Error whilst synchronizing game data with database</font><br>\n";
    }

    echo (SQLCore::installProcsAndFuncs(true))
        ? "<font color='green'>OK &mdash; created MySQL functions/procedures</font><br>\n"
        : "<font color='red'>FAILED &mdash; could not create MySQL functions/procedures</font><br>\n";

	if (isset($upgradeSQLs[$version])) {
		$core_SQLs = $upgradeSQLs[$version];
		$status = true;
		foreach ($core_SQLs as $query) { $status &= (mysql_query($query) or die(mysql_error()."\n<br>SQL:\n<br>---\n<br>".$query));}
		$cnt = "(".count($core_SQLs)." total)";
	    echo ($status) ? "<font color='green'>OK &mdash; Core SQLs</font> $cnt<br>\n" : "<font color='red'>FAILED &mdash; Core SQLs</font> $cnt<br>\n";
    }

	if (isset($upgradeFuncs[$version])) {
		$core_Funcs = $upgradeFuncs[$version];
		$status = true;
		foreach ($core_Funcs as $func) { $status &= call_user_func($func);}
		echo ($status) ? "<font color='green'>OK &mdash; Custom PHP upgrade code (<i>".implode(', ',$core_Funcs)."</i>)</font><br>\n" : "<font color='red'>FAILED &mdash; Custom PHP upgrade code</font><br>\n";
    }

    if (isset($upgradeSettings[$version]) && $upgradeSettings[$version]['syncall']) {
        echo (SQLCore::installMVs())
            ? "<font color='green'>OK &mdash; created MV tables</font><br>\n"
            : "<font color='red'>FAILED &mdash; could not create MV tables</font><br>\n";
            
        list($status,$added,$dropped) = SQLCore::reviseEStables();
        echo ($status)
            ? "<font color='green'>OK &mdash; create/update ES tables</font><br>\n" . '<!-- DEV. INFO: Added new cols: '.implode(', ', $added).'. Removed cols: '.implode(', ', $dropped).'.-->'
            : "<font color='red'>FAILED &mdash; create/update ES tables</font><br>\n";
    }

    if (isset($upgradeSettings[$version]) && $upgradeSettings[$version]['reload_indexes']) {
        echo (SQLCore::installTableIndexes())
            ? "<font color='green'>OK &mdash; applied table indexes</font><br>\n"
            : "<font color='red'>FAILED &mdash; could not apply one more more table indexes</font><br>\n";
    }

    switch ($version) {
        case '075-080':
            # Convert league to LRB6.
            global $DEA, $stars, $skillarray; # Make global so that below include()s will overwrite their values.
            require('lib/game_data_lrb6.php'); # Load LRB6.
            SQLCore::syncGameData();

        default:
            break;
    }

    if (isset($upgradeSettings[$version]) && $upgradeSettings[$version]['syncall']) {
        echo (mysql_query("CALL syncAll()"))
            ? "<font color='green'>OK &mdash; synchronised all dynamic stats and properties</font><br>\n"
            : "<font color='red'>FAILED &mdash; could not synchronise all dynamic stats and properties</font><br>\n";
    }
    
    // Done!
    return isset($upgradeMsgs[$version]) ? $upgradeMsgs[$version] : '' ;
}

/*
    Provides helper/shortcut-routines for writing upgrade SQL code.
*/

class SQLUpgrade
{
    const NONE = 'SELECT \'1\'';

    public static function doesColExist($tbl, $col)
    {
        global $db_name;
        $colCheck = "SELECT EXISTS(SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db_name' AND COLUMN_NAME='$col' AND TABLE_NAME='$tbl') AS 'exists'";
        $result = mysql_query($colCheck);
        $row = mysql_fetch_assoc($result);
        return (bool) $row['exists'];
    }

    public static function runIfColumnNotExists($tbl, $col, $query)
    {
        return self::doesColExist($tbl, $col) ? self::NONE : $query;
    }

    // EXACTLY like runIfColumnNotExists(), but has the logic reversed at the return statement.
    public static function runIfColumnExists($tbl, $col, $query)
    {
        return self::doesColExist($tbl, $col) ? $query : self::NONE;
    }

    public static function runIfTrue($evalQuery, $query)
    {
        $result = mysql_query($evalQuery);
        if (!$result || mysql_num_rows($result) == 0) {
            return self::NONE;
        }
        $row = mysql_fetch_row($result);
        return ((int) $row[0]) ? $query : self::NONE;
    }
    
    public static function getCurrentDatabaseVersion() {
        if(!self::doesColExist('version', 'version'))
            return false;
        
        $result = mysql_query("SELECT version from version");
        if (!$result || mysql_num_rows($result) == 0)
            return false;

        $row = mysql_fetch_row($result);
        return (int) $row[0];
    }
    
    public static function updateDatabaseVersion($version) {
        // Drop table and create
        Table::createTable('version', array('version' => 'MEDIUMINT UNSIGNED NOT NULL'));
        return "INSERT INTO version (version) VALUES ($version)";
    }
}

