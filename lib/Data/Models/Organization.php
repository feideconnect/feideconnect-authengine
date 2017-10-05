<?php

namespace FeideConnect\Data\Models;

use FeideConnect\Utils\Misc;

/*
CREATE TABLE feideconnect.organizations (
    id text PRIMARY KEY,
    kindid int,
    logo blob,
    logo_updated timestamp,
    name map<text, text>,
    organization_number text,
    realm text,
    type set<text>
)
 */

class Organization extends \FeideConnect\Data\Model {

    public $id, $name, $realm, $type, $uiinfo, $service;

    protected static $_properties = [
        'id' => 'text',
        'name' => 'map<text,text>',
        'realm' => 'text',
        'type' => 'set<text>',
        'uiinfo' => 'json',
        'services' => 'set<text>',
    ];

    public function getTypes() {
        $t = [];
        foreach ($this->type as $type) {
            switch ($type) {
                case 'primary_and_lower_secondary':
                    $t[] = 'go';
                    break;
                case 'upper_secondary':
                    $t[] = 'vgs';
                    break;
                case 'higher_education':
                    $t[] = 'he';
                    break;
                // case 'service_provider':
                //     $t[] = 'sp'; break;
            }

        }
        return $t;

    }

    public function getName() {
        $lang = Misc::getBrowserLanguage(array_keys($this->name));
        return $this->name[$lang];
    }

    public function getOrgInfo() {

        $res = [];
        $prepared = parent::getAsArray();
        $res["id"] = $prepared["realm"];
        $res["type"] = $this->getTypes();

        $res["title"] = $this->getName();
        if (isset($prepared["uiinfo"])) {
            $res["uiinfo"] = $prepared["uiinfo"];
        }
        $res["services"] = $prepared["services"];

        $res["distance"] = null;

        return $res;
    }

    public function isHomeOrg() {
        return $this->hasType("home_organization");
    }

    public function hasType($type) {
        if ($this->type === null) {
            return false;
        }
        return in_array($type, $this->type);
    }

}
