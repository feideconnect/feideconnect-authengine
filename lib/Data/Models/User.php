<?php

namespace FeideConnect\Data\Models;

use FeideConnect\Logger;
use FeideConnect\Config;
use FeideConnect\Authentication\Account;
use FeideConnect\Data\Model;
use Cassandra\Type\Uuid;
use Cassandra\Type\CollectionMap;
use Cassandra\Type\CollectionSet;
use Cassandra\Type\Base;
use Cassandra\Type\Timestamp;

/**
 * User
 */
class User extends \FeideConnect\Data\Model {

    public $userid, $email, $name, $profilephoto, $profilephotohash, $userid_sec, $userid_sec_seen, $selectedsource, $aboveagelimit, $usageterms, $created, $updated;

    protected static $_properties = array(
        "userid", "email", "name",
        "profilephoto", "profilephotohash",
        "userid_sec", "userid_sec_seen", "selectedsource",
        "aboveagelimit", "usageterms",
        "created", "updated"
    );
    protected static $_types = [
        "created" => "timestamp",
        "updated" => "timestamp"
    ];

    public function isBelowAgeLimit() {
        return $this->aboveagelimit === false;
    }

    public function getStorableArray() {

        $prepared = parent::getStorableArray();

        if (isset($this->name)) {
            $prepared["name"] =  new CollectionMap($this->name, Base::ASCII, Base::ASCII);
        }
        if (isset($this->email)) {
            $prepared["email"] =  new CollectionMap($this->email, Base::ASCII, Base::ASCII);
        }
        if (isset($this->profilephoto)) {
            $prepared["profilephoto"] =  new CollectionMap($this->profilephoto, Base::ASCII, Base::BLOB);
        }
        if (isset($this->profilephotohash)) {
            $prepared["profilephotohash"] =  new CollectionMap($this->profilephotohash, Base::ASCII, Base::ASCII);
        }
        if (isset($this->userid)) {
            $prepared["userid"] = new Uuid($this->userid);
        }
        if (isset($this->userid_sec)) {
            $prepared["userid_sec"] =  new CollectionSet($this->userid_sec, Base::ASCII);
        }
        if (isset($this->userid_sec_seen)) {
            $prepared["userid_sec_seen"] =  new CollectionMap($this->userid_sec_seen, Base::ASCII, Base::TIMESTAMP);
        }

        return $prepared;
    }


    /**
     * [setUserInfo description]
     * @param [type] $sourceID     [description]
     * @param [type] $name         [description]
     * @param [type] $email        [description]
     * @param [type] $profilephoto [description]
     */
    public function setUserInfo($sourceID, $name = null, $email = null, $profilephoto = null, $profilephotohash = null) {

        if (empty($this->name)) $this->name = [];
        if (empty($this->email)) $this->email = [];
        if (empty($this->profilephoto)) $this->profilephoto = [];

        if (empty($sourceID)) throw new \Exception('Cannot set userinfo to a user without a sourceid.');

        if (!empty($name)) {
            if (empty($this->name)) $this->name = [];
            $this->name[$sourceID] = $name;
        }
        if (!empty($email)) {
            if (empty($this->email)) $this->email = [];
            $this->email[$sourceID] = $email;
        }
        if (!empty($profilephoto)) {
            if (empty($this->profilephoto)) $this->profilephoto = [];
            $this->profilephoto[$sourceID] = $profilephoto;
        }
        if (!empty($profilephotohash)) {
            if (empty($this->profilephotohash)) $this->profilephotohash = [];
            $this->profilephotohash[$sourceID] = $profilephotohash;
        }

    }


    public static function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }



    public function getUserIDsecPrefixed($prefix) {
        $res = [];
        if (empty($this->userid_sec)) {
            $this->userid_sec = [];
            return $res;
        }
        foreach ($this->userid_sec as $k) {
            if (self::startsWith($k, $prefix)) {
                $res[] = $k;
            }
        }
        return $res;
    }

    public function ensureProfileAccess($save = false) {


        $res = $this->getUserIDsecPrefixed('p:');

        if (count($res) < 1) {
            $profilePhotoAccess =  'p:' . Model::genUUID();
            $this->userid_sec[] = $profilePhotoAccess;

            if ($save) {
                $this->_repo->addUserIDsec($this->userid, $profilePhotoAccess);
            }

            return true;

        }
        return false;
    }

    public function getProfileAccess() {
        $res = $this->getUserIDsecPrefixed('p:');
        if (count($res) > 0) {
            return $res[0];
        }
        return null;
    }



    public function getSourcedProperty($name, $sourceID) {

        // echo "About to pick " . $name . " " . $soureID . "\n";
        // print_r($)

        if (isset($this->{$name}) && is_array($this->{$name})) {
            if (isset($this->{$name}[$sourceID])) {
                return $this->{$name}[$sourceID];
            }
        }
        return null;
    }

    public function getVerifier() {

        $salt = Config::getValue('salt', null, true);
        $rawstr = 'consent' . '|' . $salt . '|' . $this->userid;

        Logger::info('Calculating verifier from this string', array(
            'rawstring' => 'consent' . '|{salt:hidden}|' . $this->userid
        ));
        return sha1($rawstr);
    }


    public function debug() {

        echo "Debug object " . get_class($this) . "\n";
        // print_r($this->getAsArray());

        $a = $this->getAsArray();

        if (!empty($this->profilephoto)) {
            $f = $this->profilephoto;
            // unset($this->profilephoto);
            $a['profilephoto'] = [];
            foreach ($f as $k => $p) {
                $a['profilephoto'][$k] = base64_encode($p);
            }
        }


        echo json_encode($a, JSON_PRETTY_PRINT) . "\n";

    }


    /**
     * [getUserInfo description]
     * @param  [type] $sourceID [description]
     * @return [type]           [description]
     */
    public function getUserInfo($sourceID = null) {

        $res = array();

        $src = null;
        if (!empty($this->selectedsource)) {
            $src = $this->selectedsource;
        }
        if ($sourceID !== null) {
            $src = $sourceID;
        }
        if ($src === null) {
            throw new \Exception('Cannot get user info from user without specifying source');
        }

        $res['name'] = $this->getSourcedProperty('name', $src);
        $res['email'] = $this->getSourcedProperty('email', $src);
        $res['profilephoto'] = $this->getSourcedProperty('profilephoto', $src);
        $res['profilephotohash'] = $this->getSourcedProperty('profilephotohash', $src);
        return $res;

    }


    public function getBasicUserInfo($includeEmail = false, $allowseckeys = ['uuid', 'p']) {

        $ui = $this->getUserInfo();
        $userinfo = [
            'userid' => $this->userid,
            'userid_sec' => [],
            'name' => $ui['name'],
        ];
        if ($includeEmail) {
            $userinfo['email'] = $ui['email'];
        }

        if ($allowseckeys === true) {
            $userinfo["userid_sec"] = $this->userid_sec;

        } else if (is_array($allowseckeys)) {
            if (!empty($allowseckeys)) {
                foreach ($allowseckeys as $key) {
                    $nc = $this->getUserIDsecPrefixed($key);

                    if ($key === 'p' && count($nc) > 0) {
                        $userinfo['profilephoto'] = $nc[0];
                        continue;
                    }

                    foreach ($nc as $nk) {
                        $userinfo['userid_sec'][] = $nk;
                    }
                }
            }
        }




        return $userinfo;


    }


    public function getFeideRealms() {
        $feideids = $this->getUserIDsecPrefixed('feide');
        $realms = array();
        foreach ($feideids as $feideid) {
            $parts = explode('@', $feideid);
            if (count($parts) === 2) {
                $realms[$parts[1]] = true;
            }
        }
        return array_keys($realms);
    }

    public function updateUserBasics(Account $a) {

        $this->aboveagelimit = $a->aboveAgeLimit();
        $this->_repo->updateUserBasics($this);

    }


    public function updateFromAccount(Account $a) {

        $sourceID = $a->getSourceID();
        $existing = $this->getUserInfo($sourceID);


        /*
         * ----- SECTION Check if userinfo name and email needs to be updated
         *
         */

        $modified = false;
        if ($a->getName() !== $existing['name']) $modified = true;
        if ($a->getMail() !== $existing['email']) $modified = true;

        if ($modified) {
            Logger::info('Updating userinfo', [
                'userid' => $this->userid,
                'sourceID' => $sourceID,
                'name' => [
                    'from' => $existing['name'],
                    'to' => $a->getName()
                ],
                'email' => [
                    'from' => $existing['email'],
                    'to' => $a->getMail(),
                ],
            ]);

            $this->setUserInfo($sourceID, $a->getName(), $a->getMail());
            $this->_repo->updateUserInfo($this, $sourceID, ["name", "email"]);

        }

        if ($this->aboveagelimit !== $a->aboveAgeLimit()) {
            $this->aboveagelimit = $a->aboveAgeLimit();

            $this->updateUserBasics($a);

        }






        /*
         * ----- SECTION Check if profile photo needs to be updated
         *
         */

        $modified = false;
        if ($a->photo !== null) {
            if ($existing['profilephotohash'] === null) {
                $modified = true;
            } else {
                if ($a->photo->getHash() !== $existing['profilephotohash']) {
                    $modified = true;
                }
            }
        }


        if ($modified) {
            $this->setUserInfo($sourceID, null, null, $a->photo->getPhoto(), $a->photo->getHash());

            Logger::info('Updating profile photo for user', [
                'userid' => $this->userid,
                'sourceID' => $sourceID,
                'profilephotohash' => [
                    'from' => $existing['profilephotohash'],
                    'to' => $a->photo->getHash()
                ]
            ]);

            $this->_repo->updateProfilePhoto($this, $sourceID);

        }


    }



    public function toLog() {
        return $this->getAsArrayLimited(["userid", "userid_sec", "name"]);
    }

}
