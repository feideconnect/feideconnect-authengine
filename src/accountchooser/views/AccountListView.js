const dust = require('dustjs-linkedin');
const template = require('./dust_accountlist.dust');

class AccountListView {
    constructor(app) {
        this.app = app;
    }

    update(data) {
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
};

module.exports = AccountListView;
