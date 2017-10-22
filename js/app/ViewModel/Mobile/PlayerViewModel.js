var PlayerViewModel = function(player) {
    var self = this;

    self.number = player.nr;
    self.id = player.player_id;
    self.name = player.name;
    self.position = player.position;
    self.ma = player.ma;
    self.st = player.st;
    self.ag = player.ag;
    self.av = player.av;
    self.skills = player.current_skills;
    self.spp = player.mv_spp;
    self.missNextGame = player.is_mng;
    self.nigglingInjuryCount = player.inj_ni;
    self.mayBuyNewSkill = player.may_buy_new_skill > 0;
    
    var statsString = '{0}/{1}/{2}/{3}';
    statsString = statsString.replace('{0}', player.inj_ma !== '0' ? ('<span class="injury">' + player.ma + '</span>') : player.ma);
    statsString = statsString.replace('{1}', player.inj_st !== '0' ? ('<span class="injury">' + player.st + '</span>') : player.st);
    statsString = statsString.replace('{2}', player.inj_ag !== '0' ? ('<span class="injury">' + player.ag + '</span>') : player.ag);
    statsString = statsString.replace('{3}', player.inj_av !== '0' ? ('<span class="injury">' + player.av + '</span>') : player.av);
    self.statsString = statsString;
};