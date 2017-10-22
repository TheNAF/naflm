<?php

/*
 *  Copyright (c) William Leonard <email protected> 2009. All Rights Reserved.
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

class UPLOAD_BOTOCS implements ModuleInterface
{
    /***************
     * Properties 
     ***************/

    public $userfile = array();
    public $xmlresults = '';
    public $replay;
    public $error = '';
    public $tour_id = 0;
    public $coach_id = '';

    //Parsed values
    public $winner = '';
    public $concession = false;
    public $gate = 0;
    public $hash = '';
    public $hometeam = '';
        public $homescore = 0;
        public $homewinnings = 0;
        public $homeff = 0;
        public $homefame = 0;
        public $hometransferedGold = 0;
        public $homeplayers;
        public $tv_home = 0;
        public $homerotters = 0;
    public $awayteam = '';
        public $awayscore = 0;
        public $awaywinnings = 0;
        public $awayff = 0;
        public $awayfame = 0;
        public $awaytransferedGold = 0;
        public $awayplayers;
        public $tv_away = 0;
        public $awayrotters = 0;

    public $hometeam_id = 0;
    public $awayteam_id = 0;
    public $match_id = 0;

    public $revUpdate = false;
    public $extrastats = false;

    public $reporttype = "botocs";

    public $success = "";


    function __construct($userfile, $tour_id, $coach_id, $reroll) {

        global $settings;
        $this->extrastats = $settings['leegmgr_extrastats'];
        $status = true;
        libxml_use_internal_errors(true);
        $this->userfile = $userfile;
        $this->tour_id = $tour_id;
        $this->coach_id = $coach_id;

        if ( !$this->processFile() ) return false;
        if ( $this->reporttype == "botocs" )
        {
            if ( !$this->valXML() ) return false;
            if ( !$this->parse_results() ) return false;
        }
        else if ( $this->reporttype == "cyanide" )
        {
            if ( !$this->parse_cy_results() ) return false;
            if ( $this->winner && !$this->checkCoach($this->winner) )
            {
                $this->error = "You must be the winner to upload the match.";
                return false;
            }
            if ( $reroll && $this->checkCoach($this->winner) )
            {
                if ( $this->winner == $this->hometeam && $reroll > ($this->homewinnings / 10000 - $this->homefame - 1) )
                {
                    $oldwinnings = $this->homewinnings;
                    $this->homewinnings = (rand(1,6) + $this->homefame + 1) * 10000;
                    $this->success .= "Rerolled old winnings of ".$oldwinnings." to ".$this->homewinnings;
                }
                else if ( $reroll > ($this->awaywinnings / 10000 - $this->awayfame - 1) )
                {
                    $oldwinnings = $this->awaywinnings;
                    $this->awaywinnings = (rand(1,6) + $this->awayfame + 1) * 10000;
                    $this->success .= "Rerolled old winnings of ".$oldwinnings." to ".$this->awaywinnings;
                }
            }
        }

        if ( !$this->checkCoach ( $this->hometeam ) && !$this->checkCoach ( $this->awayteam ) )
        {
            $this->error = "You must be the owner of one of the teams in the report to upload a match.";
            return false;
        }

        $this->hometeam_id= $this->checkTeam ( $this->hometeam );
        $this->awayteam_id=$this->checkTeam ( $this->awayteam );
        if ( !$this->hometeam_id || !$this->awayteam_id )
        {
            $this->error = "One of the teams was not found on the site.";
            return false;
        }
        if ( !$this->checkHash ( $this->hash ) ) return false;
        if ( !$this->addMatch () )
        {
            return false;
        }
        $team_home = new Team( $this->hometeam_id );
        $this->tv_home = $team_home->value;
        $team_away = new Team( $this->awayteam_id );
        $this->tv_away = $team_away->value;

        if ( $this->tour_id == -1 && $this->match_id < 1 )
        {
            $this->tour_id = $this->get_tour_id();
            if ( !$this->get_tour_id() )
            {
                $this->error = "The Free for All tournament id could not be retrieved.";
                return false;
            }
        }

        $this->checkSpiralingExpenses();

        if ( !$this->updateMatch () )
        {
            $this->error = "Failed to update the match.";
            return false;
        }

        if ( !$this->matchEntry ( $this->hometeam_id, $this->homeplayers ) ) return false;
        if ( !$this->matchEntry ( $this->awayteam_id, $this->awayplayers ) ) return false;

        foreach (array(0 => 'home', 1 => 'away') as $N => $team) {
            if ( isset($this->{"${team}rotters"}) && $this->{"${team}rotters"} > 0 )
            {
                $i = 0;
                while ( $i < $this->{"${team}rotters"} && !${"team_$team"}->isFull() )
                {
                    $this->createRotter( $this->{"${team}team_id"}, ${"team_$team"}->f_rname, 90+$i );
                    $i++;
                }
            }
        }

$query = "UPDATE matches SET
tcas2 = IF(
        IFNULL((SELECT SUM(sustained_bhs+sustained_sis+sustained_kill) FROM match_data_es WHERE f_tid = team1_id AND f_mid = $this->match_id),0)=0,

        (SELECT SUM(bh+ki+si) FROM match_data WHERE f_team_id = team2_id AND f_match_id = $this->match_id),
        (SELECT SUM(sustained_bhs+sustained_sis+sustained_kill) FROM match_data_es WHERE f_tid = team1_id AND f_mid = $this->match_id)
    ),
tcas1 = IF(
        IFNULL((SELECT SUM(sustained_bhs+sustained_sis+sustained_kill) FROM match_data_es WHERE f_tid = team2_id AND f_mid = $this->match_id),0)=0,

        (SELECT SUM(bh+ki+si) FROM match_data WHERE f_team_id = team1_id AND f_match_id = $this->match_id),
        (SELECT SUM(sustained_bhs+sustained_sis+sustained_kill) FROM match_data_es WHERE f_tid = team2_id AND f_mid = $this->match_id)
    )
WHERE match_id = $this->match_id";


        if ( !mysql_query( $query ) )
        {
            $this->error = "Failed to report the TCAS.  Error: ".mysql_error();
            return false;
        }

        $match = new Match( $this->match_id );
        $match->finalizeMatchSubmit(); # Must be run AFTER ALL match data has been submitted. This syncs stats.
        $match->setLocked(true);

        //Begin add replay
        $query = "UPDATE leegmgr_matches SET replay = \"$this->replay\" WHERE mid = $this->match_id";

        if ( !mysql_query( $query ) )
        {
            $this->error = "Failed to upload the replay file with the following error: ".mysql_error();
            return false;
        }
        //End add replay
        return true;

    }

    function parse_results() {

        global $settings;
        if ( !$settings['leegmgr_botocs'] )
        {
            $this->error = "BOTOCS match reports are not permitted in this league.";
            return false;
        }

        global $ES_fields; # Used by EPS.

        // These are the general stats fields required by OBBLM:
        $reqStats = array(
            # Format: 'obblm_name' => 'parsed_name'
            'mvp'   => 'mvp',
            'cp'    => 'completion',
            'td'    => 'touchdown',
            'intcpt'=> 'interception',
            'bh'    => $this->extrastats ? 'inflicted_bh_spp_casualties'   : 'casualties',
            'si'    => $this->extrastats ? 'inflicted_si_spp_casualties'   : 'INVALID', # INVALID will fail as obj. prop. below and set field = 0.
            'ki'    => $this->extrastats ? 'inflicted_kill_spp_casualties' : 'INVALID', # INVALID will fail as obj. prop. below and set field = 0.
            #'ir_d1' => 'improvement_roll1',
            #'ir_d2' => 'improvement_roll2',
        );

        // Start!
        $results =  simplexml_load_string( $this->xmlresults );
        $this->hash = md5($this->xmlresults);
        $this->gate = 0; # Initialize it.
        $this->winner = strval($results->winner);

        foreach (array(0 => 'home', 1 => 'away') as $N => $team) {
            
            // Team properties
            $this->gate += $results->team[$N]->fans;
            $this->{"${team}team"}      = strval($results->team[$N]->attributes()->name);
            $this->{"${team}score"}     = intval($results->team[$N]->score);
            $this->{"${team}winnings"}  = $results->team[$N]->winnings - $results->team[$N]->transferedGold;
            $this->{"${team}ff"}        = (int) $results->team[$N]->fanfactor;
            $this->{"${team}fame"}      = (int) $results->team[$N]->fame;
            $this->{"${team}rotters"}      = (int) $results->team[$N]->attributes()->rotters;
            #$this->{"${team}transferedGold"} = (int) $results->team[$N]->transferedGold;
            
            // Player properties
            $players = array();
            foreach ($results->team[$N]->players->player as $p)
            {
                $nr = intval($p->attributes()->number);
                $players[$nr]['nr']     = $p->attributes()->number;
                $players[$nr]['name']   = $p->attributes()->name;
                $players[$nr]['star']   = addslashes($p->attributes()->starPlayer);
                $players[$nr]['merc']   = $p->attributes()->mercenary;

                $players[$nr]['ir1_d1']    = ( isset($p->improvement_roll1->roll1[0]) ) ? $p->improvement_roll1->roll1[0] : 0;
                $players[$nr]['ir1_d2']    = ( isset($p->improvement_roll2->roll2[0]) ) ? $p->improvement_roll2->roll2[0] : 0;
                $players[$nr]['ir2_d1']    = ( isset($p->improvement_roll1->roll1[1]) ) ? $p->improvement_roll1->roll1[1] : 0;
                $players[$nr]['ir2_d2']    = ( isset($p->improvement_roll2->roll2[1]) ) ? $p->improvement_roll2->roll2[1] : 0;
                $players[$nr]['ir3_d1']    = ( isset($p->improvement_roll1->roll1[2]) ) ? $p->improvement_roll1->roll1[2] : 0;
                $players[$nr]['ir3_d2']    = ( isset($p->improvement_roll2->roll2[2]) ) ? $p->improvement_roll2->roll2[2] : 0;

                $players[$nr]['inj']    = $p->injuries->injury[0];
                $players[$nr]['agn1']   = $p->injuries->injury[1];
                //This adds in any SPP casualties that may not have been reported on
                $total_spp_cas = $p->inflicted_bh_spp_casualties + $p->inflicted_si_spp_casualties + $p->inflicted_kill_spp_casualties;
                if ($p->casualties > $total_spp_cas )
                    $p->inflicted_bh_spp_casualties = $p->inflicted_bh_spp_casualties + ($p->casualties - $total_spp_cas);

                foreach ($reqStats as $name_OBBLM => $name_BOTOCS) {
                    $players[$nr][$name_OBBLM] = (isset($p->$name_BOTOCS) ? (int) $p->$name_BOTOCS : 0);
                }
                # Cut out the fields EPS wants and add them as a player "property", which we later pass as the second argument to $match->entry() like so:
                $players[$nr]['EPS'] = ($this->extrastats) ? array_intersect_key((array) $p, $ES_fields) : array();
            }
            $this->{"${team}players"} = $players; # Assign proccessed players.
        }
        
        // Check winner and concession to change the score to 2 to 0 in favor of the team that did not concede.
        if ($this->concession = $results->winner->attributes()->concession) {
            if ( $this->winner == $this->hometeam && $this->homescore <= $this->awayscore )
                $this->homescore = $this->homescore + $this->awayscore - $this->homescore +1;
            if ( $this->winner == $this->awayteam && $this->awayscore <= $this->homescore )
                $this->awayscore = $this->awayscore + $this->homescore - $this->awayscore +1;
        }
        
        return true;
    }

    function parse_cy_results() {

        global $settings;
        if ( !$settings['leegmgr_cyanide'] )
        {
            $this->error = "Cyanide match reports are not permitted in this league.";
            return false;
        }
        include('cyanide/lib_cy_match_db.php');

        #Create a temporary file name that the match report file can be written to.
        $temp_path = sys_get_temp_dir();
        $tempname = tempnam($temp_path, "");

        #Open and write the retrieved ZIP file to the temporary file.
        $f_r = fopen($tempname, 'w+');
        fwrite($f_r, $this->xmlresults);
        fseek($f_r, 0);
        fclose($f_r);

        $cy_parse = new cy_match_db($tempname);

        // Start!
        $this->hash = md5($this->xmlresults);
        $this->gate = 0; # Initialize it.
        $this->winner = $cy_parse->winner;

        foreach (array(0 => 'home', 1 => 'away') as $N => $team) {
            
            // Team properties
            $this->gate += $cy_parse->{"${team}fans"}; #{"${team}team"}
            $this->{"${team}team"}      = $cy_parse->{"${team}team"};
            $this->{"${team}score"}     = $cy_parse->{"${team}score"};
            $this->{"${team}winnings"}  = $cy_parse->{"${team}winnings"};
            $this->{"${team}ff"}        = (int) $cy_parse->{"${team}ff"};
            $this->{"${team}fame"}      = (int) $cy_parse->{"${team}fame"};
            // Player properties
            $this->{"${team}players"} = $cy_parse->{"${team}players"};
        }
        
        // Check winner and concession to change the score to 2 to 0 in favor of the team that did not concede.
        if ($this->concession = $cy_parse->concession) {
            if ( $this->winner == $this->hometeam && $this->homescore <= $this->awayscore )
                $this->homescore = $this->homescore + $this->awayscore - $this->homescore +1;
            if ( $this->winner == $this->awayteam && $this->awayscore <= $this->homescore )
                $this->awayscore = $this->awayscore + $this->homescore - $this->awayscore +1;
        }

        return true;
    }

    function addMatch () {

        global $settings;

        if ( $settings['leegmgr_schedule'] ) {
            $schmatch_id = $this->getschMatch();    //match id where the home team is the same for OBBLM and the client
            $revmatch_id = $this->getschMatchRev(); //match id where the match is reversed
            if ( !$schmatch_id && !$revmatch_id ) {
                $this->match_id = 0; 
            }
            else if ( !$schmatch_id || !$revmatch_id ) {
                $this->match_id = max($schmatch_id, $revmatch_id);
                if ( $revmatch_id ) $this->revUpdate = true;                
            }
            else if ( $schmatch_id && $revmatch_id ) {
                $this->match_id = min($schmatch_id, $revmatch_id);
                if ( $revmatch_id < $schmatch_id ) $this->revUpdate = true;
            }
        }
        if ( $this->chkAltSchedule() && !$this->match_id )
        {
            $this->error = "One of the teams has another match scheduled.";
            return false;
        }

        if ( !$this->match_id && $settings['leegmgr_schedule'] !== 'strict' ) {
            list($exitStatus, $this->match_id) = Match_BOTOCS::create( $input = array("team1_id" => $this->hometeam_id, "team2_id" => $this->awayteam_id, "round" => 1, "f_tour_id" => $this->tour_id, "hash" => $this->hash ) );
            if ($exitStatus) {
                $this->error = Match::$T_CREATE_ERROR_MSGS[$exitStatus];
                return false;
            }
        }

        if ( $this->match_id < 1 ) return false;

        $match = new Match_BOTOCS($this->match_id);
        $match->setBOTOCSHash($this->hash);

        return true;
    }

    function matchEntry ( $team_id, $teamPlayers ) {

        $addZombie = false;
        $match = new Match( $this->match_id );

        $team = new Team( $team_id );
        $players = $team->getPlayers();
        $merc_nr = 1;

        foreach ( $teamPlayers as $player )
        {
            $f_player_id = '';
            if ( $player['nr'] == 100 ) $addZombie = true;  //Must add zombie last so that dead players can be reported first.
            else $addZombie = false;
            if ( $player['star'] == "true" )
            {
                global $stars;
                $stname = strval($player['name']);
                if ( strpos ( $stname, "Morg " ) ===  0 ) $stname = "Morg 'n' Thorg";
                if ( strpos ( $stname, "Brick Far" ) === 0 ) $stname = "Brick Far'th (+ Grotty)";
                if ( $stname == "Grotty" ) $stname = "Grotty (included in Brick Far'th)";

                $f_player_id = $stars[$stname]['id'];
                $player['inj'] = '';
            }

            if ( $player['merc'] == "true" )
            {
                $f_player_id = ID_MERCS;
            }

            foreach ( $players as $p  )
            {
                if ( $p->nr == $player['nr'] && Match::player_validation($p, $match) && !$f_player_id ) {
                    $f_player_id = $p->player_id;
                    break;
                }
            }

            // Make $player[$f] into $$f. 
            foreach (array('mvp', 'cp', 'td', 'intcpt', 'bh', 'ki', 'si') as $f) {
                if (array_key_exists($f, $player)) {
                    $$f = $player[$f]; # NOTE: These fields are validated and typecasted correctly already in parse_results(), no further processing needed.
                    if (empty($$f)) {
                        $$f = 0;
                    }
                }
                else {
                    $$f = 0;
                }
            }

            $inj = $this->switchInjury ( $player['inj'] );

            $agn1 = $this->switchInjury ( $player['agn1'] );
            if ( $agn1 > $inj ) list($inj, $agn1) = array($agn1, $inj);
            if ( $agn1 == 8 || $agn1 == 2 ) $agn1 = 1;

            if ( $f_player_id == ID_MERCS )
            {
                $match->entry( 
                    $f_player_id,
                    $input = array ( 
                        'f_team_id' => $team_id,
                        "mvp" => $mvp, "cp" => $cp, "td" => $td, "intcpt" => $intcpt, "bh" => $bh, "si" => $si, "ki" => $ki, 
                        #"ir_d1" => $ir_d1, "ir_d2" => $ir_d2,
                        "ir1_d1" => $player['ir1_d1'], "ir1_d2" => $player['ir1_d2'], "ir2_d1" => $player['ir2_d1'], "ir2_d2" => $player['ir2_d2'], "ir3_d1" => $player['ir3_d1'], "ir3_d2"=> $player['ir3_d2'],
                        "inj" => $inj, "agn1" => NONE, "agn2" => $agn1,
                        "skills" => 0, "nr" => $merc_nr,),
                    $player['EPS']
                );
                $merc_nr++;
                continue;
            }

            if ( !$addZombie && ( Match::player_validation($p, $match) || $player['star'] == "true" ) )
                $match->entry( 
                    $f_player_id,
                    $input = array ( 
                        'f_team_id' => $team_id,
                        "mvp" => $mvp, "cp" => $cp, "td" => $td, "intcpt" => $intcpt, "bh" => $bh, "si" => $si, "ki" => $ki, 
                        #"ir_d1" => $ir_d1, "ir_d2" => $ir_d2,
                        "ir1_d1" => $player['ir1_d1'], "ir1_d2" => $player['ir1_d2'], "ir2_d1" => $player['ir2_d1'], "ir2_d2" => $player['ir2_d2'], "ir3_d1" => $player['ir3_d1'], "ir3_d2"=> $player['ir3_d2'],
                        "inj" => $inj, "agn1" => $agn1, "agn2" => NONE,),
                    $player['EPS']
                );
            else
            {
                global $DEA;
                $pos_id = $DEA[$team->f_rname]['players']['Zombie']['pos_id'];
                list($exitStatus, $pid) = Player::create(
                    $input = array(
                        'nr' => $player['nr'], 
                        'f_pos_id' => $pos_id, 
                        'team_id' => $team_id, 
                        'name' => $player['name'], 
                    ),
                    $opts = array(
                        'free' => true,
                        'force' => true,
                    )
                );

                if ( $exitStatus == Player::T_CREATE_SUCCESS )
                {
                    $match->entry( 
                        $pid,
                        $input = array ( 
                            'f_team_id' => $team_id,
                            "mvp" => $mvp, "cp" => $cp, "td" => $td, "intcpt" => $intcpt, "bh" => $bh, "si" => $si, "ki" => $ki, 
                            #"ir_d1" => $ir_d1, "ir_d2" => $ir_d2,
                            "ir1_d1" => $player['ir1_d1'], "ir1_d2" => $player['ir1_d2'], "ir2_d1" => $player['ir2_d1'], "ir2_d2" => $player['ir2_d2'], "ir3_d1" => $player['ir3_d1'], "ir3_d2"=> $player['ir3_d2'],
                            "inj" => $inj, "agn1" => $agn1, "agn2" => NONE ),
                        $player['EPS']
                    );
                }
            }
        }

        ##ADD EMPTY RESULTS FOR PLAYERS WITHOUT RESULTS MAINLY FOR MNG

        foreach ( $players as $p  )
        {
            if ( Match::player_validation($p, $match) ) {
                $player = new Player ( $p->player_id );
                $p_matchdata = $match->getPlayerEntry( $player->player_id );
                if ( empty($p_matchdata) ) {
                    $match->entry(
                        $p->player_id,
                        $input = array ( 
                            'f_team_id' => $team_id,
                            "mvp" => 0, "cp" => 0,"td" => 0,"intcpt" => 0,"bh" => 0,"si" => 0,"ki" => 0, 
                            #"ir_d1" => 0, "ir_d2" => 0,
                            "ir1_d1" => 0, "ir1_d2" => 0, "ir2_d1" => 0, "ir2_d2" => 0, "ir3_d1" => 0, "ir3_d2"=> 0,
                            "inj" => NONE, "agn1" => NONE, "agn2" => NONE ), 
                        array() # No EPS!
                    );
                }
            }
        }

        return true;

    }

    function updateMatch () {

        $match = new Match( $this->match_id );
        if (!$this->revUpdate) $match->update( $input = array("submitter_id" => $this->coach_id, "stadium" => $this->hometeam_id, "gate" => $this->gate, "fans" => 0, "ffactor1" => $this->homeff, "ffactor2" => $this->awayff, "fame1" => $this->homefame, "fame2" => $this->awayfame, "income1" => $this->homewinnings, "income2" => $this->awaywinnings, "team1_score" => $this->homescore, "team2_score" => $this->awayscore, "smp1" => 0, "smp2" => 0, "tcas1" => 0, "tcas2" => 0, "tv1" => $this->tv_home, "tv2" => $this->tv_away,) );
        else $match->update( $input = array("submitter_id" => $this->coach_id, "stadium" => $this->hometeam_id, "gate" => $this->gate, "fans" => 0, "ffactor2" => $this->homeff, "ffactor1" => $this->awayff, "fame2" => $this->homefame, "fame1" => $this->awayfame, "income2" => $this->homewinnings, "income1" => $this->awaywinnings, "team2_score" => $this->homescore, "team1_score" => $this->awayscore, "smp1" => 0, "smp2" => 0, "tcas1" => 0, "tcas2" => 0, "tv2" => $this->tv_home, "tv1" => $this->tv_away,) );

        return true;
    }

    function checkCoach ( $team ) {

        $query = sprintf("SELECT owned_by_coach_id FROM teams WHERE owned_by_coach_id = '%s' and name = '%s' ", mysql_real_escape_string($this->coach_id), mysql_real_escape_string($team) );

        if ( !mysql_fetch_array( mysql_query( $query ) ) )
        {
            return false;
        }

        return true;

    }

    function checkHash () {

        $query = sprintf("SELECT hash FROM leegmgr_matches WHERE hash = '%s' ", mysql_real_escape_string($this->hash) );
        $hashresults = mysql_query($query);
        $hashresults = mysql_fetch_array($hashresults);
        $hashresults = $hashresults['hash'];

        if ( $hashresults == $this->hash ) {
            $this->error = "Unique match id already exists: ".$this->hash;
            return false;
        }

        return true;

    }

    function checkTeam ( $teamname ) {

        $query = sprintf("SELECT team_id FROM teams WHERE name = '%s' ", mysql_real_escape_string($teamname) );
        $team_id = mysql_query($query);

        if (!$team_id) return false;

        $team_id = mysql_fetch_array($team_id);
        $team_id = $team_id['team_id'];

        return $team_id;

    }

    function getschMatch() {

        $team_id1 = $this->hometeam_id;
        $team_id2 = $this->awayteam_id;

        $query = "SELECT match_id FROM matches WHERE submitter_id IS NULL AND ( team1_id = $team_id1 ) AND  ( team2_id = $team_id2 ) ORDER BY match_id ASC";

        $match_id = mysql_query($query);
        $match_id = mysql_fetch_array($match_id);
        $match_id = $match_id['match_id'];

        return $match_id;

    }

    function getschMatchRev() {

        $team_id2 = $this->hometeam_id;
        $team_id1 = $this->awayteam_id;

        $query = "SELECT match_id FROM matches WHERE submitter_id IS NULL AND ( team1_id = $team_id1 ) AND  ( team2_id = $team_id2 ) ORDER BY match_id ASC";

        $match_id = mysql_query($query);
        $match_id = mysql_fetch_array($match_id);
        $match_id = $match_id['match_id'];

        return $match_id;

    }

    function chkAltSchedule() {

        $team_id1 = $this->hometeam_id;
        $team_id2 = $this->awayteam_id;

        $query = "SELECT match_id FROM matches WHERE submitter_id IS NULL AND ( ( team1_id = $team_id1 ) OR  ( team1_id = $team_id2 ) OR  ( team2_id = $team_id1 ) OR ( team2_id = $team_id2 ) )";

        $match_id = mysql_query($query);
        $match_id = mysql_fetch_array($match_id);
        $match_id = $match_id['match_id'];

        return $status = ($match_id > 0) ? true : false;

    }

    function switchInjury ( $inj ) {

        switch ( $inj ) {
            case NULL:
                $injeffect = NONE;
                break;
            case "Miss Next Game":
                $injeffect = MNG;
                break;
            case "Niggling Injury":
                $injeffect = NI;
                break;
            case "-1 MA":
                $injeffect = MA;
                break;
            case "-1 AV":
                $injeffect = AV;
                break;
            case "-1 AG":
                $injeffect = AG;
                break;
            case "-1 ST":
                $injeffect = ST;
                break;
            case "Dead":
                $injeffect = DEAD;
                break;
            default:
                $injeffect = NONE;
                break;
        }

        return $injeffect;

    }

    function checkSpiralingExpenses () {
        $team_home = new Team( $this->hometeam_id );
        $tv_home = $team_home->value;
        $team_away = new Team( $this->awayteam_id );
        $tv_away = $team_away->value;
        //Spiraling Expenses
        switch ( $tv_home ) {
            case ( $tv_home >= 1750000 && $tv_home <= 1890000 ):
                $this->homewinnings -= 10000;
                break;
            case ( $tv_home >= 1900000 && $tv_home <= 2040000 ):
                $this->homewinnings -= 20000;
                break;
            case ( $tv_home >= 2050000 && $tv_home <= 2190000 ):
                $this->homewinnings -= 30000;
                break;
            case ( $tv_home >= 2200000 && $tv_home <= 2340000 ):
                $this->homewinnings -= 40000;
                break;
            case ( $tv_home >= 2350000 && $tv_home <= 2490000 ):
                $this->homewinnings -= 50000;
                break;
            case ( $tv_home >= 2500000 && $tv_home <= 2640000 ):
                $this->homewinnings -= 60000;
                break;
            case ( $tv_home >= 2650000 && $tv_home <= 2790000 ):
                $this->homewinnings -= 70000;
                break;
            case ( $tv_home >= 2800000 && $tv_home <= 2940000 ):
                $this->homewinnings -= 80000;
                break;
            case ( $tv_home > 2950000 && $tv_home <= 3090000 ):
                $this->homewinnings -= 90000;
                break;
            case ( $tv_home > 3100000 ):
                $this->homewinnings -= 100000;
                break;
        }
        switch ( $tv_away ) {
            case ( $tv_away >= 1750000 && $tv_away <= 1890000 ):
                $this->awaywinnings -= 10000;
                break;
            case ( $tv_away >= 1900000 && $tv_away <= 2040000 ):
                $this->awaywinnings -= 20000;
                break;
            case ( $tv_away >= 2050000 && $tv_away <= 2190000 ):
                $this->awaywinnings -= 30000;
                break;
            case ( $tv_away >= 2200000 && $tv_away <= 2340000 ):
                $this->awaywinnings -= 40000;
                break;
            case ( $tv_away >= 2350000 && $tv_away <= 2490000 ):
                $this->awaywinnings -= 50000;
                break;
            case ( $tv_away >= 2500000 && $tv_away <= 2640000 ):
                $this->awaywinnings -= 60000;
                break;
            case ( $tv_away >= 2650000 && $tv_away <= 2790000 ):
                $this->awaywinnings -= 70000;
                break;
            case ( $tv_away >= 2800000 && $tv_away <= 2940000 ):
                $this->awaywinnings -= 80000;
                break;
            case ( $tv_away > 2950000 && $tv_away <= 3090000 ):
                $this->awaywinnings -= 90000;
                break;
            case ( $tv_away > 3100000 ):
                $this->awaywinnings -= 100000;
                break;
        }
    }

    function createRotter($team_id, $race, $nr)
    {
        global $DEA;
        $pos_id = $DEA[$race]['players']['Rotter']['pos_id'];

        list($exitStatus, $pid) = Player::create(
            $input = array(
                'nr' => $nr, 
                'f_pos_id' => $pos_id, 
                'team_id' => $team_id, 
                'name' => "Rotter".$nr, 
            ),
            $opts = array(
                'free' => true,
                'force' => true,
            )
        );

        if ( !$exitStatus == Player::T_CREATE_SUCCESS )
        {
            $this->error = "Failed to add a rotter to the roster.";
            return false;
        }

        return true;
    }


    function libxml_display_error($error)
    {
        $return = "<br/>\n";
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "<b>Warning $error->code</b>: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "<b>Error $error->code</b>: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "<b>Fatal Error $error->code</b>: ";
                break;
        }

        $return .= trim($error->message);
        if ($error->file) $return .= " in <b>$error->file</b>";

        $return .= " on line <b>$error->line</b>\n";

        return $return;
    }

    function libxml_display_errors() {
        $errors = libxml_get_errors();

        foreach ($errors as $error) {
            $this->error .= $this->libxml_display_error($error);
        }

        libxml_clear_errors();
    }

    // Enable user error handling
    #libxml_use_internal_errors(true);

    function valXML() {

        $xsdfile = ($this->extrastats) ? 'modules/leegmgr/botocsreport_extra.xsd' : 'modules/leegmgr/botocsreport.xsd';
        $tmpfname = tempnam("/tmp", "XML");

        $handle = fopen($tmpfname, "w");
        fwrite($handle, $this->xmlresults);
        fclose($handle);

        $xml = new DOMDocument();
        $xml->load($tmpfname); 

        if (!$xml->schemaValidate($xsdfile)) {
            unlink($tmpfname);
            $this->error = "DOMDocument::schemaValidate() Generated Errors!";
            $this->error .= "<!-- ";
            $this->libxml_display_errors();
            $this->error .= " -->";
            $this->error .= "<br><br>The most likely cause of this error is an incorrect match report template.
                            Please download the appropriate template and regenerate the report.";
            return false;
        }

        return true;
    }

    function get_tour_id() {
        $did = get_alt_col('teams', 'team_id', $this->hometeam_id, 'f_did');
        $query = "SELECT tour_id FROM tours WHERE (f_did = $did) AND (type = 1) AND (locked != 1) ORDER BY tour_id DESC";

        $tour_id = mysql_query($query);
        $tour_id = mysql_fetch_array($tour_id);
        $tour_id = $tour_id['tour_id'];

        return ($tour_id > 0) ? $tour_id : false;
    }

    private static function form() {
        
        /**
         * Creates an upload form.
         *
         * 
         **/

        global $coach;
        
        if (is_object($coach)) {
            # Tours the logged in coach can "see".
            list(,,$tours) = Coach::allowedNodeAccess(Coach::NODE_STRUCT__FLAT, $coach->coach_id, array(T_NODE_TOURNAMENT => array('type' => 'type', 'locked' => 'locked', 'f_did' => 'f_did')));
            $tourlist = "";
            $coach_lid = ( isset($_SESSION['NS_node_id']) && $_SESSION['NS_node_id'] > 0 ) ? $_SESSION['NS_node_id'] : 1;
            foreach ($tours as $trid => $t)
            {
                $lid = get_alt_col('divisions', 'did', $t['f_did'], 'f_lid');
                if ($t['type'] == TT_FFA && !$t['locked'] && $coach_lid == $lid && $t['tname'] != "Pandora's Box") $tourlist .= "<option value='$trid'>$t[tname]</option>\n";
            }
#            $tourlist = HTMLOUT::nodeList(T_NODE_TOURNAMENT, 'ffatours', array(T_NODE_TOURNAMENT => array('type' => TT_FFA, 'locked' => 0)), array(), array('hide_empty'=>array(T_NODE_DIVISION,T_NODE_LEAGUE)));
            global $settings;
            $cy_reroll = "";
            if ( $settings['leegmgr_cyanide'] )
                $cy_reroll = "
                    Reroll winnings if roll is less than: 
                    <select name='reroll'>
                        <option value='2'>2</option>
                        <option value='3'>3</option>
                        <option value='4'>4</option>
                        <option value='5'>5</option>
                        <option value='6'>6</option>
                    </select><br>";

            $form = "
                <!-- The data encoding type, enctype, MUST be specified as below -->
                <form enctype='multipart/form-data' action='handler.php?type=leegmgr' method='POST'>
                    <!-- MAX_FILE_SIZE must precede the file input field -->
                    <input type='hidden' name='MAX_FILE_SIZE' value='256000' />
                    <!-- Name of input element determines name in $_FILES array -->
                    Send this file <input name='userfile' type='file' /><br>
                    Save in tournament
                    <select name='ffatours'>
                        <optgroup label='Existing FFA'>

                            {$tourlist}

                        </optgroup>
                    </select>

                    <br>{$cy_reroll}<br>
                    <input type='submit' value='Send File' />
                </form>
            ";
            return "
            <div class='boxCommon'>
            <h3 class='boxTitle".T_HTMLBOX_MATCH."'>Upload match</h3>
            <div class='boxBody'>
            ".$form."
            </div>
            </div>
            ";
        }
        else {
            return 'You must login in order to upload a match report.';
        }
    }
    
    private static function submitForm($userfile, $tour_id, $coach_id, $reroll ) {

        $upload = new UPLOAD_BOTOCS($userfile, $tour_id, $coach_id, $reroll);
        if ( !$upload->error )
        {
            Print "Upload was successful.";
            Print "<br>".$upload->success;
            unset($upload);
        }
        else
        {
            Print "<br><b>Error: {$upload->error}</b><br>";
            unset($upload);
            unset($_FILES['userfile']);
            UPLOAD_BOTOCS::main(array(true));
        }

    }

    function processFile() {

            $status = true;
            $uploaddir = '/var/www/uploads/';
            if ( !$this->userfile['name'] )
            {
                $this->error = "Please choose a file to upload.";
                return false;
            }
            $uploadfile = $uploaddir . basename($this->userfile['name']);
            $this->replay = mysql_real_escape_string(fread(fopen($this->userfile['tmp_name'], "r"), filesize($this->userfile['tmp_name'])));

            if (strlen($this->userfile['tmp_name'])>3) $zip = zip_open($this->userfile['tmp_name']);

            if ( $zip  &&
                          ( $this->userfile['type'] == "application/x-zip-compressed" ||
                            $this->userfile['type'] == "application/octet-stream"     ||
                            $this->userfile['type'] == "application/zip"              ||
                            $this->userfile['type'] == "application/x-zip"            ||
                            $this->userfile['type'] == ""
                          )
                                                                                         )
            {
                while ($zip_entry = zip_read($zip))
                {
                    if (strpos(zip_entry_name($zip_entry),"report.xml") !== false || strpos(zip_entry_name($zip_entry),"MatchReport.sqlite") !== false)
                    {
                        $this->xmlresults = zip_entry_read($zip_entry, 256000);
                        if ( zip_entry_name($zip_entry) == "report.xml" ) $this->reporttype = "botocs";
                        else if ( zip_entry_name($zip_entry) == "MatchReport.sqlite" ) $this->reporttype = "cyanide";
                        zip_entry_close($zip_entry);
                    }

                }
                zip_close($zip);

                if ( !isset($this->xmlresults) )
                {
                    $this->error = "The zip file does not contain the results xml file.";
                    $status = false;
                }
            }

            else
            {
                $this->error = "You must upload a zip file with the results in it.";
                $status = false;
            }

        return $status;

    }

    /*
     * Module interface
     */ 

    public static function main($argv) {
        
        // Module registered main function.
        global $coach;
        global $settings;
        if ( !$settings['leegmgr_enabled'] ) die ("LeegMgr is currently disabled.");
        #Begin Replay Retrieval
        if ( isset($_GET['replay']) )
        {
            #Retrieve the entire ZIP file that was previously uploaded.
            $mid = $_GET['replay'];
            if ( is_numeric($mid) )
            {
                $zip = mysql_query( "SELECT replay FROM `leegmgr_matches` WHERE mid = $mid" );
                $zip = mysql_fetch_array($zip);
                $zip = $zip[0];
            }
            if ( !isset($zip) || !$zip )
            {
                Print "An upload could not be retrieved for the specified match id.";
                return false;
            }

            #Create a temporary file name that the ZIP file can be written to.
            $temp_path = sys_get_temp_dir();
            $tempname = tempnam($temp_path, "");

            #Open and write the retrieved ZIP file to the temporary file.
            $f_r = fopen($tempname, 'w+');
            fwrite($f_r, $zip);
            fseek($f_r, 0);
            fclose($f_r);

            #Open up the temp file for extracting the replay.rep file from the ZIP file.
            $zip_r = zip_open($tempname);
            while ($zip_entry = zip_read($zip_r))
            {
                if (strpos(zip_entry_name($zip_entry),"replay.rep") !== false || strpos(zip_entry_name($zip_entry),"Replay_") !== false)
                {
                    $replay = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                    if ( strpos(zip_entry_name($zip_entry),"replay.rep") !== false ) $extension = ".rep";
                    else if ( strpos(zip_entry_name($zip_entry),"Replay_") !== false ) $extension = ".db";
                    zip_entry_close($zip_entry);
                }
            }
            zip_close($zip_r);

            #Specify the header so that the browser is prompted to download the replay.rep file.
            header('Content-type: application/octec-stream');
            header('Content-Disposition: attachment; filename=match'.$mid.$extension);
            #Whatever is printed to the screen will be in the file.
            Print $replay;

		return true;
        }
        #End Replay Retrieval
        if ( !isset($argv[0]) ) HTMLOUT::frame_begin();
        if ( isset($_FILES['userfile']) && isset($_SESSION['coach_id']) )
        {
            $userfile = $_FILES['userfile'];
            $tour_id = ( isset($_POST['ffatours']) ) ? $_POST['ffatours'] : -1;
            $coach_id = $_SESSION['coach_id'];
            $reroll = ( isset($_POST['reroll']) ) ? $_POST['reroll']: 2;
            self::submitForm($userfile, $tour_id, $coach_id, $reroll);
        }
        else
        {
            Print "<html><body>";
            Print UPLOAD_BOTOCS::form();
            Print "</body></html>";
        }

        HTMLOUT::frame_end();

    }
    
    public static function getModuleAttributes()
    {
        return array(
            'author'     => 'William Leonard',
            'moduleName' => 'BOTOCS match upload',
            'date'       => '2009',
            'setCanvas'  => false,
        );
    }

    public static function getModuleTables()
    {
        return array(
            'leegmgr_matches' =>
                array( 
                    'mid'    => 'MEDIUMINT', 
                    'hash'   => 'VARCHAR(32)',
                    'replay' => 'MEDIUMBLOB',
                ),
        );
    }
    
    public static function getModuleUpgradeSQL()
    {
        return array(
            '075-080' => array(
                'CREATE TABLE IF NOT EXISTS leegmgr_matches (
                    mid     MEDIUMINT,
                    replay  MEDIUMBLOB,
                    hash    VARCHAR(32)
                )',
                // In case of people having used the 0.80 revisions we must save their exisitng leegmgr data:
                'DROP TABLE IF EXISTS leegmgr_matches_temp',
                SQLUpgrade::runIfColumnExists('matches', 'hash_botocs', 
                    'CREATE TABLE leegmgr_matches_temp (
                        mid     MEDIUMINT,
                        replay  MEDIUMBLOB,
                        hash    VARCHAR(32)
                    )
                '),
                SQLUpgrade::runIfColumnExists('matches', 'hash_botocs', 'INSERT INTO leegmgr_matches_temp (mid, hash) SELECT match_id, hash_botocs FROM matches'),
                SQLUpgrade::runIfColumnExists('matches', 'hash_botocs', 'UPDATE leegmgr_matches_temp, leegmgr_matches SET leegmgr_matches_temp.replay = leegmgr_matches.replay WHERE leegmgr_matches_temp.mid = leegmgr_matches.mid'),
                SQLUpgrade::runIfColumnExists('matches', 'hash_botocs', 'DROP TABLE leegmgr_matches'),
                SQLUpgrade::runIfColumnExists('matches', 'hash_botocs', 'ALTER TABLE leegmgr_matches_temp RENAME TO leegmgr_matches'),
            ),
        );
    }
    
    public static function triggerHandler($type, $argv)
    {
        switch ($type) {
            case ( $type == T_TRIGGER_MATCH_DELETE || $type == T_TRIGGER_MATCH_RESET ):
                $result = mysql_query( 'DELETE FROM leegmgr_matches WHERE mid = '.$argv[0].' LIMIT 1' );
                break;
        }
    }

}

?>
