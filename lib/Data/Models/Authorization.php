<?php

namespace FeideConnect\Data\Models;

class Authorization extends \FeideConnect\Data\Model {

    public $clientid, $userid, $scopes, $issued, $apigk_scopes;

    protected static $_properties = [
        'clientid' => 'uuid',
        'userid' => 'uuid',
        'scopes' => 'set<text>',
        'issued' => 'timestamp',
        'apigk_scopes' => 'map<text,set<text>>',
    ];

    public function getScopeList() {
        if (empty($this->scopes)) {
            return [];
        }
        return $this->scopes;
    }

    public function addScopes($scopes) {
        if (empty($this->scopes)) {
            $this->scopes = [];
        }
        foreach ($scopes as $s) {
            if (!in_array($s, $this->scopes)) {
                $this->scopes[] = $s;
            }
        }
    }

    public function includeScopes($requiredscopes) {

        $myScopes = $this->getScopeList();

        if ($requiredscopes === null) {
            return true;
        }
        // echo '<pre>'; print_r($requiredscopes); exit;
        assert('is_array($requiredscopes)');
        foreach ($requiredscopes as $rs) {
            if (!in_array($rs, $myScopes)) {
                return false;
            }
        }
        return true;
    }

    public function remainingScopes($requiredscopes) {
        $myScopes = $this->getScopeList();
        return array_diff($requiredscopes, $myScopes);
    }


}
