const Cookies = require('js-cookie');
const Controller = require('./Controller');

class LanguageSelector extends Controller {
    constructor(el) {
        super(el, false);

        var that = this;
        this.languages = {
            "nb": "Bokmål",
            "nn": "Nynorsk",
            "en": "English"
        };
        this.selected = 'nn';

        this.config = {};

        this.el.on('click', '.ls', function(e) {
            e.preventDefault();
            var s = $(e.currentTarget).data('lang');
            // console.log("Selected ", s);
            that.setLang(s);
        });
    }

    setConfig(config) {
        this.config = config;
    }

    setLang(lang) {

        var langCookieDomain = '.dataporten.no';
        if (this.config.hasOwnProperty('langCookieDomain')) {
            langCookieDomain = this.config.langCookieDomain;
        }

        Cookies.set('lang', lang, {'path': '/', 'domain': langCookieDomain, 'expires': (365*10)});
        window.location.reload();

    }

    initLoad(lang) {
        // console.log("initload");
        var that = this;
        this.selected = lang;

        // console.log("Lang was ", Cookies.get('lang'));
        return Promise.resolve()
                      .then(that.proxy("draw"))
                      .then(that.proxy("_initLoaded"));

    }

    draw() {
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
};

module.exports = LanguageSelector;
