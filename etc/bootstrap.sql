
DROP TABLE IF EXISTS clients;
DROP INDEX IF EXISTS clients_owner_idx;
DROP INDEX IF EXISTS clients_scopes_idx;
DROP INDEX IF EXISTS clients_scopes_requested_idx;
DROP INDEX IF EXISTS clients_organization_idx;
DROP TABLE IF EXISTS clients_counters;


DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS userid_sec;
DROP INDEX IF EXISTS user_userid_sec_idx;

DROP TABLE IF EXISTS groups;
DROP INDEX IF EXISTS groups_owner_idx;
DROP INDEX IF EXISTS groups_public_idx;



DROP TABLE IF EXISTS oauth_codes;

DROP TABLE IF EXISTS oauth_tokens;
DROP INDEX IF EXISTS oauth_tokens_userid_idx;
DROP INDEX IF EXISTS oauth_tokens_clientid_idx;

DROP TABLE IF EXISTS oauth_authorizations;

/* DROP INDEX IF EXISTS oauth_authorizations_userid_idx; */
DROP INDEX IF EXISTS oauth_authorizations_clientid_idx;
DROP INDEX IF EXISTS oauth_authorizations_scopes_idx;


DROP TABLE IF EXISTS apigk;
DROP INDEX IF EXISTS apigk_owner_idx;
DROP INDEX IF EXISTS apigk_organization_idx;

DROP TABLE IF EXISTS group_members;
DROP INDEX IF EXISTS group_members_groupid_idx;
DROP INDEX IF EXISTS group_members_status_idx;
DROP INDEX IF EXISTS group_members_type_idx;

DROP TABLE IF EXISTS groupmember;

DROP TABLE IF EXISTS grep_codes;
DROP INDEX IF EXISTS grep_codes_code_idx;

DROP TABLE IF EXISTS mandatory_clients;

DROP TABLE IF EXISTS organizations;
DROP INDEX IF EXISTS organizations_kindid_idx;
DROP INDEX IF EXISTS organizations_realm_idx;
DROP INDEX IF EXISTS organizations_organization_number_idx;

DROP TABLE IF EXISTS roles;
DROP INDEX IF EXISTS roles_orgid_idx;

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
	organization text,
	created timestamp,
	updated timestamp
);
CREATE INDEX clients_owner_idx 				ON clients(owner);
CREATE INDEX clients_scopes_idx 			ON clients(scopes);
CREATE INDEX clients_scopes_requested_idx 	ON clients(scopes_requested);
CREATE INDEX clients_organization_idx       ON clients(organization);

CREATE TABLE clients_counters (
	id uuid PRIMARY KEY,
	count_tokens counter,
	count_users counter
);




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

	aboveagelimit boolean,
	usageterms boolean,

	userid_sec set<text>,
	userid_sec_seen map<text, timestamp>
);
CREATE TABLE userid_sec (
	userid_sec text,
	userid uuid,
	PRIMARY KEY(userid_sec)
);
CREATE INDEX user_userid_sec_idx ON users(userid_sec);




/* Ad-hoc groups */
CREATE TABLE groups (
	id uuid PRIMARY KEY,

	name text,
	descr text,
	logo blob,
	public boolean,

	owner uuid,
	created timestamp,
	updated timestamp
);

CREATE INDEX groups_owner_idx ON groups(owner);
CREATE INDEX groups_public_idx ON groups(public);


CREATE TABLE group_members (
    userid uuid,
    groupid uuid,
    type text,
    status text,
    added timestamp,
    PRIMARY KEY (userid, groupid)
);
CREATE INDEX group_members_groupid_idx ON group_members(groupid);
CREATE INDEX group_members_status_idx ON group_members(status);
CREATE INDEX group_members_type_idx ON group_members(type);


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
	idtoken text,

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
CREATE INDEX oauth_authorizations_scopes_idx ON oauth_authorizations (scopes);




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
	organization text,
	created timestamp,
	updated timestamp
);
CREATE INDEX apigk_owner_idx ON apigk(owner);
CREATE INDEX apigk_organization_idx ON apigk(organization);

CREATE TABLE grep_codes (
	id text PRIMARY KEY,
	title map<text, text>,
	code text,
	last_changed timestamp
);
CREATE INDEX grep_codes_code_idx ON grep_codes(code);

CREATE TABLE mandatory_clients (
    realm text,
    clientid uuid,
    PRIMARY KEY (realm, clientid)
);


/* Organizations */
CREATE TABLE organizations(
       id  text PRIMARY KEY,
       fs_groups boolean,
       kindid int,
       realm text,
       type set<text>,
       organization_number text,
       name map<text, text>,
       logo blob,
       logo_updated timestamp
);
CREATE INDEX organizations_kindid_idx ON organizations(kindid);
CREATE INDEX organizations_realm_idx ON organizations(realm);
CREATE INDEX organizations_organization_number_idx ON organizations(organization_number);


CREATE TABLE roles(
       feideid text,
       orgid text,
       role set<text>,
       PRIMARY KEY(feideid, orgid)
);
CREATE INDEX roles_orgid_idx ON roles(orgid);
