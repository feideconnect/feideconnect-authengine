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
$metadata['https://idp.feide.no'] = array (
  'metadata-set' => 'saml20-idp-remote',
  'entityid' => 'https://idp.feide.no',
  'SingleSignOnService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp.feide.no/simplesaml/saml2/idp/SSOService.php',
    ),
  ),
  'SingleLogoutService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',
    ),
  ),
  'certData' => 'MIIDhjCCAm4CCQCZwrMQOJ3URzANBgkqhkiG9w0BAQUFADCBhDELMAkGA1UEBhMCTk8xEjAQBgNVBAcTCVRyb25kaGVpbTETMBEGA1UEChMKVW5pbmV0dCBBUzEOMAwGA1UECxMFRkVJREUxFTATBgNVBAMTDGlkcC5mZWlkZS5ubzElMCMGCSqGSIb3DQEJARYWbW9yaWEtZHJpZnRAdW5pbmV0dC5ubzAeFw0xNDA0MTEwOTM1MTBaFw0zNDA0MTEwOTM1MTBaMIGEMQswCQYDVQQGEwJOTzESMBAGA1UEBxMJVHJvbmRoZWltMRMwEQYDVQQKEwpVbmluZXR0IEFTMQ4wDAYDVQQLEwVGRUlERTEVMBMGA1UEAxMMaWRwLmZlaWRlLm5vMSUwIwYJKoZIhvcNAQkBFhZtb3JpYS1kcmlmdEB1bmluZXR0Lm5vMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAr3UtSny6D+DRQzdjWOdd+eQZxa9aKrx/v70Uo+yvnzgenLLS+MsUxbiSLkAPIbkWOO2kLdG9XSZ9sp9S5aGYMnsarxeGEXV1AS6olrpo5QJOZoQStFB0dYEXzBSJifTIsEmyXByd8mE64dkMcdzG90eBzfcFNwU6vKjln0vmoDocJrKZvUoF7d1egD+aUa9o3BneMDylcp8mkCe6XcnPlJ8QqxQ/RBmaly/Hl/zTZei8+pEu7ICRiorD2iHEDM/EhsclOrMKiRFBuZN8yB4sgknhdmAiWRyB/D4CEj74MQDQPp7Mr1B0Vxn7Y7ZeStt19HxEjzxyJGsdC9BMrn+tzwIDAQABMA0GCSqGSIb3DQEBBQUAA4IBAQBwZmzNzTgbYAuQGikkRbKInog5OCMo3GhZO82+IrtasJC6rNPrz/+8KHfIOUB83wnfEMnKKygW7ELeSnvlbKUyve6DbNXrHjMJYzjqLG3cdgIKZaFyTfWaQiY8G82qP38Lc7rtgLoh/F7lpqCdunzPfSQBraGH2IAHyP6x3tjlsGGTj/LN8sT20iHRk8IXsBsMGv5DcZ4n+zB2E5hyfxH87sNYu6gaIrpcxcv5N0AK++fvpnrhlEmT0rW7b8wgBB4BmaPfCCb4DbDgHvIBPmG8QF7SNjUGuVPUFJRPTkvhighbeuRtoNpq0W1EVXKq0ZeBO8jJ6Si9LAdFvqwy70D0',
  'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
  'OrganizationName' =>
  array (
    'en' => 'Feide',
    'no' => 'Feide',
  ),
  'OrganizationDisplayName' =>
  array (
    'en' => 'Feide - Norwegian educational institutions',
    'no' => 'Feide - Norske utdanningsinstitusjoner',
  ),
  'OrganizationURL' =>
  array (
    'en' => 'http://www.feide.no/introducing-feide',
    'no' => 'http://www.feide.no/',
  ),
);
$metadata['idporten.difi.no-v3'] = array (
  'entityid' => 'idporten.difi.no-v3',
  'encryption.blacklisted-algorithms' => array(),
  'NameIDFormats' =>
  array (
    0 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
    1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
  ),
  'contacts' =>
  array (
  ),
  'metadata-set' => 'saml20-idp-remote',
  'sign.authnrequest' => true,
  'SingleSignOnService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idporten.difi.no/opensso/SSORedirect/metaAlias/norge.no/idp3',
    ),
    1 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://idporten.difi.no/opensso/SSOSoap/metaAlias/norge.no/idp3',
    ),
  ),
  'SingleLogoutService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idporten.difi.no/opensso/IDPSloRedirect/metaAlias/norge.no/idp3',
      'ResponseLocation' => 'https://idporten.difi.no/opensso/IDPSloRedirect/metaAlias/norge.no/idp3',
    ),
  ),
  'ArtifactResolutionService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://idporten.difi.no/opensso/ArtifactResolver/metaAlias/norge.no/idp3',
      'index' => 0,
      'isDefault' => true,
    ),
  ),
  'keys' =>
  array (
    0 =>
    array (
      'encryption' => false,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => '
MIIFCTCCA/GgAwIBAgILCCtPHJR3OA6DBLQwDQYJKoZIhvcNAQELBQAwSzELMAkGA1UEBhMCTk8x
HTAbBgNVBAoMFEJ1eXBhc3MgQVMtOTgzMTYzMzI3MR0wGwYDVQQDDBRCdXlwYXNzIENsYXNzIDMg
Q0EgMzAeFw0xNTExMjMxMzI0NDlaFw0xOTAzMjAyMjU5MDBaMIGcMQswCQYDVQQGEwJOTzEsMCoG
A1UECgwjRElSRUtUT1JBVEVUIEZPUiBGT1JWQUxUTklORyBPRyBJS1QxHTAbBgNVBAsMFElELXBv
cnRlbiBwcm9kdWtzam9uMSwwKgYDVQQDDCNESVJFS1RPUkFURVQgRk9SIEZPUlZBTFROSU5HIE9H
IElLVDESMBAGA1UEBRMJOTkxODI1ODI3MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA
1/WC90fKN2BoHUd4GxDFhRqztGpfGSQ/+QbhxtY58OgpeHSamAEtJxUg6Xz99ychsZ7xegsnjwhg
Yzt0yAPmYQxMNHHEpBn59iyw7VDMc4eG3ucUL8OnyqBLDXKTW4Aj6q+06auaB57ZXMgWkj/eChSR
4QOwVztkD5KnBKAooAzMy5IP2BQFmHpSrcX4GrPFQhfmvThtyQ+5JCuIdRrfnyEHrhWCePkRjUAn
B+rDfcxUrDlYD5ZFD8QCu7yVEFtrO4G5k54aqkPDT5vRBIIEVzqc40GWpy3nLWthJwVWeBbvOZ/G
oTqlMfbL4A3qAaOxL0YA9ufglqrrHm+MzLzYhwIDAQABo4IBmjCCAZYwCQYDVR0TBAIwADAfBgNV
HSMEGDAWgBTMw/gHt5xtek71pysdBfmzRxyR0TAdBgNVHQ4EFgQUfFG3zT6/rKpf4F12oBfjErlM
P6kwDgYDVR0PAQH/BAQDAgSwMBUGA1UdIAQOMAwwCgYIYIRCARoBAwIwgaUGA1UdHwSBnTCBmjAv
oC2gK4YpaHR0cDovL2NybC5idXlwYXNzLm5vL2NybC9CUENsYXNzM0NBMy5jcmwwZ6BloGOGYWxk
YXA6Ly9sZGFwLmJ1eXBhc3Mubm8vZGM9QnV5cGFzcyxkYz1OTyxDTj1CdXlwYXNzJTIwQ2xhc3Ml
MjAzJTIwQ0ElMjAzP2NlcnRpZmljYXRlUmV2b2NhdGlvbkxpc3QwegYIKwYBBQUHAQEEbjBsMDMG
CCsGAQUFBzABhidodHRwOi8vb2NzcC5idXlwYXNzLm5vL29jc3AvQlBDbGFzczNDQTMwNQYIKwYB
BQUHMAKGKWh0dHA6Ly9jcnQuYnV5cGFzcy5uby9jcnQvQlBDbGFzczNDQTMuY2VyMA0GCSqGSIb3
DQEBCwUAA4IBAQC5+RXpvw5Rgx+t3zolor51s1Q0rhIABVpxMGbeRAqzPyZh/8zKzxeCM/ZvKO+s
GNFIoJg8gH+HGtNZEYt+0g8SjJC0KJW0NCePpPvS4xzP9rXVW09N1CuEotc4odaBK3Ud2eb9BsV5
gX8uKcwGiIGgtVDe2zWi6tonzvzzZYqoUwR/nKBNEHlQAr4qoGzr2ataN38ntUdbfTbh9zEo1D56
Qy6iRzqo55ErwUSd1luTqObnc9XWiJlg6Es11uFVReezF/Z+DXSTc46coGELx/U3yoCfNh5fHYMV
7X0RlHChQ9HySh9TAqu4AW7wH1ZXhjbEkKDLuEoIMYRNISBtxMbs
                    ',
    ),
    1 =>
    array (
      'encryption' => true,
      'signing' => false,
      'type' => 'X509Certificate',
      'X509Certificate' => '
MIIFCTCCA/GgAwIBAgILCCtPHJR3OA6DBLQwDQYJKoZIhvcNAQELBQAwSzELMAkGA1UEBhMCTk8x
HTAbBgNVBAoMFEJ1eXBhc3MgQVMtOTgzMTYzMzI3MR0wGwYDVQQDDBRCdXlwYXNzIENsYXNzIDMg
Q0EgMzAeFw0xNTExMjMxMzI0NDlaFw0xOTAzMjAyMjU5MDBaMIGcMQswCQYDVQQGEwJOTzEsMCoG
A1UECgwjRElSRUtUT1JBVEVUIEZPUiBGT1JWQUxUTklORyBPRyBJS1QxHTAbBgNVBAsMFElELXBv
cnRlbiBwcm9kdWtzam9uMSwwKgYDVQQDDCNESVJFS1RPUkFURVQgRk9SIEZPUlZBTFROSU5HIE9H
IElLVDESMBAGA1UEBRMJOTkxODI1ODI3MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA
1/WC90fKN2BoHUd4GxDFhRqztGpfGSQ/+QbhxtY58OgpeHSamAEtJxUg6Xz99ychsZ7xegsnjwhg
Yzt0yAPmYQxMNHHEpBn59iyw7VDMc4eG3ucUL8OnyqBLDXKTW4Aj6q+06auaB57ZXMgWkj/eChSR
4QOwVztkD5KnBKAooAzMy5IP2BQFmHpSrcX4GrPFQhfmvThtyQ+5JCuIdRrfnyEHrhWCePkRjUAn
B+rDfcxUrDlYD5ZFD8QCu7yVEFtrO4G5k54aqkPDT5vRBIIEVzqc40GWpy3nLWthJwVWeBbvOZ/G
oTqlMfbL4A3qAaOxL0YA9ufglqrrHm+MzLzYhwIDAQABo4IBmjCCAZYwCQYDVR0TBAIwADAfBgNV
HSMEGDAWgBTMw/gHt5xtek71pysdBfmzRxyR0TAdBgNVHQ4EFgQUfFG3zT6/rKpf4F12oBfjErlM
P6kwDgYDVR0PAQH/BAQDAgSwMBUGA1UdIAQOMAwwCgYIYIRCARoBAwIwgaUGA1UdHwSBnTCBmjAv
oC2gK4YpaHR0cDovL2NybC5idXlwYXNzLm5vL2NybC9CUENsYXNzM0NBMy5jcmwwZ6BloGOGYWxk
YXA6Ly9sZGFwLmJ1eXBhc3Mubm8vZGM9QnV5cGFzcyxkYz1OTyxDTj1CdXlwYXNzJTIwQ2xhc3Ml
MjAzJTIwQ0ElMjAzP2NlcnRpZmljYXRlUmV2b2NhdGlvbkxpc3QwegYIKwYBBQUHAQEEbjBsMDMG
CCsGAQUFBzABhidodHRwOi8vb2NzcC5idXlwYXNzLm5vL29jc3AvQlBDbGFzczNDQTMwNQYIKwYB
BQUHMAKGKWh0dHA6Ly9jcnQuYnV5cGFzcy5uby9jcnQvQlBDbGFzczNDQTMuY2VyMA0GCSqGSIb3
DQEBCwUAA4IBAQC5+RXpvw5Rgx+t3zolor51s1Q0rhIABVpxMGbeRAqzPyZh/8zKzxeCM/ZvKO+s
GNFIoJg8gH+HGtNZEYt+0g8SjJC0KJW0NCePpPvS4xzP9rXVW09N1CuEotc4odaBK3Ud2eb9BsV5
gX8uKcwGiIGgtVDe2zWi6tonzvzzZYqoUwR/nKBNEHlQAr4qoGzr2ataN38ntUdbfTbh9zEo1D56
Qy6iRzqo55ErwUSd1luTqObnc9XWiJlg6Es11uFVReezF/Z+DXSTc46coGELx/U3yoCfNh5fHYMV
7X0RlHChQ9HySh9TAqu4AW7wH1ZXhjbEkKDLuEoIMYRNISBtxMbs
                    ',
    ),
  ),
);
