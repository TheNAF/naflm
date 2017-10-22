<?php
MTS('Loading SQL core...');
require('lib/class_sqlcore.php');
MTS('SQL core loaded.');

if (isset($_POST['act'])) {
    $act = $_POST['act']; # Shortcut.
    if (preg_match('/\(\)$/', $act)) {
        status(mysql_query("CALL $act"), "Ran $act");
    }
    else {
        switch ($act) 
        {
            case 'gdsync': status(SQLCore::syncGameData(), 'PHP game data synced with DB'); break;
            case 'tblidx': status(SQLCore::installTableIndexes(), 'Indices installed'); break;
            case 'funcs': status(SQLCore::installProcsAndFuncs(true), 'DB functions and procedures installed'); break;
        }
    }
}
title($lng->getTrn('menu/admin_menu/cpanel'));
?>
<p>
OBBLM database maintenance and synchronisation routines.<br><br>
</p>
<form method="POST">
    <b>Database maintenance:</b><br>
    <INPUT TYPE=RADIO NAME="act" VALUE="gdsync">Synchronise database with game data files. &mdash; Run this when having changed the PHP game data files <i>lib/game_data*.php</i>.<br>
    <INPUT TYPE=RADIO NAME="act" VALUE="funcs">Re-install database back-end procedures and functions. &mdash; Run this when having altered the "house ranking system" definitions in <i>settings.php</i>.<br>
    <INPUT TYPE=RADIO NAME="act" VALUE="tblidx">Re-install table indices.<br>
    <br>
    <b>Database synchronisation:</b><br>
    <INPUT TYPE=RADIO NAME="act" VALUE="syncAll()"><i>syncAll()</i> &mdash; Synchronises all stats, relations and dynamic properties. This may take a few minutes!<br>
    <!--
    <INPUT TYPE=RADIO NAME="act" VALUE="syncAllMVs()"><i>syncAllMVs()</i> - Synchronises all stats.<br>
    <INPUT TYPE=RADIO NAME="act" VALUE="syncAllDProps()"><i>syncAllDProps()</i> - Synchronises all dynamic properties (TVs, PVs etc.).<br>
    <INPUT TYPE=RADIO NAME="act" VALUE="syncAllRels()"><i>syncAllRels()</i> - Synchronises all object (player, team, coach) ownership relations.<br>
    -->
    <br><br>
    <input type="submit" name='submit' value="Run it">
</form>
<br>
<hr width='30%' align='left'>

<?php
?>
