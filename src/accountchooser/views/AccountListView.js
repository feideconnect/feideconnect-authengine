const Class = require('../Class');
const dust = require('dustjs-linkedin');
const template = 'templates/dust_accountlist.dust';

const AccountListView = Class.extend({
    "init": function(app) {
        this.app = app;
    },

    "update": function(data) {
        var that = this;
        return new Promise(function(resolve, reject) {
            dust.render(template, data, function(err, out) {
                if (err) {
                    return reject(err);
                }
                return resolve(out);
            });
        });

    }
});

module.exports = AccountListView;
