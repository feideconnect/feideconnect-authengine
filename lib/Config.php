<?php

namespace FeideConnect;

/**
 * Config
 */

class Config {

    protected $properties;

    protected static $instance = null;

    public function __construct(array $properties) {
        $this->properties = $properties;
    }

    // ------ ------ ------ ------ Object methods


    protected static function arrayPick($arr, $pick) {

        $ref =& $arr;
        for ($i = 0; $i < count($pick); $i++) {
            if (array_key_exists($pick[$i], $ref)) {
                $ref =& $ref[$pick[$i]];
            } else {
                throw new \UnexpectedValueException();
            }
        }
        return $ref;
    }

    public function get($key, $default = null, $required = false) {

        try {
            return self::arrayPick($this->properties, explode('.', $key));
        } catch (\UnexpectedValueException $e) {
            if ($required === true) {
                throw new \Exception('Missing required global configuration property [' . $key . ']');
            }
            return $default;
        }


    }

    public function getConfig() {
        return $this->properties;
    }



    // ------ ------ ------ ------ Class methods

    // public static function getBaseURL($app = 'api') {
    //     return self::getValue('scheme', 'https') . '://' . $app . '.' . GlobalConfig::hostname() . '/';
    // }

    public static function getValue($key, $default = null, $required = false) {
        $config = self::getInstance();
        return $config->get($key, $default, $required);
    }

    public static function getTemplateConfig() {
        return [
            'cacheBust' => (getenv('JENKINS_BUILD_NUMBER') !== false) ? getenv('JENKINS_BUILD_NUMBER') : 'noBuild',
            'isDevelopment' => (getenv('NODE_ENV') === 'development'),
            'assets' => self::getValue('assets', [], false),
        ];
    }


    /**
     * The way to load a global config object.
     *
     * @return [type] [description]
     */
    public static function getInstance() {

        if (!is_null(self::$instance)) {
            return self::$instance;
        }


        $file = 'config.json';
        $configFilename = self::dir('etc/', $file);
        // echo "Looking for " . $configFilename;
        if (!file_exists($configFilename)) {
            self::makeEmptyInstance();
            throw new \Exception('Could not find config file ' . $configFilename);
        }
        $configRaw = file_get_contents($configFilename);
        if (empty($configRaw)) {
            self::makeEmptyInstance();
            throw new \Exception('Config file was empty');
        }
        $config = json_decode($configRaw, true);
        if ($config === null || !is_array($config)) {
            self::makeEmptyInstance();
            throw new \Exception('Config file was not properly encoded JSON');
        }

        $envOverride = [
            "storage" => [],
            "endpoints" => [],
        ];
        if (getenv('FC_CASSANDRA_CONTACTPOINTS') !== false) {
            $envOverride["storage"]["nodes"] = explode(", ", getenv('FC_CASSANDRA_CONTACTPOINTS'));
        }
        if (getenv('FC_CASSANDRA_KEYSPACE') !== false) {
            $envOverride["storage"]["keyspace"] = getenv('FC_CASSANDRA_KEYSPACE');
        }
        if (getenv('FC_CASSANDRA_USESSL') !== false) {
            $envOverride["storage"]["use_ssl"] = (getenv('FC_CASSANDRA_USESSL') !== "false");
        }
        if (getenv('FC_ENDPOINT_CORE') !== false) {
            $envOverride["endpoints"]["core"] = getenv('FC_ENDPOINT_CORE');
        }
        if (getenv('FC_ENDPOINT_CLIENTADM') !== false) {
            $envOverride["endpoints"]["clientadm"] = getenv('FC_ENDPOINT_CLIENTADM');
        }
        if (getenv('AE_SALT') !== false) {
            $envOverride["salt"] = getenv('AE_SALT');
        }
        if (getenv('AE_DEBUG') !== false) {
            $envOverride["debug"] = (getenv('AE_DEBUG') === "true");
        }
        if (getenv('AE_GEODB') !== false) {
            $envOverride["geodb"] = getenv('AE_GEODB');
        }
        if (getenv('AE_LOGLEVEL') !== false) {
            $envOverride["logging"]["level"] = getenv('AE_LOGLEVEL');
        }
        if (getenv('AE_LOG_ERRORLOG') !== false) {
            $envOverride["logging"]["errorlog"] = (getenv('AE_LOG_ERRORLOG') === "true");
        }
        if (getenv('AE_STATSD_SERVER') !== false) {
            $envOverride["statsd"]["server"] = getenv('AE_STATSD_SERVER');
        }
        if (getenv('AE_STATSD_NAMESPACE') !== false) {
            $envOverride["statsd"]["namespace"] = getenv('AE_STATSD_NAMESPACE');
        }
        if (getenv('LANG_COOKIE_DOMAIN') !== false) {
            $envOverride["langCookieDomain"] = getenv('LANG_COOKIE_DOMAIN');
        }

        if (getenv('FEIDE_IDP') !== false) {
            $feideIdP = getenv('FEIDE_IDP');
            $envOverride["feideIdP"] = $feideIdP;
            /* Patch `disco`-entries in configuration to make sure that Feide IdP is used here as well. */
            foreach ($config['disco'] as &$discoEntry) {
                if ($discoEntry['type'] === 'saml' && $discoEntry['id'] === 'https://idp.feide.no') {
                    $discoEntry['id'] = $feideIdP;
                }
            }
            unset($discoEntry); // Clear the reference, so that no following code can access last entry by reference.
        }

        if (getenv('AE_TESTUSERSFILE') !== false) {
            $testusersExternalFile = self::readJSONfile(getenv('AE_TESTUSERSFILE'));
            $envOverride["testUsers"] = $testusersExternalFile;
        }

        // get dynamic assets config
        try {
            $config['assets'] = self::readJSONfile('assets.json');
        } catch (\Exception $e) {
            // couldn't find built assets
            $config['assets'] = [];
        }

        $config = array_replace_recursive($config, $envOverride);

        self::$instance = new Config($config);
        return self::$instance;
    }

    public static function makeEmptyInstance() {
        self::$instance = new Config([]);
        return self::$instance;
    }


    /**
     * Will return the base directory for the installation, such as
     * in example /var/www/feideconnect
     * @return [type] [description]
     */
    public static function baseDir() {
        return dirname(__DIR__) . '/';
    }

    /**
     * Returns a subfolder, relative to the base directory:
     * In example dir('templates/') may return
     * /var/www/feideconnect/templates/
     *
     * Filename, if present, is added to the end.
     *
     * @param  string $path [description]
     * @return [type]       [description]
     */
    public static function dir($path = '', $file = '', $component = null) {
        if ($component === null) {
            return self::baseDir() . $path . $file;
        }

        $endpoints = self::getValue("endpoints", []);
        if (!isset($endpoints[$component])) {
            throw new \Exception('Missing endpoint definition for  ' . $component . ' in config.json');
        }

        $base = $endpoints[$component];
        return $base . '/' . $path . $file;
    }


    public static function filepath($path = '') {

        $filepath = $path;

        if (empty($path)) {
            return self::baseDir();
        }

        if ($path[0] === '/') {
            return $filepath;
        }

        return self::baseDir() . $path;

    }


    /**
     * A helper function to read a JSON syntax file in the etc directory.
     * @param  [type] $file [description]
     * @return [type]       [description]
     */
    public static function readJSONfile($file) {

        $configFilename = self::dir('etc/', $file);
        if (!file_exists($configFilename)) {
            throw new \Exception('Could not find JSON file ' . $configFilename);
        }
        $data = file_get_contents($configFilename);
        if ($data === false) {
            throw new \Exception('Error reading JSON file ' . $configFilename);
        }

        $dataParsed = json_decode($data, true);
        if ($dataParsed === false) {
            throw new \Exception('Error parsing JSON file ' . $configFilename);
        }

        return $dataParsed;
    }


    public static function getCountryCodes() {
        $langCodes = 'AD,"Andorra"
    AE,"United Arab Emirates"
    AF,"Afghanistan"
    AG,"Antigua and Barbuda"
    AI,"Anguilla"
    AL,"Albania"
    AM,"Armenia"
    AO,"Angola"
    AP,"Asia/Pacific Region"
    AQ,"Antarctica"
    AR,"Argentina"
    AS,"American Samoa"
    AT,"Austria"
    AU,"Australia"
    AW,"Aruba"
    AX,"Aland Islands"
    AZ,"Azerbaijan"
    BA,"Bosnia and Herzegovina"
    BB,"Barbados"
    BD,"Bangladesh"
    BE,"Belgium"
    BF,"Burkina Faso"
    BG,"Bulgaria"
    BH,"Bahrain"
    BI,"Burundi"
    BJ,"Benin"
    BL,"Saint Bartelemey"
    BM,"Bermuda"
    BN,"Brunei Darussalam"
    BO,"Bolivia"
    BQ,"Bonaire, Saint Eustatius and Saba"
    BR,"Brazil"
    BS,"Bahamas"
    BT,"Bhutan"
    BV,"Bouvet Island"
    BW,"Botswana"
    BY,"Belarus"
    BZ,"Belize"
    CA,"Canada"
    CC,"Cocos (Keeling) Islands"
    CD,"Congo, The Democratic Republic of the"
    CF,"Central African Republic"
    CG,"Congo"
    CH,"Switzerland"
    CI,"Cote d\'Ivoire"
    CK,"Cook Islands"
    CL,"Chile"
    CM,"Cameroon"
    CN,"China"
    CO,"Colombia"
    CR,"Costa Rica"
    CU,"Cuba"
    CV,"Cape Verde"
    CW,"Curacao"
    CX,"Christmas Island"
    CY,"Cyprus"
    CZ,"Czech Republic"
    DE,"Germany"
    DJ,"Djibouti"
    DK,"Denmark"
    DM,"Dominica"
    DO,"Dominican Republic"
    DZ,"Algeria"
    EC,"Ecuador"
    EE,"Estonia"
    EG,"Egypt"
    EH,"Western Sahara"
    ER,"Eritrea"
    ES,"Spain"
    ET,"Ethiopia"
    EU,"Europe"
    FI,"Finland"
    FJ,"Fiji"
    FK,"Falkland Islands (Malvinas)"
    FM,"Micronesia, Federated States of"
    FO,"Faroe Islands"
    FR,"France"
    GA,"Gabon"
    GB,"United Kingdom"
    GD,"Grenada"
    GE,"Georgia"
    GF,"French Guiana"
    GG,"Guernsey"
    GH,"Ghana"
    GI,"Gibraltar"
    GL,"Greenland"
    GM,"Gambia"
    GN,"Guinea"
    GP,"Guadeloupe"
    GQ,"Equatorial Guinea"
    GR,"Greece"
    GS,"South Georgia and the South Sandwich Islands"
    GT,"Guatemala"
    GU,"Guam"
    GW,"Guinea-Bissau"
    GY,"Guyana"
    HK,"Hong Kong"
    HM,"Heard Island and McDonald Islands"
    HN,"Honduras"
    HR,"Croatia"
    HT,"Haiti"
    HU,"Hungary"
    ID,"Indonesia"
    IE,"Ireland"
    IL,"Israel"
    IM,"Isle of Man"
    IN,"India"
    IO,"British Indian Ocean Territory"
    IQ,"Iraq"
    IR,"Iran, Islamic Republic of"
    IS,"Iceland"
    IT,"Italy"
    JE,"Jersey"
    JM,"Jamaica"
    JO,"Jordan"
    JP,"Japan"
    KE,"Kenya"
    KG,"Kyrgyzstan"
    KH,"Cambodia"
    KI,"Kiribati"
    KM,"Comoros"
    KN,"Saint Kitts and Nevis"
    KP,"Korea, Democratic People\'s Republic of"
    KR,"Korea, Republic of"
    KW,"Kuwait"
    KY,"Cayman Islands"
    KZ,"Kazakhstan"
    LA,"Lao People\'s Democratic Republic"
    LB,"Lebanon"
    LC,"Saint Lucia"
    LI,"Liechtenstein"
    LK,"Sri Lanka"
    LR,"Liberia"
    LS,"Lesotho"
    LT,"Lithuania"
    LU,"Luxembourg"
    LV,"Latvia"
    LY,"Libyan Arab Jamahiriya"
    MA,"Morocco"
    MC,"Monaco"
    MD,"Moldova, Republic of"
    ME,"Montenegro"
    MF,"Saint Martin"
    MG,"Madagascar"
    MH,"Marshall Islands"
    MK,"Macedonia"
    ML,"Mali"
    MM,"Myanmar"
    MN,"Mongolia"
    MO,"Macao"
    MP,"Northern Mariana Islands"
    MQ,"Martinique"
    MR,"Mauritania"
    MS,"Montserrat"
    MT,"Malta"
    MU,"Mauritius"
    MV,"Maldives"
    MW,"Malawi"
    MX,"Mexico"
    MY,"Malaysia"
    MZ,"Mozambique"
    NA,"Namibia"
    NC,"New Caledonia"
    NE,"Niger"
    NF,"Norfolk Island"
    NG,"Nigeria"
    NI,"Nicaragua"
    NL,"Netherlands"
    NO,"Norway"
    NP,"Nepal"
    NR,"Nauru"
    NU,"Niue"
    NZ,"New Zealand"
    OM,"Oman"
    PA,"Panama"
    PE,"Peru"
    PF,"French Polynesia"
    PG,"Papua New Guinea"
    PH,"Philippines"
    PK,"Pakistan"
    PL,"Poland"
    PM,"Saint Pierre and Miquelon"
    PN,"Pitcairn"
    PR,"Puerto Rico"
    PS,"Palestinian Territory"
    PT,"Portugal"
    PW,"Palau"
    PY,"Paraguay"
    QA,"Qatar"
    RE,"Reunion"
    RO,"Romania"
    RS,"Serbia"
    RU,"Russian Federation"
    RW,"Rwanda"
    SA,"Saudi Arabia"
    SB,"Solomon Islands"
    SC,"Seychelles"
    SD,"Sudan"
    SE,"Sweden"
    SG,"Singapore"
    SH,"Saint Helena"
    SI,"Slovenia"
    SJ,"Svalbard and Jan Mayen"
    SK,"Slovakia"
    SL,"Sierra Leone"
    SM,"San Marino"
    SN,"Senegal"
    SO,"Somalia"
    SR,"Suriname"
    SS,"South Sudan"
    ST,"Sao Tome and Principe"
    SV,"El Salvador"
    SX,"Sint Maarten"
    SY,"Syrian Arab Republic"
    SZ,"Swaziland"
    TC,"Turks and Caicos Islands"
    TD,"Chad"
    TF,"French Southern Territories"
    TG,"Togo"
    TH,"Thailand"
    TJ,"Tajikistan"
    TK,"Tokelau"
    TL,"Timor-Leste"
    TM,"Turkmenistan"
    TN,"Tunisia"
    TO,"Tonga"
    TR,"Turkey"
    TT,"Trinidad and Tobago"
    TV,"Tuvalu"
    TW,"Taiwan"
    TZ,"Tanzania, United Republic of"
    UA,"Ukraine"
    UG,"Uganda"
    UM,"United States Minor Outlying Islands"
    US,"United States"
    UY,"Uruguay"
    UZ,"Uzbekistan"
    VA,"Holy See (Vatican City State)"
    VC,"Saint Vincent and the Grenadines"
    VE,"Venezuela"
    VG,"Virgin Islands, British"
    VI,"Virgin Islands, U.S."
    VN,"Vietnam"
    VU,"Vanuatu"
    WF,"Wallis and Futuna"
    WS,"Samoa"
    YE,"Yemen"
    YT,"Mayotte"
    ZA,"South Africa"
    ZM,"Zambia"
    ZW,"Zimbabwe"';

        // Get all languages
        $allLangauges = [];
        $lines = explode("\n", $langCodes);
        foreach($lines AS $line) {
            if (preg_match('/^[\s]*([A-Z]+),"(.*)"$/', $line, $matches)) {
                $langcode = strtolower($matches[1]);
                $langname = $matches[2];
                $allLangauges[$langcode] = $langname;
            }
        }
        return $allLangauges;

    }



}
