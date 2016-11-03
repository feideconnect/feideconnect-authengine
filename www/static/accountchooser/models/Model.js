define(function(require, exports, module) {
    "use strict";

    var Class = require('../Class');

    var Model = Class.extend({
        "init": function(props) {
            for(var key in props) {
                this[key] = props[key];
            }
        },
        "getStorable": function() {
            var res = {};
            for(var key in this) {
                if (typeof this[key] !== 'function') {
                    res[key] = this[key];
                }
            }

            return res;
        },
        "has": function(key) {
            return this.hasOwnProperty(key) && typeof this[key] !== 'function';
        },
        
        "getView": function() {
            var res = {};
            for(var key in this) {
                if (typeof this[key] !== 'function') {
                    res[key] = this[key];
                }
            }

            return res;
        }
    });


    return Model;
});
