USE feideconnect;

DROP INDEX IF EXISTS clients_scopes_idx;
DROP INDEX IF EXISTS clients_scopes_requested_idx;
CREATE INDEX clients_scopes_idx 			ON clients(scopes);
CREATE INDEX clients_scopes_requested_idx 	ON clients(scopes_requested);

DROP INDEX IF EXISTS oauth_authorizations_scopes_idx;
CREATE INDEX oauth_authorizations_scopes_idx ON oauth_authorizations (scopes);