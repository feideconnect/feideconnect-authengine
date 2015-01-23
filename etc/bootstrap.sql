/* cqlsh 127.0.0.1 -f etc/bootstrap.sql */


USE feideconnect;


DROP TABLE IF EXISTS clients;
DROP INDEX IF EXISTS clients_owner_idx;


DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS userid_sec;
DROP TABLE IF EXISTS groups;
DROP TABLE IF EXISTS groupmember;

DROP TABLE IF EXISTS oauth_codes;

DROP TABLE IF EXISTS oauth_tokens;
DROP INDEX IF EXISTS oauth_tokens_userid_idx;
DROP INDEX IF EXISTS oauth_tokens_clientid_idx;

DROP TABLE IF EXISTS oauth_authorizations;

/* DROP INDEX IF EXISTS oauth_authorizations_userid_idx; */
DROP INDEX IF EXISTS oauth_authorizations_clientid_idx;

DROP TABLE IF EXISTS apigk;
DROP INDEX IF EXISTS apigk_owner_idx;

/* Clients */
CREATE TABLE clients (
	id uuid PRIMARY KEY,
	client_secret text,

	name text,
	descr text,
	logo blob,
	type text,

	redirect_uri list<text>,
	scopes set<text>,
	scopes_requested set<text>,
	status set<text>,

	owner uuid,
	created timestamp,
	updated timestamp
);
CREATE INDEX clients_owner_idx 				ON clients(owner);








/* Users */
CREATE TABLE users (
	userid uuid PRIMARY KEY,
	created timestamp,
	updated timestamp,

	name map<text, text>,
	email map<text, text>,
	profilephoto map<text, blob>,
	profilephotohash map<text, text>,

	selectedsource text,

	userid_sec set<text>,
	userid_sec_seen map<text, timestamp>
);
CREATE TABLE userid_sec (
	userid_sec text,
	userid uuid,
	PRIMARY KEY(userid_sec)
);




/* Ad-hoc groups */
CREATE TABLE groups (
	id text PRIMARY KEY,
	admins set<uuid>,
	created timestamp,
	description text,
	displayname text,
	members set<uuid>,
	owner uuid,
	public boolean,
	type text,
	updated timestamp
);


CREATE TABLE groupmember (
	groupid text,
	userid uuid,
	type text,
	PRIMARY KEY(groupid, userid)
);


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



CREATE TABLE oauth_codes (
	code uuid,

	clientid uuid,
	userid uuid,

	scope set<text>,
	token_type text,

	redirect_uri text,

	issued timestamp,
	validuntil timestamp,

	PRIMARY KEY(code)
);





CREATE TABLE oauth_authorizations (
	userid uuid,
	clientid uuid,
	issued timestamp,
	scopes set<text>,
	PRIMARY KEY (userid, clientid)
);

/* CREATE INDEX oauth_authorizations_userid_idx ON oauth_authorizations (userid); */
CREATE INDEX oauth_authorizations_clientid_idx ON oauth_authorizations (clientid); 



CREATE TABLE apigk (
	id text PRIMARY KEY,

	name text,
	descr text,
	logo blob,

	endpoints list<text>,

	trust text, 		-- JSON Structure
	expose text,		-- JSON Structure
	scopedef text, 		-- JSON Structure
	requireuser boolean,
	httpscertpinned text, 	-- X509 Certificate

	status set<text>,

	owner uuid,
	created timestamp,
	updated timestamp
);
CREATE INDEX apigk_owner_idx ON apigk(owner);


