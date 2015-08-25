define(function(require, exports, module) {
	"use strict";

	var Class = require('./Class');
	var DiscoveryController = require('./DiscoveryController');
	var AccountStore = require('../oauthgrant/AccountStore');



    var AccountSelector = Class.extend({

    	"init": function(app, store) {
    		var that = this;
    		this.store = store;
            this.app = app;

            $("#accounts").on("click", ".accountentry", function(e) {
                e.preventDefault();
                var userid = $(e.currentTarget).data("userid");

                if  ($("#accounts").hasClass("modeRemove")) {
                    // console.log("Ignoring, since in remove mode...");
                    return;
                }
            
                that.app.disco.go(that.store.accts[userid]);
                // console.log("Selected to login using", userid, that.store.accts[userid]);
            });
            $("#accounts").on("click", ".actRemove", function(e) {
                e.preventDefault(); e.stopPropagation();
                var userid = $(e.currentTarget).closest('.accountentry').data("userid");
                that.store.removeAccountTag(userid);
                if (that.store.hasAny() ) {
                    that.draw();    
                } else {
                    $("#paneselector").hide();
                    that.app.disco.activate();
                }
                
            
                // that.app.disco.go(that.store.accts[userid]);
                // console.log("About to remove", userid);
            });


            $("#accounts").on("click", "#removeacct", function(e) {
                e.preventDefault();
                $("#accounts").addClass("modeRemove");
            });
            $("#accounts").on("click", "#removedone", function(e) {
                e.preventDefault();
                $("#accounts").removeClass("modeRemove");
            });


            $("body").on("click", "#altlogin", function(e) {
                e.preventDefault();
                 $("#paneselector").hide();
                that.app.disco.activate();
            });

    	},

        "activate": function() {
            this.draw();
            $("#paneselector").show();
        },
        
        "draw": function() {
            var txt = '';
            for(var userid in this.store.accts) {
                var a = this.store.accts[userid];
                txt += '<a href="#" class="list-group-item accountentry" data-userid="' + userid + '" style="">' +
                    '<div class="media"><div class="media-left media-middle">' + 
                            '<img class="media-object" style="width: 64px; height: 64px" src="' + a.photo + '" alt="...">' + 
                        '</div>' +
                        '<div class="media-body">' + 
                        '<p class="showOnRemove" style=""><button class="btn btn-danger actRemove" style="float: right">Remove</button></p>' + 
                        '<i style="float: right; margin-top: 20px" class="fa fa-chevron-right fa-2x hideOnRemove"></i>' +
                        '<p style="font-size: 140%; margin: 0px">' + a.name + '</p>' + 
                        '<p style="font-size: 100%; margin: 0px; margin-top: -6px">' + a.title + '</p>' + 
                        '<p style="font-size: 70%; color: #aaa; margin: 0px">' + userid + '</p>' + 

                        '</div>' +
                    '</div>' +
                '</a>';

            }

            txt += '<div class="list-group-item">' + 

                '<p style="text-align: right; font-size: 80%; marging-top: 2em">' +
                    '   <a id="removeacct" class="hideOnRemove" href="" style="color: #888; "><i class="fa fa-times"></i> remove accounts</a>' +
                    '   <a class="showOnRemove" id="removedone" href="" style="color: #888"><i class="fa fa-check"></i> done</a>' + 
                '</p>' +
                '<p style="text-align: center; marging-top: 1em"><a id="altlogin" href="">or login with another account</a></p>' +

                '</div>';
            $("#accounts").empty().append(txt);

        }

    });
    return AccountSelector;




});