# Data model : Clients



CQL (see etc/bootstrap.cql for updated version):


	/* Clients */
	CREATE TABLE feideconnect.clients (
	    id uuid PRIMARY KEY,
	    authproviders set<text>,
	    client_secret text,
	    created timestamp,
	    descr text,
	    logo blob,
	    name text,
	    organization text,
	    orgauthorization map<text, text>,
	    owner uuid,
	    redirect_uri list<text>,
	    scopes set<text>,
	    scopes_requested set<text>,
	    status set<text>,
	    type text,
	    updated timestamp
	)
	CREATE INDEX clients_authproviders_idx ON feideconnect.clients (authproviders);
	CREATE INDEX clients_organization_idx ON feideconnect.clients (organization);
	CREATE INDEX clients_owner_idx ON feideconnect.clients (owner);
	CREATE INDEX clients_scopes_idx ON feideconnect.clients (scopes);
	CREATE INDEX clients_scopes_requested_idx ON feideconnect.clients (scopes_requested);



# Authproviders

A set of tags that identify which providers that are allowed to login using this client.

	all

	social|all
	social|idporten
	social|openidp

	feide|all
	feide|go
	feide|uh
	feide|realm|uninett.no




