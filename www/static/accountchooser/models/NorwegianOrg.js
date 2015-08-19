define(function(require, exports, module) {
	"use strict";	

	var 
		Model = require('./Model'),
		Utils = require('../Utils')
		;



	var NorwegianOrg = Model.extend({
		"init": function(a) {
    		this.feideid = 'https://idp.feide.no';
    		this.feideid = 'https://idp-test.feide.no';
    		this._super(a);
		},
		"getDistance": function(loc) {

			if (this.hasOwnProperty("geo") && this.geo.hasOwnProperty("lat") && this.geo.hasOwnProperty("lon")) {
				console.error("Compare ", loc.lat, loc.lon, this.geo.lat, this.geo.lon);
				var dist = Utils.calculateDistance(loc.lat, loc.lon, this.geo.lat, this.geo.lon);
				console.error("Dist is ", dist);
				return dist;
			}

			return 9999;

		},
		"isEnabled": function() {
			if (!this.services) {
				return false;
			}
			for(var i = 0; i < this.services.length; i++) {
				if (this.services[i] === 'auth') {
					return true;
				}
			}
			return false;
		},
		"getHTML": function() {
			
			var classes = '';
			if (!this.isEnabled()) {
				classes += ' disabled';
			}
			var txt = '';
			var datastr = 'data-id="' + Utils.quoteattr(this.feideid) + '" data-subid="' + Utils.quoteattr(this.id) + '" data-type="saml"';
			txt += '<a href="#" class="list-group-item idpentry' + classes + '" ' + datastr + '>' +
				'<div class="media"><div class="media-left media-middle">' + 
						'<img class="media-object" style="width: 48px; height: 48px" src="https://api.feideconnect.no/orgs/fc:org:' + this.id + '/logo" alt="...">' + 
					'</div>' +
					'<div class="media-body"><p>' + this.title + '</p></div>' +
				'</div>' +
			'</a>';
			return txt;
		}
		
	});

	return NorwegianOrg;

});