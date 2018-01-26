class Model {
    constructor(props) {
        for(var key in props) {
            this[key] = props[key];
        }
    }

    getStorable() {
        var res = {};
        for(var key in this) {
            if (typeof this[key] !== 'function') {
                res[key] = this[key];
            }
        }

        return res;
    }

    has(key) {
        return this.hasOwnProperty(key) && typeof this[key] !== 'function';
    }

    getView() {
        var res = {};
        for(var key in this) {
            if (typeof this[key] !== 'function') {
                res[key] = this[key];
            }
        }

        return res;
    }
};

module.exports = Model;
