var MobileViewModel = function(playersOnSelectedTeam, matches, lastMatchId) {
    var self = this;
                        
    // Matches come from the database as "30000", but we deal with them as "30". Convert before we do anything else.
    _.each(matches, function(match) {
        match.income1 /= 1000;        
        match.income2 /= 1000;
        match.gate /= 1000;
        
        match.tv1 = parseInt(match.tv1, 10) / 1000;
        match.tv2 = parseInt(match.tv2, 10) / 1000;
    });
    
    self.openPlayerDialog = function(playerViewModel, event) {
        var playerId = playerViewModel.id;
        var player = _.find(playersOnSelectedTeam, function(player) {
            return player.player_id === playerId;
        });
        
        self.playerDialogViewModel.player(player);
        $('#PlayerDialog').dialog({modal: true});
    };
    
    function openMatchDialog(matchId) {
        var match = _.find(matches, function(match) {
            // match.match_id is a string
            return match.match_id === matchId || match.match_id === matchId + '';
        });

        self.matchDialogViewModel.openMatch(match);
    }
    
    self.hasLastOpenedMatchId = function() {
        return lastMatchId !== -1;
    };
    
    self.openLastOpenedMatchDialog = function() {
        openMatchDialog(lastMatchId);
    };
    
    self.openMatchDialog = function(playerViewModel, event) {
        var matchId = $(event.target).attr('data-match-id');
        openMatchDialog(matchId);
    }
	
	self.isMenuVisible = ko.observable(false);
	
	self.showMenu = function(viewModel, event) {
		self.isMenuVisible(true);
	}
    
    $('html').click(function(event) {
        if(!$(event.target).is('#menu') && !$(event.target).is('#open-menu')) {
            self.isMenuVisible(false);
        }
    });
    
    self.teamViewModel = new TeamViewModel(playersOnSelectedTeam);
    self.playerDialogViewModel = new PlayerDialogViewModel();
    self.matchDialogViewModel = new MatchDialogViewModel(playersOnSelectedTeam);
};