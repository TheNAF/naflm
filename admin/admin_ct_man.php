<?php

if (isset($_POST['type'])) {
    $IS_COACH = ($_POST['type'][1] == 'c');
    if (!is_numeric($id = get_alt_col($IS_COACH ? 'coaches' : 'teams', 'name', $_POST['name'], ($IS_COACH ? 'coach' : 'team').'_id'))) {
        status(false, 'Invalid name. Check spelling?');
        $_POST['type'] = 'QUIT';
    }
    else {
        $o = $IS_COACH ? new Coach($id) : new Team($id);
        if (!$coach->mayManageObj($IS_COACH ? T_OBJ_COACH : T_OBJ_TEAM, $IS_COACH ? $o->coach_id : $id)) {
            status(false, 'You do not have permissions to manage the selected coach or team.');
            $_POST['type'] = 'QUIT';
        }
    }

    switch ($_POST['type'])
    {
        case 'QUIT':
            break;
            
        case 'rt':
        case 'rc':
            status($o->setRetired(!(isset($_POST['unretire']) && $_POST['unretire'])));
            break;

        case 'dt':
        case 'dc':
            status($o->delete());
            break;
    }
}
title($lng->getTrn('menu/admin_menu/ct_man'));
?>
<script>
    $(document).ready(function(){
        var options, a1,a2,a3,a4;
        toptions = { minChars:2, serviceUrl:'handler.php?type=autocomplete&obj=<?php echo T_OBJ_TEAM; ?>' };
        coptions = { minChars:2, serviceUrl:'handler.php?type=autocomplete&obj=<?php echo T_OBJ_COACH; ?>' };
        a1 = $('#t1').autocomplete(toptions);
        a2 = $('#t2').autocomplete(toptions);
        a3 = $('#c1').autocomplete(coptions);
        a4 = $('#c2').autocomplete(coptions);
    });
</script>

<table>
    <tr>
        <td colspan='2'>
            <b>Please note:</b>
            <ul>
                <li>For the sake of keeping league statistics intact you are not allowed to delete teams or coaches which have played matches.</li>
                <li>Once retired a coach cannot login and teams are no longer manageable/editable from their team pages.</li>
            </ul>
        </td>
    </tr>
    <tr>
        <td valign='top'>
            <div class="boxCommon">
                <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Retire team</div>
                <div class="boxBody">
                <form method="POST">
                Team name<br>
                <input type="text" name="name" id="t1" size="30" maxlength="50"><br><br>
                Un-retire (ie. regret retiring) instead of retiring? <input type='checkbox' name='unretire' value='1'><br><br>
                <input type='submit' value='Retire/unretire'>
                <input type='hidden' name='type' value='rt'>
                </form>
                </div>
            </div>
        </td>
        <td valign='top'>
            <div class="boxCommon">
                <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Delete team</div>
                <div class="boxBody">
                <form method="POST">
                Team name<br>
                <input type="text" name="name" id="t2" size="30" maxlength="50"><br><br>
                <input type='submit' value='Delete' onclick="if(!confirm('Are you sure you want to delete? This can NOT be undone.')){return false;}">
                <input type='hidden' name='type' value='dt'>
                </form>
                </div>
            </div>
        </td>
    </tr>
    <tr><td colspan="2"><hr></td></tr>
    <tr>
        <td valign='top'>
            <div class="boxCommon">
                <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Retire coach</div>
                <div class="boxBody">
                <form method="POST">
                Coach name<br>
                <input type="text" name="name" id="c1" size="30" maxlength="50"><br><br>
                Un-retire (ie. regret retiring) instead of retiring? <input type='checkbox' name='unretire' value='1'><br><br>
                <input type='submit' value='Retire/unretire'>
                <input type='hidden' name='type' value='rc'>
                </form>
                </div>
            </div>
        </td>
        <td valign='top'>
            <div class="boxCommon">
                <div class="boxTitle<?php echo T_HTMLBOX_ADMIN;?>">Delete coach</div>
                <div class="boxBody">
                <form method="POST">
                Coach name<br>
                <input type="text" name="name" id="c2" size="30" maxlength="50"><br><br>
                <input type='submit' value='Delete' onclick="if(!confirm('Are you sure you want to delete? This can NOT be undone.')){return false;}">
                <input type='hidden' name='type' value='dc'>
                </form>
                </div>
            </div>
        </td>
    </tr>
</table>
<?php
