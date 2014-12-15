# Data model : Tokens



CQL (see etc/bootstrap.cql for updated version):


	CREATE TABLE oauth_tokens (
		access_token uuid,
		clientid uuid,
		userid uuid,

		issued timestamp,

		scope set<text>,
		token_type text,
		validuntil timestamp,
		lastuse timestamp,

		PRIMARY KEY(access_token)
	);
	CREATE INDEX oauth_tokens_userid_idx ON oauth_tokens (userid);
	CREATE INDEX oauth_tokens_clientid_idx ON oauth_tokens (clientid);

	CREATE TABLE oauth_authorizations (
		userid uuid,
		clientid uuid,
		issued timestamp,
		scopes set<text>,
		PRIMARY KEY (userid, clientid)
	);