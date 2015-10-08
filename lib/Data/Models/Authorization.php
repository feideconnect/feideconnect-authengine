<?php

namespace FeideConnect\Data\Models;

use Cassandra\Type\Uuid;
use Cassandra\Type\CollectionMap;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\Base;
use Cassandra\Type\Timestamp;

class Authorization extends \FeideConnect\Data\Model {

    public $clientid, $userid, $scopes, $issued;

    protected static $_properties = array(
        "clientid", "userid", "scopes", "issued"
    );
    protected static $_types = [
        "issued" => "timestamp"
    ];


    public function getStorableArray() {

        $prepared = parent::getStorableArray();




        if (isset($this->scopes)) {
            $prepared["scopes"] = new CollectionSet($this->scopes, Base::ASCII);
        }
        if (isset($this->clientid)) {
            $prepared["clientid"] = new Uuid($this->clientid);
        }
        if (isset($this->userid)) {
            $prepared["userid"] = new Uuid($this->userid);
        }

        return $prepared;
    }

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
