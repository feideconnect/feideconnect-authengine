<?php

namespace tests;

use FeideConnect\Localization;
use FeideConnect\Config;
use FeideConnect\Utils\Misc;

class LocalizationTest extends \PHPUnit_Framework_TestCase {

    public function __construct() {
    }


    public function testMisc() {

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'nb,no;q=0.8,en;q=0.5,en-US;q=0.3';
        Misc::reset();

        $availableLanguages = Config::getValue('availableLanguages', ['en']);
        $lang = Misc::getBrowserLanguage($availableLanguages);
    }

    public function testMisc2() {

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'nb,no;q=0.8,en;q=0.5,en-US;q=0.3';
        Misc::reset();

        $availableLanguages = Config::getValue('availableLanguages', ['en']);
        $lang = Misc::getBrowserLanguage($availableLanguages);
        
        $this->assertEquals($lang, 'nb' );
    }

    public function testLocalizeEntry() {

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8,nn;q=0.6,nb;q=0.4';
        Misc::reset();

        $data = [
            'en' => 'car',
            'nb' => 'bil'
        ];
        $result = Localization::localizeEntry($data);
        $this->assertEquals($result, 'car' );
    }

    public function testLocalizeEntryNo() {

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8,nn;q=0.6,nb;q=0.4';
        Misc::reset();

        $data = [
            'nb' => 'bil'
        ];
        $result = Localization::localizeEntry($data);
        $this->assertEquals($result, 'bil' );
    }

    public function testLocalizeEntryNo2() {

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'nb,no;q=0.8,en;q=0.5,en-US;q=0.3';
        Misc::reset();

        $data = [
            'en' => 'car',
            'nb' => 'bil'
        ];
        $result = Localization::localizeEntry($data);
        $this->assertEquals($result, 'bil' );
    }

    public function testLocalizeEntryNo3() {

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'no,en;q=0.5,en-US;q=0.3';
        Misc::reset();

        $data = [
            'en' => 'car',
            'nb' => 'bil'
        ];
        $result = Localization::localizeEntry($data);
        $this->assertEquals($result, 'bil' );
    }

    public function testLocalizeList() {

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8,nn;q=0.6,nb;q=0.4';
        Misc::reset();

        $data = [[
            'title' => 'foo',
            'anything' => 'will',
            'descr' => [
                'en' => 'car',
                'nb' => 'bil'
            ]
        ]];

        $result = Localization::localizeList($data, ['title', 'descr']);

        $this->assertEquals($result[0]['title'], 'foo' );
        $this->assertEquals($result[0]['anything'], 'will' );
        $this->assertEquals($result[0]['descr'], 'car' );

    }




}
