const Class = require('./Class');
const EventEmitter = require('./EventEmitter');

const Controller = Class.extend({

    "init": function(el, load) {
        this.el = el || this.el || $('<div class=""></div>');

        this._loaderTimeout = 5000;

        this.onLoadedCallbacks = [];
        this.isLoaded = false;
        if (load === true) {
            this.initLoad();
        }
    },
    "registerOnLoaded": function(func) {
        this.onLoadedCallbacks.push(func);
    },
    "onLoaded": function() {

        var that = this;
        if (this.isLoaded) {
            return new Promise(function(resolve, reject) {
                resolve(that);
            });
        }

        return new Promise(function(resolve, reject) {
            that.registerOnLoaded(resolve);
            setTimeout(function() {
                if (!that.isLoaded) {
                    reject(new Error("Loading this objected timed out. (Time out is set to " + that._loaderTimeout + "ms)"));
                }
            }, that._loaderTimeout);
        });

    },
    "_initLoaded": function() {
        var i;
        // console.error("_initloaded", this.onLoadedCallbacks);
        if (!this.isLoaded) {
            this.isLoaded = true;
            for(i = 0; i < this.onLoadedCallbacks.length; i++) {
                this.onLoadedCallbacks[i](this);
            }
            this.onLoadedCallbacks = [];
        }
    },
    "initLoad": function() {
        var that = this;
        return new Promise(function(resolve, reject) {
            that._initLoaded();
            resolve();
        });
    },
    "show": function() {
        this.el.show();
    },
    "hide": function() {
        this.el.hide();
    },
    "ebind": function(type, filter, func) {
        this.el.on(type, filter, $.proxy(this[func], this));
    },
    "proxy": function(func) {
        var that = this;
        var args = Array.prototype.splice.call(arguments, 1);
        return function() {
            return that[func].apply(that, args);
        };
    }
}).extend(EventEmitter);

module.exports = Controller;
