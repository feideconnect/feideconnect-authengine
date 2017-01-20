<?php


namespace tests;

use FeideConnect\Config;

class EdugainMetadata extends \PHPUnit_Framework_TestCase {


    public static $mreg = 'https://aai.asnet.am';
    public static $mentityid = 'https://shibboleth-idp.uni-goettingen.de/uni/shibboleth';
    public static $m = array (
  'entityid' => 'https://idem-idp.imtlucca.it/idp/shibboleth',
  'description' =>
  array (
    'it' => 'IMT Institute for Advanced Studies Lucca',
    'en' => 'IMT Institute for Advanced Studies Lucca',
  ),
  'OrganizationName' =>
  array (
    'it' => 'IMT Institute for Advanced Studies Lucca',
    'en' => 'IMT Institute for Advanced Studies Lucca',
  ),
  'name' =>
  array (
    'en' => 'IMT Lucca - Identity Provider',
    'it' => 'IMT Lucca - Identity Provider',
  ),
  'OrganizationDisplayName' =>
  array (
    'it' => 'IMT Institute for Advanced Studies Lucca',
    'en' => 'IMT Institute for Advanced Studies Lucca',
  ),
  'url' =>
  array (
    'it' => 'http://www.imtlucca.it',
    'en' => 'http://www.imtlucca.it',
  ),
  'OrganizationURL' =>
  array (
    'it' => 'http://www.imtlucca.it',
    'en' => 'http://www.imtlucca.it',
  ),
  'contacts' =>
  array (
    0 =>
    array (
      'contactType' => 'technical',
      'emailAddress' =>
      array (
        0 => 'mailto:webmaster@imtlucca.it',
      ),
    ),
  ),
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
      'Location' => 'https://idem-idp.imtlucca.it/idp/profile/Shibboleth/SSO',
    ),
    1 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://idem-idp.imtlucca.it/idp/profile/SAML2/POST/SSO',
    ),
    2 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
      'Location' => 'https://idem-idp.imtlucca.it/idp/profile/SAML2/POST-SimpleSign/SSO',
    ),
    3 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idem-idp.imtlucca.it/idp/profile/SAML2/Redirect/SSO',
    ),
  ),
  'SingleLogoutService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idem-idp.imtlucca.it/idp/profile/SAML2/Redirect/SLO',
    ),
    1 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://idem-idp.imtlucca.it/idp/profile/SAML2/POST/SLO',
    ),
    2 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://idem-idp.imtlucca.it:8443/idp/profile/SAML2/SOAP/SLO',
    ),
  ),
  'ArtifactResolutionService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding',
      'Location' => 'https://idem-idp.imtlucca.it:8443/idp/profile/SAML1/SOAP/ArtifactResolution',
      'index' => 1,
    ),
    1 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://idem-idp.imtlucca.it:8443/idp/profile/SAML2/SOAP/ArtifactResolution',
      'index' => 2,
    ),
  ),
  'NameIDFormats' =>
  array (
    0 => 'urn:mace:shibboleth:1.0:nameIdentifier',
    1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
  ),
  'keys' =>
  array (
    0 =>
    array (
      'encryption' => true,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => '
MIIDPDCCAiSgAwIBAgIVAIF5g404xBT9pz/2nuMWOQA6KHj6MA0GCSqGSIb3DQEB
BQUAMB8xHTAbBgNVBAMTFGlkZW0taWRwLmltdGx1Y2NhLml0MB4XDTEzMDkxMDE2
MTEyNVoXDTMzMDkxMDE2MTEyNVowHzEdMBsGA1UEAxMUaWRlbS1pZHAuaW10bHVj
Y2EuaXQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCecL5DE82gqAf8
cuxD6rg+49Rt5l/hie4RKn2YpPYoxLxrr0TuE2PV2D/04dHJmT4g8HClV80HwHHx
cT6hItjkLtBN2JeogwefBnk4tQdaEluvd/r3RNZF9Re5NKcrHNMPJGd8RqZPwL/Z
MpPIHTJzO1WNpPO+H4w/tYVJdhlE1vn/0d3lCYBdX6FT4i10XxWx7er9sdEj9ndX
8LhHuA2O2z197jHEEufG6XAThBV8XACOaqNkw+nVbk2GS4EltIOZRqdeC0qvATs8
AiWGpK5K7bgdIfItscYTkDpbOeZwFUkdxpOJ7ujqMgjZCbJ0WfPXmWEFGuixYXki
LXM7vQQLAgMBAAGjbzBtMEwGA1UdEQRFMEOCFGlkZW0taWRwLmltdGx1Y2NhLml0
hitodHRwczovL2lkZW0taWRwLmltdGx1Y2NhLml0L2lkcC9zaGliYm9sZXRoMB0G
A1UdDgQWBBRw6DX3JFHnfUlXHQXxcYUaIkKDmTANBgkqhkiG9w0BAQUFAAOCAQEA
EzNzUHWImZwxpf8bu1BudPjcmtyQqHyPThTrpAd6UWm+MiwCPRdEYEfgWSnvGh0B
STzyTtF/0PTMXNt+ocPryWFlpigO6d4H/QBWNtlPqWmLfLFsrTm1Cvy6ihmSsqVk
nFN3qHAfJd0Xl05zk0oxDg6u8CXWzlFJcKxm2P8ujJYkJVHkt3saNinZop7+oazU
YM5NpKde4RG0tQJvDuzN3xFLCtTUZe9EnOV0tniikwk4ZGDFy7F14POhmYvq+V+W
PMrXoG08C30q+20nepBYJz2JoY9nQlmGor4yK6Ft8RvgmYq1BuwEkAHYnWf4B26l
BgRrAVj5NpDfLOfRZq+LOg==
',
    ),
  ),
  'scope' =>
  array (
    0 => 'imtlucca.it',
  ),
  'RegistrationInfo' =>
  array (
    'registrationAuthority' => 'http://www.idem.garr.it/',
  ),
  'UIInfo' =>
  array (
    'DisplayName' =>
    array (
      'en' => 'IMT Lucca - Identity Provider',
      'it' => 'IMT Lucca - Identity Provider',
    ),
    'Description' =>
    array (
      'en' => 'Identity provider for IMT Lucca users. This IdP permits to access services presents in the IDEM GARR Federation',
      'it' => 'Identity provider per gli utenti di IMT Lucca. Questo IdP permette di accedere a servizi disponibili nella federazione GARR IDEM',
    ),
    'InformationURL' =>
    array (
      'en' => 'http://www.imtlucca.it/campus/idem.php',
      'it' => 'http://www.imtlucca.it/campus/idem.php',
    ),
    'PrivacyStatementURL' =>
    array (
      'en' => 'http://www.imtlucca.it/campus/idem.php',
      'it' => 'http://www.imtlucca.it/campus/idem.php',
    ),
    'Logo' =>
    array (
      0 =>
      array (
        'url' => 'https://www.imtlucca.it/_img/logo/new/logo_imt_16x16_square_blueback.png',
        'height' => 16,
        'width' => 16,
        'lang' => 'en',
      ),
      1 =>
      array (
        'url' => 'https://www.imtlucca.it/_img/logo/new/logo_imt_16x16_square_blueback.png',
        'height' => 16,
        'width' => 16,
        'lang' => 'it',
      ),
      2 =>
      array (
        'url' => 'https://www.imtlucca.it/_img/logo/new/logo_imt_80x80_square_blueback.png',
        'height' => 60,
        'width' => 80,
        'lang' => 'en',
      ),
      3 =>
      array (
        'url' => 'https://www.imtlucca.it/_img/logo/new/logo_imt_80x80_square_blueback.png',
        'height' => 60,
        'width' => 80,
        'lang' => 'it',
      ),
    ),
  ),
);
    public static $mui = array (
  'description' =>
  array (
    'en' => 'Academic Scientific Network of Armenia',
  ),
  'OrganizationName' =>
  array (
    'en' => 'Academic Scientific Network of Armenia',
  ),
  'name' =>
  array (
    'en' => 'Academic Scientific Network of Armenia',
  ),
  'OrganizationDisplayName' =>
  array (
    'en' => 'ASNET-AM',
  ),
  'url' =>
  array (
    'en' => 'http://www.asnet.am/',
  ),
  'OrganizationURL' =>
  array (
    'en' => 'http://www.asnet.am/',
  ),
  'scope' =>
  array (
    0 => 'asnet.am',
  ),
  'RegistrationInfo' =>
  array (
    'registrationAuthority' => 'https://aai.asnet.am',
  ),
  'UIInfo' =>
  array (
    'DisplayName' =>
    array (
      'en' => 'Academic Scientific Network of Armenia',
    ),
    'Description' =>
    array (
    ),
    'InformationURL' =>
    array (
      'en' => 'https://afire.asnet.am/',
    ),
    'PrivacyStatementURL' =>
    array (
      'en' => 'https://afire.asnet.am/index.php/identity-federation-policy',
    ),
  ),
);

    protected $db;
    public function __construct() {

    }


    public function testConfig() {

        $this->assertTrue(Config::getValue('test', true) === true, 'Config picking not existing prop should return default');
        $this->assertTrue(Config::getValue('storage.type') === 'cassandra', 'Config read storage.type === cassandra');
        $this->assertTrue(Config::getValue('test.foo.li', 3) === 3, 'Config read fall back to default param');

    }
}
