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

define('LOGIN_COOKIE_COACHID', 'obblmuserid');
define('LOGIN_COOKIE_PASSWD', 'obblmpasswd');

class Coach
{
    /***************
     * Properties 
     ***************/
    
    // MySQL stored information
    public $coach_id    = 0;
    public $name        = '';
    public $realname    = '';
    public $passwd      = '';
    public $mail        = '';
    public $phone       = '';
    public $ring        = 0; // Privilege ring (ie. coach access level).
    public $retired     = false;
    public $settings    = array();

    // Shortcut for compabillity issues.
    public $admin       = false;
    
    // Privilege rings (coach access levels)
    const T_RING_GROUP_GLOBAL = 1;
    const T_RING_GROUP_LOCAL = 2;
    
    const T_RING_GLOBAL_ADMIN   = 5; // Site admins
    const T_RING_GLOBAL_NONE    = 0; // No global rights
    const T_RING_LOCAL_ADMIN    = 5; // League commissioner
    const T_RING_LOCAL_REGULAR  = 2; // Regular coach.
    const T_RING_LOCAL_NONE     = -1; // Pseudo field!!!    
    
    public static $RINGS = array(
        self::T_RING_GROUP_GLOBAL => array(self::T_RING_GLOBAL_ADMIN, self::T_RING_GLOBAL_NONE),
        self::T_RING_GROUP_LOCAL => array(self::T_RING_LOCAL_ADMIN, self::T_RING_LOCAL_REGULAR, self::T_RING_LOCAL_NONE),
    );
    
    /***************
     * Methods 
     ***************/
    
    function __construct($coach_id) {
    
        // MySQL stored information
        $this->coach_id = $coach_id;
        $this->setStats(false,false,false);

        $this->ring = (int) $this->ring;
        if (empty($this->mail)) $this->mail = '';           # Re-define as empty string, and not numeric zero.
        if (empty($this->phone)) $this->phone = '';         # Re-define as empty string, and not numeric zero.
        if (empty($this->realname)) $this->realname = '';   # Re-define as empty string, and not numeric zero.
        
       
        // Coach's site settings.
        $this->settings = array(); // Is overwriten to type = string when loading MySQL data into this object.
        foreach (get_list('coaches', 'coach_id', $this->coach_id, 'settings') as $set) {
            list($key, $val) = explode('=', $set);
            $this->settings[$key] = $val;
        }
        global $settings;
        $init = array('theme' => 1, 'lang' => Translations::fallback, 'home_lid' => null); // Setting values which must be initialized if not stored/saved in mysql.
        foreach ($init as $key => $val) {
            if (!array_key_exists($key, $this->settings) || !isset($this->settings[$key]))
                $this->settings[$key] = $val;
        }
    }
    
    public static function getByName($coachName) {
        $result = mysql_query("SELECT coach_id FROM coaches WHERE name = '$coachName'");
        $theCoach = null;
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                $theCoach = new Coach($row['coach_id']);
            }
        }
        return $theCoach;
    }
    
    public function setStats($node, $node_id, $set_avg = false)
    {
        foreach (Stats::getAllStats(T_OBJ_COACH, $this->coach_id, $node, $node_id, $set_avg) as $key => $val) {
            $this->$key = $val;
        }
        return true;
    }
    
    public function setSetting($key, $val) {
        
        $this->settings[$key] = $val;
        $settings = array();
        foreach ($this->settings as $key => $val) {
            $settings[] = implode('=', array($key, $val));
        }
        
        return set_list('coaches', 'coach_id', $this->coach_id, 'settings', $settings);
    }

    public function getTeams($lid = false, $did = false, $opts = array()) {

        /**
         * Returns an array of team objects for those teams owned by this coach.
         **/
    
        $teams = array();
        
        $F_LID = $lid ? "AND f_lid = $lid" : '';
        $F_DID = $did ? "AND f_did = $did" : '';
        $SORTBY = isset($opts['sortby']) ? $opts['sortby'] : 'name ASC';
        
        $result = mysql_query("SELECT team_id FROM teams WHERE owned_by_coach_id = $this->coach_id $F_LID $F_DID ORDER BY $SORTBY");
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($teams, new Team($row['team_id']));
            }
        }
        
        return $teams;
    }
    
    public function getReadyTeams() {
        $teams = array();
        $result = mysql_query("SELECT team_id FROM teams WHERE owned_by_coach_id = $this->coach_id AND rdy = 1");
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($teams, new Team($row['team_id']));
            }
        }
        
        return $teams;
    }

    public function getWonTours() {

        // Returns an array of tournament objects for those tournaments the coach's teams have won.
        $tours = array();
        $result = mysql_query("SELECT tour_id FROM tours,teams WHERE winner = team_id AND owned_by_coach_id = $this->coach_id");
        while ($row = mysql_fetch_assoc($result)) {
            array_push($tours, new Tour($row['tour_id']));
        }
        return $tours;
    }

    public function isDeletable() {
        
        $status = true;
        
        foreach ($this->getTeams() as $t) {
            $status &= $t->isDeletable();
        }
        
        return $status;
    }
    
    public function delete() {

        $status = true;
        if ($this->isDeletable()) {
            
            foreach ($this->getTeams() as $t) {
                $status &= $t->delete();
            }            

            $status &= mysql_query("DELETE FROM coaches WHERE coach_id = ".$this->coach_id);
        }
        else {
            $status = false;
        }
        
        return $status;
    }
    
    public function setRetired($bool) {
        return mysql_query("UPDATE coaches SET retired = ".(($bool) ? 1 : 0)." WHERE coach_id = $this->coach_id");
    }
    
    public function getLeagues() {
        $result = mysql_query("select name from memberships join leagues on memberships.lid=leagues.lid where cid = $this->coach_id");
        $leagues = array();
        while($league = mysql_fetch_object($result)) {
            array_push($leagues, $league->name);
        }
        
        return $leagues;
    }

    public function setRing($rtype, $ring, $lid = false) {
        if ($rtype == self::T_RING_GROUP_GLOBAL && in_array($ring, self::$RINGS[self::T_RING_GROUP_GLOBAL])) {
            $this->ring = $ring;
            return mysql_query("UPDATE coaches SET ring = $ring WHERE coach_id = $this->coach_id");
        }
        else if ($rtype == self::T_RING_GROUP_LOCAL && in_array($ring, self::$RINGS[self::T_RING_GROUP_LOCAL]) && $lid) {
            $status = mysql_query("DELETE FROM memberships WHERE cid = $this->coach_id AND lid = $lid");
            if ($ring != self::T_RING_LOCAL_NONE) {
                $status &= mysql_query("INSERT INTO memberships (cid,lid,ring) VALUES($this->coach_id, $lid, $ring)");
            }
            return $status;
        }
        
        return false;
    }
    
    public function isMyTeam($teamId) {
        foreach($this->getTeams() as $team) {
            if($team->team_id == $teamId)
                return true;
        }
        return false;
    }

    public function mayManageObj($obj, $id) { 
        
        $managee = new Coach($obj == T_OBJ_COACH ? $id : get_alt_col('teams', 'team_id', $id, 'owned_by_coach_id'));
        list($mangers_leagues) = Coach::allowedNodeAccess(Coach::NODE_STRUCT__FLAT, $this->coach_id);
        $MAY_MANAGE_LOCALLY = false;
        
        switch ($obj) {
    
            // Is this coach a commish in a league where the selected coach is a member?
            case T_OBJ_COACH:
                list($managees_leagues) = Coach::allowedNodeAccess(Coach::NODE_STRUCT__FLAT, $managee->coach_id);
                foreach ($managees_leagues as $lid => $desc) {
                    if (isset($mangers_leagues[$lid]) && $mangers_leagues[$lid]['ring'] == Coach::T_RING_LOCAL_ADMIN) {
                        $MAY_MANAGE_LOCALLY = true;
                        break;
                    }
                }
                break;
            
            // Is this coach a commish in the league the specified team is bounded to?
            case T_OBJ_TEAM:
                $f_lid = get_alt_col('teams', 'team_id', $id, 'f_lid');
                $MAY_MANAGE_LOCALLY = (isset($mangers_leagues[$f_lid]) && $mangers_leagues[$f_lid]['ring'] == Coach::T_RING_LOCAL_ADMIN);
                break;
        }
        
        return ($managee->ring <= $this->ring && ($MAY_MANAGE_LOCALLY || $this->ring == Coach::T_RING_GLOBAL_ADMIN));
    }
    
    public function isNodeCommish($node, $node_id) {
    
        if (!isset($this->_nodeAccess)) {
            list($this->_nodeAccess[T_NODE_LEAGUE], $this->_nodeAccess[T_NODE_DIVISION], $this->_nodeAccess[T_NODE_TOURNAMENT]) = self::allowedNodeAccess(self::NODE_STRUCT__FLAT, $this->coach_id, array(T_NODE_TOURNAMENT => array('f_did' => 'f_did'), T_NODE_DIVISION => array('f_lid' => 'f_lid')));
        }
        list($leagues,$divisions,$tours) = array($this->_nodeAccess[T_NODE_LEAGUE], $this->_nodeAccess[T_NODE_DIVISION], $this->_nodeAccess[T_NODE_TOURNAMENT]);
        if (isset($this->_nodeAccess[$node][$node_id])) {
            switch ($node) {
                case T_NODE_LEAGUE:
                    return ($leagues[$node_id]['ring'] == self::T_RING_LOCAL_ADMIN);
                case T_NODE_DIVISION:
                    return ($leagues[$divisions[$node_id]['f_lid']]['ring'] == self::T_RING_LOCAL_ADMIN);
                case T_NODE_TOURNAMENT:
                    return ($leagues[ $divisions[ $tours[$node_id]['f_did'] ]['f_lid'] ]['ring'] == self::T_RING_LOCAL_ADMIN);
            }
        }
        return false;
    }
    
    public function isGlobalAdmin() {
        return $this->ring == Coach::T_RING_GLOBAL_ADMIN;
    }

    public function getAdminMenu()
    {
        global $lng;
        // Ring access allowances.
        $ring_sys_access = array(
            'log' => $lng->getTrn('name', 'LogSubSys'), 
            'cpanel' => $lng->getTrn('menu/admin_menu/cpanel')
        );
        
        $ring_com_access = array();
        
        if(!Module::isRegistered('Scheduler'))
            $ring_com_access['schedule'] = $lng->getTrn('menu/admin_menu/schedule');
        
        $ring_com_access['nodes'] =array('title' => $lng->getTrn('menu/admin_menu/nodes'), 'sub' => array(
                array('title' => $lng->getTrn('common/tournament'), 'href' => 'node='.T_NODE_TOURNAMENT),
                array('title' => $lng->getTrn('common/division'),   'href' => 'node='.T_NODE_DIVISION),
                array('title' => $lng->getTrn('common/league'),     'href' => 'node='.T_NODE_LEAGUE),
            ));
        $ring_com_access['usr_man'] = $lng->getTrn('menu/admin_menu/usr_man');
        $ring_com_access['ct_man'] = $lng->getTrn('menu/admin_menu/ct_man');
        $ring_com_access['import'] = $lng->getTrn('menu/admin_menu/import');

        $my_admin_menu = array();

        $result = mysql_query("SELECT COUNT(*) FROM memberships WHERE cid = $this->coach_id AND ring = ".self::T_RING_LOCAL_ADMIN);
        list($cnt) = mysql_fetch_row($result);
        $my_admin_menu = array_merge(
            $this->ring == Coach::T_RING_GLOBAL_ADMIN ? $ring_com_access+$ring_sys_access : array(),
            $cnt > 0 ? $ring_com_access : array()
        );
        
        return $my_admin_menu;
    }

    public function setPasswd($passwd) {
        $query = "UPDATE coaches SET passwd = '".md5($passwd)."' WHERE coach_id = $this->coach_id";
        return (mysql_query($query) && ($this->passwd = md5($passwd)));
    }

    public function setName($name) {
        if (!isset($name) || empty($name)) {return false;}
        $result = mysql_query("SELECT coach_id FROM coaches WHERE name = BINARY('".mysql_real_escape_string($name)."')");
        if ($result && mysql_num_rows($result) > 0) {return false;} // Duplicates not allowed.
        $query = "UPDATE coaches SET name = '".mysql_real_escape_string($name)."' WHERE coach_id = $this->coach_id";
        return (mysql_query($query) && ($this->name = $name) && SQLTriggers::run(T_SQLTRIG_COACH_UPDATE_CHILD_RELS, array('id' => $this->coach_id, 'obj' => $this)));
    }

    public function setMail($mail) {
        $query = "UPDATE coaches SET mail = '".mysql_real_escape_string($mail)."' WHERE coach_id = $this->coach_id";
        return (mysql_query($query) && ($this->mail = $mail));
    }

    public function setPhone($phnr) {
        $query = "UPDATE coaches SET phone = '".mysql_real_escape_string($phnr)."' WHERE coach_id = $this->coach_id";
        return (mysql_query($query) && ($this->phone = $phnr));
    }

    public function setRealName($rname) {
        $query = "UPDATE coaches SET realname = '".mysql_real_escape_string($rname)."' WHERE coach_id = $this->coach_id";
        return (mysql_query($query) && ($this->realname = $rname));
    }

    public function isInMatch($match_id) {
    
        /**
         * Returns the boolean evaluation of a coach's participation in a specific match.
         **/
    
        $result = mysql_query("SELECT team1_id, team2_id FROM matches WHERE match_id = $match_id");
        $row    = mysql_fetch_assoc($result);
        $coach_id1 = get_alt_col('teams', 'team_id', $row['team1_id'], 'owned_by_coach_id');
        $coach_id2 = get_alt_col('teams', 'team_id', $row['team2_id'], 'owned_by_coach_id');

        return ($this->coach_id == $coach_id1 || $this->coach_id == $coach_id2);
    }
    
    public function saveText($str) {
        
        $desc = new ObjDescriptions(T_TEXT_COACH, $this->coach_id);
        return $desc->save($str);
    }

    public function getText() {

        $desc = new ObjDescriptions(T_TEXT_COACH, $this->coach_id);
        return $desc->txt;
    }
    
    public function savePic($name = false) {
        $img = new ImageSubSys(IMGTYPE_COACH, $this->coach_id);
        list($retstatus, $error) = $img->save($name);
        return $retstatus;
    }
    
    public function deletePic() {
        $img = new ImageSubSys(IMGTYPE_COACH, $this->coach_id);
        return $img->delete();    
    }
    
    public function setActivationCode($set_AC) 
    {
        if ($set_AC) {
            $AC = md5(date('l jS \of F Y h:i:s A'));
            return mysql_query("UPDATE coaches SET activation_code = '$AC' WHERE coach_id = $this->coach_id") ? $AC : false;
        }
        else {
            return mysql_query("UPDATE coaches SET activation_code = NULL WHERE coach_id = $this->coach_id");
        }
    }
    
    public function requestPasswdReset() 
    {
        $this->setRetired(true); # Prevent future login until activation code is confirmed.
        $AC = $this->setActivationCode(true);
        mail($this->mail, 
        "$_SERVER[SERVER_NAME] password reset request",
        "Hi $this->name, you have requested a new password at $_SERVER[SERVER_NAME].\nPlease follow this link which will log you on temporarily allowing YOU to set a new password: $_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]&cid=$this->coach_id&activation_code=$AC", 
        'From: noreply@'.$_SERVER['SERVER_NAME']."\r\n".'Reply-To: noreply@'.$_SERVER['SERVER_NAME']."\r\n".'X-Mailer: PHP/'.phpversion()
        );
    }

    public function confirmActivation($AC)
    {
        $query = "SELECT activation_code = '$AC' FROM coaches WHERE coach_id = $this->coach_id";
        $result = mysql_query($query);
        list($OK) = mysql_fetch_row($result);
        if ($OK) {
            $this->setRetired(false);
            $this->setActivationCode(false);
            $this->setPasswd($new_passwd = md5($AC)); # Scramble password.
            self::login($this->coach_id, $new_passwd);
            return $new_passwd;
        }
        return false;
    }
    
    /***************
     * Statics
     ***************/

    public static function exists($id) 
    {
        $result = mysql_query("SELECT COUNT(*) FROM coaches WHERE coach_id = $id");
        list($CNT) = mysql_fetch_row($result);
        return ($CNT == 1);
    }

    const NODE_STRUCT__TREE = 1;
    const NODE_STRUCT__FLAT = 2;
    public static function allowedNodeAccess($NODE_SRUCT, $cid, $extraFields = array())
    {
        $FORCE_LOCAL_ADMIN = ((int) get_alt_col('coaches', 'coach_id', $cid, 'ring') > self::T_RING_GLOBAL_NONE);
        $GLOBAL_VIEW = (!$cid || $FORCE_LOCAL_ADMIN);
        $FORCE_LOCAL_RING = ($FORCE_LOCAL_ADMIN) ? self::T_RING_LOCAL_ADMIN : (!$cid ? self::T_RING_LOCAL_REGULAR : false);
        
        $properFields = array();
        $extraFields[T_NODE_LEAGUE]['name']     = 'lname';
        $extraFields[T_NODE_DIVISION]['name']   = 'dname';
        $extraFields[T_NODE_TOURNAMENT]['name'] = 'tname';
        foreach ($extraFields as $node => $fields) {
            switch ($node) {
                case T_NODE_LEAGUE:     $tbl = 'l'; break;
                case T_NODE_DIVISION:   $tbl = 'd'; break;
                case T_NODE_TOURNAMENT: $tbl = 't'; break;
            }
            foreach ($fields as $ref => $name) {
                $properFields[] = "$tbl.$ref AS '$name'";
            }
        }
        if (!$GLOBAL_VIEW) {
            $properFields[] = "m.ring AS 'ring'";
        }
        $query = "SELECT l.lid AS 'lid', d.did AS 'did', t.tour_id AS 'trid',".implode(',',$properFields)."
            FROM leagues AS l LEFT JOIN divisions AS d ON d.f_lid = l.lid LEFT JOIN tours AS t ON t.f_did = d.did ".
            ((!$GLOBAL_VIEW) ? ", memberships AS m WHERE m.lid = l.lid AND m.cid = $cid" : '') . ' GROUP BY lid DESC, did DESC, trid DESC';
        $result = mysql_query($query);

        switch ($NODE_SRUCT)
        {
            case self::NODE_STRUCT__TREE:
                $struct = array();
                while ($r = mysql_fetch_object($result)) {
                    if (!empty($r->trid)) $struct[$r->lid][$r->did][$r->trid]['desc'] = array_intersect_key((array) $r, array_fill_keys(array_values($extraFields[T_NODE_TOURNAMENT]),null));
                    if (!empty($r->did))  $struct[$r->lid][$r->did]['desc']           = array_intersect_key((array) $r, array_fill_keys(array_values($extraFields[T_NODE_DIVISION]),null));
                                          $struct[$r->lid]['desc']                    = array_intersect_key((array) $r, array_fill_keys(array_values($extraFields[T_NODE_LEAGUE]),null));
                    $struct[$r->lid]['desc']['ring'] = ($FORCE_LOCAL_RING) ? $FORCE_LOCAL_RING : $r->ring;
                }            
                return $struct;
                
            case self::NODE_STRUCT__FLAT:
                $leagues = $divisions = $tours = array();
                while ($r = mysql_fetch_object($result)) {
                    if (!empty($r->trid)) $tours[$r->trid]    = array_intersect_key((array) $r, array_fill_keys(array_values($extraFields[T_NODE_TOURNAMENT]),null));
                    if (!empty($r->did))  $divisions[$r->did] = array_intersect_key((array) $r, array_fill_keys(array_values($extraFields[T_NODE_DIVISION]),null));
                                          $leagues[$r->lid]   = array_intersect_key((array) $r, array_fill_keys(array_values($extraFields[T_NODE_LEAGUE]),null));
                    $leagues[$r->lid]['ring'] = ($FORCE_LOCAL_RING) ? $FORCE_LOCAL_RING : $r->ring;
                }
                return array($leagues,$divisions,$tours);
                
            default:
                return false;
        }
    }

    public static function getCoaches($where = false) {
        $coaches = array();
        
        $query  = "SELECT coach_id FROM coaches";
        if($where) {
            $query = $query . ' WHERE ' . $where;
        }
        
        $result = mysql_query($query);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                array_push($coaches, new Coach($row['coach_id']));
            }
        }
                    
        return $coaches;
    }
    
    public static function login($coach, $passwd, $setCookie = false) {
        // $coach may be cid or coach name.

        if (!is_numeric($coach))
            $coach = get_alt_col('coaches', 'name', $coach, 'coach_id');

        if (self::checkPasswd($coach, $passwd) && !get_alt_col('coaches', 'coach_id', $coach, 'retired')) {
            self::_setSession($coach);
            if ($setCookie) {self::_setCookie($coach);}
            return true;
        }
        else {
            self::_delSession();
            self::_delCookies();
            return false;
        }
    }
    
    public static function cookieLogin() {

        return (
            !isset($_SESSION['logged_in']) && 
            isset($_COOKIE[LOGIN_COOKIE_COACHID]) && 
            isset($_COOKIE[LOGIN_COOKIE_PASSWD]) && 
            !get_alt_col('coaches', 'coach_id', $_COOKIE[LOGIN_COOKIE_COACHID], 'retired') && # Is not retired?
            self::checkPasswd($_COOKIE[LOGIN_COOKIE_COACHID], $_COOKIE[LOGIN_COOKIE_PASSWD], false) &&
            self::_setSession($_COOKIE[LOGIN_COOKIE_COACHID])
        );
    }
    
    public static function logout() {
        self::_delSession();
        self::_delCookies();
        return true;
    }
    
    public static function isLoggedIn() {
        if(isset($_SESSION['logged_in']))
            return $_SESSION['logged_in'];
        
        return false;
    }
    
    public static function checkPasswd($cid, $passwd, $MD5 = true) {
        return (get_alt_col('coaches', 'coach_id', $cid, 'passwd') == ($MD5 ? md5($passwd) : $passwd));
    }
    
    protected static function _setSession($cid) {
        $_SESSION['logged_in'] = true;
        $_SESSION['coach']     = get_alt_col('coaches', 'coach_id', $cid, 'name');
        $_SESSION['coach_id']  = $cid;
        return true;
    }
    
    protected static function _delSession() {
        session_unset();
        session_destroy();
        return true;
    }

    protected static function _setCookie($cid) {
        $expire=time()+60*60*24*30;
        setcookie(LOGIN_COOKIE_COACHID, $cid, $expire);
        setcookie(LOGIN_COOKIE_PASSWD, get_alt_col('coaches', 'coach_id', $cid, 'passwd'), $expire);
        return true;
    }    
   
    protected static function _delCookies() {
        setcookie(LOGIN_COOKIE_COACHID, '', time()-3600);
        setcookie(LOGIN_COOKIE_PASSWD, '', time()-3600);
        return true;
    }
    
    public static function create(array $input) {
        
        /**
         * Creates a new coach.
         *
         * Input: name, realname, passwd, mail, phone, ring, settings, def_leagues (array of LIDs)
         **/
         
        global $settings;
        
        if (
            empty($input['name']) || 
            empty($input['passwd']) || 
            get_alt_col('coaches', 'name', $input['name'], 'coach_id') || # Name exists already?
            !in_array($input['ring'], self::$RINGS[self::T_RING_GROUP_GLOBAL])
            ) 
            return false;

        $query = "INSERT INTO coaches (name, realname, passwd, mail, phone, ring, settings) 
                    VALUES ('" . mysql_real_escape_string($input['name']) . "',
                            '" . mysql_real_escape_string($input['realname']) . "', 
                            '" . md5($input['passwd']) . "', 
                            '" . mysql_real_escape_string($input['mail']) . "', 
                            '" . mysql_real_escape_string($input['phone']) . "', 
                            " . $input['ring'].",
                            '".array_strpack_assoc('%k=%v', $input['settings'], ',')."')";

        if (($status = mysql_query($query)) && is_numeric($cid = mysql_insert_id())) {
            // Set default memberships
            $newCoach = new Coach($cid);
            foreach (array_merge($settings['default_leagues'], $input['def_leagues']) as $lid) {
                $status &= $newCoach->setRing(self::T_RING_GROUP_LOCAL, self::T_RING_LOCAL_REGULAR, $lid);
            }
        }
        return $status ? $cid : false;
    }
}

