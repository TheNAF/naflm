var SelectedPlayerViewModel = function() {
    var self = this,
        onUpdatePlayerValue = function(playerId, valueType, value) {};
    
    self.selectedPlayerEntry = ko.observable({});
    self.injuryTable = ko.observable({});
    
    var selectedPlayerId = ko.computed(function() { return self.selectedPlayerEntry().player_id; });
    
    function makePropertyObject(propKey) {
        return {
            read: function() {
                return self.selectedPlayerEntry()[propKey];
            },
            write: function(value) {
                self.selectedPlayerEntry()[propKey] = value;
            }
        };
    }
    
    self.mvp = ko.computed(makePropertyObject("mvp")).extend({notify: "always"});
    self.completions = ko.computed(makePropertyObject("cp")).extend({notify: "always"});
    self.touchdowns = ko.computed(makePropertyObject("td")).extend({notify: "always"});
    self.interceptions = ko.computed(makePropertyObject("intcpt")).extend({notify: "always"});
    self.badlyHurt = ko.computed(makePropertyObject("bh")).extend({notify: "always"});
    self.sustainedInjury = ko.computed(makePropertyObject("si")).extend({notify: "always"});
    self.killed = ko.computed(makePropertyObject("ki")).extend({notify: "always"});
    self.injured = ko.computed(makePropertyObject("inj")).extend({notify: "always"});
    
    self.injuryText = ko.computed(function() {
        var injuryTable = self.injuryTable(),
            injuryId = self.injured();
            
        if(!injuryTable[injuryId])
            return injuryTable["1"];
            
        return injuryTable[injuryId];
    });
    
    self.injuries = ko.computed(function() {
        var i = _.map(self.injuryTable(), function(injuryName, injuryId) {
            return {id: injuryId, name: injuryName};
        });
        return i;
    });
};