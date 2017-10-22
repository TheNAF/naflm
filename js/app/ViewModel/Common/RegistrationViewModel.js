var RegistrationViewModel = function(leaguesJson) {
    var self = this;
    
    self.isCommissioner = ko.observable(false);
    self.selectedLeague = ko.observable(-1);
    self.leagues = leaguesJson;
    
    self.showLeagueSelection = ko.computed(function() {
        return !self.isCommissioner();
    });
}