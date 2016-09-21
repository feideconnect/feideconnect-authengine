<?php

namespace FeideConnect\Data\Models;

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
        'orgauthorization' => 'map<text,json>',
        'authoptions' => 'json',
        'systemdescr' => 'default',
        'supporturl' => 'default',
        'loginurl' => 'default',
        'homepageurl' => 'default',
        'privacypolicyurl' => 'default',
    ];

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

    public function toLog() {
        return $this->getAsArrayLimited(["id", "name"]);
    }
}
