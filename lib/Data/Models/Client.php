<?php

namespace FeideConnect\Data\Models;

use Cassandra\Type\CollectionMap;
use Cassandra\Type\Base;

class Client extends \FeideConnect\Data\Model {

    public $id, $client_secret, $created, $descr, $name, $owner, $organization, $logo, $redirect_uri, $scopes, $scopes_requested, $status, $type, $updated, $authproviders, $orgauthorization, $authoptions;

    protected static $_properties = [
        'id' => 'uuid',
        'client_secret' => 'default',
        'created' => 'timestamp',
        'descr' => 'default',
        'name' => 'default',
        'owner' => 'uuid',
        'organization' => 'default',
        'logo' => 'blob',
        'redirect_uri' => 'list<text>',
        'scopes' => 'set<text>',
        'scopes_requested' => 'set<text>',
        'status' => 'set<text>',
        'type' => 'default',
        'updated' => 'timestamp',
        'authproviders' => 'default',
        'orgauthorization' => 'default',
        'authoptions' => 'default',
        'systemdescr' => 'default',
        'supporturl' => 'default',
        'loginurl' => 'default',
        'homepageurl' => 'default',
        'privacypolicyurl' => 'default',
    ];

    public static function fromDB($key, $value) {
        switch ($key) {
        case 'orgauthorization':
            if (is_null($value)) {
                return null;
            }
            $ret = [];
            foreach ($value as $realm => $authz) {
                $ret[$realm] = json_decode($authz);
            }
            return $ret;
        case 'authoptions':
            if (is_null($value)) {
                return null;
            }
            return json_decode($value, true);
        default:
            return parent::fromDB($key, $value);
        }
    }

    public function getScopeList() {
        if (empty($this->scopes)) {
            return [];
        }
        return $this->scopes;
    }

    public function getScopeQueue() {
        if (empty($this->scopes_requested)) {
            return [];
        }

        $hasScopes = $this->getScopeList();
        return array_values(array_diff($this->scopes_requested, $hasScopes));
    }

    public function hasStatus($status) {

        if ($this->status === null) {
            return false;
        }
        foreach ($this->status as $s) {
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
        foreach ($this->authproviders as $a) {
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

    public function requireInteraction() {
        if (!isset($this->authoptions["requireInteraction"])) {
            return true;
        }
        return $this->authoptions["requireInteraction"];
    }

    public function getStorableArray() {

        $prepared = parent::getStorableArray();


        if (isset($this->orgauthorization)) {
            $encoded = array();
            foreach ($this->orgauthorization as $realm => $authz) {
                $encoded[$realm] = json_encode($authz);
            }
            $prepared["orgauthorization"] = new CollectionMap($encoded, Base::ASCII, BASE::ASCII);
        }

        if (isset($this->authoptions)) {
            $prepared["authoptions"] = json_encode($this->authoptions);
        }


        // echo var_export($prepared, true);

        return $prepared;
    }


    public function toLog() {
        return $this->getAsArrayLimited(["id", "name"]);
    }
}
