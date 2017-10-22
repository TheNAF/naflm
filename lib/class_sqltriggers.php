<?php 

// Trigger types
define('T_SQLTRIG_PLAYER_NEW',      1);
define('T_SQLTRIG_PLAYER_DPROPS',   2);
define('T_SQLTRIG_PLAYER_RELS',     3);

define('T_SQLTRIG_TEAM_NEW',        11);
define('T_SQLTRIG_TEAM_DPROPS',     12);
define('T_SQLTRIG_TEAM_RELS',       13);
define('T_SQLTRIG_TEAM_UPDATE_CHILD_RELS', 14);

define('T_SQLTRIG_COACH_UPDATE_CHILD_RELS', 24);
define('T_SQLTRIG_COACH_TEAMCNT', 25);

define('T_SQLTRIG_RACE_TEAMCNT', 35);

#define('T_SQLTRIG_MATCHDATA', 41); # Deprecated

define('T_SQLTRIG_MATCH_UPD', 51);
define('T_SQLTRIG_MATCH_DEL', 52);

class SQLTriggers
{
    public static function run($T_SQLTRIG, array $argv)
    {
        switch ($T_SQLTRIG)
        {
            case ($T_SQLTRIG <= 10): self::_player($T_SQLTRIG, $argv); break;
            case ($T_SQLTRIG <= 20): self::_team($T_SQLTRIG, $argv); break;
            case ($T_SQLTRIG <= 30): self::_coach($T_SQLTRIG, $argv); break;
            case ($T_SQLTRIG <= 40): self::_race($T_SQLTRIG, $argv); break;
#            case ($T_SQLTRIG <= 50): self::_md($T_SQLTRIG, $argv); break; # Deprecated
            case ($T_SQLTRIG <= 60): self::_match($T_SQLTRIG, $argv); break;
        }
        
        return true;
    }
    
    protected static function _player($T_SQLTRIG, array $argv)
    {
        $pid = $argv['id'];
        $p = $argv['obj'];
        switch ($T_SQLTRIG)
        {
            case T_SQLTRIG_PLAYER_NEW: 
                self::_player(T_SQLTRIG_PLAYER_RELS, $argv);
                self::_player(T_SQLTRIG_PLAYER_DPROPS, $argv);
                break;
            
            case T_SQLTRIG_PLAYER_DPROPS: 
                mysql_query("CALL getPlayerDProps($pid, @inj_ma,@inj_av,@inj_ag,@inj_st,@inj_ni, @ma,@av,@ag,@st, @ma_ua,@av_ua,@ag_ua,@st_ua, @value, @status, @date_died)") or die(mysql_error());
                mysql_query("UPDATE players SET 
                            inj_ma = @inj_ma, inj_av = @inj_av, inj_ag = @inj_ag, inj_st = @inj_st, inj_ni = @inj_ni, 
                            ma = @ma, av = @av, ag = @ag, st = @st, 
                            ma_ua = @ma_ua, av_ua = @av_ua, ag_ua = @ag_ua, st_ua = @st_ua, 
                            value = @value, status = @status, date_died = @date_died WHERE player_id = $pid") or die(mysql_error());
                self::_team(T_SQLTRIG_TEAM_DPROPS, array('id' => $p->owned_by_team_id, 'obj' => new Team($p->owned_by_team_id))); # TV updated dependency.
                break;
            
            case T_SQLTRIG_PLAYER_RELS: 
                mysql_query("CALL getPlayerRels($pid, @f_cid, @f_rid, @f_cname, @f_rname, @f_tname, @f_pos_name)") or die(mysql_error());
                mysql_query("UPDATE players SET f_cid = @f_cid, f_rid = @f_rid, f_cname = @f_cname, f_rname = @f_rname, f_tname = @f_tname, f_pos_name = @f_pos_name WHERE player_id = $pid") or die(mysql_error());
                break;
        }
    }
    
    protected static function _team($T_SQLTRIG, array $argv)
    {
        $tid = $argv['id'];
        $t = $argv['obj'];
        switch ($T_SQLTRIG)
        {
            case T_SQLTRIG_TEAM_NEW: 
                self::_team(T_SQLTRIG_TEAM_DPROPS, $argv);
                self::_team(T_SQLTRIG_TEAM_RELS, $argv);
                self::_coach(T_SQLTRIG_COACH_TEAMCNT, array('id' => $t->owned_by_coach_id, 'obj' => new Coach($t->owned_by_coach_id)));
                self::_race(T_SQLTRIG_RACE_TEAMCNT, array('id' => $t->f_race_id, 'obj' => new Race($t->f_race_id)));
                
                break;
            
            case T_SQLTRIG_TEAM_DPROPS: 
                mysql_query("CALL getTeamDProps($tid, @tv, @ff)") or die(mysql_error());
                mysql_query("UPDATE teams SET tv = @tv, ff = @ff WHERE team_id = $tid") or die(mysql_error());
                break;
            
            case T_SQLTRIG_TEAM_RELS: 
                mysql_query("CALL getTeamRels($tid, @f_cname, @f_rname)") or die(mysql_error());
                mysql_query("UPDATE teams SET f_cname = @f_cname, f_rname = @f_rname WHERE team_id = $tid") or die(mysql_error());
                break;
                
            case T_SQLTRIG_TEAM_UPDATE_CHILD_RELS: 
                mysql_query("UPDATE players,teams SET f_tname = teams.name, f_cid = owned_by_coach_id, players.f_cname = teams.f_cname WHERE team_id = $tid AND owned_by_team_id = team_id") or die(mysql_error());
                mysql_query("UPDATE mv_players,teams SET f_cid = owned_by_coach_id WHERE team_id = $tid AND f_tid = team_id") or die(mysql_error());
                mysql_query("UPDATE mv_teams,teams SET f_cid = owned_by_coach_id WHERE team_id = $tid AND f_tid = team_id") or die(mysql_error());
                self::_coach(T_SQLTRIG_COACH_TEAMCNT, array('id' => $t->owned_by_coach_id, 'obj' => new Coach($t->owned_by_coach_id)));
                break;
        }
    }
    
    protected static function _coach($T_SQLTRIG, array $argv)
    {
        $cid = $argv['id'];
        $c = $argv['obj'];
        switch ($T_SQLTRIG)
        {
            case T_SQLTRIG_COACH_UPDATE_CHILD_RELS: 
                mysql_query("UPDATE players,coaches SET f_cname = coaches.name WHERE coach_id = $cid AND f_cid = coach_id") or die(mysql_error());
                mysql_query("UPDATE teams,coaches SET f_cname = coaches.name WHERE coach_id = $cid AND owned_by_coach_id = coach_id") or die(mysql_error());
                break;
                
            case T_SQLTRIG_COACH_TEAMCNT:
                mysql_query("UPDATE coaches SET team_cnt = getTeamCnt(".T_OBJ_COACH.", $cid, NULL) WHERE coach_id = $cid") or die(mysql_error());
                break;
        }
    }
    
    protected static function _race($T_SQLTRIG, array $argv)
    {
        $rid = $argv['id'];
        $r = $argv['obj'];
        switch ($T_SQLTRIG)
        {
            case T_SQLTRIG_RACE_TEAMCNT:
                mysql_query("UPDATE races SET team_cnt = getTeamCnt(".T_OBJ_RACE.", $rid, NULL) WHERE race_id = $rid") or die(mysql_error());
                break;
        }
    }
    
    protected static function _match($T_SQLTRIG, array $argv)
    {
        switch ($T_SQLTRIG)
        {
            case T_SQLTRIG_MATCH_UPD:
                mysql_query("CALL match_sync($argv[mid], $argv[trid], $argv[tid1], $argv[tid2], $argv[played])") or die(mysql_error());
                break;

            case T_SQLTRIG_MATCH_DEL:
                mysql_query("CALL match_sync($argv[mid], $argv[trid], $argv[tid1], $argv[tid2], TRUE)") or die(mysql_error());
                break;
        }
    }
}

