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

	var Class = require('./Class');

	var FeideWriter = require('./FeideWriter');
	var LocationController = require('./LocationController');


	var Waiter = Class.extend({
		"init": function(callback, waitms) {
			this.callback = callback;
			this.counter = 0;
			this.waitms = waitms || 300;
		},
		"ping": function() {
			var that = this;
			this.counter++;
			setTimeout(function() {
				if (--that.counter <= 0) {
					if (typeof that.callback === 'function') {
						that.callback();
					}
				}
			}, this.waitms);
		}

	});


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

    		this.initialized = false;

    		this.feideid = 'https://idp.feide.no';
    		this.feideid = 'https://idp-test.feide.no';

    		this.orgs = [];
    		this.extra = [];

    		this.maxshow = 10;

    		this.searchTerm = null;

    		this.parseRequest();
    		this.searchWaiter = new Waiter(function() {
    			that.drawData();
    		});

			$('.dropdown-toggle').dropdown();
			$('[data-toggle="tooltip"]').tooltip();

			$("body").on("click", "#actshowall", function(e) {
				e.preventDefault(); e.stopPropagation();

				that.maxshow = 9999;
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


				that.go(so);

			});

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

    	"loadDataExtra": function() {
    		var that = this;
			$.getJSON('/accountchooser/extra', function(extra) {
				that.extra = extra;
				that.drawDataExtra();
			});

    	},

    	"loadData": function() {
    		var that = this;
    		var loc = this.location.getLocation();
			$.getJSON('/orgs?lat=' + loc.lat + '&lon=' + loc.lon + '', function(orgs) {
				that.orgs = orgs;
				that.drawData();
			});
    	},

    	"matchSearchTerm": function(item) {

    		if (this.searchTerm === null) {
    			return true;
    		}


    		var searchTerm = this.searchTerm;
    		console.log("Searching fdor [" + searchTerm + "]");

    		if (item.title.toLowerCase().indexOf(searchTerm) !== -1) {
    			return true;
    		}

    		return false;

    	},

    	"drawData": function() {

			var txt = '';
			var c = 1; var missed = 0;
			for(var i = 0; i < this.orgs.length; i++) {

				if (!this.matchSearchTerm(this.orgs[i])) {
					missed++;
					continue;
				}

				if (c > this.maxshow) {
					var remaining = this.orgs.length - missed - c;

					if (remaining > 0) {
						txt += '<p style="font-size: 94%; text-align: center"><a style="color: #777" id="actshowall" href="#"><i class="fa fa-chevron-down"></i> show all  &nbsp;' + 
							'('  + remaining + ' items hidden)</a>'

					}
					break;
				}
				c++;

				var datastr = 'data-id="' + quoteattr(this.feideid) + '" data-subid="' + quoteattr(this.orgs[i].id) + '" data-type="saml"';
				txt += '<a href="#" class="list-group-item idpentry" ' + datastr + '>' +
					'<div class="media"><div class="media-left media-middle">' + 
							'<img class="media-object" style="width: 48px; height: 48px" src="https://api.feideconnect.no/orgs/fc:org:' + this.orgs[i].id + '/logo" alt="...">' + 
						'</div>' +
						'<div class="media-body"><p>' + this.orgs[i].title + '</p></div>' +
					'</div>' +
				'</a>';

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