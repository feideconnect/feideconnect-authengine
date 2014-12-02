/* cqlsh 158.38.213.74 -f etc/bootstrap.sql */

CREATE KEYSPACE 
	IF NOT EXISTS 
	andreastest
	WITH REPLICATION = { 'class' : 'SimpleStrategy', 'replication_factor' : 3 };

USE andreastest;


DROP TABLE IF EXISTS clients;
DROP INDEX IF EXISTS clients_owner_idx;
DROP INDEX IF EXISTS clients_scopes_idx;
DROP INDEX IF EXISTS clients_scopes_requested_idx;

DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS userid_sec;
DROP TABLE IF EXISTS groups;
DROP TABLE IF EXISTS groupmember;

DROP TABLE IF EXISTS oauth_tokens;
DROP INDEX IF EXISTS oauth_tokens_userid_idx;
DROP INDEX IF EXISTS oauth_tokens_clientid_idx;

DROP TABLE IF EXISTS oauth_authorizations;
DROP INDEX IF EXISTS oauth_authorizations_scopes_idx;
/* DROP INDEX IF EXISTS oauth_authorizations_userid_idx; */
DROP INDEX IF EXISTS oauth_authorizations_clientid_idx;


CREATE TABLE clients (
	id uuid PRIMARY KEY,
	client_secret text,
	created timestamp,
	descr text,
	name text,
	owner uuid,
	redirect_uri list<text>,
	scopes set<text>,
	scopes_requested set<text>,
	status set<text>,
	type text,
	updated timestamp
);
CREATE INDEX clients_owner_idx 				ON clients(owner);
CREATE INDEX clients_scopes_idx 			ON clients(scopes);
CREATE INDEX clients_scopes_requested_idx 	ON clients(scopes_requested);


CREATE TABLE users (
	userid uuid PRIMARY KEY,
	created timestamp,
	email text,
	name text,
	profilephoto blob,

	userid_sec set<text>
);
CREATE TABLE userid_sec (
	userid_sec text,
	userid uuid,
	PRIMARY KEY(userid_sec)
);



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

CREATE TABLE oauth_authorizations (
	userid uuid,
	clientid uuid,
	issued timestamp,
	scopes set<text>,
	PRIMARY KEY (userid, clientid)
);
CREATE INDEX oauth_authorizations_scopes_idx ON oauth_authorizations (scopes);
/* CREATE INDEX oauth_authorizations_userid_idx ON oauth_authorizations (userid); */
CREATE INDEX oauth_authorizations_clientid_idx ON oauth_authorizations (clientid); 


