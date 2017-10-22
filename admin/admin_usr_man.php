<?php

global $settings;
$league_ids = array_keys($leagues); # Shortcut.
$c = null;

if (isset($_POST['type'])) {

    /* 
        Commonly used coach object. 
        $c is the coach referred to in the "Coach name" HTML fields.
    */
    if (isset($_POST['cname'])) {
        if (is_numeric($cid = get_alt_col('coaches', 'name', $_POST['cname'], 'coach_id'))) {
            $c = new Coach($cid); # Needed later.
            if (!$coach->mayManageObj(T_OBJ_COACH, $cid)) {
                status(false, 'You do not have permissions to manage the selected coach.');
                $_POST['type'] = 'QUIT';
            }
        }
        else {
            status(false, 'No such coach exists. Please check your spelling.');
            $_POST['type'] = 'QUIT';
        }
    }

    switch ($_POST['type']) {

        case 'QUIT':
            $c = null;
            break;

        case 'mk_coach':
            if (get_magic_quotes_gpc()) {
                $_POST['name']      = stripslashes($_POST['name']);
                $_POST['realname']  = stripslashes($_POST['realname']);
                $_POST['mail']      = stripslashes($_POST['mail']);
                $_POST['phone']     = stripslashes($_POST['phone']);
                $_POST['passwd']    = stripslashes($_POST['passwd']);
            }
            global $_LEAGUES; $_LEAGUES = $leagues; # Trick for create_function() below.
            $errors = array(
                'Please enter a non-empty name (login).' => empty($_POST['name']),
                'The chosen name (login) is already in use. Pick another.' => is_numeric(get_alt_col('coaches', 'name', $_POST['name'], 'coach_id')),
                'Invalid choice of global access level.' => $_POST['ring'] > $coach->ring,
                'Can\'t add the new coach to a league in which you are not a commissioner' => isset($_POST['def_leagues']) && 0 < count(array_filter($_POST['def_leagues'], create_function('$lid', 'global $_LEAGUES; return (!isset($_LEAGUES[$lid]) || $_LEAGUES[$lid]["ring"] != '.Coach::T_RING_LOCAL_ADMIN.');'))),
                'The chosen language does not exist!' => !in_array($_POST['lang'], Translations::$registeredLanguages),
            );
            foreach ($errors as $msg => $halt) {
                if ($halt) { status(false,$msg); break 2; }
            }
            status($cid = Coach::create(array(
                'name'        => $_POST['name'],
                'realname'    => $_POST['realname'],
                'passwd'      => $_POST['passwd'],
                'mail'        => $_POST['mail'],
                'phone'       => $_POST['phone'],
                'ring'        => $_POST['ring'],
                'def_leagues' => isset($_POST['def_leagues']) ? $_POST['def_leagues'] : array(),
                'settings'    => array('lang' => $_POST['lang']),
            )));
            $c = new Coach($cid);
            break;

        case 'ch_ring_global':
            $errors = array(
                'You only global admins may change global access levels.' => !$IS_GLOBAL_ADMIN,
            );
            foreach ($errors as $msg => $halt) {
                if ($halt) { status(false,$msg); break 2; }
            }
            status($c->setRing(Coach::T_RING_GROUP_GLOBAL, (int) $_POST['ring']));
            break;

        case 'ch_ring_local':
            $errors = array(
                'You do not have access to the chosen league.' => ($CANT_VIEW = !array_key_exists($_POST['lid'], $leagues)), # Not amongst allowed viewable leagues?
                'You are not a commissioner in the selected league.' => $CANT_VIEW || $leagues[$_POST['lid']]['ring'] != Coach::T_RING_LOCAL_ADMIN,
            );
            foreach ($errors as $msg => $halt) {
                if ($halt) { status(false,$msg); break 2; }
            }
            // Single?
            if (isset($c)) {
                status($c->setRing(Coach::T_RING_GROUP_LOCAL, (int) $_POST['ring'], (int) $_POST['lid']));
            }
            // Mass user?
            elseif (isset($_POST['massuser']) && $_POST['massuser']) {
                $changed = 0;
                foreach (get_rows('coaches', array('coach_id', 'name')) as $subm_coach) {
                    if (isset($_POST["cid$subm_coach->coach_id"]) && $_POST["cid$subm_coach->coach_id"]) {
                        $c = new Coach($subm_coach->coach_id); # Needed later.
                        if (!$coach->mayManageObj(T_OBJ_COACH, $c->coach_id)) {
                            status(false, 'You do not have permissions to manage the selected coach "'.$c->name.'".');
                        }
                        $c->setRing(Coach::T_RING_GROUP_LOCAL, (int) $_POST['ring'], (int) $_POST['lid']);
                        $changed++;
                    }
                }
                status(true, "Changed $changed local access levels");
            }
            break;

        case 'ch_passwd':
            $errors = array(
                'Please use a password of at least 5 characters.' => strlen($_POST['passwd']) < 5,
            );
            foreach ($errors as $msg => $halt) {
                if ($halt) { status(false,$msg); break 2; }
            }
            status($c->setPasswd($_POST['passwd']));
            break;
            
        case 'disp_access_levels':
            status(true); # Display the access levels in box below.
            $_SHOW_ACCESS_LEVELS = true;
            break;
    }

    // Reload manage state.
    $coach = new Coach($coach->coach_id); # Re-load in case of we changed our OWN (logged on coach) settings.
}

title($lng->getTrn('menu/admin_menu/usr_man'));

$T_GLOBAL_RINGS = array(
    Coach::T_RING_GLOBAL_ADMIN => 'Global commissioner (site admin)',
    Coach::T_RING_GLOBAL_NONE  => 'No global rights (regular coach)',
);
$T_LOCAL_RINGS = array(
    Coach::T_RING_LOCAL_ADMIN   => 'Local commissioner',
    Coach::T_RING_LOCAL_REGULAR => 'Regular coach',
);

?>
<script>
    $(document).ready(function(){
        var options, a1,a2,a3,a4;
        options = { minChars:2, serviceUrl:'handler.php?type=autocomplete&obj=<?php echo T_OBJ_COACH; ?>' };
        a1 = $('#coach1').autocomplete(options);
        a2 = $('#coach2').autocomplete(options);
        a3 = $('#coach3').autocomplete(options);
        a4 = $('#coach4').autocomplete(options);
    });
</script>
    
<?php echo "<b><a TARGET='_blank' href='".DOC_URL_GUIDE."'>".$lng->getTrn('common/needhelp')."</a></b><br>"; ?>

<div style='float:left; width:100%;'> <!-- ALL -->
<div class="boxCommon">
    <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">
        Create new coach
    </div>
    <div class="boxBody">
        <form method="POST">
        Coach name (login)<br> <input type="text" name="name" size="20" maxlength="50"><br><br>
        Full name<br> <input type="text" name="realname" size="20" maxlength="50"><br><br>
        Mail (optional)<br> <input type="text" name="mail" size="20" maxlength="129"><br><br>
        Phone (optional)<br> <input type="text" name="phone" size="20" maxlength="129"><br><br>
        Password<br> <input type="password" name="passwd" size="20" maxlength="50"><br><br>
        Language<br>
        <select name='lang'>
            <?php
            foreach (Translations::$registeredLanguages as $lang) {
                echo "<option value='$lang'>$lang</option>\n";
            }
            ?>
        </select>
        <br><br>
        Global site access level<br>
        <select name="ring">
            <?php
            foreach ($T_GLOBAL_RINGS as $r => $desc) {
                if ($r <= $coach->ring) {
                    echo "<option value='$r' ".(($r == Coach::T_RING_GLOBAL_NONE) ? 'SELECTED' : '').">$desc</option>\n";
                }
            }
            ?>
        </select>
        <br><br>
        Local (league) access<br>
        <SELECT NAME="def_leagues[]" MULTIPLE>
        <?php
        foreach ($settings['default_leagues'] as $lid) {
            if (get_alt_col('leagues', 'lid', $lid, 'lid')) {
                echo "<OPTION DISABLED VALUE='$lid'>".get_alt_col('leagues', 'lid', $lid, 'name')." (added to automatically)</OPTION>\n";
            }
        }
        foreach ($leagues as $lid => $desc) {
            if ($desc['ring'] == Coach::T_RING_LOCAL_ADMIN && !in_array($lid, $settings['default_leagues'])) {
                echo "<OPTION VALUE='$lid'>$desc[lname]</OPTION>\n";
            }
        }
        ?>
        </SELECT>
        <br><br>
        <input type="hidden" name="type" value="mk_coach">
        <input type="submit" name="button" value="Create coach">
        </form>
    </div>
</div>

<div style='float:left;'> <!-- Outer -->
<div class="row"> <!-- Inner row 1 -->

<div class="boxCommon">
    <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">
        Change local access level
    </div>
    <div class="boxBody">
        <form method="POST">
        Coach name<br> <input type="text" name="cname" id="coach1" size="20" maxlength="50"><br><br>
        <div id='massuserlist' style='display:none;'>
        Coaches<br>
        <br>
        <?php 
        foreach (get_rows('coaches', array('coach_id', 'name')) as $subm_coach) {
            echo "<input type='checkbox' name='cid$subm_coach->coach_id' value='1'> $subm_coach->name<br>\n";
        }
        ?>
        <br>
        </div>
        Access level<br>
        <select name="ring">
            <?php
            foreach ($T_LOCAL_RINGS as $r => $desc) {
                echo "<option value='$r' ".(($r == Coach::T_RING_LOCAL_REGULAR) ? 'SELECTED' : '').">$desc</option>\n";
            }
            echo "<option value='".Coach::T_RING_LOCAL_NONE."'>None</option>\n";
            ?>
        </select>
        <br><br>
        League<br>
        <select name='lid'>
            <?php
            foreach ($leagues as $lid => $desc) {
                if ($desc['ring'] == Coach::T_RING_LOCAL_ADMIN) { # Only allow to add coaches to commish's leagues.
                    echo "<option value='$lid'>$desc[lname]</option>\n";
                }
            }
            ?>
        </select>
        <br><br>
        <input type="hidden" name="type" value="ch_ring_local">
        <input type="hidden" name="massuser" id="massuser" value="0">
        <input type="submit" name="button" value="Change LOCAL access">
        </form>
        <br>
        <a href='javascript:void();' onClick='slideDownFast("massuserlist");document.getElementById("coach1").disabled=1;document.getElementById("massuser").value=1;'><b>Mass user access changes?</b></a>
    </div>
</div>

<div class="boxCommon">
    <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">
        Change global access level
    </div>
    <div class="boxBody">
        <form method="POST">
        Coach name<br> <input type="text" name="cname" id="coach2" size="20" maxlength="50"><br><br>
        Access level<br>
        <select name="ring">
            <?php
            foreach ($T_GLOBAL_RINGS as $r => $desc) {
                if ($r <= $coach->ring) {
                    echo "<option value='$r' ".(($r == Coach::T_RING_GLOBAL_NONE) ? 'SELECTED' : '').">$desc</option>\n";
                }
            }
            ?>
        </select>
        <br><br>
        <input type="hidden" name="type" value="ch_ring_global">
        <input type="submit" name="button" value="Change GLOBAL access" <?php echo (!$IS_GLOBAL_ADMIN) ? 'DISABLED' : '';?>>
        </form>
    </div>
</div>

<?php if (Module::isRegistered('Registration') && $settings['allow_registration']) { ?>
    <div class="boxCommon">
        <div class="boxTitle<?php echo T_HTMLBOX_ADMIN; ?>">
            Activate Users
        </div>
        <div class="boxBody">
            <?php
                $retiredCoaches = Coach::getCoaches('retired = 2');
                    
                if(empty($retiredCoaches)) {
                    echo 'No coaches to activate!';
                } else { ?>
                    <table>
                        <?php 
                            foreach($retiredCoaches as $retiredCoach) { 
                                if($coach->mayManageObj(T_OBJ_COACH, $retiredCoach->coach_id)) {
                                ?>
                                    <tr>
                                        <td><?php echo $retiredCoach->name; ?></td>
                                        <td><?php echo join(', ', $retiredCoach->getLeagues()); ?></td>
                                        <td>
                                            <form method="POST" action="handler.php?type=registration&form=activate">
                                                <input type="hidden" name="activate_name" value="<?php echo $retiredCoach->name; ?>" />
                                                <button>Activate</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php 
                                }
                            } ?>
                    </table>
                    <br />
                    <em>Admin:</em> Avoid activating users from other leagues.
                <?php } ?>
        </div>
    </div>
<?php } ?>
    
</div> <!-- END row 1 -->
<div class="row"> <!-- Intter row 2 -->

<div class="boxCommon">
    <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">
        Display coach access levels
    </div>
    <div class="boxBody">
        <form method="POST">
        Coach name<br> <input type="text" name="cname" id="coach3" size="20" maxlength="50"><br><br>
        <?php
        if (is_object($c)) {
            echo "Access levels of '$c->name' are<br><br>\n";
            echo "<b>Global</b><br>".$T_GLOBAL_RINGS[$c->ring]."<br><br>";
            echo "<b>Local</b><br>";
            list($_leagues) = Coach::allowedNodeAccess(Coach::NODE_STRUCT__FLAT, $c->coach_id);
            if (empty($_leagues)) {
                echo "<i>None</i>";
            }
            else {
                echo "<table><tr style='font-style:italic;'><td>League</td><td>Access level</td></tr>\n";
                foreach ($_leagues as $lid => $desc) {
                    echo "<tr><td>$desc[lname]</td><td>".$T_LOCAL_RINGS[$desc['ring']]."</td></tr>\n";
                }
                echo "</table>";
            }
        }
        ?>
        <br><br>
        <input type="hidden" name="type" value="disp_access_levels">
        <input type="submit" name="button" value="Display coach's access levels">
        </form>
    </div>
</div>

<div class="boxCommon">
    <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">
        Change coach password
    </div>
    <div class="boxBody">
        <form method="POST">
        Coach name<br> <input type="text" name="cname" id="coach4" size="20" maxlength="50"><br><br>
        New password<br> <input type="password" name="passwd" size="20" maxlength="50"><br><br>
        <input type="hidden" name="type" value="ch_passwd">
        <input type="submit" name="button" value="Change password">
        </form>
    </div>
</div>

</div> <!-- END row 2 -->
</div> <!-- END Outer -->
</div> <!-- END ALL -->
<?php
