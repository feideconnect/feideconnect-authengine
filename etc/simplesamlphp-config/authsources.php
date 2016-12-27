<?php

$config = array(

    // This is a authentication source which handles admin authentication.
    'admin' => array(
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ),

    // An authentication source which can authenticate against both SAML 2.0
    // and Shibboleth 1.3 IdPs.
    'default-sp' => array(
        'saml:SP',

        // The entity ID of this SP.
        // Can be NULL/unset, in which case an entity ID is generated based on the metadata URL.
        'entityID' => null,

        // The entity ID of the IdP this should SP should contact.
        // Can be NULL/unset, in which case the user will be shown a list of available IdPs.
        'idp' => null,

        // The URL to the discovery service.
        // Can be NULL/unset, in which case a builtin discovery service will be used.
        'discoURL' => '/disco',

        'privatekey' => 'saml.pem',
        'certificate' => 'saml.crt',

        'sign.authnrequest' => true,
        'redirect.sign' => true,
        'metadata.sign.enable' => true,
        'WantAssertionsSigned' => true

    ),

    'facebook' => array(
        'authfacebook:Facebook',
        // Register your Facebook application on http://www.facebook.com/developers
        // App ID or API key (requests with App ID should be faster; https://github.com/facebook/php-sdk/issues/214)
        'api_key' => getenv('AE_AS_FACEBOOK_KEY'),
        'secret' => getenv('AE_AS_FACEBOOK_SECRET'),
        // which additional data permissions to request from user
        // see http://developers.facebook.com/docs/authentication/permissions/ for the full list
        'req_perms' => 'public_profile',
    ),
    

    
    // LinkedIn OAuth Authentication API.
    // Register your application to get an API key here:
    //  https://www.linkedin.com/secure/developer

    'linkedin' => array(
        'authlinkedin:LinkedIn',

        'key' => getenv('AE_AS_LINKEDIN_KEY'),
        'secret' => getenv('AE_AS_LINKEDIN_SECRET'),

    ),


    /*
    // Twitter OAuth Authentication API.
    // Register your application to get an API key here:
    //  http://twitter.com/oauth_clients
    */
    'twitter' => array(
        'authtwitter:Twitter',
        'key' => getenv('AE_AS_TWITTER_KEY'),
        'secret' => getenv('AE_AS_TWITTER_SECRET'),

        // Forces the user to enter their credentials to ensure the correct users account is authorized.
        // Details: https://dev.twitter.com/docs/api/1/get/oauth/authenticate
        'force_login' => FALSE,
    ),

);
