var TeamViewModel = function(playersOnSelectedTeam) {
    var self = this;
    
    self.players = ko.computed(function() {
        return _.map(playersOnSelectedTeam, function(player) {
            return new PlayerViewModel(player);
        });
    });
};