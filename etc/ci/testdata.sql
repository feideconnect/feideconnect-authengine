-- Test client running on http://andreas.uninettlabs.no/fclient/

-- INSERT INTO "clients" (id, name, owner, redirect_uri, scopes) VALUES
-- (3de141c0-1fd0-4b6e-92da-ea542f63a999, 'Andreas test', 76a7a061-3c55-430d-8ee0-6f82ec42501f, ['http://andreas.uninettlabs.no/fclient/'], {'groups', 'userinfo'} ); 

-- INSERT INTO "clients" (id, client_secret, name, owner, redirect_uri, scopes) VALUES
-- (6751edcc-0cad-4173-ac73-13bf8aa8b487, '6419b557-60d0-4f22-8bf9-fcf1cbb3c11f', 'PHP Demo ', 76a7a061-3c55-430d-8ee0-6f82ec42501f, ['http://andreas.uninettlabs.no/feide-connect-php-demo/'], {'groups', 'userinfo'} ); 

USE feideconnect;


INSERT INTO "clients" (id, client_secret, name, owner, redirect_uri, scopes) VALUES
(34e87b41-ad1b-47ec-8d67-f6fb0a7b96f8, 'ba1ea23f-04ff-47c2-86b8-448817c2021c', 'Travis CI ', 76a7a061-3c55-430d-8ee0-6f82ec42501f, ['http://127.0.0.1/ci/callback'], {'groups', 'userinfo'} ); 


