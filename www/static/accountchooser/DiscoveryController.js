define(function(require, exports, module) {
    "use strict";
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

    var Utils = require('./Utils');
    var Class = require('./Class');
    var Controller = require('./Controller');
    var LocationController = require('./LocationController');
    var DiscoveryFeedLoader = require('./DiscoveryFeedLoader');
    var Provider = require('./models/Provider');
    var NorwegianOrg = require('./models/NorwegianOrg');
    var ProviderListView = require('./views/ProviderListView');
    var Waiter = require('./Waiter');

    var sortByTitle  = function(a, b) {
        if (a.title < b.title) {
            return -1;
        }
        if (a.title > b.title) {
            return 1;
        }
        return 0;
    }


    var DiscoveryController = Controller.extend({
        "init": function(app) {
            var that = this;
            this.providerListView = new ProviderListView(app);

            this.app = app;
            this.feideid = app.config.feideIdP; // Will be set in initLoad, loading from app config.
            this.initialized = false;
            this.activeTypes = null;
            this.country = 'no';
            this.countrylist = [
                "am",
                "au",
                "at",
                "be",
                "br",
                "ca",
                "cl",
                "hr",
                "cz",
                "dk",
                "ec",
                "ee",
                "fi",
                "fr",
                "ge",
                "de",
                "gr",
                "hu",
                "ie",
                "il",
                "it",
                "jp",
                "kr",
                "lv",
                "lt",
                "lu",
                "mk",
                "md",
                "nl",
                "no",
                "pl",
                "pt",
                "si",
                "es",
                "se",
                "ch",
                "ua",
                "gb",
                "us"
            ];

            this.countrylist.sort();
            // this.countries = {};
            // for(var i = 0; i < countries.length; i++) {
            //  this.countries[countries[i].id] = countries[i].title;
            // }

            this.orgs = [];             // Norwegian Feide organizations.
            this.extra = [];            // Extra providers, such as IDporten and Guest IdPs
                                        // EduGAIN / International providers. Prefixed with language code.
                                        //  Will be loaded country by country by the DiscoveryFeedLoader.

            this.maxshow = 10;
            this.searchTerm = null;

            this.parseRequest();
            this.searchWaiter = new Waiter(function() {
                that.drawData();
            });

            this.dfl = new DiscoveryFeedLoader();
            this.dfl.onLoaded()
                .then(function() {
                    // that.providers = that.dfl.getData();
                    // if (that.country !== "no") {
                    //     that.drawData();
                    // }
                });

            this._super(undefined, false);

            $('.dropdown-toggle').dropdown();
            $('[data-toggle="tooltip"]').tooltip();

            $("body").on("click", "#actshowall", function(e) {
                e.preventDefault(); e.stopPropagation();
                that.maxshow = 9999;
                that.drawData();
            });

            $("#countryselector").on("click", ".selectcountry", function(e) {
                // e.preventDefault(); e.stopPropagation();
                var c = $(e.currentTarget).data("country");
                that.updateCurrentCountry(c);

                if (c === 'no') {
                    that.drawData();
                } else {
                    that.dfl.loadData(c)
                        .then(function(data) {
                            that.drawData();
                        });

                }

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

                // console.error("Classes", t.hasClass("hasactive"), t)
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

        },


        "initLoad": function() {

            var that = this;

            this.location = new LocationController();
            this.location.onUpdate(function(loc) {
                that.loadData();
                that.updateLocationView();
            });
            this.updateLocationView();
            return this.app.onLoaded()
                .then(function() {
                    that.updateCurrentCountry('no');
                    that.drawBasics();
                    that.loadData();
                    that.loadDataExtra();
                })
                .then(this.proxy("_initLoaded"));

        },

        "updateCurrentCountry": function(c) {
            // console.log("Selected country is " + c);
            this.country = c;
            // console.log(this.countries);
            $("#selectedcountry").empty().append('<img style="margin-top: -3px; margin-right: 5px" src="/static/media/flag/' + c + '.png"> ' + this.app.dictionary['c' + c] +' <span class="caret"></span>');
        },

        "activate": function() {
            if (!this.isLoaded) {
                this.initLoad();
            }
            $("#panedisco").show();
        },

        "go": function(so, strict) {
            var that = this;
            var url = that.request.return;
            var sep = (url.indexOf('?') > -1) ? '&' : '?';
            url += sep + 'acresponse=' + encodeURIComponent(JSON.stringify(so));
            if (strict) {
                url += '&strict=1';
            }

            // console.log("Go to ", so);

            window.location = url;

        },


        "updateLocationView": function() {
            var loc = this.location.getLocation();
            // console.log("updateLocationView", loc);
            $("#locationtitle").empty().append(loc.title);
            if (loc.stored) {
                $("#removelocation").show();
            } else {
                $("#removelocation").hide();
            }

        },

        "parseRequest": function() {
            if (acrequest) {
                this.request = acrequest;
            }
        },

        "loadData": function() {
            var that = this;
            $.getJSON('/orgs', function(orgs) {

                that.orgs = [];
                for(var i = 0; i < orgs.length; i++) {
                    that.orgs.push(new NorwegianOrg(orgs[i], that.feideid));
                }

                that.drawData();
            });
        },

        "loadDataExtra": function() {
            var that = this;
            $.getJSON('/accountchooser/extra', function(extra) {
                that.extra = [];
                for (var i = 0; i < extra.length; i++) {
                    that.extra.push(new Provider(extra[i]));
                }
                that.drawDataExtra();
            });

        },


        "matchAuthProviderFilterExtra": function(item) {

            var providers = this.app.getAuthProviderDef();

            for(var i = 0; i < providers.length; i++) {

                if (item.matchType(providers[i])) {
                    return true;
                }

            }
            return false;
        },


        "matchAuthProviderFilter": function(item) {

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
        },


        "matchSearchTerm": function(item) {

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

        },

        "getCompareDistanceFunc": function() {

            var geo = this.location.getLocation();
            // console.error("Location is ", geo);

            return function(a, b) {

                var dista = a.getDistance(geo);
                var distb = b.getDistance(geo);

                if (dista === distb) { return 0; }
                return ((dista < distb) ? -1 : 1);
            };

        },

        "drawBasics": function() {
            var ct, cn, txt = '';
            var preparedCountryList = [];
            for(var i = 0; i < this.countrylist.length; i++) {
                preparedCountryList.push({
                    "title":  this.app.dictionary['c' + this.countrylist[i]],
                    "code": this.countrylist[i]
                });
            }
            preparedCountryList.sort(sortByTitle);
            for(var i = 0; i < preparedCountryList.length; i++) {
                txt += '<li><a class="selectcountry" data-country="' + preparedCountryList[i].code + '" href="#">' +
                    '<img style="margin-top: -4px; margin-right: 5px" src="/static/media/flag/' + preparedCountryList[i].code + '.png">' +
                    ' ' + preparedCountryList[i].title + '</a></li>';
            }
            $("#countryselector").empty().append(txt);
        },

        "hasActiveSessionOnAuthsource": function(type) {

            if (this.activeTypes === null) {
                this.activeTypes = {};
                for (var i = 0; i < window.activeAccounts.length; i++) {
                    this.activeTypes[window.activeAccounts[i].type] = true;
                }
            }
            console.error("this.activeTypes", this.activeTypes, type, this.activeTypes[type]);
            return !!(type && this.activeTypes[type]);
        },

        "getMatchingActiveAccount": function(a) {
            if (!window.activeAccounts) {
                return null;
            }

            for (var i = 0; i < window.activeAccounts.length; i++) {
                var x = window.activeAccounts[i];

                if (x.id === a.id && x.subid && x.subid === a.subid) {
                    x.userid = x.userids[0];
                    return x;
                }

                console.log("Comparing ", a, "with", x);
                // if (a.matchesActiveAccount(x)) {
                //     return x;
                // }
            }
            return null;

        },


        "drawData": function() {
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
                // console.log("Searching for ", it[i]);
                // console.log(this.app.accountstore);

                it[i].activeAccounts = this.getMatchingActiveAccount(it[i]);
                it[i].enforceLogout = this.hasActiveSessionOnAuthsource("saml");

                console.error("NorwegianOrg Provider debug", it[i])

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
                })

            if (showit.length === 0 && this.country === 'no') {
                $(".orgchoices").hide();
                $(".altchoices").removeClass("col-md-4").addClass("col-md-12");
            }
            $("#usersearch").focus();
        },


        "drawDataExtra": function() {

            var txt = '';
            var c = 0;
            var showit = [];

            for(var i = 0; i < this.extra.length; i++) {

                if (!this.matchAuthProviderFilterExtra(this.extra[i])) {
                    continue;
                }

                this.extra[i].activeAccounts = this.getMatchingActiveAccount(this.extra[i]);
                this.extra[i].enforceLogout = this.hasActiveSessionOnAuthsource(this.extra[i].type);

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



    });
    return DiscoveryController;




});
