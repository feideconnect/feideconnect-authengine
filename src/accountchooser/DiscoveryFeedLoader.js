const Controller = require('./Controller');
const Provider = require('./models/Provider');

class DiscoveryFeedLoader extends Controller {
    constructor() {
        super(undefined, true);
        this._callback = null;
        this.providers = {};
    }

    initLoad() {
        return this._initLoaded();
    }

    loadData(country) {
        var that = this;

        if (this.providers[country]) {
            return Promise.resolve();
        }

        return new Promise(function(resolve, reject) {
            var url = '/metadata/providers/' + country;
            $.ajax({
                dataType: "json",
                url: url,
                success: function(data) {
                    that.providers[country] = [];
                    for(var i = 0; i < data.length; i++) {
                        data[i].country = country;
                        that.providers[country].push(new Provider(data[i]));
                    }
                    // that.providers = data;
                    resolve(data);
                },
                error: function(err, a, b) {
                    console.error("error ", err, a, b);
                    reject(err);
                }
            });
        });

    }

    getData(country) {
        return this.providers[country];
    }

};

module.exports = DiscoveryFeedLoader;
