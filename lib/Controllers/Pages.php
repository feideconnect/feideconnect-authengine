<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\TextResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\Config;
use FeideConnect\Utils\Misc;
use FeideConnect\Utils\URL;

class Pages {

    public static function reject() {

        return (new TemplatedHTMLResponse('reject'))->setData([
            "head" => "You rejected the authorization request for an application"
        ]);
    }

    public static function loggedout() {

        return (new TemplatedHTMLResponse('loggedout'))->setData([
            "head" => "You are now logged out"
        ]);
    }


    public static function robot() {
        $txt = "User-agent: *\nDisallow: /\n";
        return new TextResponse($txt);
    }

    public static function emptyResponse() {

        $res = new TemplatedHTMLResponse('emptyresponsee');
        $res->setDenyFrame(false);
        return $res;

    }

    public static function debug() {

        $data = [];
        $data['endpoints'] = [
            "oauth.base" => URL::getBaseURL() . 'oauth/',
            "this" => URL::selfURL(),
            "this.host" =>  URL::selfURLhost(),
        ];
        $data['client'] = $_SERVER['REMOTE_ADDR'];


        $cookie = (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : null);

        $hdrs = getallheaders();
        $langheader = (isset($hdrs['Accept-Language']) ? $hdrs['Accept-Language'] : null);
        // var_dump($hdrs); exit;

        $availlang = Config::getValue('availableLanguages');
        $defaultlang = $availlang[count($availlang) - 1];
        $locenabled = Config::getValue('enableLocalization');

        $data['lang'] = [
            "cookie" => $cookie,
            "accept-language" => $langheader,
            "available" => $availlang,
            "default" => $defaultlang,
            "enabled" => $locenabled,
            "selected" => Misc::getBrowserLanguage($availlang)
        ];

        $baseDIR = dirname(dirname(__DIR__));

        $data['dir'] = [];
        $data['dir']['base'] = Config::dir();
        $data['dir']['core'] = Config::dir('', '', 'core');
        $data['dir']['clientadm'] = Config::dir('', '', 'clientadm');

        $checkEnvVariables = [
            "AE_SERVER_NAME",
            "AE_SAML_TECHNICALCONTACT_EMAIL",
            "FC_CASSANDRA_CONTACTPOINTS",
            "FC_CASSANDRA_KEYSPACE",
            "FC_CASSANDRA_USESSL",
            "CASSANDRA_USERNAME",
            "CASSANDRA_PASSWORD",
            "AE_DEBUG",
            "AE_SALT",
            "FC_ENDPOINT_CORE",
            "FC_ENDPOINT_CLIENTADM",
            "AE_AS_TWITTER_KEY",
            "AE_AS_TWITTER_SECRET",
            "AE_AS_LINKEDIN_KEY",
            "AE_AS_LINKEDIN_SECRET",
            "AE_AS_FACEBOOK_KEY",
            "AE_AS_FACEBOOK_SECRET",
            "AE_SAML_ADMINPASSWORD",
            "AE_SAML_SECRETSALT",
            "AE_SAML_TECHNICALCONTACT_NAME",
            "FC_CASSANDRA_SESSION_KEYSPACE",
            "FC_CASSANDRA_SESSION_USESSL",
            "FEIDE_IDP",
        ];


        $geofile = Config::filepath(Config::getValue('geodb'));
        $data["files"] = [
            "config.json" => file_exists($baseDIR . '/etc/config.json'),
            $geofile => file_exists($geofile),
        ];
        $data['envvars'] = [];
        foreach($checkEnvVariables AS $ev) {
            $x = getenv($ev);
            $data['envvars'][$ev] = !empty($x);
        }

        $res = new JSONResponse($data);
        $res->setCORS(false);
        return $res;

    }

    public static function frontpage() {
        $txt = "Dataporten auth-engine. See https://docs.dataporten.no for more information.\n";
        return new TextResponse($txt);
    }


}
