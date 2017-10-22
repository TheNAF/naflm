<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2008-2011. All Rights Reserved.
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

/* 
 *  Generic class for handling the "texts" table.
 */

// Table "text" type definitions.
define('T_TEXT_MSG',    1);
define('T_TEXT_COACH',  2);
define('T_TEXT_TEAM',   3);
define('T_TEXT_PLAYER', 4);
#define('T_TEXT_HOF',    5); # Deprecated
#define('T_TEXT_WANTED', 6); # Deprecated
define('T_TEXT_MATCH_SUMMARY', 7);
#define('T_TEXT_TOUR',   8); # Deprecated
#define('T_TEXT_GUEST',  9); # Deprecated
#define('T_TEXT_LOG',    10); # Deprecated
#define('T_TEXT_MATCH_COMMENT', 11); # Deprecated
define('T_TEXT_TNEWS',  12); // Team news.
define('T_TEXT_WELCOME',13); // League welcome message.

class TextSubSys
{
    /***************
     * Properties 
     ***************/

    // MySQL stored information    
    public $txt_id = 0;
    public $type   = 0;
    public $f_id   = 0 ;
    public $date   = '';
    public $txt2   = '';
    public $txt    = '';
    
    /***************
     * Methods 
     ***************/

    function __construct($txt_id) 
    {
        $result = mysql_query("SELECT * FROM texts WHERE txt_id = $txt_id");
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                foreach ($row as $key => $val) {
                    $this->$key = $val;
                }
            }
        }
    }
    
    public function delete()
    {
        return (mysql_query("DELETE FROM texts WHERE txt_id = $this->txt_id"));
    }

    public function pin($bool)
    {
        return (mysql_query("UPDATE texts SET pinned = $bool WHERE txt_id = $this->txt_id"));
    }
    
    public function edit($txt, $txt2, $f_id = false, $type = false)
    {
        if (mysql_query("UPDATE texts SET 
                        txt2 = '".mysql_real_escape_string($txt2)."', 
                        txt = '".mysql_real_escape_string($txt)."' 
                        ".(($f_id) ? ", f_id = $f_id " : '')." 
                        ".(($type) ? ", type = $type " : '')." 
                        WHERE txt_id = $this->txt_id")) {
            $this->txt  = $txt;
            $this->txt2 = $txt2;
            return true;
        }
        else
            return false;
    }
    
    /***************
     * Statics
     ***************/
    
    public static function create($f_id, $type, $txt, $txt2, $f_id2 = false)
    {
        $query = "INSERT INTO texts (f_id, txt2, txt, date, type".(($f_id2) ? ', f_id2' : '').") 
                VALUES ($f_id, '".mysql_real_escape_string($txt2)."', '".mysql_real_escape_string($txt)."', NOW(), $type".(($f_id2) ? ", $f_id2" : '').")";
        return mysql_query($query);
    }
    
    public static function getMainBoardMessages($n, $lid = false, $get_tnews = true, $get_summaries = true) 
    {
        global $settings;
        $board = array();

        // First we add all commissioner messages to the board structure.
        foreach (Message::getMessages($n, $lid) as $m) {
            $o = (object) array();
            // Specific fields:
            $o->msg_id    = $m->msg_id;
            $o->author_id = $m->f_coach_id;
            // General fields:
            $o->cssidx    = T_HTMLBOX_ADMIN; // CSS box index
            $o->type      = T_TEXT_MSG;
            $o->author    = get_alt_col('coaches', 'coach_id', $m->f_coach_id, 'name');
            $o->title     = $m->title;
            $o->message   = $m->message;
            $o->date      = $m->date_posted;
            $o->pinned    = $m->pinned;
            array_push($board, $o);
        }

        // Now we add all game summaries.
        if ($get_summaries) {
            foreach (MatchSummary::getSummaries($n, $lid) as $r) {
                $o = (object) array();
                $m = new Match($r->match_id);
                // Specific fields:
                $o->date_mod  = $m->date_modified;
                $o->match_id  = $m->match_id;
                // General fields:
                $o->cssidx    = T_HTMLBOX_MATCH; // CSS box index
                $o->type      = T_TEXT_MATCH_SUMMARY;
                $o->author    = get_alt_col('coaches', 'coach_id', $m->submitter_id, 'name');
                $o->title     = "Match: $m->team1_name $m->team1_score&mdash;$m->team2_score $m->team2_name";
                $o->message   = $m->getText();
                $o->date      = $m->date_played;
                $o->pinned    = 0; // Not allowed for other types than board messages
                array_push($board, $o);
            }
        }
        
        // And finally team news.
        if ($get_tnews) {
            foreach (TeamNews::getNews(false, $n, $lid) as $t) {
                $o = (object) array();
                // Specific fields:
                    # none
                // General fields:
                $o->cssidx    = T_HTMLBOX_INFO; // CSS box index
                $o->type      = T_TEXT_TNEWS;
                $o->author    = get_alt_col('teams', 'team_id', $t->f_id, 'name');
                $o->title     = "Team news: $o->author";
                $o->message   = $t->txt;
                $o->date      = $t->date;
                $o->pinned    = 0; // Not allowed for other types than board messages
                array_push($board, $o);
            }
        }

        // Last touch on the board.
        if (!empty($board)) {
            objsort($board, array('-pinned','-date'));
            if ($n) {
                $board = array_slice($board, 0, $n);
            }
        }
        
        return $board;
    }
}

/* 
 *  Handles text Descriptions for players (T_TEXT_PLAYER), teams (T_TEXT_TEAM) and coaches (T_TEXT_COACH).
 */

class ObjDescriptions extends TextSubSys
{
    /***************
     * Properties 
     ***************/
     
     // From MySQL.
     public $type = 0;
     public $txt = '';

    /***************
     * Methods 
     ***************/

    function __construct($type, $f_id) 
    {
        $result = mysql_query("SELECT * FROM texts WHERE type = $type AND f_id = $f_id");
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                foreach ($row as $key => $val) {
                    $this->$key = $val;
                }
            }
        }
        
        $this->type = $type;
        $this->f_id = $f_id;
    }

    public function save($txt)
    {
        return (empty($this->txt)) 
            ? parent::create($this->f_id, $this->type, $txt, false) 
            : parent::edit($txt, false, false, false);
    }
}

/* 
 *  Handles messages for the messages board.
 */

class Message extends TextSubSys
{
    const T_BROADCAST = 0;

    /***************
     * Properties 
     ***************/

    public $msg_id      = 0;
    public $f_coach_id  = 0;
    public $f_lid       = self::T_BROADCAST;
    public $date_posted = '';
    public $title       = '';
    public $message     = '';
    
    /***************
     * Methods 
     ***************/

    function __construct($msg_id) 
    {
        parent::__construct($msg_id);
        
        $this->msg_id       = $this->txt_id;        
        $this->f_coach_id   = $this->f_id;
        $this->f_lid        = $this->f_id2;
        $this->date_posted  = $this->date;
        $this->title        = $this->txt2;
        $this->message      = $this->txt;
        
        unset($this->txt2);
        unset($this->txt);
    }

    public function edit($new_title, $new_msg, $f_coach_id = false, $type = T_TEXT_MSG) 
    {
        return (parent::edit($new_msg, $new_title, $f_coach_id, $type) && ($this->title = $this->txt2) && ($this->message = $this->txt));
    }

    /***************
     * Statics
     ***************/

    public static function getMessages($n = false, $lid = false) 
    {
        $m = array();

        $result = mysql_query("SELECT txt_id 
            FROM texts
            WHERE type = ".T_TEXT_MSG." AND (f_id2 = ".self::T_BROADCAST." OR ".(($lid) ? "f_id2 = $lid" : 'TRUE').") 
            ORDER BY pinned DESC, date DESC LIMIT $n");
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($m, new Message($row['txt_id']));
            }
        }
        
        return $m;
    }

    public static function create($input, $type = T_TEXT_MSG, $txt = '', $txt2 = '', $f_id2 = false)
    {
        return parent::create($input['f_coach_id'], $type, $input['msg'], $input['title'], $input['f_lid']);
    }
}

/* 
 *  Handles match summaries.
 */

class MatchSummary extends TextSubSys
{
    /*
        Please note: 
        
            The date field for MatchSummary is not used for anything and its contents is not reliable. 
            Instead use the date fields of the corresponding match object.
    */

    /***************
     * Properties 
     ***************/

    public $match_id = 0;
    public $exists = false; // Does this match already have a summary entry in the texts table?

    /***************
     * Methods 
     ***************/    

    function __construct($mid) 
    {
        $this->match_id = $mid;
        $query = "SELECT txt_id FROM texts WHERE f_id = $mid AND type = ".T_TEXT_MATCH_SUMMARY;
        $result = mysql_query($query);
        if ($result && mysql_num_rows($result) > 0) {
            $this->exists = true;
            $row = mysql_fetch_assoc($result);
            parent::__construct($row['txt_id']);
        }
    }
    
    public function save($txt)
    {
        return (!$this->exists) 
            ? parent::create($this->match_id, T_TEXT_MATCH_SUMMARY, $txt, false) 
            : parent::edit($txt, false, false, false);
    }
    
    public static function getSummaries($n = false, $lid = false) {
        
        $r = array();
        
        $query = "SELECT match_id, txt FROM matches, tours, divisions, texts WHERE 
                f_tour_id = tour_id
            AND f_did = did
            AND match_id = f_id 
            AND texts.type = ".T_TEXT_MATCH_SUMMARY." 
            AND date_played IS NOT NULL 
            AND txt IS NOT NULL 
            AND txt != '' 
            ".(($lid) ? "AND f_lid = $lid" : '')." 
            ORDER BY date_played DESC LIMIT $n";

        $result = mysql_query($query);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                if (!empty($row['txt'])) {
                    $r[] = new self($row['match_id']);
                }
            }
        }

        return $r;
    }
}

/* 
 *  Team news board.
 */

class TeamNews extends TextSubSys
{
    /***************
     * Properties 
     ***************/

    public $news_id = 0;
    public $f_id = 0; // Submitter (team id).
    public $txt = '';

    /***************
     * Methods 
     ***************/    

    public function __construct($nid) 
    {
        $this->news_id = $nid;
        parent::__construct($nid);
    }

    public function edit($txt, $txt2 = '', $f_id = false, $type = false)
    {
        return parent::edit($txt, $txt2, $f_id, $type);    
    }

    /* 
        Parent has delete() implemented.
    */

    /***************
     * Statics
     ***************/
    
    public static function create($str, $tid, $type = T_TEXT_TNEWS, $txt2 = '', $f_id2 = false)//Should be $f_id, $type, $txt, $txt2, $f_id2 = false
    {
        return parent::create($tid, $type, $str, $f_id2);
    }
    
    public static function getNews($tid = false, $n = false, $lid = false)
    {
        $news = array();
        
        $query = "SELECT txt_id FROM texts, teams 
            WHERE f_id = team_id AND type = ".T_TEXT_TNEWS.(($tid) ? " AND f_id = $tid " : '').(($lid) ? " AND teams.f_lid = $lid " : ''). " 
            ORDER BY date DESC LIMIT $n";
        $result = mysql_query($query);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($news, new TeamNews($row['txt_id']));
            }
        }
        
        return $news;
    }
}

