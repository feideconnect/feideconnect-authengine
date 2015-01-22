

var _ =   function(func, wrapper) {
    return function() {
      var args = [func].concat(slice.call(arguments, 0));
      return wrapper.apply(this, args);
    };
  };


var FlowEngine = function(page, completed, error) {

	var that = this;
	this.page = page;
	this.states = {};
	this.current = '_init_';

	this.completed = completed;
	this.error = error;

	this.nextCandidates = [];

	page.onUrlChanged = function(url) {
		console.log('URL change [' + url + ']');
	}
	page.onConsoleMessage = function(msg) {
		console.log('[' + that.current + '] ›' + msg);
	};

	page.onLoadFinished = (function(x) {
		// console.log("Load page finnished ");
		return function(status) {
			// console.log("Loaded finnished. " + page.url);
			x.pageLoaded();
		};
	})(that);

};

FlowEngine.prototype.pageLoaded = function() {

	var test, i, next;

	console.log("-------------  ------------- ------------- -------------");

	// if (this.nextCandidates === false) {
	// 	console.log("Bypassing this step.");
	// }

	var ns;
	console.log("Page loaded from [" +  this.current + "] [" + this.page.url.substr(0, 160) + "]");
	// console.log("About to process " + JSON.stringify(this.nextCandidates, undefined, 2));

	for(i = 0; i < this.nextCandidates.length; i++) {
		test = this.states[this.nextCandidates[i]].detect(this.page);
		if (!test) {
			console.log(" [ ] State not match: [" + this.nextCandidates[i] + "]");
		} else {
			next = this.states[this.nextCandidates[i]];
			this.current = this.nextCandidates[i];
			
			console.log(" [X] State did match [" + this.nextCandidates[i] + "]");

			ns = (next.nextStates ? next.nextStates.join(',') : 'null');
			console.log("     Setting next candidates to " + ns);
			console.log("     ------> Execute [" + this.nextCandidates[i] + "] <------");
			console.log("");

			this.nextCandidates = next.nextStates;
			next.run(this.page);

			if (next.nextStates === null) {
				console.log(this.page);
				return this.completed();
			}

			return;
		}
	}
	console.error("No states detected. Dumping output");
	console.log("----- --- --- -- -- - - .");
	console.log(this.page.content);
	return this.error();

};

FlowEngine.prototype.addState = function(id, detect, run, nextStates)  {
	this.states[id] = {
		"detect": detect,
		"run": run,
		"nextStates": nextStates
	};

};


FlowEngine.prototype.go = function(url, nextStates) {


	console.log("Started a flowengine by going to [" + url + "]. Next states is " + nextStates.join(', '));

	this.nextCandidates = nextStates;
	this.page.open(url);

	// console.log("Config to go:"); console.log(JSON.stringify(this, undefined, 4));

};

FlowEngine.prototype.unexpected = function() {



};


exports.FlowEngine = FlowEngine;


