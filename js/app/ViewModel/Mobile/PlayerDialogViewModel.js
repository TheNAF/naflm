var PlayerDialogViewModel = function() {
    var self = this;
    
    self.player = ko.observable({});
    
    self.number = ko.computed(function() { return self.player().nr; });
    self.name = ko.computed(function() { return self.player().name; });
    self.position = ko.computed(function() { return self.player().position; });
    
    self.statString = ko.computed(function() { 
        var player = self.player();
        return player.ma + '/' + player.st + '/' + player.ag + '/' + player.av; 
    });
    
    self.skillsString = ko.computed(function() { return self.player().current_skills; });
    self.spp = ko.computed(function() { return self.player().mv_spp; });
    
    self.mayBuyNewSkill = ko.computed(function() { return self.player().may_buy_new_skill > 0; });
    
    self.choosableNormalSkills = ko.computed(function() {
        if(!self.player().choosable_skills_strings)
            return [];
        
        return _.map(self.player().choosable_skills_strings['norm'], function(skillName, skillId) {
            return { id: skillId, name: skillName };
        });
    }); 
    
    self.choosableDoubleSkills = ko.computed(function() {
        if(!self.player().choosable_skills_strings)
            return [];
        
        return _.map(self.player().choosable_skills_strings['doub'], function(skillName, skillId) {
            return { id: skillId, name: skillName };
        });
    }); 
    
    self.choosableCharacteristicIncreases = ko.computed(function() {
        if(!self.player().choosable_skills_strings)
            return [];
        
        return _.map(self.player().choosable_skills_strings['chr'], function(characteristic, characteristicId) {
            return { id: 'ach_' + characteristicId, name: characteristic };
        });
    });
    
    self.selectedNewSkill = ko.observable(null);
    
    self.player.subscribe(function() {
        var firstSkill = _.first(self.choosableNormalSkills());
        self.selectedNewSkill(firstSkill.id);
    });
    
    self.saveSkill = function() {
        var player = self.player(),
            selectedNewSkillId = self.selectedNewSkill();
        $.ajax({
            type: 'POST',
            url: 'team_webservice.php',
            data: {
                'type': 'skill',
                'player': player.player_id,
                'teamId': player.owned_by_team_id,
                'skill': selectedNewSkillId
            },
            success: function(result) {
                window.location.href = 'index.php?mobile=1';
            }
        });
    };
};