
var Match = function(playersOnSelectedTeam) {
    var self = this,
        playerEntries = ko.observable({}),
        onMatchLoadedHandler = function() {};
    
    self.serverMatch = ko.observable({});
    self.myTeamId = ko.observable(-1);
    self.selectedPlayer = ko.observable({});
    
    self.getMatchId = function() {
        return self.serverMatch().match_id;
    };
        
    var matchTeamOneId = ko.computed(function() {
        return parseInt(self.serverMatch().team1_id, 10);
    });
    
    function isTeamOne() {
        return matchTeamOneId() === self.myTeamId();
    }
    
    function makePropertyObject(team1PropKey, team2PropKey) {
        return {
            read: function() {
                return isTeamOne() ? self.serverMatch()[team1PropKey] : self.serverMatch()[team2PropKey];
            },
            write: function(value) {
                self.serverMatch()[(isTeamOne() ? team1PropKey : team2PropKey)] = value;
                self.serverMatch.valueHasMutated();
            }
        };
    }
    
    self.name = ko.computed(makePropertyObject('team1_name', 'team2_name'));
    self.myScore = ko.computed(makePropertyObject('team1_score', 'team2_score'));
    self.theirScore = ko.computed(makePropertyObject('team2_score', 'team1_score'));
    self.treasuryChange = ko.computed(makePropertyObject('income1', 'income2'));
    self.fanFactorChange = ko.computed(makePropertyObject('ffactor1', 'ffactor2'));
        
    self.matchIsLocked = ko.computed(function() {
        return self.serverMatch().locked;
    });
    
    self.playersInMatch = ko.computed(function() {
        var playerMatchEntries = playerEntries();
        return _.filter(playersOnSelectedTeam, function(player) {
            var inMatch = _.find(playerMatchEntries, function(playerEntry, playerId) {
                return playerId === player.player_id;
            });
            player.numberAndName = '#' + player.nr + ' ' + player.name;
            return inMatch ? player : null;
        });
    });
    
    self.load = function(newMatch) {
        self.selectedPlayer(_.first(playersOnSelectedTeam));
        
        return $.ajax({
            type: 'GET',
            url: 'match_webservice.php',
            data: {
                'action': 'getplayerentries',
                'match_id': self.getMatchId(),
                'team_id': self.myTeamId()
            },
            success: function(result) {
                try {
                    if(result === 'You must be logged into OBBLM to use this webservice.') {
                        alert('Your session has expired. Login and check "Remember me" to prevent this.');
                        return;
                    }
                    
                    playerEntries(JSON.parse(result));
                    self.selectedPlayer.valueHasMutated();
                } catch(error) {
                    alert('AJAX Error: ' + error + '. Please report this to an admin!');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('AJAX Error: ' + textStatus + '. Please report this to an admin!');
            }
        });
    };
    
    self.selectedPlayerViewModel = new SelectedPlayerViewModel();

    self.selectedPlayer.subscribe(function(newSelectedPlayer) {
        var entry = _.find(playerEntries(), function(entry, playerId) {
            return playerId === newSelectedPlayer.player_id;
        });
        
        // Happens on first load -- if it happens after that, there's a problem!
        if(entry === undefined)
            return;
        
        self.selectedPlayerViewModel.selectedPlayerEntry(entry);
    });

    self.saveMatch = function() {
        var match = self.serverMatch();
        var data = { 
            match_id : match.match_id,
            action: 'update'            
        };
        
        data[isTeamOne() ? 'ff1' : 'ff2'] = self.fanFactorChange();
        data[isTeamOne() ? 'inc1' : 'inc2'] = self.treasuryChange();
        data[isTeamOne() ? 'result1' : 'result2'] = self.myScore();
        data[isTeamOne() ? 'result2' : 'result1'] = self.theirScore();
     
        data['team_id'] = self.myTeamId();
        
        _.each(playerEntries(), function(playerEntry, playerId) {
            _.each(playerEntry, function(value, key) {
                data[key + '_' + playerId] = value;
            });
        });
        
        return $.ajax({
            type: "POST",
            url: 'match_webservice.php',
            data: data
        });
    };
};