# Data model : Users



CQL (see etc/bootstrap.cql for updated version):

	/* Users */
	CREATE TABLE users (
		userid uuid PRIMARY KEY,
		created timestamp,

		name map<text, text>,
		email map<text, text>,
		profilephoto map<text, blob>,

		selectedsource text,

		userid_sec set<text>,
		userid_sec_seen map<text, timestamp>
	);
	CREATE TABLE userid_sec (
		userid_sec text,
		userid uuid,
		PRIMARY KEY(userid_sec)
	);


Example user object:

	{
	    "userid": "2a1676ce-4a3f-4417-ba9b-fd453ec658c1",
	    "email": {
	        "feide:uninett.no": "andreas.solberg@uninett.no"
	    },
	    "name": {
	        "feide:uninett.no": "Andreas \u00c5kre Solberg"
	    },
	    "userid_sec": [
	        "feide:andreas@uninett.no",
	        "mail:andreas.solberg@uninett.no"
	    ],
	    "selectedsource": "feide:uninett.no"
	}

For each user, one can maintain a set of indexed user info properties from different sources. In the example above, an user has name and email from a wource feide:uninett.no. The same user if merged with another authenticated account, may contain properties from other sources as well. The selectedsource property points to one of the sources represented that should be used unless others are specified in the request. HEre is an example of the same user with properties from one additional source:


	{
	    "userid": "2a1676ce-4a3f-4417-ba9b-fd453ec658c1",
	    "email": {
	        "feide:uninett.no": "andreas.solberg@uninett.no",
	        "openidp": "andreas@solweb.no"
	    },
	    "name": {
	        "feide:uninett.no": "Andreas \u00c5kre Solberg",
	        "openidp": "Andreas Solberg"
	    },
	    "userid_sec": [
	        "feide:andreas@uninett.no",
	        "mail:andreas.solberg@uninett.no"
	    ],
	    "selectedsource": "feide:uninett.no"
	}



