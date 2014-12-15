# Data model : Clients



CQL (see etc/bootstrap.cql for updated version):


	/* Clients */
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



.
