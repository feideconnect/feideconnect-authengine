<?php
/*
 * The configuration of SimpleSAMLphp
 *
 */

$config = array(

    'baseurlpath' => '/simplesaml/',
    'certdir' => '/conf/cert/',
    'loggingdir' => '/var/log/simplesamlphp/',
    'datadir' => 'data/',
    'tempdir' => '/tmp/simplesaml',

    'debug' => true,

    'showerrors' => true,
    'errorreporting' => true,

    'debug.validatexml' => false,

    'auth.adminpassword' => getenv('AE_SAML_ADMINPASSWORD'),
    'admin.protectindexpage' => false,
    'admin.protectmetadata' => false,

    'secretsalt' => getenv('AE_SAML_SECRETSALT'),

    'technicalcontact_name' => getenv('AE_SAML_TECHNICALCONTACT_NAME'),
    'technicalcontact_email' => getenv('AE_SAML_TECHNICALCONTACT_EMAIL'),

    'timezone' => null,

    'logging.level' => SimpleSAML_Logger::NOTICE,
    'logging.handler' => 'errorlog',

    'logging.logfile' => 'simplesamlphp.log',

    'statistics.out' => array(),


    'enable.saml20-idp' => false,
    'enable.shib13-idp' => false,
    'enable.adfs-idp' => false,
    'enable.wsfed-sp' => false,
    'enable.authmemcookie' => false,

    'session.duration' => 8 * (60 * 60),
    'session.datastore.timeout' => (4 * 60 * 60), // 4 hours
    'session.state.timeout' => (60 * 60), // 1 hour
    'session.cookie.name' => 'SimpleSAMLSessionID',
    'session.cookie.lifetime' => 0,
    'session.cookie.path' => '/',
    'session.cookie.domain' => null,
    'session.cookie.secure' => (getenv('HTTPS_ON') !== 'off'),
    'session.disable_fallback' => false,
    'enable.http_post' => false,
    'session.phpsession.cookiename' => null,
    'session.phpsession.savepath' => null,
    'session.phpsession.httponly' => false,
    'session.authtoken.cookiename' => 'SimpleSAMLAuthToken',
    'session.rememberme.enable' => false,
    'session.rememberme.checked' => false,
    'session.rememberme.lifetime' => (14 * 86400),

    'language.available' => array(
        'en', 'no', 'nn', 'se', 'da', 'de', 'sv', 'fi', 'es', 'fr', 'it', 'nl', 'lb', 'cs',
        'sl', 'lt', 'hr', 'hu', 'pl', 'pt', 'pt-br', 'tr', 'ja', 'zh', 'zh-tw', 'ru', 'et',
        'he', 'id', 'sr', 'lv', 'ro', 'eu'
    ),
    'language.rtl' => array('ar', 'dv', 'fa', 'ur', 'he'),
    'language.default' => 'en',

    'language.parameter.name' => 'language',
    'language.parameter.setcookie' => true,

    'language.cookie.name' => 'language',
    'language.cookie.domain' => null,
    'language.cookie.path' => '/',
    'language.cookie.lifetime' => (60 * 60 * 24 * 900),

    'attributes.extradictionary' => null,

    'theme.use' => 'default',

    'idpdisco.enableremember' => true,
    'idpdisco.rememberchecked' => true,
    'idpdisco.validate' => true,
    'idpdisco.extDiscoveryStorage' => null,
    'idpdisco.layout' => 'dropdown',

    'shib13.signresponse' => true,

    'authproc.sp' => array(
        90 => 'core:LanguageAdaptor',
    ),

    'metadata.sources' => array(
        array('type' => 'flatfile', 'directory' => '/conf/simplesamlphp-metadata'),
        array('type' => 'cassandrastore:CassandraMetadataStore'),
    ),

    'store.type'                    => 'cassandrastore:CassandraStore',
    'store.cassandra.nodes' => explode(", ", getenv('FC_CASSANDRA_CONTACTPOINTS')),
    'store.cassandra.keyspace' => getenv('FC_CASSANDRA_SESSION_KEYSPACE'),
    'store.cassandra.use_ssl' => getenv('FC_CASSANDRA_SESSION_USESSL') !== 'false',
    'store.cassandra.ssl_ca' => '/etc/ssl/certs/cassandraca.pem',
    'store.cassandra.username' => getenv('CASSANDRA_USERNAME'),
    'store.cassandra.password' => getenv('CASSANDRA_PASSWORD'),

    'metastore.cassandra.keyspace' => 'metadata',
    'metastore.cassandra.nodes' => explode(", ", getenv('FC_CASSANDRA_CONTACTPOINTS')),
    'metastore.cassandra.use_ssl' =>getenv('FC_CASSANDRA_SESSION_USESSL') !== 'false',
    'metastore.cassandra.ssl_ca' => '/etc/ssl/certs/cassandraca.pem',
    'metastore.cassandra.username' => getenv('CASSANDRA_USERNAME'),
    'metastore.cassandra.password' => getenv('CASSANDRA_PASSWORD'),

    'metadata.sign.enable' => false,
    'metadata.sign.privatekey' => null,
    'metadata.sign.privatekey_pass' => null,
    'metadata.sign.certificate' => null,
    'proxy' => null,
    'trusted.url.domains' => null,

);
