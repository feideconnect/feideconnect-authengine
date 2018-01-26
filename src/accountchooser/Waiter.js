class Waiter {
    constructor(callback, waitms) {
        this.callback = callback;
        this.counter = 0;
        this.waitms = waitms || 300;
    }

    ping() {
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

};

module.exports = Waiter;