<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2009-2011. All Rights Reserved.
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

class Wanted implements ModuleInterface
{
/***************
 * Properties 
 ***************/

public $wanted_id   = 0;
public $pid         = 0;
public $date        = '';
public $why         = '';
public $bounty      = '';

/***************
 * Methods 
 ***************/    

function __construct($wanted_id) 
{
    $result = mysql_query("SELECT * FROM wanted WHERE wanted_id = $wanted_id");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            foreach ($row as $key => $val) {
                $this->$key = $val;
            }
        }
    }
}

public function edit($why, $bounty) 
{
    if (mysql_query("UPDATE wanted SET 
                    why = '".mysql_real_escape_string($why)."', 
                    bounty = '".mysql_real_escape_string($bounty)."' 
                    WHERE wanted_id = $this->wanted_id")) {
        $this->why = $why;
        $this->bounty = $bounty;
        return true;
    }
    else
        return false;
}

public function delete()
{
    return (mysql_query("DELETE FROM wanted WHERE wanted_id = $this->wanted_id"));
}

/***************
 * Statics
 ***************/

public static function getWanted($node, $id, $N = false)
{
    $list = array();
    if (!$node && !$id) { # Special case
        $node = 'ALL';
    }
    $_TBL = 'wanted'; # Table name.
    $_IS_OBJ = in_array($node, array(T_OBJ_COACH, T_OBJ_TEAM));
    $_LIMIT = ($N && is_numeric($N)) ? " LIMIT $N" : '';
    $_ORDER_BY__NODE = " ORDER BY $_TBL.date DESC ";
    $_ORDER_BY__OBJ = " ORDER BY $_TBL.date DESC ";
    $_COMMON_PLAYERS_FIELDS = "players.owned_by_team_id AS 'f_tid', players.f_tname, players.f_cid, players.f_cname, players.date_died, players.value, players.name, players.f_pos_name, players.date_died"; # From players table.
    switch ($node) {
        case T_NODE_LEAGUE:
            if (!isset($_WHERE)) { 
                $_WHERE = "AND mv_players.f_lid = $id ";
            }
            # Fall through
        case T_NODE_DIVISION:
            if (!isset($_WHERE)) { 
                $_WHERE = "AND mv_players.f_did = $id ";
            }
            # Fall through
        case T_NODE_TOURNAMENT:
            if (!isset($_WHERE)) { 
                $_WHERE = "AND mv_players.f_trid = $id "; 
            }
            # Fall through
        case 'ALL':
            if (!isset($_WHERE)) { 
                $_WHERE = " ";
            }
            $query = "SELECT DISTINCT ${_TBL}_id AS 'id', mv_players.f_lid as 'lid', $_COMMON_PLAYERS_FIELDS FROM $_TBL,mv_players,players WHERE $_TBL.pid = mv_players.f_pid AND mv_players.f_pid = players.player_id ".$_WHERE.$_ORDER_BY__NODE.$_LIMIT;
            break;
            
        case T_OBJ_COACH:
            $query = "SELECT ${_TBL}_id AS 'id', $_COMMON_PLAYERS_FIELDS FROM $_TBL,players,teams WHERE $_TBL.pid = players.player_id AND players.owned_by_team_id = teams.team_id AND teams.owned_by_coach_id = $id ".$_ORDER_BY__OBJ.$_LIMIT;
            break;
        case T_OBJ_TEAM:
            $query = "SELECT ${_TBL}_id AS 'id', $_COMMON_PLAYERS_FIELDS FROM $_TBL,players       WHERE $_TBL.pid = players.player_id AND players.owned_by_team_id = $id ".$_ORDER_BY__OBJ.$_LIMIT;
            break;
            
        default:
            return array();
    }

    $result = mysql_query($query) or die(mysql_error());
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $entry = new self($row['id']);
            // Add extra fields to object.
            unset($row['id']);
            foreach ($row as $f => $val) {
                $entry->$f = $val;
            }
            $list[] = $entry;
        }
    }
    return $list;
}

public static function create($player_id, $why, $bounty)
{
        return (mysql_query("
                INSERT INTO wanted 
                (pid, why, bounty, date) 
                VALUES 
                ($player_id, '".mysql_real_escape_string($why)."', '".mysql_real_escape_string($bounty)."', NOW())
                "));
}

/***************
 * Interface
 ***************/

public static function main($argv) 
{
    // func may be "isWanted" or "makeList".
    $func = array_shift($argv);
    return call_user_func_array(array(__CLASS__, $func), $argv);
}

public static function getModuleAttributes()
{
    return array(
        'author'     => 'Nicholas Mossor Rathmann',
        'moduleName' => 'Wanted',
        'date'       => '2008',
        'setCanvas'  => false,
    );
}

public static function getModuleTables()
{
    return array(
        'wanted' => array(
            'wanted_id' => 'MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
            'pid'    => 'MEDIUMINT UNSIGNED',
            'date'   => 'DATETIME',
            'why'    => 'TEXT',
            'bounty' => 'TEXT',
        )
    );
}

public static function getModuleUpgradeSQL()
{
    return array(
        '075-080' => array(
            'CREATE TABLE IF NOT EXISTS wanted
            (
                    wanted_id   MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    pid         MEDIUMINT UNSIGNED,
                    date        DATETIME,
                    why         TEXT,
                    bounty      TEXT
            )',
            'INSERT INTO wanted (pid, date, why, bounty) SELECT f_id, date, txt, txt2 FROM texts WHERE type = 6 ORDER BY date ASC',
            'DELETE FROM texts WHERE type = 6',
        ),
    );
}

public static function triggerHandler($type, $argv){}

/***************
 * main() related.
 ***************/

public static  function isWanted($pid) 
{
    $query = "SELECT pid FROM wanted WHERE pid = $pid";
    return (($result = mysql_query($query)) && mysql_num_rows($result) > 0);
}

public static function makeList() {
    global $lng, $coach, $settings;
    HTMLOUT::frame_begin(); # Make page frame, banner and menu.
    
    title($lng->getTrn('name', __CLASS__));
    echo $lng->getTrn('desc', __CLASS__)."<br><br>\n";
    list($sel_node, $sel_node_id) = HTMLOUT::nodeSelector(array());

    $ALLOW_EDIT = (is_object($coach) && $coach->isNodeCommish($sel_node, $sel_node_id));
    
    /* A new entry was sent. Add it to system */
    if (isset($_POST['player_id']) && $ALLOW_EDIT) {
        if (get_magic_quotes_gpc()) {
            $_POST['bounty'] = stripslashes($_POST['bounty']);
            $_POST['why']    = stripslashes($_POST['why']);
        }
        switch ($_GET['action'])
        {
            case 'edit':
                $e = new self($_GET['wanted_id']);
                status($e->edit($_POST['why'], $_POST['bounty']));
                break;
            
            case 'new':
                status(self::create($_POST['player_id'], $_POST['why'], $_POST['bounty']));
                break;
        }
    }
    
    /* Was a request for a new entry made? */ 
    
    if (isset($_GET['action']) && $ALLOW_EDIT) {
        
        // Default schema values. These are empty unless "edit" is chosen.
        $player_id = false;
        $bounty = '';
        $why = '';
        
        switch ($_GET['action'])
        {
            case 'delete':
                if (isset($_GET['wanted_id']) && is_numeric($_GET['wanted_id'])) {
                    $e = new self($_GET['wanted_id']);
                    status($e->delete());
                    unset($e);
                }
                else {
                    fatal('Sorry. You did not specify which wanted-id you wish to delete.');
                }                
                break;
                
            case 'edit':
                if (isset($_GET['wanted_id']) && is_numeric($_GET['wanted_id'])) {
                    $e = new self($_GET['wanted_id']);
                    $player_id = $e->pid;
                    $why = $e->why;
                    $bounty = $e->bounty;
                    $_POST['lid'] = get_alt_col('mv_players', 'f_pid', $player_id, 'f_lid');
                }
                else {
                    fatal('Sorry. You did not specify which wanted-id you wish to edit.');
                }
                
                // Fall-through to "new" !!!

            case 'new':
                echo "<a href='handler.php?type=wanted'><-- ".$lng->getTrn('common/back')."</a><br><br>";
                $_DISABLED = !isset($_POST['lid']) ? 'DISABLED' : '';
                $node_id = isset($_POST['lid']) ? $_POST['lid'] : null;
                ?>
                <form name="STS" method="POST" enctype="multipart/form-data">
                <b><?php echo $lng->getTrn('common/league');?></b><br>
                <?php
                echo HTMLOUT::nodeList(T_NODE_LEAGUE, 'lid', array(), array(), array('sel_id' => $node_id));
                ?>
                <input type='submit' value='<?php echo $lng->getTrn('common/select');?>'>
                </form>
                <br>
                <form method="POST">
                <b><?php echo $lng->getTrn('player', __CLASS__).'</b>&nbsp;&mdash;&nbsp;'.$lng->getTrn('sort_hint', __CLASS__);?><br>
                <?php
                $query = "SELECT DISTINCT player_id, f_tname, name FROM players, mv_players WHERE player_id = f_pid AND f_lid = $node_id ORDER by f_tname ASC, name ASC";
                $result = mysql_query($query);
                if ($result && mysql_num_rows($result) == 0) {
                    $_DISABLED = 'DISABLED';
                }
                ?>
                <select name="player_id" id="players" <?php echo $_DISABLED;?>>
                    <?php
                    while ($row = mysql_fetch_assoc($result)) {
                        echo "<option value='$row[player_id]' ".(($player_id == $row['player_id']) ? 'SELECTED' : '').">$row[f_tname]: $row[name] </option>\n";
                    }
                    ?>
                </select>           
                <br><br>
                <b><?php echo $lng->getTrn('g_title', __CLASS__).'</b>&nbsp;&mdash;&nbsp;'.$lng->getTrn('title', __CLASS__);?><br>
                <input type="text" name="bounty" size="60" maxlength="100" value="<?php echo $bounty;?>" <?php echo $_DISABLED;?>>
                <br><br>
                <b><?php echo $lng->getTrn('g_about', __CLASS__).'</b>&nbsp;&mdash;&nbsp;'.$lng->getTrn('about', __CLASS__);?><br>
                <textarea name="why" rows="15" cols="100" <?php echo $_DISABLED;?>><?php echo $why;?></textarea>
                <br><br>
                <input type="submit" value="<?php echo $lng->getTrn('submit', __CLASS__);?>" name="Submit" <?php echo $_DISABLED;?>>
                </form>
                <br>
                <?php
                echo $lng->getTrn('note', __CLASS__);
        
                return;
                break;

        }
    }
    
    if ($ALLOW_EDIT) {
        echo "<br><a href='handler.php?type=wanted&amp;action=new'>".$lng->getTrn('new', __CLASS__)."</a><br>\n";
    }
    
    /* Print the wanted players */
    self::printList($sel_node, $sel_node_id, $ALLOW_EDIT);
    HTMLOUT::frame_end();
}

public static function printList($node, $node_id, $ALLOW_EDIT)
{
    global $lng;
    $entries = self::getWanted($node,$node_id);
    echo "<table style='table-layout:fixed; width:".(count($entries) == 1 ? 50 : 100)."%;'><tr>"; # The percentage difference is a HTML layout fix.
    $i = 1;
    foreach ($entries as $e) {
        if ($i > 2) {
            echo "\n</tr>\n<tr>\n";
            $i = 1;
        }
        ?>
        <td style='width:50%;' valign='top'>
        <div class="boxWide" style="width: 80%; margin: 20px auto 20px auto;">
            <div class="boxTitle<?php echo T_HTMLBOX_INFO;?>"><?php 
                echo $lng->getTrn('wanted', __CLASS__).": <a href='".urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$e->pid,false,false)."'>$e->name</a>";
                echo ' (<a href="'.urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$e->f_tid,false,false).'">'.$e->f_tname.'</a>)';
            ?>
            </div>
            <div class="boxBody">
                <table class="common">
                    <tr>
                        <td colspan="2" align="left" valign="top">
                            <b><?php echo $lng->getTrn('g_title', __CLASS__);?>:</b><br>
                            <?php echo $e->bounty;?>
                            <br>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                        <br>
                        <b><?php echo $lng->getTrn('g_about', __CLASS__);?>:</b><br>
                        <?php 
                        echo $e->why;
                        if ($e->date_died) {
                            echo "<br><br><font color='red'><b>".$lng->getTrn('killed', __CLASS__)."</b></font>\n";
                        }
                        ?>
                        </td>
                        <td align="right" style="width: 25%;">
                            <img border='0px' height='75' width='75' alt='player picture' src="<?php $img = new ImageSubSys(T_OBJ_PLAYER, $e->pid); echo $img->getPath();?>">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <td align="left">
                        <?php echo $lng->getTrn('posted', __CLASS__).' '.textdate($e->date,true);?>
                        </td>
                        <td align="right">
                        <?php
                        if ($ALLOW_EDIT) {
                            ?> 
                            <a href="handler.php?type=wanted&amp;action=edit&amp;wanted_id=<?php echo $e->wanted_id;?>"><?php echo $lng->getTrn('edit', __CLASS__);?></a>
                            &nbsp;
                            <a href="handler.php?type=wanted&amp;action=delete&amp;wanted_id=<?php echo $e->wanted_id;?>"><?php echo $lng->getTrn('del', __CLASS__);?></a> 
                            <?php
                        }
                        ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        </td>
        <?php
        $i++;
    }
    echo "</tr></table>";
}
}
?>
