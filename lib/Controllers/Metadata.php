<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\ImageResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Config;
// use FeideConnect\Data\StorageProvider;
// use FeideConnect\Authentication\UserID;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Localization;
use FeideConnect\Data\Models\IdProvider;


class Metadata {

    public static function getMetadataBootstrap() {

        $res = Config::getValue('federations');
        if (empty($res)) {
            throw new Exception('Missing configuration for federations');
        }
        return new JSONResponse($res);
    }

    private static function getRegAuthoritiesData() {
        $res = [];
        $metastore = new \sspmod_cassandrastore_MetadataStore_CassandraMetadataStore([]);
        $data = $metastore->getFeed("edugain");
        $config = Config::getValue('federations');
        if (empty($config)) {
            throw new Exception('Missing configuration for federations');
        }
        $configC = [];
        $configR = [];
        foreach($config AS &$c) {
            $configC[$c["country"]] = &$c;
            $configR[$c["regauth"]] = &$c;
            $c["available"] = false;
        }
        $reg = [];
        foreach($data AS $entityid => $e) {
            $regauth = $e['metadata']['RegistrationInfo']['registrationAuthority'];
            if (!isset($reg[$regauth])) {
                $reg[$regauth] = [
                    "country" => (isset($configR[$regauth]) ? $configR[$regauth]['country'] : null ),
                    "counter" => 0,
                ];
                if (isset($configR[$regauth])) {
                    $configR[$regauth]['available'] = true;
                }
            }
            $reg[$regauth]['counter']++;
        }

        $notConfigured = [];
        foreach($reg AS $i => $r) {
            if ($r['country'] === null) {
                $notConfigured[] = $i;
            //
            }
        }

        $res['federations'] = $config;
        $res['notConfigured'] = $notConfigured;
        $res['registrationAuthorities'] = $reg;
        return $res;
    }

    public static function getRegAuthorities() {


        return new JSONResponse(self::getRegAuthoritiesData());
    }

    public static function getLogo() {
        if (!isset($_GET['entityid'])) {
            throw new \Exception('Required GET query string paramter entityid not provided');
        }
        $entityid = $_GET['entityid'];
        $metastore = new \sspmod_cassandrastore_MetadataStore_CassandraMetadataStore([]);
        $logoEntry = $metastore->getLogo("edugain", $entityid);

        // echo '<pre>';
        // print_r($logoEntry);
        // echo (string)$logoEntry['logo'];
        // //

        $img = new ImageResponse();
        $img->setImage($logoEntry['logo']->toBinaryString(), "png");
        $img->setHeader('ETag', $logoEntry['logo_etag']);
        $img->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', ($logoEntry['logo_updated']->microtime(true))) );
        return $img;

    }


    public static function getProvidersByCountry($country) {

        $res = [];
        $config = Config::getValue('federations');
        if (empty($config)) {
            throw new Exception('Missing configuration for federations');
        }
        $configC = [];
        $configR = [];
        foreach($config AS &$c) {
            $configC[$c["country"]] = &$c;
            $configR[$c["regauth"]] = &$c;
        }

        if (!$configC[$country]) {
            return new JSONResponse($res);
        }
        $regauth = $configC[$country]['regauth'];

        // $data = [
        //     "country" => $country,
        //     "reg" => $regauth,
        // ];
        // return new JSONResponse($data);


        $metastore = new \sspmod_cassandrastore_MetadataStore_CassandraMetadataStore([]);
        // $metastore = new CassandraMetadataStore([]);
        $metadata = $metastore->getRegAuthUI("edugain", $regauth);


        foreach($metadata AS $entityid => $e) {
            $res[] = IdProvider::uiFromMeta($e);
        }


        return new JSONResponse($res);

    }

    public static function getCountryCodes() {
        $allLangauges = Config::getCountryCodes();

        $regauths = self::getRegAuthoritiesData();
        // Make sure federations list are sorted by name of country in english
        foreach($regauths['federations'] AS &$x) {
            // echo $regauths['federations'][$k]; exit;
            // $regauths['federations'][$k] = 1;
            $x['sortableName'] = isset($allLangauges[$x['country']]) ? $allLangauges[$x['country']] : 'Unnamed country';
        }
        $sf = function($a, $b){
            return strcmp($a["sortableName"], $b["sortableName"]);
        };
        usort($regauths['federations'], $sf);



        // Select only the ones that are in use, has metadata.
        $selectedLangauges = [];
        $selectedLangcodes = [];

        foreach($regauths['federations'] AS $f) {
            if ($f['country'] === 'no' || $f['available']) {
                $selectedLangauges['c' . $f['country']] = isset($allLangauges[$f['country']]) ? $allLangauges[$f['country']] : 'Unnamed country';
                $selectedLangcodes[] = $f['country'];
            }
        }

        $data = [
            'languageCodes' => $selectedLangcodes,
            'languages' => $selectedLangauges,
        ];

        return new JSONResponse($data);

    }

}
