<?php

namespace FeideConnect\Data\Models;

use Cassandra\Type\Uuid;
use Cassandra\Type\CollectionList;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\CollectionMap;
use Cassandra\Type\Base;
use Cassandra\Type\Timestamp;
use Cassandra\Type\Blob;

class Client extends \FeideConnect\Data\Model {

    public $id, $client_secret, $created, $descr, $name, $owner, $organization, $logo, $redirect_uri, $scopes, $scopes_requested, $status, $type, $updated, $authproviders, $orgauthorization;


    protected static $_properties = array(
        "id", "client_secret", "created", "descr", "name", "owner", "organization",
        "logo",
        "redirect_uri", "scopes", "scopes_requested", "status", "type", "updated", "authproviders", "orgauthorization",
    );
    protected static $_types = [
        "created" => "timestamp",
        "updated" => "timestamp"
    ];

    function __construct($props = array()) {

        parent::__construct($props);

        if (isset($props["orgauthorization"])) {
            $this->orgauthorization = array();
            foreach ($props["orgauthorization"] as $realm => $authz) {
                $this->orgauthorization[$realm] = json_decode($authz);
            }
            unset ($props["orgauthorization"]);
        }


    }

    public function getScopeList() {
        if (empty($this->scopes)) return [];
        return $this->scopes;
    }

    public function hasStatus($status) {

        if ($this->status === null) {
            return false;
        }
        foreach($this->status as $s) {
            if ($s === $status) {
                return true;
            }
        }
        return false;
    }
    public function getAuthProviders() {
        $res = [];
        if (empty($this->authproviders)) {
            return [["all"]];
        }
        foreach($this->authproviders as $a) {
            $res[] = explode('|', $a);
        }
        return $res;

    }

    public function getOrgAuthorization($realm) {
        if (!isset($this->orgauthorization[$realm])) {
            return [];
        }
        return $this->orgauthorization[$realm];
    }


    public function getStorableArray() {

        $prepared = parent::getStorableArray();


        if (isset($this->id)) {
            $prepared["id"] = new Uuid($this->id);
        }
        if (isset($this->logo)) {
            $prepared["logo"] =  new Blob($this->logo);
        }

        if (isset($this->redirect_uri)) {
            $prepared["redirect_uri"] =  new CollectionList($this->redirect_uri, Base::ASCII);
        }
        if (isset($this->scopes)) {
            $prepared["scopes"] =  new CollectionSet($this->scopes, Base::ASCII);
        }
        if (isset($this->scopes_requested)) {
            $prepared["scopes_requested"] =  new CollectionSet($this->scopes_requested, Base::ASCII);
        }
        if (isset($this->status)) {
            $prepared["status"] =  new CollectionSet($this->status, Base::ASCII);
        }

        if (isset($this->owner)) {
            $prepared["owner"] =  new Uuid($this->owner);
        }

        if (isset($this->orgauthorization)) {
            $encoded = array();
            foreach($this->orgauthorization as $realm => $authz) {
                $encoded[$realm] = json_encode($authz);
            }
            $prepared["orgauthorization"] = new CollectionMap($encoded, Base::ASCII, BASE::ASCII);
        }


        // echo var_export($prepared, true);

        return $prepared;
    }


    public function toLog() {
        return $this->getAsArrayLimited(["id", "name", "redirect_uri"]);
    }
}
