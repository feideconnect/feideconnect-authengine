<?php

namespace FeideConnect\Data\Models;

class Client extends \FeideConnect\Data\Model {

    public $id, $client_secret, $created, $descr, $name, $owner, $organization, $logo, $redirect_uri, $scopes, $scopes_requested, $status, $type, $updated, $authproviders, $orgauthorization, $authoptions;

    protected static $_properties = [
        'id' => 'uuid',
        'client_secret' => 'text',
        'created' => 'timestamp',
        'descr' => 'text',
        'name' => 'text',
        'owner' => 'uuid',
        'organization' => 'text',
        'logo' => 'blob',
        'redirect_uri' => 'list<text>',
        'scopes' => 'set<text>',
        'scopes_requested' => 'set<text>',
        'status' => 'set<text>',
        'type' => 'text',
        'updated' => 'timestamp',
        'authproviders' => 'set<text>',
        'orgauthorization' => 'map<text,json>',
        'authoptions' => 'json',
        'systemdescr' => 'text',
        'supporturl' => 'text',
        'loginurl' => 'text',
        'homepageurl' => 'text',
        'privacypolicyurl' => 'text',
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
        if (!empty($this->authproviders)) {
            return $this->authproviders;
        } else {
            return ['all'];
        }
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

    // The simple view returns the most important properties as an assoc array,
    // as needed by in example the accountchooser page.
    public function getSimpleView() {
        $data = [
            "id" => $this->id,
            "name" => $this->name,
            "authproviders" => $this->authproviders,
        ];
        return $data;
    }

}
