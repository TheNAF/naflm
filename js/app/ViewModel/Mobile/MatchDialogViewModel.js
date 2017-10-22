var MatchDialogViewModel = function(playersOnSelectedTeam) {
    var self = this,
        match = new Match(playersOnSelectedTeam);
            
    // Just copy straight from Match
    self.serverMatch = match.serverMatch;
    self.myTeamId = match.myTeamId;
    self.matchIsLocked = match.matchIsLocked;
    self.name = match.name;
    self.myScore = match.myScore;
    self.theirScore = match.theirScore;
    self.treasuryChange = match.treasuryChange;
    self.fanFactorChange = match.fanFactorChange;
    self.selectedPlayer = match.selectedPlayer;
    self.playersInMatch = match.playersInMatch;
    self.selectedPlayerViewModel = match.selectedPlayerViewModel;
    
    self.openMatch = function(serverMatch) {
        var theirTeamName = parseInt(serverMatch.team1_id, 10) === match.myTeamId() 
            ? serverMatch.team2_name
            : serverMatch.team1_name;
        
        self.serverMatch(serverMatch);
        match
            .load(serverMatch)
            .then(function() {
                $('#MatchDialog').dialog({modal: true, title: 'vs. ' + theirTeamName});
            });
    };
    
    self.saveMatch = function() {
        match.saveMatch().done(function() {
            // reload page so updates are made
            window.location.search = '?mobile=1&lastMatchId=' + match.getMatchId() + '&SelectedTeam=' + self.myTeamId();
        });
    };
    
    self.close = function() {
        $('#MatchDialog').dialog('close');
    };
};