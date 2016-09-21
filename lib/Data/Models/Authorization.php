<?php

namespace FeideConnect\Data\Models;

use Cassandra\Type\CollectionMap;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\Base;
use Cassandra\Type\Timestamp;

class Authorization extends \FeideConnect\Data\Model {

    public $clientid, $userid, $scopes, $issued, $apigk_scopes;

    protected static $_properties = [
        'clientid' => 'uuid',
        'userid' => 'uuid',
        'scopes' => 'default',
        'issued' => 'timestamp',
        'apigk_scopes' => 'default',
    ];


    public function getStorableArray() {

        $prepared = parent::getStorableArray();




        if (isset($this->scopes)) {
            $prepared["scopes"] = new CollectionSet($this->scopes, Base::ASCII);
        }
        if (isset($this->apigk_scopes)) {
            $prepared["apigk_scopes"] = new CollectionMap($this->apigk_scopes, Base::ASCII, ["type" => Base::COLLECTION_SET, "value" => Base::ASCII]);
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
