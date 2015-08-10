# Accountchooser


Located at `/accountchooser`.



## The request


The request is sent as a urlencoded JSON encoded object as the query string parameter with name `request`.

The request object may look like this:


	{
		"return": "https://auth.dev.feideconnect.no/foo"		
	}


## The response


The request is sent as a urlencoded JSON encoded object as the query string parameter with name `response`.



	{
		"type": "saml",
		"id": "https://idp.feide.no",
		"subid": "ntnu.no"
	}




## Setting up new authentication sources

Configure SimpleSALMphp auth source, or add config for one of the existing.

Test login with simplesamlphp.

If new auth source, add an entry in : `config.json` : `authTypes`.

Make sure an account map is matching the new authentication source in the config.json: `accountMaps` map.

If appropriate add a new entry in `disco2.json` to add an entry in the accountchooser selector dialog.

Inspect the `getVisualTag()` function in `Account.php` to implement an appropriate visual tag for storing an representation of the authenticated accounts in the account chooser.












