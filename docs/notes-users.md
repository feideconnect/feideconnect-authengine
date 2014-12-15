# Håndtering av bruker fra autentisering


Autentisering med Feide.


Fra simpleSAMLphp får vi brukerattributter ala:

	{
        "eduPersonAffiliation": [
            "employee",
            "member"
        ],
        "eduPersonPrincipalName": [
            "andreas@uninett.no"
        ],
        "eduPersonNIN": [
        	"10108012345"
        ]
        "mail": [
            "andreas.solberg@uninett.no"
        ],
        "eduPersonEntitlement": [
            "urn:mace:rediris.es:entitlement:wiki:tfemc2",
            "urn:mace:dir:entitlement:common-lib-terms",
            "urn:mace:terena.org:tcs:personal-user",
            "urn:mace:terena.org:tcs:personal-admin",
            "urn:mace:terena.org:tcs:escience-user"
        ],
        "eduPersonOrgUnitDN": [
            "ou=ASM,ou=UNINETT,ou=organization,dc=uninett,dc=no"
        ],
        "displayName": [
            "Andreas \u00c5kre Solberg"
        ],
        "eduPersonOrgDN:o": [
            "UNINETT"
        ],
        "eduPersonOrgUnitDN:ou": [
            "Avdeling for System og Mellomvare|Department for Systems and Middleware|ASM"
        ],
        "source": "feide:uninett.no",
        "idp": "https:\/\/idp-test.feide.no"
    }


Basert på hvilke idp som er kilden, kanskje kombiert med authsource, så skal vi ha en *mapper* som kan generere et *account* object.

Eksempel på accountmap

	"https://idp-test.feide.no": {
		"userid": {
			"feide":  "eduPersonPrincipalName",
			"nin": "eduPersonNIN",
			"mail": "mail"
		}
	}





	account = authentiator.getAccount()


	{
		"userid": [
			"feide:andreas@uninett.no",
			"mail:andreas.solberg@uninett.no",
			"nin:10108012345"
		],
		"name": "Andreas Åkre Solberg",
		"mail": "andreas.solberg@uninett.no"
		"profilephoto": "...",
		"source": "feide:uninett.no"
	}


Vi forsøker å hente et eksiterende brukerobjekt, eller generere et nytt:

	usermapper.getUser(account);

Vi forsøker å finne et eksisterende object med disse sekundærnøklene: 

	[
		"feide:andreas@uninett.no",
		"mail:andreas.solberg@uninett.no",
		"nin:10108012345"
	]


Hvis vi ikke finner, så lager vi et nytt objekt:


	{
		"userid": "4de7c030-5296-4213-b552-dcaf940c72e7"
		"userid-sec": [
			"feide:andreas@uninett.no",
			"mail:andreas.solberg@uninett.no",
			"nin:10108012345"
		],
		"userid-sec-seen": [
			"feide:andreas@uninett.no": "2013-9-22 12:01",
			"mail:andreas.solberg@uninett.no": "2013-9-22 12:01",
			"nin:10108012345": "2013-9-22 12:01"
		],
		"selectedSource": "feide:uninett.no",
		"name": {
			"feide:uninett.no": "Andreas Åkre Solberg"
		},
		"mail": {
			"feide:uninett.no": "andreas.solberg@uninett.no"
		},
		"profilephoto": {
			"feide:uninett.no": "..."
		}
	}

Hvis vi finner et eksiterende objekt, slik som dette:


	{
		"userid": "4de7c030-5296-4213-b552-dcaf940c72e7"
		"userid-sec": [
			"feide:andrs@ntnu.no",
			"nin:10108012345"
		],
		"userid-sec-seen": [
			"feide:andrs@ntnu.no": "2013-9-22 12:01",
			"nin:10108012345": "2013-9-22 12:01"
		],
		"selectedSource": "feide:ntnu.no",
		"name": {
			"feide:ntnu.no": "Andreas Åkre Solberg"
		},
		"mail": {
			"feide:ntnu.no": "andreas@ntnu.no"
		},
		"profilephoto": {
			"feide:ntnu.no": "..."
		}
	}

Kan vi oppdatere det ved å legge til:

	{
		"userid": "4de7c030-5296-4213-b552-dcaf940c72e7"
		"userid-sec": [
			"feide:andrs@ntnu.no",
			"feide:andreas@uninett.no",
			"nin:10108012345"
		],
		"userid-sec-seen": [
			"feide:andrs@ntnu.no": "2013-9-22 12:01",
			"nin:10108012345": "2013-9-22 12:01",
			"feide:andreas@uninett.no": "2014-12-14 08:45"
		],
		"selectedSource": "feide:ntnu.no",
		"name": {
			"feide:ntnu.no": "Andreas Åkre Solberg",
			"feide:uninett.no": "Andreas Solberg"
		},
		"mail": {
			"feide:ntnu.no": "andreas@ntnu.no",
			"feide:uninett.no": "andreas@uninett.no"
		},
		"profilephoto": {
			"feide:ntnu.no": "...",
			"feide:uninett.no": "..."
		},
		"profilephoto-srchash": {
			"feide:ntnu.no": "...",
			"feide:uninett.no": "..."
		}
	}

Sjekk og oppdater

* navn,
* epost,
* profilbilde (med srchash)

Så sjekk og oppdater *userid-sec-seen* hvis over et døgn siden sist.





















