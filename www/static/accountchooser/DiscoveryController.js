define(function(require, exports, module) {
	"use strict";

	var Utils = require('./Utils');


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
	 * 	- including feide preselect org (FeideWriter)
	 * 
	 */



	var Class = require('./Class');

	var Controller = require('./Controller');

	var FeideWriter = require('./FeideWriter');
	var LocationController = require('./LocationController');
	var DiscoveryFeedLoader = require('./DiscoveryFeedLoader');

	var Provider = require('./models/Provider');
	var NorwegianOrg = require('./models/NorwegianOrg');

	var Waiter = require('./Waiter');



    var DiscoveryController = Controller.extend({
    	"init": function(app) {
    		var that = this;

    		this.app = app;

    		this.feideid = null; // Will be set in initLoad, loading from app config.

    		this.initialized = false;

    		this.country = 'no';
    		this.countrylist = ['no', 'dk', 'fi', 'se', 'is', 'nl'];
    		// this.countries = {};
    		// for(var i = 0; i < countries.length; i++) {
    		// 	this.countries[countries[i].id] = countries[i].title;
    		// }


    		this.orgs = [];
    		this.extra = [];
    		this.providers = [];

    		this.maxshow = 10;

    		this.searchTerm = null;

    		this.parseRequest();
    		this.searchWaiter = new Waiter(function() {
    			that.drawData();
    		});

			this.dfl = new DiscoveryFeedLoader();

			this.dfl.onLoaded()
				.then(function() {
					that.providers = that.dfl.getData();
					if (that.country !== "no") {
						that.drawData();
					}
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

				if (id) {
					so.id = id;
				}
				if (subid) {
					so.subid = subid;
				}
				if (type) {
					so.type = type;
				}

				so.rememberme = $("#rememberme").is(":checked");
				
				if (!that.request.return) {
					console.error("Invalid return address"); 
					return;
				}

				if (t.hasClass("disabled")) {
					return alert("This provider is not yet enabled on Dataporten.");
				}

				that.go(so);

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


		"setFeideIdP": function(idp) {
			this.feideid = idp;
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

    	"go": function(so) {
    		var that = this;
			var url = that.request.return;
			var sep = (url.indexOf('?') > -1) ? '&' : '?';
			url += sep + 'acresponse=' + encodeURIComponent(JSON.stringify(so));

			if (that.feideid === so.id) {

				var f = (new FeideWriter(this.app, so.subid, that.feideid))
					.onLoad( function() {
						window.location = url;	
					})
					.load();

			} else {
				window.location = url;	
			}
    	},


    	"updateLocationView": function() {
    		var loc = this.location.getLocation();
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
    		var loc = this.location.getLocation();
			$.getJSON('/orgs?lat=' + loc.lat + '&lon=' + loc.lon + '', function(orgs) {

				that.orgs = [];
				for(var i = 0; i < orgs.length; i++) {
					that.orgs.push(new NorwegianOrg(orgs[i]));
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

					if (providers[i][1] === 'all') {
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
			}

			return false;
		},


    	"matchSearchTerm": function(item) {

    		if (this.searchTerm === null) {
    			return true;
    		}

    		var searchTerm = this.searchTerm;
    		// console.log("Searching for [" + searchTerm + "]");


    		if (item.title === null) {
    			return false;
    		}
    		if (item.title.toLowerCase().indexOf(searchTerm) !== -1) {
    			return true;
    		}

    		return false;

    	},

    	"matchCountry": function(item) {

    		if (this.country === 'no') {
    			return true;
    		}

    		if (this.country === null) {
    			return true;
    		}

    		if (!item.hasOwnProperty("country")) {
    			return false;
    		}
    		
    		if (item.country.toLowerCase() === this.country) {
    			return true;
    		}
    		return false;

    	},

    	"getCompareDistanceFunc": function() {

    		var geo = this.location.getLocation();

    		return function(a, b) {

    			var dista = a.getDistance(geo);
    			var distb = b.getDistance(geo);

    			if (dista === distb) { return 0; }
				return ((dista < distb) ? -1 : 1);
    		}

    	},

    	"drawBasics": function() {
    		var ct, cn, txt = '';
    		
    		for(var i = 0; i < this.countrylist.length; i++) {
    			ct = 'c' + this.countrylist[i];
    			cn = this.app.dictionary[ct];
     			// console.error("Country is ", ct, cn );
     			txt += '<li><a class="selectcountry" data-country="' + this.countrylist[i] + '" href="#">' + 
	     			'<img style="margin-top: -4px; margin-right: 5px" src="/static/media/flag/' + this.countrylist[i] + '.png">' + 
	     			' ' + cn + '</a></li>';
    		}
    		$("#countryselector").empty().append(txt);
    	},

    	"drawData": function() {

    		var that = this;
    		var it = null;

    		// console.error("Draw data with ", this.country);
    		if (this.country === 'no') {
    			it = this.orgs;
    		} else {
    			it = this.providers;
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

				if (!this.matchCountry(it[i])) {
					missed++;
					continue;
				}

				showit.push(it[i]);
			}

			if (this.country !== 'no') {			
				var sf = this.getCompareDistanceFunc();
				showit.sort(sf);
			}

			for (i = 0; i < showit.length; i++) {

				if (c > (this.maxshow - 1)) {
					var remaining = it.length - missed - c;

					if (remaining > 0) {
						txt += '<p style="font-size: 94%; text-align: center"><a style="color: #777" id="actshowall" href="#"><i class="fa fa-chevron-down"></i> ' +
						  this.app.dictionary.showall + '  &nbsp;' + 
							'('  + remaining + ' ' + this.app.dictionary.hidden +')</a>'

					}
					break;
				}
				c++;
				txt += showit[i].getHTML(that.app.config.feideIdP);
			}
			$("#idplist").empty().append(txt);
			if (cc === 0 && this.country === 'no') {
				$(".orgchoices").hide();
				$(".altchoices").removeClass("col-md-4").addClass("col-md-12");
			}

			$("#usersearch").focus();

    	},


    	"drawDataExtra": function() {

			var txt = '';
			var c = 0;

			for(var i = 0; i < this.extra.length; i++) {

				if (!this.matchAuthProviderFilterExtra(this.extra[i])) {
					continue;
				}


				var iconImage = '';
				if (this.extra[i].iconImage) {
					iconImage = '<img class="media-object" style="width: 48px; height: 48px" src="/static/media/disco/' + this.extra[i].iconImage + '" alt="...">';
				} else if (this.extra[i].icon) {
					iconImage = '<i style="margin-left: 6px" class="' + this.extra[i].icon + '"></i>';
				}

				var idtxt = '';
				if (this.extra[i].id) {
					idtxt += ' data-id="' + Utils.quoteattr(this.extra[i].id) + '"';  
				}
				if (this.extra[i].type) {
					idtxt += ' data-type="' + Utils.quoteattr(this.extra[i].type) + '"';  
				}
				if (this.extra[i].subid) {
					idtxt += ' data-subid="' + Utils.quoteattr(this.extra[i].subid) + '"';  
				}

				c++;
				txt += '<a href="#" class="list-group-item idpentry" ' + idtxt + '>' +
					'<div class="media"><div class="media-left media-middle">' + iconImage + '</div>' +
						'<div class="media-body"><p>' + this.extra[i].title + '</p></div>' +
					'</div>' +
				'</a>';

			}

			if (c === 0) {
				$(".altchoices").hide();
				$(".orgchoices").removeClass("col-md-8").addClass("col-md-12");

			}
			$("#idplistextra").empty().append(txt);

    	}



    });
    return DiscoveryController;




});
