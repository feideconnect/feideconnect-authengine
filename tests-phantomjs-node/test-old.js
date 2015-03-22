

var phantom = require('phantom');
var assert = require("assert")


// describe('Array', function(){
// 	describe('#indexOf()', function(){
// 		it('should return -1 when the value is not present', function(){
// 			assert.equal(-1, [1,2,3].indexOf(5));
// 			assert.equal(-1, [1,2,3].indexOf(0));
// 			})
// 		})
// })


describe('Google web page', function() {

	it('is being prepared', function(pdone) {

		phantom.create(function (ph) {

			console.log("CREATED..");
			pdone();

			ph.createPage(function (page) {
				page.open("http://www.google.com", function (status) {
					console.log("opened google? ", status);

					it('Should open correctly', function() {
						assert.equal(status, "200");
					});

					it('should have the correct page title', function(done) {

						page.evaluate(function () { return document.title; }, function (result) {
							console.log('Page title is ' + result);

							assert.equal(result, "google", "Page title");
							done();
							ph.exit();
						});


					});



				});
			});



			});




	});





});






