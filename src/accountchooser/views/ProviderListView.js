const dust = require('dustjs-linkedin');
const template = require('./dust_providerlist.dust');

class ProviderListView {
    constructor(app) {
        this.app = app;
        this.providers = [];
    }

    setProviders(providers) {
        this.providers = providers;
    }


    update(items, maxentries) {
        var that = this;
        return new Promise(function(resolve, reject) {
            // console.log("UPDATE YAY", items)
            var data = {
                dict: that.app.dictionary
            };

            if (items.length > 0) {
                data.providers = items.slice(0, maxentries).map(function(p) {
                    return p.getView();
                });
            }
            data.hasMore = (items.length > maxentries);
            data.remaining = items.length - maxentries;
            dust.render(template, data, function(err, out) {
                if (err) {
                    return reject(err);
                }
                return resolve(out);
            });
        });
    }
};

module.exports = ProviderListView;
