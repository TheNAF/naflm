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

class HOF implements ModuleInterface
{

/***************
 * Properties 
 ***************/

public $hof_id      = 0;
public $pid         = 0;
public $date        = '';
public $title       = '';
public $about       = '';

/***************
 * Methods 
 ***************/    

function __construct($hof_id) 
{
    $result = mysql_query("SELECT * FROM hof WHERE hof_id = $hof_id");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            foreach ($row as $key => $val) {
                $this->$key = $val;
            }
        }
    }
}

public function edit($title, $about) 
{
    if (mysql_query("UPDATE hof SET 
                    title = '".mysql_real_escape_string($title)."', 
                    about = '".mysql_real_escape_string($about)."' 
                    WHERE hof_id = $this->hof_id")) {
        $this->title = $title;
        $this->about = $about;
        return true;
    }
    else
        return false;
}

public function delete()
{
    return (mysql_query("DELETE FROM hof WHERE hof_id = $this->hof_id"));
}

/***************
 * Statics
 ***************/

public static function getHOF($node, $id, $N = false)
{
    $list = array();
    if (!$node && !$id) { # Special case
        $node = 'ALL';
    }
    $_TBL = 'hof'; # Table name.
    $_IS_OBJ = in_array($node, array(T_OBJ_COACH, T_OBJ_TEAM));
    $_LIMIT = ($N && is_numeric($N)) ? " LIMIT $N" : '';
    $_ORDER_BY__NODE = " ORDER BY $_TBL.date DESC ";
    $_ORDER_BY__OBJ = " ORDER BY $_TBL.date DESC ";
    $_COMMON_PLAYERS_FIELDS = "players.owned_by_team_id AS 'f_tid', players.f_tname, players.f_cid, players.f_cname, players.date_died, players.value, players.name, players.f_pos_name"; # From players table.
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

public static function create($player_id, $title, $about)
{
    return (mysql_query("
            INSERT INTO hof 
            (pid, title, about, date) 
            VALUES 
            ($player_id, '".mysql_real_escape_string($title)."', '".mysql_real_escape_string($about)."', NOW())
            "));
}

/***************
 * Interface
 ***************/

public static function main($argv) 
{
    // func may be "isInHOF" or "makeList".
    $func = array_shift($argv);
    return call_user_func_array(array(__CLASS__, $func), $argv);
}

public static function getModuleAttributes()
{
    return array(
        'author'     => 'Nicholas Mossor Rathmann',
        'moduleName' => 'Hall of fame',
        'date'       => '2008',
        'setCanvas'  => false,
    );
}

public static function getModuleTables()
{
    return array(
        'hof' => array(
            'hof_id' => 'MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
            'pid'    => 'MEDIUMINT UNSIGNED',
            'date'   => 'DATETIME',
            'title'  => 'TEXT',
            'about'  => 'TEXT',
        )
    );
}

public static function getModuleUpgradeSQL()
{
    return array(
        '075-080' => array(
            'CREATE TABLE IF NOT EXISTS hof
            (
                    hof_id  MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    pid     MEDIUMINT UNSIGNED,
                    date    DATETIME,
                    title   TEXT,
                    about   TEXT
            )',
            'INSERT INTO hof (pid, date, title, about) SELECT f_id, date, txt2, txt FROM texts WHERE type = 5 ORDER BY date ASC',
            'DELETE FROM texts WHERE type = 5',
        ),
    );
}

public static function triggerHandler($type, $argv){}

/***************
 * main() related.
 ***************/

public static function isInHOF($pid) 
{
    $query = "SELECT pid FROM hof WHERE pid = $pid";
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
            $_POST['title'] = stripslashes($_POST['title']);
            $_POST['about'] = stripslashes($_POST['about']);
        }
        switch ($_GET['action'])
        {
            case 'edit':
                $e = new self($_GET['hof_id']);
                status($e->edit($_POST['title'], $_POST['about']));
                break;
            
            case 'new':
                status(self::create($_POST['player_id'], $_POST['title'], $_POST['about']));
                break;
        }
    }  
    
    /* Was a request for a new entry made? */    
    if (isset($_GET['action']) && $ALLOW_EDIT) {        
        // Default schema values. These are empty unless "edit" is chosen.
        $player_id = false;
        $title = '';
        $about = '';
        
        switch ($_GET['action'])
        {
            case 'delete':
                if (isset($_GET['hof_id']) && is_numeric($_GET['hof_id'])) {
                    $e = new self($_GET['hof_id']);
                    status($e->delete());
                    unset($e);
                }
                else {
                    fatal('Sorry. You did not specify which HOF-id you wish to delete.');
                }                
                break;
                
            case 'edit':
                if (isset($_GET['hof_id']) && is_numeric($_GET['hof_id'])) {
                    $e = new self($_GET['hof_id']);
                    $player_id = $e->pid;
                    $title = $e->title;
                    $about = $e->about;
                    $_POST['lid'] = get_alt_col('mv_players', 'f_pid', $player_id, 'f_lid');
                }
                else {
                    fatal('Sorry. You did not specify which HOF-id you wish to edit.');
                }
                
                // Fall-through to "new" !!!

            case 'new':
                echo "<a href='handler.php?type=hof'><-- ".$lng->getTrn('common/back')."</a><br><br>";
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
                <input type="text" name="title" size="60" maxlength="100" value="<?php echo $title;?>" <?php echo $_DISABLED;?>>
                <br><br>
                <b><?php echo $lng->getTrn('g_about', __CLASS__).'</b>&nbsp;&mdash;&nbsp;'.$lng->getTrn('about', __CLASS__);?><br>
                <textarea name="about" rows="15" cols="100" <?php echo $_DISABLED;?>><?php echo $about;?></textarea>
                <br><br>
                <input type="submit" value="<?php echo $lng->getTrn('submit', __CLASS__);?>" name="Submit" <?php echo $_DISABLED;?>>
                </form>
                <?php                
        
                return;
                break;

        }
    }
    
    if ($ALLOW_EDIT) {
        echo "<br><a href='handler.php?type=hof&amp;action=new'>".$lng->getTrn('new', __CLASS__)."</a><br>\n";
    }
    
    /* Print the hall of fame */
    self::printList($sel_node, $sel_node_id, $ALLOW_EDIT);
    HTMLOUT::frame_end();
}

public static function printList($node, $node_id, $ALLOW_EDIT)
{
    global $lng;
    $entries = self::getHOF($node,$node_id);
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
            <div class="boxTitle<?php echo T_HTMLBOX_INFO;?>"><?php echo "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$e->pid,false,false)."'>$e->name</a> ".$lng->getTrn('from', __CLASS__)." <a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$e->f_tid,false,false)."'>$e->f_tname</a>: $e->title";?></div>
            <div class="boxBody">
                <table class="common">
                    <tr>
                        <td align="left" valign="top">
                            <?php echo $e->about;?>
                        </td>
                        <td align="right" style='width:25%;'>
                            <img border='0px' height='75' width='75' alt='player picture' src="<?php $img = new ImageSubSys(T_OBJ_PLAYER, $e->pid); echo $img->getPath();?>">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <td align="left">
                        <?php echo $lng->getTrn('posted', __CLASS__).' '. textdate($e->date,true);?>
                        </td>
                        <td colspan="2" align="right">
                        <?php
                        if ($ALLOW_EDIT) {
                            ?> 
                            <a href="handler.php?type=hof&amp;action=edit&amp;hof_id=<?php echo $e->hof_id;?>"><?php echo $lng->getTrn('edit', __CLASS__);?></a>
                            &nbsp;
                            <a href="handler.php?type=hof&amp;action=delete&amp;hof_id=<?php echo $e->hof_id;?>"><?php echo $lng->getTrn('del', __CLASS__);?></a> 
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