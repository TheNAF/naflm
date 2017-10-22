<?php
class Mobile_HTMLOUT {
    public static function getSelectedTeamId() {
        global $coach;
        
        if(isset($_SESSION["SelectedTeam"])) {
            return (isset($_REQUEST["SelectedTeam"]) && $_REQUEST["SelectedTeam"] != $_SESSION["SelectedTeam"]) ? $_REQUEST["SelectedTeam"] : $_SESSION["SelectedTeam"];
        } else {
            $teams = $coach->getReadyTeams();
            return isset($_REQUEST["SelectedTeam"]) ? $_REQUEST["SelectedTeam"] : $teams[0]->team_id;
        }
    }
    
    public static function sec_mobile_main() {
        global $coach, $lng, $T_INJS;
        
        $teams = $coach->getReadyTeams();
        $selectedTeamId = Mobile_HTMLOUT::getSelectedTeamId();
        $_SESSION["SelectedTeam"] = $selectedTeamId;

        foreach($teams as $team) {
            if($team->team_id == $selectedTeamId)
                $selectedTeam = $team;
        }
        
        if(empty($teams)) {
            echo $lng->getTrn('mobile/team/noteams');
            return;
        } else if(!isset($selectedTeam)) {
            $selectedTeam = $teams[0];
        }
        
        $playersOnSelectedTeam = $selectedTeam->getPlayers();

        // Filter players depending on settings and view mode.
        $tmp_players = array();
        foreach ($playersOnSelectedTeam as $player) {
            if ($player->is_dead || $player->is_sold) {
                continue;
            }
            array_push($tmp_players, $player);
        }
        $playersOnSelectedTeam = $tmp_players;
        
        foreach($playersOnSelectedTeam as $player) {
            Player_HTMLOUT::setChoosableSkillsTranslations($player);
        }

        list($recentMatches, $pages) = Stats::getMatches(T_OBJ_TEAM, $selectedTeamId, false, false, false, false, array(), true, false);
        list($upcomingMatches, $pages) = Stats::getMatches(T_OBJ_TEAM, $selectedTeamId, false, false, false, false, array(), true, true);
        $allMatches = array_merge($recentMatches, $upcomingMatches);
        ?>
        <script type="text/javascript">
            $(document).ready(function() {
                var playersOnSelectedTeam = <?php echo json_encode($playersOnSelectedTeam); ?>;
                var matches = <?php echo json_encode($allMatches); ?>;
                var injuryTable = <?php echo json_encode($T_INJS); ?>;
                var lastMatchId = <?php echo isset($_GET['lastMatchId']) ? $_GET['lastMatchId'] : -1; ?>;
                $('#tabs').tabs();
                $('#SelectedTeam').change(function() {
                    this.form.submit();
                });
				
                var mobileViewModel = new MobileViewModel(playersOnSelectedTeam, matches, lastMatchId);
                
                mobileViewModel.matchDialogViewModel.selectedPlayerViewModel.injuryTable(injuryTable);
                mobileViewModel.matchDialogViewModel.myTeamId(<?php echo $selectedTeamId; ?>);
                
                ko.applyBindings(mobileViewModel);
            });
        </script>
        <div class="main">
            <form method="post" action="<?php echo getFormAction(''); ?>">
                 <select id="SelectedTeam" name="SelectedTeam">
                    <?php
                        foreach($teams as $team) {
                            $isThisTeam = ($team->team_id == $selectedTeamId);
                            $teamName = $team->name . ' (TV' . ($team->tv/1000) . ')';
                            echo '<option value="' . $team->team_id . '"' . ($isThisTeam ? ' selected="selected"' : '') . '>' . $teamName . '</option>';
                        }
                    ?>
                </select>
                <span class="button-panel">
					<img id="open-menu" src="images/menu.svg" alt="Menu" class="icon ui-button ui-state-default ui-corner-all" data-bind="click: showMenu" />
					<ul id="menu" class="ui-state-default ui-corner-left ui-corner-left ui-corner-br" data-bind="visible: isMenuVisible">
						<li><a href="<?php echo getFormAction('?section=management'); ?>"><?php echo $lng->getTrn('mobile/team/management'); ?></a></li>
						<li><a href="index.php"><?php echo $lng->getTrn('mobile/team/desktop_site'); ?></a></li>
						<li><a href="<?php echo getFormAction('?logout=1'); ?>"><?php echo $lng->getTrn('menu/logout'); ?></a></li>
					</ul>
                </span>
            </form>
            <div>
                <?php 
                    echo $selectedTeam->treasury/1000 . 'k, FF' . $selectedTeam->rg_ff . ', ' . $selectedTeam->ass_coaches . ' Coaches, ' . $selectedTeam->cheerleaders . ' Cheerleaders'; 
                    if($selectedTeam->apothecary) { echo ', ' . $lng->getTrn('common/apothecary'); } 
                ?>
            </div>
            <a data-bind="visible: hasLastOpenedMatchId(), click: openLastOpenedMatchDialog">Open Last Match</a>
            <div id="tabs">
                <ul>
                    <li><a href="#Teams"><?php echo $lng->getTrn('common/team'); ?></a></li>
                    <li><a href="#Games"><?php echo $lng->getTrn('menu/matches_menu/name'); ?></a></li>
                </ul>
                <?php Mobile_HTMLOUT::teamSummaryView($playersOnSelectedTeam); ?>
                <?php Mobile_HTMLOUT::matchSummaryView($recentMatches, $upcomingMatches, $selectedTeamId); ?>
            </div>
        </div>
        <?php   
    }
    
    private static function teamSummaryView($playersOnSelectedTeam) {
        global $lng;
        ?>
        <div id="Teams">
            <table id="Players">
                <thead>
                    <tr>
                        <th></th>
                        <th><?php echo $lng->getTrn('common/name'); ?></th>
                        <th><?php echo $lng->getTrn('common/pos'); ?></th>
                        <th><?php echo $lng->getTrn('common/stats'); ?></th>
                        <th><?php echo $lng->getTrn('common/skills'); ?></th>
                        <th>SPP</th>
                        <th>NI</th>
                    </tr>
                </thead>
                <tbody data-bind="foreach: teamViewModel.players">
                    <tr data-bind="css: {'miss-next-game': missNextGame, 'may-buy-new-skill': mayBuyNewSkill}">
                        <td data-bind="text: number"></td>
                        <td><a href="#" data-bind="click: $parent.openPlayerDialog, text: name"></td>
                        <td data-bind="text: position"></td>
                        <td data-bind="html: statsString"></td>
                        <td data-bind="html: skills"></td>
                        <td data-bind="text: spp"></td>
                        <td data-bind="text: nigglingInjuryCount"></td>
                    </tr>
                </tbody>
            </table>
            <div>
                <span class="miss-next-game"><?php echo $lng->getTrn('mobile/team/miss_next_game'); ?></span>, <span class="may-buy-new-skill"><?php echo $lng->getTrn('mobile/team/may_buy_new_skill'); ?></span>.
            </div>
            <div id="PlayerDialog" data-bind="with: playerDialogViewModel">
                <table>
                    <tbody>
                        <tr><td><?php echo $lng->getTrn('common/number'); ?>:</td><td class="data" data-bind="text: number"></td></tr>
                        <tr><td><?php echo $lng->getTrn('common/name'); ?>:</td><td class="data" data-bind="text: name"></td></tr>
                        <tr><td><?php echo $lng->getTrn('common/pos'); ?>:</td><td class="data" data-bind="text: position"></td></tr>
                        <tr><td>MA/ST/AG/AV:</td><td class="data" data-bind="text: statString"></td></tr>
                        <tr><td><?php echo $lng->getTrn('common/skills'); ?>:</td><td class="data" data-bind="html: skillsString"></td></tr>
                        <tr><td>SPP:</td><td class="data" data-bind="text: spp"></td></tr>
                        <tr data-bind="visible: mayBuyNewSkill">
                            <td>Skill up: </td>
                            <td>
                                <select data-bind="value: selectedNewSkill">
                                    <optgroup label="Normal skills" data-bind="foreach: choosableNormalSkills">
                                        <option data-bind="value: id, text: name" />
                                    </optgroup>
                                    <optgroup label="Double skills" data-bind="foreach: choosableDoubleSkills">
                                        <option data-bind="value: id, text: name" />
                                    </optgroup>
                                    <optgroup label="Characteristic increases" data-bind="foreach: choosableCharacteristicIncreases">
                                        <option data-bind="value: id, text: name" />
                                    </optgroup>
                                </select>
                                <button data-bind="click: saveSkill"><?php echo $lng->getTrn('common/save'); ?></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    private static function outputMatchesRows($matches, $myTeamId) {
        foreach($matches as $match) {
            $myTeamName = ($match->team1_id == $myTeamId ? $match->team1_name : $match->team2_name);
            $otherTeamName = ($match->team1_id == $myTeamId ? $match->team2_name : $match->team1_name);
            $myTeamTv = ($match->team1_id == $myTeamId ? $match->team1_tv : $match->team2_tv) / 1000;
            $otherTeamTv = ($match->team1_id == $myTeamId ? $match->team2_tv : $match->team1_tv) / 1000;

            $myTeamLink = '<a data-bind="click: openMatchDialog" href="#" data-match-id="' . $match->match_id . '">' . $myTeamName . '</a>';

            echo '<tr>';
            echo '<td class="team-name">' . $myTeamLink  . '</td>';
            echo '<td>v.</td>';
            echo '<td class="team-name">' . $otherTeamName  . ' (TV' . $otherTeamTv . ')'. '</td>';
            echo '</tr>';
        }
    }
    
    private static function matchSummaryView($recentMatches, $upcomingMatches, $selectedTeamId) {
        global $lng;
        ?>
        <div id="Games">
            <table id="GamesTable">
                <tbody>
                    <tr><td colspan="4"><h3><?php echo $lng->getTrn("common/recentmatches"); ?></h3></td></tr>
                    <?php Mobile_HTMLOUT::outputMatchesRows($recentMatches, $selectedTeamId); ?>
                    <tr><td colspan="4"><h3><?php echo $lng->getTrn("common/upcomingmatches"); ?></h3></td></tr>
                    <?php Mobile_HTMLOUT::outputMatchesRows($upcomingMatches, $selectedTeamId); ?>
                </tbody>
            </table>
            <div>
                <a href="index.php?mobile=1&section=matches&type=usersched"><?php echo $lng->getTrn('menu/matches_menu/usersched'); ?></a>
            </div>
            
            <div id="MatchDialog" data-bind="with: matchDialogViewModel">
                <div data-bind="if: matchIsLocked">
                    <?php Mobile_HTMLOUT::readonlyMatchView(); ?>
                </div>
                <div data-bind="ifnot: matchIsLocked">
                    <?php Mobile_HTMLOUT::editableMatchView(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    private static function readonlyMatchView() {
        global $lng;
        ?>
        <fieldset>
            <legend><?php echo $lng->getTrn('common/team'); ?>: <span class="data" data-bind="text: name"></span></legend>
            <div>
                <span class="label"><?php echo $lng->getTrn('common/score'); ?>:</span>
                <span class="data" data-bind="text: myScore"></span><?php echo $lng->getTrn('mobile/matches/match/for_me_to'); ?><span class="data" data-bind="text: theirScore"></span>
            </div>
            <div>
                <span class="label"><?php echo $lng->getTrn('matches/report/treas'); ?>:</span>
                <span class="data" data-bind="text: treasuryChange"></span>
            </div>
            <div>
                <span class="label"><?php echo $lng->getTrn('matches/report/ff'); ?>:</span>
                <span class="data" data-bind="text: fanFactorChange"></span>
            </div>
        </fieldset>
        <fieldset id="SelectedPlayer">
            <legend><?php echo $lng->getTrn('common/player'); ?>: <select data-bind="value: selectedPlayer, options: playersInMatch, optionsText: 'numberAndName'"></select></legend>
            <div data-bind="with: selectedPlayerViewModel">
                <div>
                    <span class="label"><?php echo $lng->getTrn('matches/report/mvp'); ?>:</span>
                    <span class="data" data-bind="text: mvp"></span>
                </div>
                <div>
                    <span class="label"><?php echo $lng->getTrn('common/cp'); ?>:</span>
                    <span class="data" data-bind="text: completions"></span>
                </div>
                <div>
                    <span class="label"><?php echo $lng->getTrn('common/td'); ?>:</span>
                    <span class="data" data-bind="text: touchdowns"></span>
                </div>
                <div>
                    <span class="label"><?php echo $lng->getTrn('common/intcpt'); ?>:</span>
                    <span class="data" data-bind="text: interceptions"></span>
                </div>
                <div>
                    <span class="label"><?php echo $lng->getTrn('common/bh'); ?>:</span>
                    <span class="data" data-bind="text: badlyHurt"></span>
                </div>
                <div>
                    <span class="label"><?php echo $lng->getTrn('common/si'); ?>:</span>
                    <span class="data" data-bind="text: sustainedInjury"></span>
                </div>
                <div>
                    <span class="label"><?php echo $lng->getTrn('common/ki'); ?>:</span>
                    <span class="data" data-bind="text: killed"></span>
                </div>
                <div>
                    <span class="label"><?php echo $lng->getTrn('common/injs'); ?>:</span>
                    <span class="data" data-bind="text: injuryText"></span>
                </div>
            </div>
        </fieldset>
        <?php
    }

    private static function editableMatchView() {
        global $lng;
        ?>
        <fieldset>
            <legend><?php echo $lng->getTrn('common/team'); ?>: <span class="data" data-bind="text: name"></span></legend>

            <div class="row">
                <span class="label"><?php echo $lng->getTrn('common/score'); ?>:</span>
                <input type="number" data-bind="value: myScore" /> for me, to <input type="number" data-bind="value: theirScore" />
            </div>
            <div class="row">
                <span class="label"><?php echo $lng->getTrn('matches/report/treas'); ?>:</span>
                <input type="number" id="TreasuryChange" data-bind="value: treasuryChange" />k
            </div>
            <div class="row">
                <span class="label"><?php echo $lng->getTrn('matches/report/ff'); ?>:</span>
                <span>1<input type="radio" name="FanFactorChange" data-bind="checked: fanFactorChange" value="1" /></span>
                <span>0<input type="radio" name="FanFactorChange" data-bind="checked: fanFactorChange" value="0" /></span>
                <span>-1<input type="radio" name="FanFactorChange" data-bind="checked: fanFactorChange" value="-1" /></span>
            </div>
        </fieldset>
        
        <fieldset id="SelectedPlayer">
            <legend><?php echo $lng->getTrn('common/player'); ?>: <select data-bind="value: selectedPlayer, options: playersInMatch, optionsText: 'numberAndName'"></select></legend>
            
            <div data-bind="with: selectedPlayerViewModel">
                <div class="row">
                    <span class="label"><?php echo $lng->getTrn('matches/report/mvp'); ?>:</span>
                    <span>0<input type="radio" name="Mvp" data-bind="checked: mvp" value="0" /></span>
                    <span>1<input type="radio" name="Mvp" data-bind="checked: mvp" value="1" /></span>
                    <span>2<input type="radio" name="Mvp" data-bind="checked: mvp" value="2" /></span>
                </div>
                <div class="row">
                    <span class="label"><?php echo $lng->getTrn('common/cp'); ?>:</span>
                    <input type="number" data-bind="value: completions" />
                </div>
                <div class="row">
                    <span class="label"><?php echo $lng->getTrn('common/td'); ?>:</span>
                    <input type="number" data-bind="value: touchdowns" />
                </div>
                <div class="row">
                    <span class="label"><?php echo $lng->getTrn('common/intcpt'); ?>:</span>
                    <input type="number" data-bind="value: interceptions" />
                </div>
                <div class="row">
                    <span class="label"><?php echo $lng->getTrn('common/bh'); ?>:</span>
                    <input type="number" data-bind="value: badlyHurt" />
                </div>
                <div class="row">
                    <span class="label"><?php echo $lng->getTrn('common/si'); ?>:</span>
                    <input type="number" data-bind="value: sustainedInjury" />
                </div>
                <div class="row">
                    <span class="label"><?php echo $lng->getTrn('common/ki'); ?>:</span>
                    <input type="number" data-bind="value: killed" />
                </div>
                <div class="row">
                    <span class="label"><?php echo $lng->getTrn('common/injs'); ?>:</span>
                    <select data-bind="value: injured, options: injuries, optionsValue: 'id', optionsText: 'name'"></select>
                </div>
            </div>
        </fieldset>
        <div class="button-panel">
            <input type="button" value="<?php echo $lng->getTrn('common/save'); ?>" data-bind="click: saveMatch" />
            <a href="#" data-bind="click: close"><?php echo $lng->getTrn('common/back'); ?></a>
        </div>
        <?php
    }
}
