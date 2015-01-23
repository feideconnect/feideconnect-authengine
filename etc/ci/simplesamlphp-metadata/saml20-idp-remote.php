<?php
/**
 * SAML 2.0 remote IdP metadata for simpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote 
 */

/*
 * Guest IdP. allows users to sign up and register. Great for testing!
 */
$metadata['https://openidp.feide.no'] = array(
	'name' => array(
		'en' => 'Feide OpenIdP - guest users',
		'no' => 'Feide Gjestebrukere',
	),
	'description'          => 'Here you can login with your account on Feide RnD OpenID. If you do not already have an account on this identity provider, you can create a new one by following the create new account link and follow the instructions.',

	'SingleSignOnService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SSOService.php',
	'SingleLogoutService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',
	'certFingerprint'      => 'c9ed4dfb07caf13fc21e0fec1572047eb8a7a4cb'
);

$metadata['https://idp-test.feide.no'] = array (
  'metadata-set' => 'saml20-idp-remote',
  'entityid' => 'https://idp-test.feide.no',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp-test.feide.no/simplesaml/saml2/idp/SSOService.php',
    ),
  ),
  'SingleLogoutService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp-test.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',
    ),
  ),
  'certData' => 'MIIDkDCCAngCCQCLL8NWusxhbzANBgkqhkiG9w0BAQUFADCBiTELMAkGA1UEBhMCTk8xEjAQBgNVBAcTCVRyb25kaGVpbTETMBEGA1UEChMKVW5pbmV0dCBBUzEOMAwGA1UECxMFRkVJREUxGjAYBgNVBAMTEWlkcC10ZXN0LmZlaWRlLm5vMSUwIwYJKoZIhvcNAQkBFhZtb3JpYS1kcmlmdEB1bmluZXR0Lm5vMB4XDTE0MDQxMTEwMjkxMloXDTM0MDQxMTEwMjkxMlowgYkxCzAJBgNVBAYTAk5PMRIwEAYDVQQHEwlUcm9uZGhlaW0xEzARBgNVBAoTClVuaW5ldHQgQVMxDjAMBgNVBAsTBUZFSURFMRowGAYDVQQDExFpZHAtdGVzdC5mZWlkZS5ubzElMCMGCSqGSIb3DQEJARYWbW9yaWEtZHJpZnRAdW5pbmV0dC5ubzCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAMFL8ZFo/E42mPw4r27+HVn54E0ltmb88q1MsfGyiRlaEvVdnIo81tTUonjG4EP58wz/bQ49dSPOOoNVZ4NkhU2G4x81XErqEGFw31NBQerXp0Gcs8A93aIVGluKfCW5kDZtV+WnE0P2trwyPS5vKTVvs4MvIoDrGoWRT0y2ok9xzv5nxbICrSzsnBTC5rMrKFgKeaoappnZHt3isttfVZSP3aidmHEbl2Hw7xci554woRjx7n2kOxgOUa8A49HqV7Sr9lZDyffusOZ8QRBjongfBOgNGcrkyxXjI9xs1dD9ZKrwlORNx54kP9/rpHe+drXCV9QvR6zNrxHnxbEuWiUCAwEAATANBgkqhkiG9w0BAQUFAAOCAQEAFOsehLFueCFZqVOua+Uc81amKA+ZWHkvZWOavCsfzozZSLH4gGtwzMA1/6bh+FhURB+QdIiglH9EUDWWItaC8SCvhDo87v3bzg+LT8AE9go8mI15AraZAF6XwJC6r23UOsHcn68GLuDF+om8slizTTec6aQtA9qkhMLSwMarvk1S3m8KZEVOcghB9cpgyt3otz0JbiOmfIDoetbNeEa/x6sLXi9il/H5mtEmJUhdB6YjKaIPtMiILr1ow7DaHmJGgt+qyr09rZXOCz3okDko6WRCGCw5EdgDuYwiHz4xtixLhBvY5TKqIwgKAhNYKRxO6C4ugrS/ToCgC0j1epeK6A==',
  'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
  'OrganizationName' => 
  array (
    'en' => 'Feide',
    'no' => 'Feide',
  ),
  'OrganizationDisplayName' => 
  array (
    'en' => 'Feide - Norwegian educational institutions (test-IdP)',
    'no' => 'Feide - Norske utdanningsinstitusjoner (test-IdP)',
  ),
  'OrganizationURL' => 
  array (
    'en' => 'http://www.feide.no/introducing-feide',
    'no' => 'http://www.feide.no/',
  ),
);