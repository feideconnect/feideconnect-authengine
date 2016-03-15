define(function(require, exports, module) {
	"use strict";	

	var 
		Model = require('./Model'),
		Utils = require('../Utils')
		;



	var NorwegianOrg = Model.extend({
		"init": function(a) {
    		this._super(a);
		},
		"getDistance": function(loc) {

			if (this.hasOwnProperty("geo") && this.geo.hasOwnProperty("lat") && this.geo.hasOwnProperty("lon")) {
				var dist = Utils.calculateDistance(loc.lat, loc.lon, this.geo.lat, this.geo.lon);
				return dist;
			}

			return 9999;

		},
		"isType": function(type) {
			if (!this.type) {
				return false;
			}
			for (var i = 0; i < this.type.length; i++) {
				if (this.type[i] === type) {
					return true;
				}
			}
			return false;
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
		"getHTML": function(feideIdP) {
			
			var classes = '';
			if (!this.isEnabled()) {
				classes += ' disabled';
			}
			var txt = '';
			var datastr = 'data-id="' + Utils.quoteattr(feideIdP) + '" data-subid="' + Utils.quoteattr(this.id) + '" data-type="saml"';
			txt += '<a href="#" class="list-group-item idpentry' + classes + '" ' + datastr + '>' +
				'<div class="media"><div class="media-left media-middle">' + 
						'<img class="media-object" style="width: 48px; height: 48px" src="https://api.dataporten.no/orgs/fc:org:' + this.id + '/logo" alt="...">' + 
					'</div>' +
					'<div class="media-body"><p>' + Utils.quoteattr(this.title) + '</p></div>' +
				'</div>' +
			'</a>';
			return txt;
		}
		
	});

	return NorwegianOrg;

});