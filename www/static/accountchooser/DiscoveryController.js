define(function(require, exports, module) {
	"use strict";


	function quoteattr(s, preserveCR) {
	    preserveCR = preserveCR ? '&#13;' : '\n';
	    return ('' + s) /* Forces the conversion to string. */
	        .replace(/&/g, '&amp;') /* This MUST be the 1st replacement. */
	        .replace(/'/g, '&apos;') /* The 4 other predefined entities, required. */
	        .replace(/"/g, '&quot;')
	        .replace(/</g, '&lt;')
	        .replace(/>/g, '&gt;')
	        /*
	        You may add other replacements here for HTML only 
	        (but it's not necessary).
	        Or for XML, only if the named entities are defined in its DTD.
	        */ 
	        .replace(/\r\n/g, preserveCR) /* Must be before the next replacement. */
	        .replace(/[\r\n]/g, preserveCR);
	}

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

	var FeideWriter = require('./FeideWriter');
	var LocationController = require('./LocationController');
	var DiscoveryFeedLoader = require('./DiscoveryFeedLoader');

	var Provider = require('./models/Provider');
	var NorwegianOrg = require('./models/NorwegianOrg');

	var Waiter = require('./Waiter');


	var normalizeST = function(searchTerm) {
		var x = searchTerm.toLowerCase().replace(/\W/g, '');
		if (x === '') {
			return null;
		}
		return x;
	}

	var stok = function(str) {
		console.log("STR", str);
		if (str === null) {return true;}
		if (str.length > 2) { return true; }
		return false;
	}


    var DiscoveryController = Class.extend({
    	"init": function() {
    		var that = this;

    		this.feideid = 'https://idp.feide.no';
    		this.feideid = 'https://idp-test.feide.no';

    		this.initialized = false;


    		this.country = 'no';
    		this.countries = {};
    		for(var i = 0; i < countries.length; i++) {
    			this.countries[countries[i].id] = countries[i].title;
    		}


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
			this.dfl.onUpdate(function(providers) {
				that.providers = [];
				for(var i = 0; i < providers.length; i++) {
					that.providers.push(new Provider(providers[i]));
				}

				// console.error("Received", that.country);
				if (that.country !== "no") {
					that.drawData();
				}
			});

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
				var st = normalizeST($("#usersearch").val());

				if (st !== that.searchTerm) {
					that.searchTerm = st;
					if (stok(st)) {
						that.searchWaiter.ping();	
					}
				}


				console.log("Search term is now ", st);
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
					return alert("This provider is not yet technically connected to the Connect pilot.");
				}

				that.go(so);

			});

    	},

    	"updateCurrentCountry": function(c) {
    		// console.log("Selected country is " + c);
    		this.country = c;
    		// console.log(this.countries);
    		$("#selectedcountry").empty().append('<img style="margin-top: -3px; margin-right: 5px" src="/static/media/flag/' + c + '.png"> ' + this.countries[c] +' <span class="caret"></span>');
    	},

    	"initialize": function() {
    		var that = this;
    		this.initialized = true;

    		this.location = new LocationController();
    		this.location.onUpdate(function(loc) {
    			that.loadData();
    			that.updateLocationView();
    		});
    		
			this.updateLocationView();

    		this.loadData();
    		this.loadDataExtra();
    	},


    	"activate": function() {

    		if (!this.initialized) {
    			this.initialize();
    		}

			$("#panedisco").show();

    	},

    	"go": function(so) {
    		var that = this;
			var url = that.request.return;
			url += '&acresponse=' + encodeURIComponent(JSON.stringify(so));

			if (that.feideid === so.id) {

				var f = (new FeideWriter(so.subid))
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
    		// console.error("Location is ", loc);
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
				that.extra = extra;
				that.drawDataExtra();
			});

    	},


    	"matchSearchTerm": function(item) {

    		if (this.searchTerm === null) {
    			return true;
    		}

    		var searchTerm = this.searchTerm;
    		console.log("Searching for [" + searchTerm + "]");


    		if (item.title === null) {
    			console.error("Title is empty", item); 
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
			var c = 1; var missed = 0;
			for(i = 0; i < it.length; i++) {

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


				if (c > this.maxshow) {
					var remaining = it.length - missed - c;

					if (remaining > 0) {
						txt += '<p style="font-size: 94%; text-align: center"><a style="color: #777" id="actshowall" href="#"><i class="fa fa-chevron-down"></i> show all  &nbsp;' + 
							'('  + remaining + ' items hidden)</a>'

					}
					break;
				}
				c++;
				txt += showit[i].getHTML();

			}
			$("#idplist").empty().append(txt);
			$("#usersearch").focus();

    	},


    	"drawDataExtra": function() {

			var txt = '';
			for(var i = 0; i < this.extra.length; i++) {
				var iconImage = '';
				if (this.extra[i].iconImage) {
					iconImage = '<img class="media-object" style="width: 48px; height: 48px" src="/static/media/disco/' + this.extra[i].iconImage + '" alt="...">';
				} else if (this.extra[i].icon) {
					iconImage = '<i style="margin-left: 6px" class="' + this.extra[i].icon + '"></i>';
				}

				var idtxt = '';
				if (this.extra[i].id) {
					idtxt += ' data-id="' + quoteattr(this.extra[i].id) + '"';  
				}
				if (this.extra[i].type) {
					idtxt += ' data-type="' + quoteattr(this.extra[i].type) + '"';  
				}
				if (this.extra[i].subid) {
					idtxt += ' data-subid="' + quoteattr(this.extra[i].subid) + '"';  
				}

				txt += '<a href="#" class="list-group-item idpentry" ' + idtxt + '>' +
					'<div class="media"><div class="media-left media-middle">' + iconImage + '</div>' +
						'<div class="media-body"><p>' + this.extra[i].title + '</p></div>' +
					'</div>' +
				'</a>';

			}

			$("#idplistextra").empty().append(txt);

    	}



    });
    return DiscoveryController;




});