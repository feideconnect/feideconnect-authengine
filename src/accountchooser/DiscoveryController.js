/**
 * Functional description
 *
 * Will load metadata fee from these sourceS:
 * - DiscoJuice/edugain
 * - Feide org API
 * - Extra feed
 *
 * Will handle
 * - selection of country,
 * - Incremental search
 * - Geo location changes  (LocationController)
 * - Selecting a provider.
 *
 */

const $ = require('jquery');
const Utils = require('./Utils');
const Controller = require('./Controller');
const LocationController = require('./LocationController');
const DiscoveryFeedLoader = require('./DiscoveryFeedLoader');
const Provider = require('./models/Provider');
const NorwegianOrg = require('./models/NorwegianOrg');
const ProviderListView = require('./views/ProviderListView');
const Waiter = require('./Waiter');
const blank = require('../app/images/blank.png');

require('selectize');

var sortByTitle  = function(a, b) {
    if (a.title < b.title) {
        return -1;
    }
    if (a.title > b.title) {
        return 1;
    }
    return 0;
};


class DiscoveryController extends Controller {
    constructor(app, request, accounts, location) {
        super(undefined, false);

        var that = this;
        this.request = request;
        this.activeAccounts = accounts;
        this.location = location;
        this.providerListView = new ProviderListView(app);

        this.app = app;
        this.feideid = app.config.feideIdP; // Will be set in initLoad, loading from app config.
        this.initialized = false;
        this.activeTypes = null;
        this.country = 'no';
        this.countrylist = [
            "am", "au", "at", "be", "br", "ca", "cl", "hr", "cz", "dk", "ec", "ee", "fi", "fr", "ge", "de", "gr", "hu", "ie", "il", "it", "jp", "kr", "lv", "lt", "lu", "mk", "md", "nl", "no", "pl", "pt", "si", "es", "se", "ch", "ua", "gb", "us"
        ];

        this.countrylist.sort();

        /* Norwegian Feide organizations. */
        this.orgs = [];

        /* Extra providers, such as IDporten and Guest IdPs
           EduGAIN / International providers. Prefixed with language code.
           Will be loaded country by country by the DiscoveryFeedLoader. */
        this.extra = [];

        this.maxshow = 3;
        this.searchTerm = null;

        this.searchWaiter = new Waiter(function() {
            that.drawData();
        });

        this.dfl = new DiscoveryFeedLoader();

        $("body").on("click", "#actshowall", function(e) {
            e.preventDefault(); e.stopPropagation();
            that.maxshow = 9999;
            that.drawData();
        });

        $("#usersearch").on("propertychange change click keyup input paste", function() {
            var st = Utils.normalizeST($("#usersearch").val());

            if (st !== that.searchTerm) {
                that.searchTerm = st;
                if (Utils.stok(st)) {
                    that.searchWaiter.ping();
                }
            }
            // console.log("Search term is now ", st);
        });

        $("body").on("click", ".idplist .idpentry", function(e) {
            e.preventDefault();
            var so = {
                "type": "saml"
            };
            var t = $(e.currentTarget);
            var type = t.data("type");
            var id = t.data("id");
            var subid = t.data("subid");
            var userid = t.data("userid");
            var logout = t.data("logout");

            if (t.hasClass("hasactive")) {
                t.closest(".idplist").toggleClass("active");
                return;
            }

            if (id) {
                so.id = id;
            }
            if (subid) {
                so.subid = subid;
            }
            if (type) {
                so.type = type;
            }
            if (logout) {
                so.logout = 1;
            }

            so.rememberme = $("#rememberme").is(":checked");

            if (!that.request.return) {
                console.error("Invalid return address");
                return;
            }

            if (t.hasClass("disabled")) {
                alert("This provider is not yet enabled on Dataporten.");
                return;
            }
            that.go(so, !!logout);

        });

    }


    initLoad() {

        var that = this;

        $('#page-title').text(this.app.dictionary['selectprovider']);
        this.location = new LocationController();
        this.location.onUpdate(function(loc) {
            that.loadData();
            that.updateLocationView();
        });
        this.updateLocationView();
        return this.app.onLoaded()
                   .then(function() {
                       that.addCountryDropdown();
                       that.loadData();
                       that.loadDataExtra();
                   })
                   .then(this.proxy("_initLoaded"));

    }

    activate() {
        if (!this.isLoaded) {
            this.initLoad();
        }
        $("#panedisco").show();
    }

    go(so, strict) {
        var that = this;
        var url = that.request.return;
        var sep = (url.indexOf('?') > -1) ? '&' : '?';
        url += sep + 'acresponse=' + encodeURIComponent(JSON.stringify(so));
        if (strict) {
            url += '&strict=1';
        }
        // console.log("Go to ", so); return;
        window.location = url;
    }


    updateLocationView() {
        var loc = this.location.getLocation();
        // console.log("updateLocationView", loc);
        $("#locationtitle").empty().append(loc.title);
        if (loc.stored) {
            $("#removelocation").show();
        } else {
            $("#removelocation").hide();
        }

    }

    loadData() {
        var that = this;
        $.getJSON('/orgs', function(orgs) {

            that.orgs = [];
            for(var i = 0; i < orgs.length; i++) {
                that.orgs.push(new NorwegianOrg(orgs[i], that.feideid));
            }

            that.drawData();
        });
    }

    loadDataExtra() {
        var that = this;
        $.getJSON('/accountchooser/extra', function(extra) {
            that.extra = [];
            for (var i = 0; i < extra.length; i++) {
                that.extra.push(new Provider(extra[i]));
            }
            that.drawDataExtra();
        });

    }


    matchAuthProviderFilterExtra(item) {
        var providers = this.app.getAuthProviderDef();
        for(var i = 0; i < providers.length; i++) {
            if (item.matchType(providers[i])) {
                return true;
            }
        }
        return false;
    }


    matchAuthProviderFilter(item) {

        var providers = this.app.getAuthProviderDef();

        // console.log("---- MATCHING");
        // console.log(item);
        // console.log(providers);

        for(var i = 0; i < providers.length; i++) {
            // console.log("Compare", JSON.stringify(providers[i]), item);
            if (providers[i][0] === 'all') {
                return true;
            }
            if (providers[i][0] === 'feide') {

                if (providers[i][1] === 'all' && item.country === 'no') {
                    return true;
                }
                switch (providers[i][1]) {
                    case 'go':
                    case 'he':
                    case 'vgs':
                        if (item.isType(providers[i][1])) {
                            return true;
                        }
                        break;

                    case 'realm':
                        if (providers[i][2] === item.id) {
                            return true;
                        }
                        break;
                }

            }
            if (providers[i][0] === 'edugain' && item.country && item.country !== 'no') {
                if (providers[i].length === 1) {
                    return true;
                }
                if (providers[i][1] === item.country) {
                    return true;
                }
            }
        }

        return false;
    }


    matchSearchTerm(item) {

        if (this.searchTerm === null) {
            return true;
        }

        var searchTerm = this.searchTerm;
        // console.log("Searching for [" + searchTerm + "]");

        if (item.title && item.title.toLowerCase().indexOf(searchTerm) !== -1) {
            return true;
        }

        if (item.descr && item.descr.toLowerCase().indexOf(searchTerm) !== -1) {
            return true;
        }

        if (item.keywords) {
            for(var k in item.keywords) {
                if (item.keywords[k].toLowerCase().indexOf(searchTerm) !== -1) {
                    return true;
                }
            }
        }

        return false;

    }

    getCompareDistanceFunc() {

        var geo = this.location.getLocation();
        // console.error("Location is ", geo);

        return function(a, b) {

            var dista = a.getDistance(geo);
            var distb = b.getDistance(geo);

            if (dista === distb) { return 0; }
            return ((dista < distb) ? -1 : 1);
        };

    }

    addCountryDropdown() {
        var preparedCountryList = this.countrylist.map(function(country) {
            return {
                "title":  this.app.dictionary['c' + country],
                "code": country
            }
        }.bind(this));

        preparedCountryList.sort(sortByTitle);

        // Render function used for options and selected items
        function renderOption(data, escape) {
            return `<div><img src='static/${blank}' class='flag flag-${data.code}'/>${data.title}</div>`;
        }

        // Render function used for options and selected items
        function renderItem(data, escape) {
            return `<div><img src='static/${blank}' class='flag flag-${data.code}'/></div>`;
        }

        $("#countryselector").selectize({
            options: preparedCountryList,
            items: ['no'],  // Initially selected country
            valueField: 'code',
            labelField: 'title',
            searchField: 'title',
            render: {
                option: renderOption,
                item: renderItem
            },
            onChange: this.countryChangeListener.bind(this)
        });
    }

    countryChangeListener(code) {
        if (!code) {
            return;
        }
        this.country = code;
        var drawData = this.drawData.bind(this);
        if (code === 'no') {
            drawData();
        } else {
            this.dfl.loadData(code).then(drawData);
        }
    }

    hasActiveSessionOnAuthsource(type) {

        if (this.activeTypes === null) {
            this.activeTypes = {};
            for (var i = 0; i < this.activeAccounts.length; i++) {
                this.activeTypes[this.activeAccounts[i].type] = true;
            }
        }
        // console.error("this.activeTypes", this.activeTypes, type, this.activeTypes[type]);
        return !!(type && this.activeTypes[type]);
    }

    getMatchingActiveAccount(a) {
        if (!this.activeAccounts) {
            return null;
        }

        for (var i = 0; i < this.activeAccounts.length; i++) {
            var x = this.activeAccounts[i];
            x.userid = x.userids[0];

            if (x.type === a.type && x.id === a.id && x.subid && x.subid === a.subid) {
                return x;
            } else if (x.type === a.type && x.id === a.id && !x.hasOwnProperty("subid")) {
                console.log("Returning X as x does not have subid", x)
                return x;
            }
        }
        return null;

    }


    drawData() {
        var that = this;
        var it = null;

        if (this.country === 'no') {
            it = this.orgs;
        } else {
            // it = this.providers;
            it = that.dfl.getData(this.country);
        }

        var i;
        var showit = [];
        var txt = '';
        var c = 0; var missed = 0;
        var cc = 0;
        for(i = 0; i < it.length; i++) {

            if (!this.matchAuthProviderFilter(it[i])) {
                continue;
            }

            cc++;

            if (!this.matchSearchTerm(it[i])) {
                missed++;
                continue;
            }

            it[i].activeAccounts = this.getMatchingActiveAccount(it[i]);
            it[i].enforceLogout = this.hasActiveSessionOnAuthsource("saml");

            showit.push(it[i]);

        }

        var sf = this.getCompareDistanceFunc();
        showit.sort(sf);

        this.providerListView.update(showit, this.maxshow)
            .then(function(html) {
                // console.log("output html is ", html)
                $("#idplist").empty().append(html);
            })
            .catch(function(err) {
                console.error("Error processing template for providerListView", err);
            });

        $("#usersearch").focus();
    }


    drawDataExtra() {

        var txt = '';
        var c = 0;
        var showit = [];

        for(var i = 0; i < this.extra.length; i++) {

            if (!this.matchAuthProviderFilterExtra(this.extra[i])) {
                continue;
            }

            this.extra[i].activeAccounts = this.getMatchingActiveAccount(this.extra[i]);
            this.extra[i].enforceLogout = this.hasActiveSessionOnAuthsource(this.extra[i].type);

            if (!this.extra[i].logout && this.extra[i].activeAccounts !== null) {
                console.error("Does not support logout", this.extra[i]);
                this.extra[i].directAccount = this.extra[i].activeAccounts;
                this.extra[i].enforceLogout = false;
                delete this.extra[i].activeAccounts;
            }

            showit.push(this.extra[i]);
            c++;
        }

        this.providerListView.update(showit, 99999)
            .then(function(html) {
                // console.log("output html is ", html)
                $("#idplistextra").empty().append(html);
            })
            .catch(function(err) {
                console.error("Error processing template for providerListView", err);
            })

        if (c === 0) {
            $(".altchoices").hide();
            $(".orgchoices").removeClass("col-md-8").addClass("col-md-12");

        }

    }
};

module.exports = DiscoveryController;
