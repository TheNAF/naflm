<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2012. All Rights Reserved.
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

class Cemetery implements ModuleInterface
{

public static function main($argv)
{
    global $coach;
    list($tid) = $argv;
    if ($tid) {
        if (!get_alt_col('teams','team_id',$tid,'name')) {
            fatal('Invalid team');
        }
        $team = new Team($tid);
        $ALLOW_EDIT = (is_object($coach) && ($team->owned_by_coach_id == $coach->coach_id || $coach->mayManageObj(T_OBJ_TEAM, $tid)) && !$team->is_retired); # Show team action boxes?
    }
    else {
        $team = null;
        $ALLOW_EDIT = false;
    }
    
    if ($ALLOW_EDIT && isset($_POST['action']) && isset($_POST['pid'])) {
        $pid = (int) $_POST['pid'];
        switch ($_POST['action'])
        {
            case 'delete':
                status(self::delete((int) $pid));
                break;
                    
            case 'new':
            case 'edit':
                status(self::edit((int) $pid, $_POST['title'], $_POST['about']));
                break;
        }
    }
    self::printList($team,$ALLOW_EDIT);    
    return true;
}

private static function printList($team,$ALLOW_EDIT)
{
    global $lng;
    
    if ($team) {
        $entries = self::entries($team->team_id);
        title("<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$team->team_id,false,false)."'>".$team->name.'</a>&nbsp;'.$lng->getTrn('name', __CLASS__));
    }
    else {
        $entries = self::entries(null);
        title($lng->getTrn('name', __CLASS__));
    }
    echo "<table style='table-layout:fixed; width:".(count($entries) == 1 ? 50 : 100)."%;'><tr>"; # The percentage difference is a HTML layout fix.
    $i = 1;
    foreach ($entries as $e) {
        $boxname = "container".$e->pid;
        $ENTRY_EXISTS = (isset($e->cemetery_id) && (int) $e->cemetery_id > 0);
        if ($i > 2) {
            echo "\n</tr>\n<tr>\n";
            $i = 1;
        }
        ?>
        <td style='width:50%;' valign='top'>
        <div class="boxWide" style="width: 80%; margin: 20px auto 20px auto;">
            <div class="boxTitle<?php echo T_HTMLBOX_INFO;?>">
                <?php 
                echo "<a href='".urlcompile(T_URL_PROFILE,T_OBJ_PLAYER,$e->pid,false,false)."'>$e->name</a>";
                if (!$team) { # Not a single teams cemetery, but all of the cemetery.
                    echo " (<a href='".urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$e->f_tid,false,false)."'>".$e->f_tname.'</a>)';
                }
                if (!empty($e->title)) {
                    echo ": $e->title";
                }
                ?>
            </div>
            <div class="boxBody">
                <table class="common">
                    <tr>
                        <td align="left" valign="top">
                            <?php 
                            echo $lng->getTrn('died', __CLASS__)." ".textdate($e->date_died,true).' '.$lng->getTrn('after', __CLASS__).' <b>'.$e->lifetime.' '.$lng->getTrn('days', __CLASS__)."</b><br><br>";
                            echo $e->about;
                            ?>
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
                        <?php
                            if ($ENTRY_EXISTS) 
                                echo $lng->getTrn('posted', __CLASS__).' '. textdate($e->date,true);
                        ?>
                        </td>
                        <td align="right">
                        <?php
                        if ($ALLOW_EDIT) {
                            ?> 
                            <a href='javascript:void(0);' onClick="slideToggle('<?php echo $boxname;?>');"><?php echo $lng->getTrn('edit', __CLASS__);?></a>
                            &nbsp;
                            <?php
                            if (isset($e->cemetery_id) && (int) $e->cemetery_id > 0) {
                                echo inlineform(array('action' => 'delete', 'pid' => $e->pid), "cemform$e->cemetery_id", $lng->getTrn('del', __CLASS__) );
                            }
                        }
                        ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div id="<?php echo $boxname;?>" style="display:none; clear:both;">
                                <br>
                                <form method="POST">
                                    <?php echo "<b>".$lng->getTrn('g_title', __CLASS__).'</b> &mdash; '.$lng->getTrn('title', __CLASS__);?><br>
                                    <textarea name="title" cols="48" rows="1"><?php if ($ENTRY_EXISTS) { echo $e->title;}?></textarea><br><br>
                                    <?php echo "<b>".$lng->getTrn('g_about', __CLASS__).'</b> &mdash; '.$lng->getTrn('about', __CLASS__);?><br>
                                    <textarea name="about" cols="48" rows="12"><?php if ($ENTRY_EXISTS) { echo $e->about;}?></textarea>
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="pid" value="<?php echo $e->pid;?>">
                                    <br><br>
                                    <input type="submit" value="<?php echo $lng->getTrn('common/submit');?>">
                                </form>
                            </div>
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


private static function entries($tid)
{
    $WHERE_TID = ($tid) ? "AND owned_by_team_id = $tid" : '';
    $query = "SELECT name, date_died, player_id as 'pid', owned_by_team_id 'f_tid', f_tname,  cemetery_id,date,title,about, DATEDIFF(date_died,date_bought) AS 'lifetime' FROM players LEFT JOIN cemetery ON players.player_id = cemetery.pid WHERE date_died IS NOT NULL $WHERE_TID ORDER BY players.date_died DESC";
    $result = mysql_query($query);
    $entries = array();
    if (mysql_num_rows($result) > 0) {
        while ($obj = mysql_fetch_object($result)) {
            $entries[] = $obj;
        }    
    }
    return $entries;
}

public static function edit($pid, $title, $about) 
{
    $ENTRY_EXISTS = get_alt_col('cemetery', 'pid', $pid, 'date');
    if ($ENTRY_EXISTS) {
        $query = "UPDATE cemetery SET 
            title = '".mysql_real_escape_string($title)."', 
            about = '".mysql_real_escape_string($about)."' 
            WHERE pid = $pid";
        return mysql_query($query);
    }
    else {
        $query = "INSERT INTO cemetery 
            (pid, title, about, date) 
            VALUES 
            ($pid, '".mysql_real_escape_string($title)."', '".mysql_real_escape_string($about)."', NOW())";
        return mysql_query($query);
    }
}

public static function delete($pid)
{
    return (mysql_query("DELETE FROM cemetery WHERE pid = $pid"));
}

/*
 *  This function returns information about the module and its author.
 */
public static function getModuleAttributes()
{
    return array(
        'author'     => 'Nicholas Rathmann',
        'moduleName' => 'Cemetery',
        'date'       => '2012',
        'setCanvas'  => true, # If true, whenever your main() is run through Module::run() your code's output will be "sandwiched" into the standard HTML frame.
    );
}

/*
 *  This function returns the MySQL table definitions for the tables required by the module. If no tables are used array() should be returned.
 */
public static function getModuleTables()
{
    return array(
        'cemetery' => array(
            'cemetery_id' => 'MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
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
        '091-095' => array(
            'CREATE TABLE IF NOT EXISTS cemetery
            (
                    cemetery_id MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    pid         MEDIUMINT UNSIGNED,
                    date        DATETIME,
                    title       TEXT,
                    about       TEXT
            )',
        ),
    );
}
public static function triggerHandler($type, $argv){}

}
?>
