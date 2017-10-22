<?php
$_SHOW = array(T_NODE_LEAGUE, T_NODE_DIVISION, T_NODE_TOURNAMENT);
if (isset($_GET['node']) && in_array($_GET['node'], $_SHOW)) {
    $_SHOW = array($_GET['node']);
}

if (isset($_POST['type'])) {
    if (get_magic_quotes_gpc()) {
        foreach (array('name', 'location',) as $i) {
            $_POST[$i] = isset($_POST[$i]) ? stripslashes($_POST[$i]) : '';
        }
    }
    if (isset($_POST['lid'])  && (!isset($leagues[$_POST['lid']])   || $leagues[$_POST['lid']]['ring'] != Coach::T_RING_LOCAL_ADMIN) || 
        isset($_POST['did'])  && (!isset($divisions[$_POST['did']]) || $leagues[$divisions[$_POST['did']]['f_lid']]['ring'] != Coach::T_RING_LOCAL_ADMIN) ||
        isset($_POST['trid']) && (!isset($tours[$_POST['trid']])    || $leagues[$divisions[$tours[$_POST['trid']]['f_did']]['f_lid']]['ring'] != Coach::T_RING_LOCAL_ADMIN)
       ) {
        status(false, 'You do not have permissions to administrate the chosen node');
        $_POST['type'] = 'QUIT';
    }
    else {
        $l = (isset($_POST['lid'])) ? new League($_POST['lid']) : null;
        $d = (isset($_POST['did'])) ? new Division($_POST['did']) : null;
        $t = (isset($_POST['trid'])) ? new Tour($_POST['trid']) : null;
    }

    switch ($_POST['type'])
    {
        case 'QUIT': break;
        case 'new_league':      
            $create_league_failure_message = League::create($_POST['name'], $_POST['location'], isset($_POST['tie_teams']) && $_POST['tie_teams']);
            status($IS_GLOBAL_ADMIN && !$create_league_failure_message, $create_league_failure_message); 
            break;
        case 'new_division':    status(Division::create($_POST['lid'], $_POST['name'])); break;
        case 'mod_league':      status($l->setName($_POST['name']) && $l->setLocation($_POST['location']) && $l->setTeamDivisionTies(isset($_POST['tie_teams']) && $_POST['tie_teams'])); break;
        case 'mod_division':    status($d->setName($_POST['name'])); break;
        case 'del_league':      status($IS_GLOBAL_ADMIN && $l->delete()); break;
        case 'del_division':    status($IS_GLOBAL_ADMIN && $d->delete()); break;
        case 'del_tournament':  status($IS_GLOBAL_ADMIN ? $t->delete(true) : false, $IS_GLOBAL_ADMIN ? '' : 'Note that only site admins may use this feature.'); break;
        
        case 'new_tournament':
        	$input = array();
        	$input['name'] = $_POST['name'];
            $input['did'] = $_POST['did'];
            $input['rs'] = $_POST['rs'];
            $input['type'] = $_POST['tourtype'];
            $input['allow_sched'] = isset($_POST['allow_sched']) ? $_POST['allow_sched'] : 0;
            $input['teams'] = array();
			$lid = $divisions[$input['did']]['f_lid'];
			if (strlen($input['name']) == 0) {
		        status(false, 'You must enter a name for the tournament.');
            }
            else {
				status(Tour::create($input));
				if (isset($_POST['locked'])) {
				    // N/A, locked for new_toursnament type
				}
			}
            break;
            
        case 'mod_tournament':
            $syncPTS = ($t->rs != (int) $_POST['rs']);
            $t->rs = $_POST['rs'];
            $t->type = $_POST['tourtype'];
            $t->name = $_POST['name'];
            $t->locked = isset($_POST['locked']) ? $_POST['locked'] : 0;
            $t->allow_sched = isset($_POST['allow_sched']) ? $_POST['allow_sched'] : 0;
            status($t->save());
            if ($syncPTS) {
                $OK = $t->syncPTS();
                status($OK, $OK ? 'Synchronized points for the new ranking system' : 'Failed to Synchronize points for the new ranking system');
            }
            break;


    }
    
    setupGlobalVars(T_SETUP_GLOBAL_VARS__COMMON); # Re-load $leagues, $divisions, $tours
}

title($lng->getTrn('menu/admin_menu/nodes'));

?>
<b>Please note:</b> When deleting any node (i.e. tournaments, divisions or leagues) a "syncAll()" re-synchronisation should be run afterwards from the <a href='index.php?section=admin&amp;subsec=cpanel'>OBBLM core panel</a>.
<table>
    <?php
    if (in_array(T_NODE_TOURNAMENT, $_SHOW)) {
    ?>
    <tr>
        <td valign='top'>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Create tournament</div>
            <div class="boxBody">
            <form method="POST">
            In division<br>
            <?php
            echo HTMLOUT::nodeList(T_NODE_DIVISION,'did',array('OTHER' => array('ring' => Coach::T_RING_LOCAL_ADMIN)),array(),array('empty_str' => array(T_NODE_DIVISION => '')));
            ?>
            <br><br>
            Name<br>
            <input type="text" name="name"><br><br>
            Ranking system &mdash; <?php echo $lng->getTrn('admin/prefixes');?><br>
            <select name='rs'>
            <?php
            global $hrs;
            foreach ($hrs as $idx => $r) {
                echo "<option value='$idx'>#$idx&nbsp;&nbsp;".Tour::getRSstr($idx)."</option>\n";
            }
            ?>
            </select>

            <br><br>
            Tournament type<br>
            <input type="radio" name="tourtype" value="<?php echo TT_RROBIN ?>" DISABLED> Round-Robin<br>
            <input type="radio" name="tourtype" value="<?php echo TT_FFA;  ?>" CHECKED> FFA<br>
            <br><input type="checkbox" name="locked" value="1" DISABLED>Locked &mdash; prevents scheduling in tournament
            <br><input type="checkbox" name="allow_sched" value="1" CHECKED>Coaches may schedule their own matches
		    <br><br>
            <input type='hidden' name='type' value='new_tournament'>
            <input type='submit' value='Create' <?php echo empty($divisions) ? ' DISABLED ' : '';?>>
            </form>
            </div>
        </div>
        </td>
        <td valign='top'>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Modify tournament</div>
            <div class="boxBody">
            <form method="POST" id=''>
            Tournament<br>
            <?php
            echo '<script type="text/javascript"> 
            var tdata;
            function adminModTour(trid) {
                document.getElementById("name").value = tdata[trid]["tname"];
                var rs = document.getElementById("rs");
                for (var i=0; i<rs.length; i++){
                     if (rs.options[i].value == tdata[trid]["rs"]){
                        rs.selectedIndex = i;
                        break;
                     }
                }
                var types = document.getElementById("tourtype").parentNode; // Must access radio objects in the way
                var TT_FFA = '.TT_FFA.';
                var is_FFA = (TT_FFA == tdata[trid]["type"]);
                types.tourtype[0].checked = is_FFA;
                types.tourtype[1].checked = !is_FFA;
                document.getElementById("locked").checked = tdata[trid]["locked"] == 0 ? false : true;
                document.getElementById("allow_sched").checked = tdata[trid]["allow_sched"] == 0 ? false : true;
            }
            </script>';
            list(,,$tdata) = Coach::allowedNodeAccess(Coach::NODE_STRUCT__FLAT, null, array(T_NODE_TOURNAMENT => array('rs' => 'rs', 'type' => 'type', 'locked' => 'locked', 'allow_sched' => 'allow_sched')));
            $easyconvert = new array_to_js();
            @$easyconvert->add_array($tdata, 'tdata');
            echo $easyconvert->output_all();
            echo HTMLOUT::nodeList(T_NODE_TOURNAMENT,'trid',array('OTHER' => array('ring' => Coach::T_RING_LOCAL_ADMIN)),array(),array('extra_tags' => array('onChange="adminModTour(this.options[this.selectedIndex].value);"'), 'empty_str' => array(T_NODE_DIVISION => '')));
            ?>
            <br><br>
            Name<br>
            <input type="text" id='name' name="name"><br><br>
            Ranking system &mdash; <?php echo $lng->getTrn('admin/prefixes');?><br>
            <select id='rs' name='rs'>
            <?php
            global $hrs;
            foreach ($hrs as $idx => $r) {
                echo "<option value='$idx'>#$idx&nbsp;&nbsp;".Tour::getRSstr($idx)."</option>\n";
            }
            ?>
            </select>
            <br><br>
            Tournament type<br>
            <input type="radio" id='tourtype' name="tourtype" value="<?php echo TT_FFA;  ?>" CHECKED> FFA<br>
            <input type="radio" id='tourtype' name="tourtype" value="<?php echo TT_RROBIN ?>"> Round-Robin<br>
            <br><input type="checkbox" id='locked' name="locked" value="1">Locked &mdash; prevents scheduling in tournament
            <br><input type="checkbox" id='allow_sched' name="allow_sched" value="1" CHECKED>Coaches may schedule their own matches
		    <br><br>
            <input type='hidden' name='type' value='mod_tournament'>
            <input type='submit' value='Modify' <?php echo empty($tours) ? ' DISABLED ' : '';?>>
            </form>
            <script>
                var tours = document.getElementById('trid');
                var trid = tours.options[tours.selectedIndex].value;
                adminModTour(trid);
            </script>
            </div>
        </div>
        </td>
    </tr>
    <tr>
        <td valign='top' colspan='2'>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Tournament deletion</div>
            <div class="boxBody">
            <form method="POST">

                I wish to delete the tournament<br><?php echo HTMLOUT::nodeList(T_NODE_TOURNAMENT,'trid',array('OTHER' => array('ring' => Coach::T_RING_LOCAL_ADMIN)),array(),array('empty_str' => array(T_NODE_DIVISION => '')));?>

                <br><br>
                <b>Warning!</b><br>
                <br>
                This feature is only meant to be used for non-played or empty tournaments and test-tournaments.<br>
                If you decide to delete a proper tournament you should know that this will
                <br>
                <ul>
                    <li>delete the tournament associated data forever (this includes team and player gained stats in the tournament).</li>
                    <li>generate incorrect player statuses for those matches following (date-wise) the matches deleted. Re-saving/changing old matches may therefore be problematic.</li>
                </ul>
                <br>
                <?php echo $ONLY_FOR_GLOBAL_ADMIN;?><br>
                <br>
                <input type="hidden" name="type" value="del_tournament">
                <input type="submit" value="Delete" <?php echo ($IS_GLOBAL_ADMIN && !empty($tours)) ? '' : 'DISABLED';?> onclick="if(!confirm('Are you absolutely sure you want to delete this tournament?')){return false;}">
            </form>
            </div>
        </div>
        </td>
    </tr>
    <?php
    }
    if (in_array(T_NODE_DIVISION, $_SHOW)) {
    ?>
    <tr>
        <td valign='top'>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Create division</div>
            <div class="boxBody">
            <form method="POST">
            In league<br>
            <?php
            echo HTMLOUT::nodeList(T_NODE_LEAGUE,'lid',array('OTHER' => array('ring' => Coach::T_RING_LOCAL_ADMIN)),array(),array('empty_str' => array(T_NODE_LEAGUE => '')));
            ?>
            <br><br>
            Name<br>
            <input type="text" name="name"><br><br>
            <input type='submit' value='Create' <?php echo empty($leagues) ? ' DISABLED ' : '';?>>
            <input type='hidden' name='type' value='new_division'>
            </form>
            </div>
        </div>
        </td>
        <td valign='top'>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Modify division</div>
            <div class="boxBody">
            <form method="POST">
            Division<br>
            <?php
            echo HTMLOUT::nodeList(T_NODE_DIVISION,'did',array('OTHER' => array('ring' => Coach::T_RING_LOCAL_ADMIN)),array(), array('empty_str' => array(T_NODE_DIVISION => '', T_NODE_LEAGUE => '')));
            ?>
            <br><br>
            New name<br>
            <input type="text" name="name"><br><br>
            <input type='submit' value='Modify' <?php echo empty($divisions) ? ' DISABLED ' : '';?>>
            <input type='hidden' name='type' value='mod_division'>
            </form>
            </div>
        </div>
        </td>
    </tr>
    <tr>
        <td valign='top' colspan='2'>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Delete division</div>
            <div class="boxBody">
            <form method="POST">
            Division<br>
            <?php
            echo HTMLOUT::nodeList(T_NODE_DIVISION,'did',array('OTHER' => array('ring' => Coach::T_RING_LOCAL_ADMIN)),array(),array('empty_str' => array(T_NODE_LEAGUE => '', T_NODE_DIVISION => '')));
            ?>
            <br><br>
            <?php echo $ONLY_FOR_GLOBAL_ADMIN;?><br><br>
            <input type='submit' value='Delete' <?php echo (empty($divisions) || !$IS_GLOBAL_ADMIN) ? ' DISABLED ' : '';?> onclick="if(!confirm('Warning: You should only delete devisions when no matches are assigned to it.')){return false;}">
            <input type='hidden' name='type' value='del_division'>
            </form>
            </div>
        </div>
        </td>
    </tr>
    <?php
    }
    if (in_array(T_NODE_LEAGUE, $_SHOW)) {
    ?>
    <tr>
        <td valign='top'>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Create league</div>
            <div class="boxBody">
            <form method="POST">
            Name<br>
            <input type="text" name="name" <?php echo $IS_GLOBAL_ADMIN ? '' : 'DISABLED';?>><br><br>
            Location<br>
            <input type="text" name="location" <?php echo $IS_GLOBAL_ADMIN ? '' : 'DISABLED';?>><br><br>
            Tie teams to divisions?
            <input type="checkbox" name="tie_teams" <?php echo $IS_GLOBAL_ADMIN ? '' : 'DISABLED';?>><br><br>
            <?php echo $ONLY_FOR_GLOBAL_ADMIN;?><br><br>
            <input type='submit' value='Create' <?php echo $IS_GLOBAL_ADMIN ? '' : 'DISABLED';?>>
            <input type='hidden' name='type' value='new_league'>
            </form>
            </div>
        </div>
        </td>
        <td valign='top'>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Modify league</div>
            <div class="boxBody">
            <form method="POST">
            League<br>
            <?php
            echo HTMLOUT::nodeList(T_NODE_LEAGUE,'lid',array('OTHER' => array('ring' => Coach::T_RING_LOCAL_ADMIN)),array(),array('empty_str' => array(T_NODE_LEAGUE => '')));
            ?>
            <br><br>
            New name<br>
            <input type="text" name="name"><br><br>
            New location<br>
            <input type="text" name="location"><br><br>
            Tie teams to divisions?
            <input type="checkbox" CHECKED name="tie_teams"><br><br>
            <input type='submit' value='Modify' <?php echo empty($leagues) ? ' DISABLED ' : '';?>>
            <input type='hidden' name='type' value='mod_league'>
            </form>
            </div>
        </div>
        </td>
    </tr>
    <tr>
        <td valign='top' colspan='2'>
        <div class="boxCommon">
            <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Delete league</div>
            <div class="boxBody">
            <form method="POST">
            League<br>
            <?php
            echo HTMLOUT::nodeList(T_NODE_LEAGUE,'lid',array('OTHER' => array('ring' => Coach::T_RING_LOCAL_ADMIN)),array(),array('empty_str' => array(T_NODE_LEAGUE => '')));
            ?>
            <br><br>
            <?php echo $ONLY_FOR_GLOBAL_ADMIN;?><br><br>
            <input type='submit' value='Delete' <?php echo (empty($leagues) || !$IS_GLOBAL_ADMIN) ? ' DISABLED ' : '';?> onclick="if(!confirm('Warning: You should only delete leagues if empty, ie. no divisions/matches assigned to them.')){return false;}">
            <input type='hidden' name='type' value='del_league'>
            </form>
            </div>
        </div>
        </td>
    </tr>
    <?php
    }
    ?>
</table>
<?php
