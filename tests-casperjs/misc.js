


return;

console.log("------ config ------");
console.log(JSON.stringify(config, undefined, 4));
console.log("------ ------ ------");

casper.test.begin('Normal flow', 4, function (test) {

	var params = {
		"response_type": "code",
		"client_id": config.oauth.client_id,
		"redirect_uri": config.oauth.redirect_uri,
		"scope": config.oauth.scopes.join(' '),
		"state": fcutils.guid()
	};

	var authorizationurl = fcutils.buildUrl(config.url + 'oauth/authorization', params);

	console.log("Authorization url is :" + authorizationurl);

	casper.start(authorizationurl, function() {
		var stepname = "Login provider selection";
		test.assertTitle("Select your login provider", stepname + " title");
		test.assertHttpStatus(200, stepname + " Status code 200");
	})
	.then(function() {

		// console.log("This url is " + this.getCurrentUrl());
		// Click first link, should be Feide login
		this.click('.list-group a');

	})
	.then(function() {
		console.log("URL after choosing Feide " + this.getCurrentUrl());

		test.assertTitle("Choose affiliation");

		this.page.injectJs('https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js');
		this.evaluate(function(org) {

			$(document).ready(function() {
				$('#org').val(org).trigger('change');
				$("#submit").click();
				console.log("I've not clicked the submit button");
			});
			return true;

			// $('#username').val(username);
			// $('#password').val(password);
			// $('.submit').click();

		}, config.org);

	})

	.then(function() {
		console.log("This url is " + this.getCurrentUrl());
		// this.debugHTML();
		test.assertTitle("Enter your username and password");


		this.page.injectJs('https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js');
		this.evaluate(function(username, password) {
			$('#username').val(username);
			$('#password').val(password);
			$('.submit').click();



		}, config.username, config.password);


	})
	.then(function() {
		console.log("This url is " + this.getCurrentUrl());

		if (this.page.url.indexOf('/preprodwarning/showwarning.php') !== -1) {
			// this.debugHTML();
			// test.assertTitle("Warning about accessing a pre-production system");
			this.evaluate(function() {
				document.getElementById('yesbutton').click();
			});	
		}

	})
	.thenEvaluate(function() {

		console.log("This url is " + this.getCurrentUrl());

			// this.debugHTML();

		if (document.getElementById('attributeheader') !==  null) {

			// test.assertTitle("Warning about accessing a pre-production system");
			document.getElementById('yesbutton').click();
		}
	})
	.then(function() {




		if (this.page.title === 'Authorization Required') {


		console.log("Title i s" + this.page.title);

		this.debugHTML();
			this.evaluate(function() {
				document.getElementById('submit').click();
			});
		}




	})
	.then(function() {

		console.log("Done. This url is " + this.page.url);


	});




	// casper.then(function() {
	// 	this.click("#submit");
	// });

	// casper.thenOpen('http://phantomjs.org', function() {
	// 	this.echo(this.getTitle());
	// 	// test.assertTitle("Google", "google homepage title is the one expected");
	// });

    casper.run(function() {
        test.done();
    });


});
