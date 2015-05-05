"use strict";

$(document).ready(function() {

	$(".grantEntry").on("click", function(item) {
		console.log("Click");
		$(item.currentTarget).toggleClass("grantEntryActive");
	});

	$("body").on("click", "#actAcceptBrVilk", function(item) {
		// console.log("Click");
		$('#myModal').modal('hide');
		$('#bruksvilkar').prop('checked', true);
	});

	$("body").on("click", ".tglSimple", function(e) {
		e.preventDefault();
		$("body").toggleClass("simpleGrant");

	});

});
