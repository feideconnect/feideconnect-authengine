<?php

namespace FeideConnect\OpenIDConnect;

use FeideConnect\Utils\URL;
use FeideConnect\Config;

/**
*
*/
class OpenIDConnect {

    protected $trustStore;
    protected $issuer;


    public function __construct() {

        $this->trustStore = new TrustStore();
        $this->issuer = Config::getValue('connect.issuer');
        $this->expiration = Config::getValue('connect.expires_in', 3600);

    }

    public function getIDtoken($sub, $aud, $auth_time = null) {


        //  generate(TrustStore $trustStore, $iss, $sub, $aud, $expires_in, $setiat) {
        $idtoken = IDToken::generate($this->trustStore, $this->issuer, $sub, $aud, $this->expiration, $auth_time);
        return $idtoken;

    }


    public function getJWKs() {

        return $this->trustStore->getJWKs();

    }

    public function getProviderConfiguration() {

        $base = URL::getBaseURL() . 'oauth/';
        $base2 = URL::getBaseURL() . 'openid/';
        $config = [
            'issuer' => Config::getValue('connect.issuer'),
            'authorization_endpoint' => $base . 'authorization',
            'token_endpoint' => $base . 'token',
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'token_endpoint_auth_signing_alg_values_supported' => ['RS256'],
            'userinfo_endpoint' =>  $base2 . 'userinfo',
            'ui_locales_supported' => ["en", "no", "nb", "nn"],
            'service_documentation' => 'http://feideconnect.no/docs/gettingstarted/',
            'jwks_uri' => $base2 . 'jwks',
            'response_types_supported' => ['code', 'id_token token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
        ];
        return $config;

    }

}
