# Accountchooser


The Account Chooser runs at `/accountchooser`.

You can access the Account chooser directly without any specified incoming request, but then it does not makes any sense to select anything – and it will give you an error message when you attempt to do so.


The account chooser takes an incomming **AccountChooser Request**, requests the user to select which Identity Provider to use for authentication, and then redirects back to the requestor with a **AccountChooser Response**.


## Login_hints


Valid options for the OpenID Connect authentication request `login_hints` option:

* `feide|realm|uninett.no|andreas@uninett.no`
* `feide|realm|uninett.no`
* `feide|all`
* `edugain|https://someidp.example.org/`

Not yet supported, but reserved values:

* `idporten`
* `other|openidp`
* `social|twitter`
* `social|linkedin`
* `social|facebook`
* `other|feidetest`


## The AccountChooser Request



The request object have two defined parameters:

* `return` is the URL of where to return after asking the user to select provider. There is a strict limitation on what `return` URLs that are allowed.
* `clientid` is the identifier of the client asking for authentication through Feide Connect.


Here is an example of a request:

	{
	    "return": "https://auth.feideconnect.no/oauth/authorization?response_type=token&state=....",
	    "clientid": "6ed0879a-4c79-4714-94d4-6ef42ae6caf0"
	}


The request is sent as a urlencoded JSON encoded object as the query string parameter with name `request`.


## Presenting the account chooser dialog for the end user

The AccountChooser fetches data from:

* `api.feideconnect.no/clientadm/clients/{client id}` - Information about the requesting client.
* `/accountchooser/config` - configuration of the account chooser
* `/orgs` - a list of all Feide institutions
* `/accountchooser/extra` - All extra providers, such as guest users, ID-porten and social networks (from the `disco` section in file `etc/config.json`)
* `api.discojuice.org/feed/edugain` - a list of all eduGAIN providers (international identity providers). *Authentication with international providers is not yet enabled.*


The `authproviders` property from the client configuration is used to filter the allowed providers.


The AccountChooser uses HTML5 geo location and IP-based geo location to sort the available entries by an estimated physical distance to the user.


When the user selects a provider, the user is redirected back to the `return` URL with an **AccountChooser Response**.

If the selected provider is an Feide account, a hidden iframe is first loaded to remotely set the correct provider at the Feide IdP.



### When pre-selected accounts available

The browser may keep a set of preselected accounts in `document.localStorage` in an entry with property `accounts`. If such an entry was found, the user will be presented with a list of previously seleted accounts. The user may choose to discard this previously selected accounts and instead show the provider list as described below.



### Storing a new preseleted account

Adding an authenticated account to the list of previously-selected accounts is performed after authentication at the oauth grant page. There a visual tag of the authenticated user is stored for later quicker selection.



## The AccountChooser Response


An example of an AccountChooser Response:

	{
		"type": "saml",
		"id": "https://idp.feide.no",
		"subid": "ntnu.no"
	}

If selecting an Feide institution, the subid specifies which instituion in addition to the entityID in the id parameter.


The request is sent as a urlencoded JSON encoded object as the query string parameter with name `acresponse`.



## Setting up new authentication sources

Configure SimpleSALMphp auth source, or add config for one of the existing.

Test login with simplesamlphp.

If new auth source, add an entry in : `config.json` : `authTypes`.

Make sure an account map is matching the new authentication source in the config.json: `accountMaps` map.

If appropriate add a new entry in the `disco`-section in `config.json` to add an entry in the accountchooser selector dialog.

Inspect the `getVisualTag()` function in `Account.php` to implement an appropriate visual tag for storing an representation of the authenticated accounts in the account chooser.
