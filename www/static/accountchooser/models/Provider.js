define(function(require, exports, module) {
	"use strict";	

	var 
		Model = require('./Model')
		;

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

	var Provider = Model.extend({

		"getHTML": function() {
			
			var txt = '';
			var datastr = 'data-id="' + quoteattr(this.entityID) + '" data-subid="' + quoteattr(this.entityID) + '" data-type="saml"';
			txt += '<a href="#" class="list-group-item idpentry" ' + datastr + '>' +
				'<div class="media"><div class="media-left media-middle">';

			if (this.icon) {
				txt += '<img class="media-object" style="width: 48px; height: 48px" src="https://api.discojuice.org/logo/' + this.icon + '" alt="...">';
			}

			txt +=	'</div>' +
					'<div class="media-body"><p>' + this.title + '</p></div>' +
				'</div>' +
			'</a>';
			return txt;
		}
		
	});

	return Provider;

});