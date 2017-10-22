<?php

class TeamCompare implements ModuleInterface
{

public static function main($argv) # argv = argument vector (array).
{
    global $lng;
    title($lng->getTrn('name', __CLASS__));
    $teams = self::_teamsSelect();
    if (is_array($teams)) {
        list($tid1,$tid2) = $teams;
        self::_compare($tid1,$tid2);
    }
    return true;
}

protected static function _teamsSelect() 
{
    global $lng;
    $_SUBMITTED = isset($_POST['team1_as']) && isset($_POST['team2_as']) && $_POST['team1_as'] && $_POST['team2_as'];
    $t1 = $t2 = '';
    if ($_SUBMITTED) {
        $t1 = $_POST['team1_as'];
        $t2 = $_POST['team2_as'];
    }
    ?>
    <br>
    <center>
    <form method='POST'>
    <input type="text" id='team1_as' name="team1_as" size="30" maxlength="50" value="<?php echo $t1;?>">
    <script>
        $(document).ready(function(){
            var options, a;

            options = {
                minChars:3,
                    serviceUrl:'handler.php?type=autocomplete&obj=<?php echo T_OBJ_TEAM;?>',
            };
            a = $('#team1_as').autocomplete(options);
        });
    </script>
        VS.
    <input type="text" id='team2_as' name="team2_as" size="30" maxlength="50" value="<?php echo $t2;?>">
    <script>
        $(document).ready(function(){
            var options, b;

            options = {
                minChars:3,
                    serviceUrl:'handler.php?type=autocomplete&obj=<?php echo T_OBJ_TEAM;?>',
            };
            b = $('#team2_as').autocomplete(options);
        });
    </script>
    <br><br>
    <input type="submit" name="compare" value="<?php echo $lng->getTrn('cmp', __CLASS__);?>!">
    </form>
    </center>
    <br>
    <?php
    return $_SUBMITTED ? array(get_alt_col('teams', 'name', $t1, 'team_id'), get_alt_col('teams', 'name', $t2, 'team_id')) : null;
}

public static $T_TEAM_PROGRESS = array(
    # TV greater than => title
    '0'    => 'Easily beaten',
    '700'  => 'Underdog',
    '1000' => 'Amateur',
    '1300' => 'Experienced',
    '1600' => 'Semi-pro',
    '1900' => 'Profesional',
    '2200' => 'Unstoppable',
);
# These gives some sense of relative scale of what is large values of these properties.
public static $T_MAX_TV = 2200;
public static $T_MAX_CAS = 100;
public static $T_MAX_GF = 80; # Goals for
public static $T_MAX_PLAYED = 80;
public static $T_MAX_WON = 80;
public static $T_MAX_ELO = 380;
public static $T_MAX_CP = 100;
public static $T_MAX_INT = 15;

protected static function _getLevel($value) {
    $str = self::$T_TEAM_PROGRESS[0];
    foreach (self::$T_TEAM_PROGRESS as $tv => $lvl) {
        if ($value < $tv) {
            break;
        }
        $str = $lvl;
    }
    return $str;
}

protected static function _compare($tid1, $tid2) 
{
    $t1 = new Team($tid1);
    $t2 = new Team($tid2);
    # http://docs.jquery.com/UI/Progressbar
    ?>
      <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
      <!--
      <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
      <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
      -->
    <center>
    <table style='width:70%; '>
        <tr><td style='width:120px;'> </td>
            <td><a href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$tid1,false,false);?>"><b><?php echo $t1->name;?></b></a></td>
            <td><a href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$tid2,false,false);?>"><b><?php echo $t2->name;?></b></a></td>
        </tr>
        <tr><td>Team value</td>
            <td><?php self::_bar(($t1->value/1000)/self::$T_MAX_TV *100, $t1->value/1000 .'k &mdash; '.self::_getLevel($t1->value/1000), ($t1->value/1000)/self::$T_MAX_TV *100);?></td>
            <td><?php self::_bar(($t2->value/1000)/self::$T_MAX_TV *100, $t2->value/1000 .'k &mdash; '.self::_getLevel($t2->value/1000), ($t2->value/1000)/self::$T_MAX_TV *100);?></td>
        </tr>
        <tr><td>ELO</td>
            <td><?php self::_bar($t1->rg_elo/self::$T_MAX_ELO *100, $t1->rg_elo, $t1->rg_elo/self::$T_MAX_ELO *100);?></td>
            <td><?php self::_bar($t2->rg_elo/self::$T_MAX_ELO *100, $t2->rg_elo, $t2->rg_elo/self::$T_MAX_ELO *100);?></td>
        </tr>
        <tr><td>Games played</td>
            <td><?php self::_bar($t1->mv_played/self::$T_MAX_PLAYED *100, $t1->mv_played, $t1->mv_played/self::$T_MAX_PLAYED *100);?></td>
            <td><?php self::_bar($t2->mv_played/self::$T_MAX_PLAYED *100, $t2->mv_played, $t2->mv_played/self::$T_MAX_PLAYED *100);?></td>
        </tr>
        <tr><td>Games won</td>
            <td><?php self::_bar($t1->mv_won/self::$T_MAX_WON *100, $t1->mv_won, $t1->mv_won/self::$T_MAX_WON *100);?></td>
            <td><?php self::_bar($t2->mv_won/self::$T_MAX_WON *100, $t2->mv_won, $t2->mv_won/self::$T_MAX_WON *100);?></td>
        </tr>
        <tr><td>Goals scored</td>
            <td><?php self::_bar($t1->mv_gf/self::$T_MAX_GF *100, $t1->mv_gf, $t1->mv_gf/self::$T_MAX_GF *100);?></td>
            <td><?php self::_bar($t2->mv_gf/self::$T_MAX_GF *100, $t2->mv_gf, $t2->mv_gf/self::$T_MAX_GF *100);?></td>
        </tr>
        <tr><td>CAS inflicted</td>
            <td><?php self::_bar($t1->mv_cas/self::$T_MAX_CAS *100, $t1->mv_cas, $t1->mv_cas/self::$T_MAX_CAS *100);?></td>
            <td><?php self::_bar($t2->mv_cas/self::$T_MAX_CAS *100, $t2->mv_cas, $t2->mv_cas/self::$T_MAX_CAS *100);?></td>
        </tr>
        <tr><td>CP</td>
            <td><?php self::_bar($t1->mv_cp/self::$T_MAX_CP *100, $t1->mv_cp, $t1->mv_cp/self::$T_MAX_CP *100);?></td>
            <td><?php self::_bar($t2->mv_cp/self::$T_MAX_CP *100, $t2->mv_cp, $t2->mv_cp/self::$T_MAX_CP *100);?></td>
        </tr>
        <tr><td>Int</td>
            <td><?php self::_bar($t1->mv_intcpt/self::$T_MAX_INT *100, $t1->mv_intcpt, $t1->mv_intcpt/self::$T_MAX_INT *100);?></td>
            <td><?php self::_bar($t2->mv_intcpt/self::$T_MAX_INT *100, $t2->mv_intcpt, $t2->mv_intcpt/self::$T_MAX_INT *100);?></td>
        </tr>
    </table>
    </center>
    <br><br>
    The scales of the bar graphs are static and are relative to what is considered "much" of a given team property.
   
    <?php
}

protected static function _bar($pct, $str, $red_pct) {
    $pct = round($pct);
    if ($pct > 100) {
        $pct = 100;
    }
    $red_pct = round($red_pct);
    $color = "rgb(100%, ".(100-$red_pct)."%, 0%)";
    echo '
    <div class="ui-progressbar ui-widget ui-widget-content ui-corner-all">
       <div style="width: '.$pct.'%; background: '.$color.';" class="ui-progressbar-value ui-widget-header ui-corner-left">&nbsp;'.$str.'</div>
    </div>
    ';
    return;
}

public static function getModuleAttributes()
{
    return array(
        'author'     => 'Nicholas Mossor Rathmann',
        'moduleName' => 'Team compare',
        'date'       => '2011', # For example '2009'.
        'setCanvas'  => true, # If true, whenever your main() is run through Module::run() your code's output will be "sandwiched" into the standard HTML frame.
    );
}

public static function getModuleTables(){ return array(); }    
public static function getModuleUpgradeSQL(){ return array(); }
public static function triggerHandler($type, $argv){}

}
?>
