var system = require('system');
var page = require('webpage').create();
var LoginFlow = require('./lib/LoginFlow').LoginFlow;


console.log("sys"+ system.env["password"]);


page.viewportSize = { width: 1080, height: 720 };


var lf = new LoginFlow(page);




