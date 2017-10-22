<?php

class Search implements ModuleInterface
{

public static function main($argv) # argv = argument vector (array).
{
    global $lng;
    title($lng->getTrn('name', 'Search'));
    ?>
    <script>
        $(document).ready(function(){
            var options, a,b;

            options = { 
                minChars:2, 
                serviceUrl:'handler.php?type=autocomplete&obj=<?php echo T_OBJ_COACH;?>',
                onSelect: function(value, data){ window.location = '<?php echo str_replace("amp;", "", urlcompile(T_URL_PROFILE,T_OBJ_COACH,false,false,false));?>&obj_id='+data; },
            };
            a = $('#coach').autocomplete(options);
            
            options = { 
                minChars:2, 
                serviceUrl:'handler.php?type=autocomplete&obj=<?php echo T_OBJ_TEAM;?>',
                onSelect: function(value, data){ window.location = '<?php echo str_replace("amp;", "", urlcompile(T_URL_PROFILE,T_OBJ_TEAM,false,false,false));?>&obj_id='+data; },
            };
            b = $('#team').autocomplete(options);
        });
    </script>
    
    <div class='boxCommon'>
        <h3 class='boxTitle<?php echo T_HTMLBOX_COACH;?>'><?php echo $lng->getTrn('name', 'Search');?></h3>
        <div class='boxBody'>
            <?php echo $lng->getTrn('search_tname', 'Search');?><br>
            <input id='team' type="text" name="team" size="30" maxlength="50"><br>
            <br>
            <?php echo $lng->getTrn('search_cname', 'Search');?><br>
            <input id='coach' type="text" name="coach" size="30" maxlength="50"><br>
        </div>
    </div>
    <?php
}

public static function getModuleAttributes()
{
    return array(
        'author'     => 'Nicholas Mossor Rathmann',
        'moduleName' => 'Coach/team search',
        'date'       => 'Feb 2010',
        'setCanvas'  => true,
    );
}

public static function getModuleTables(){ return array();}    
public static function getModuleUpgradeSQL(){ return array();}
public static function triggerHandler($type, $argv){}
}
?>
