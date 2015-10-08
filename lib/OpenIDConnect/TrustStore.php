<?php

namespace FeideConnect\OpenIDConnect;

use FeideConnect\Config;
use Base64Url\Base64Url;

class TrustStore {

    protected $key;

    public function __construct() {

        $keyfile = Config::dir('etc', '/jwt-key.pem');
        $crtfile = Config::dir('etc', '/jwt-cert.pem');

        $this->key = file_get_contents($keyfile);
        $this->crt = file_get_contents($crtfile);

        // echo '<pre>File';
        // echo $keyfile;
        //  print_r($this->key); exit;

    }

    public function getSigningAlg() {
        return 'RS256';
    }

    public function getKey() {
        return $this->key;
    }


    public function getJWKs() {
        $jwks = [];
        $jwks[] = self::loadKeyFromFile($this->crt);
        return $jwks;
    }


    /*
     * Credits to Spomky-Labs/jose
     * https://github.com/Spomky-Labs/jose/blob/master/lib/Util/RSAConverter.php
     * MIT Licence: https://github.com/Spomky-Labs/jose/blob/master/LICENSE
     */


    /**
     * @param string $certificate
     * @param string $passphrase
     */
    protected static function loadCertificateValues($certificate) {

        $res = openssl_pkey_get_public($certificate);
        if ($res === false) {
            throw new \Exception("Unable to load the certificate");
        }
        $details = openssl_pkey_get_details($res);
        if ($details === false) {
            throw new \Exception("Unable to get details of the certificate");
        }
        if (!is_array($details) || !isset($details['rsa'])) {
            throw new \Exception("Certificate is not a valid RSA certificate");
        }
        return $details['rsa'];

    }

    /**
     * @param string $certificate
     * @param string $passphrase
     */
    public static function loadKeyFromFile($certificate, $passphrase = null) {
        $values = self::loadCertificateValues($certificate, $passphrase);
        $result = array('kty' => 'RSA');
        foreach ($values as $key => $value) {
            $value = Base64Url::encode($value);
            if ($key === "dmp1") {
                $result["dp"] = $value;
            } elseif ($key === "dmq1") {
                $result["dq"] = $value;
            } elseif ($key === "iqmp") {
                $result["qi"] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    // --- o --- o --- o --- o --- o --- o --- o --- o --- o ---


}
