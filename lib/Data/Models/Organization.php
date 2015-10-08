<?php

namespace FeideConnect\Data\Models;

use Cassandra\Type\Uuid;
use Cassandra\Type\CollectionList;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\CollectionMap;
use Cassandra\Type\Base;
use Cassandra\Type\Timestamp;
use Cassandra\Type\Blob;

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

    protected static $_properties = array(
        "id", "name", "realm", "type", "uiinfo", "services"
    );
    protected static $_types = [
    ];



    public function __construct($props) {

        parent::__construct($props);

        if (isset($props["uiinfo"])) {
            $this->uiinfo = json_decode($props["uiinfo"], true);
            unset($props["uiinfo"]);
        }




    }

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

    public function distance($lat, $lon) {

        if (!isset($this->uiinfo)) {
            return null;
        }
        if (!isset($this->uiinfo["geo"])) {
            return null;
        }
        if (!is_array($this->uiinfo["geo"])) {
            return null;
        }

        $distance = 9999;
        foreach ($this->uiinfo["geo"] as $geoitem) {
            $dc = Misc::distance($lat, $lon, $geoitem["lat"], $geoitem["lon"]);
            if ($dc < $distance) {
                $distance = $dc;
            }
        }
        return $distance;
    }


    public function getOrgInfo($lat = null, $lon = null) {

        $res = [];
        $prepared = parent::getAsArray();
        $res["id"] = $prepared["realm"];
        $res["type"] = $this->getTypes();

        $lang = Misc::getBrowserLanguage(array_keys($prepared["name"]));
        $res["title"] = $prepared["name"][$lang];
        if (isset($prepared["uiinfo"])) {
            $res["uiinfo"] = $prepared["uiinfo"];
        }
        $res["services"] = $prepared["services"];

        if ($lat !== null && $lon !== null) {
            $res["distance"] = $this->distance($lat, $lon);
        } else {
            $res["distance"] = null;
        }

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

    public function getStorableArray() {

        $prepared = parent::getStorableArray();
        $prepared["uiinfo"] = json_encode($this->uiinfo);

        if (isset($this->name)) {
            $prepared["name"] =  new CollectionMap($this->name, Base::ASCII, Base::ASCII);
        }

        if (isset($this->type)) {
            $prepared["type"] = new CollectionSet($this->type, Base::ASCII);
        }
        return $prepared;
    }



}
