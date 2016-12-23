<?php


namespace FeideConnect\Controllers;

// use FeideConnect\HTTP\ImageResponse;
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
        return new JSONResponse($res);
    }

    private static function getRegAuthoritiesData() {
        $res = [];
        $metastore = new \sspmod_cassandrastore_MetadataStore_CassandraMetadataStore([]);
        $data = $metastore->getFeed("edugain");
        $config = Config::getValue('federations');
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


    public static function getProvidersByCountry($country) {

        $res = [];
        $config = Config::getValue('federations');
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