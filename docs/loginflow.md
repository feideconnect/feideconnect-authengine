# Login flow

This document describes the login flow through Connect auth engine.


## OAuth Authorization endpoint (frist entry)

Parses the authorization request. A redirect the client initiated where the user ends up at the Connect platform.

This request is preprocessed to see if it needs to invoke OpenID Connect processing, or just OAuth.

First the user is authenticated. If the user has not selected already how to authenticate, the user is redirected to the account chooser to select a provider:


## User is redirected to Accountchooser

Incomming request looks like this:

	https://auth.dev.feideconnect.no/accountchooser?request=%7B%22return%22%3A%22https%3A%5C%2F%5C%2Fauth.dev.feideconnect.no%5C%2Foauth%5C%2Fauthorization%3Fresponse_type%3Dtoken%26client_id%3D441c15d4-a164-4f75-8a47-5383b99629e3%22%2C%22clientid%22%3A%22441c15d4-a164-4f75-8a47-5383b99629e3%22%7D

Where the query string paramter is `request`.

Request is a JSON encoded parameter that looks like this:

	{
		"return":"https:\/\/auth.dev.feideconnect.no\/oauth\/authorization?response_type=token&client_id=441c15d4-a164-4f75-8a47-5383b99629e3",
		"clientid":"441c15d4-a164-4f75-8a47-5383b99629e3"
	}


## Accountchooser

Når man skal sende brukeren til *auth provider*, vet man ikke på forhånd om brukeren har en aktiv SSO sesjon.

Når man skal sende brukeren til Feide, så vil accountchooser sette forhåndsvalgt provider via cookie i en hidden iframe. 
Deretter sender man brukeren til Feide, uten at man vet om man har en aktiv autentisert sesjon.


## User is redirected back from accountchooser with a response to the Oauth authorization endpoint





The user is sent back to the `return` parameter of the accountchooser request parameter, with a JSON encoded `acresponse` that looks like this:


	{
		"type": "saml",
		"id": "https://idp-test.feide.no",
		"subid": "feide.no"
	}




## OAuth Authorization endpoint (with account chooser response)





## External authentication

Send with an SAML 2.0 authentication reqeust.

And receives a SAML 2.0 authentication response afterwards.



## OAuth Authorization endpoint, after authentication, Consent Display


Når man returnerer fra Feide, må man sjekke om brukeren man nå er autentisert som er den man ønsket å autentisere seg som. 
Dersom den ikke er det spør man brukeren om man ønsker å aktivt logge ut fra Feide og så inn igjen som den aktuelle brukeren.

[ ] Show dialog to ask what to do.


## OAuth Response back to Service Provider



