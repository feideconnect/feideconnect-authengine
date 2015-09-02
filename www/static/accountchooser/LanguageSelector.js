define(function(require, exports, module) {
	"use strict";

	// var qs = require('components/querystring/querystring.min');
	var Cookies = require('components/js-cookie/src/js.cookie');
	var Controller = require('./Controller');

	var LanguageSelector = Controller.extend({
		"init": function(el) {

			var that = this;
			this.languages = {
				"nb": "Bokmål",
				"nn": "Nynorsk",
				"en": "English"
			};
			this.selected = 'nn';



			// console.error("Lang setup");
			this._super(el, false);

			this.el.on('click', '.ls', function(e) {
				e.preventDefault();
				var s = $(e.currentTarget).data('lang');
				// console.log("Selected ", s);
				that.setLang(s);
			});
		},
		"setLang": function(lang) {

			// console.log("Lang was ", Cookies.get('lang'));
			Cookies.set('lang', lang, {'path': '/', 'domain': '.feideconnect.no', 'expires': (365*10)});
			// console.log("Lang is ", Cookies.get('lang'));
			// return;

			window.location.reload();

		},

		// "getUpdatedURL": function(currentURL) {
		// 	// var currentURL = window.location.href;
		// 	var currentSplitted = currentURL.split('?');

		// 	var baseURL = currentSplitted[0];
		// 	var q = {};
		// 	if (currentSplitted.length > 1) {
		// 		q = qs.parse(currentSplitted[1]);
		// 	}

		// 	q.lang = lang;

		// 	var newURL = baseURL + '?' + qs.stringify(q);

		// 	// var x = qs.stringify({ foo: 'bar', baz: ['qux', 'quux'], corge: '' });
		// 	// console.log(qs.parse('?request=%7B"return"%3A"https%3A%5C%2F%5C%2Fauth.dev.feideconnect.no%5C%2Foauth%5C%2Fauthorization%3Fresponse_type%3Dtoken%26state%3D4780338d-3d41-488e-9801-48fb2732c2cd%26redirect_uri%3Dhttps%253A%252F%252Fdashboard.dev.feideconnect.no%252Findex.dev.html%26client_id%3De8160a77-58f8-4006-8ee5-ab64d17a5b1e"%2C"clientid"%3A"e8160a77-58f8-4006-8ee5-ab64d17a5b1e"%7D'));
		// 	console.log("URL IS ", currentURL);
		// 	console.log("URL to ", newURL);
		// 	return newURL;
		// },


		"initLoad": function(lang) {
			// console.log("initload");
			var that = this;
			this.selected = lang;

			// console.log("Lang was ", Cookies.get('lang'));
			return Promise.resolve()
				.then(that.proxy("draw"))
				.then(that.proxy("_initLoaded"));

		},
		"draw": function() {
			var txt = '';

			for(var key in this.languages) {
				if (key === this.selected) {
					txt += ' <i style="margin-left: 1.2em" class="fa  fa-language"></i> ' + this.languages[key];
				} else {
					txt += ' <a class="ls" style="border-bottom: 1px dotted #bbb; margin-left: 1.2em" data-lang="' + key + '" href="#">' + this.languages[key] + '</a>';
				}
			}


			this.el.empty().append(txt);
		}

	})


	return LanguageSelector;
});
