<?php

namespace FeideConnect\Authentication;

use FeideConnect\Logger;
use FeideConnect\Data\Models;
use FeideConnect\Data\Models\User;
use FeideConnect\Data\Model;

/**
 * This class handles
 *
 *    * Lookup existing user(s) from an account
 *    * Merge users
 *    * Update user objects
 *    * Create user objects
 *
 * Activity is initiated by user authentication of one account.
 *
 */


class UserMapper {

    protected $repo;

    public function __construct($repo) {
        $this->repo = $repo;
    }


    protected function createUser(Account $account) {


        // header('Content-Type: text/plain; charset=utf-8');
        // echo "About to create user with this account\n ";
        // print_r($account);
        // exit;

        $uuid = Model::genUUID();
        $user = new Models\User($this->repo);
        $user->userid = $uuid;

        $user->usageterms = false;
        $user->created = new \FeideConnect\Data\Types\Timestamp();

        $user->userid_sec = $account->getUserIDs();

        $user->aboveagelimit = $account->aboveAgeLimit();


        $changed = $user->ensureProfileAccess(false);

        if (isset($account->photo)) {
            $user->setUserInfo($account->getSourceID(), $account->getName(), $account->getMail(), $account->getPhoto(), $account->photo->getHash());
        } else {
            $user->setUserInfo($account->getSourceID(), $account->getName(), $account->getMail());
        }

        $user->selectedsource = $account->getSourceID();

        $user->userid_sec_seen = array();
        foreach ($user->userid_sec as $u) {
            $user->userid_sec_seen[$u] = microtime(true)*1000.0;
        }

        $this->repo->saveUser($user);

        // echo "about to create a new user";
        // $user->debug();
        // exit;

        // TODO add source id. userid-sec-seen and more.

        Logger::info('Creating a new user based upon an account', array(
            'uuid' => $uuid
        ));

        return $user;

    }





    protected function updateUser(Account $account, User $user) {





        $user->updateFromAccount($account);


        // header('Content-Type: text/plain; charset=utf-8');
        // echo "ABOUT TO UPDATE\n\n";
        // $user->debug();
        // print_r($account->attributes);
        // exit;



        $user->ensureProfileAccess(true);

        // echo "Newq account info";
        // print_r($account);

        // echo "about to update an existing user";
        // $user->debug();
        // exit;



    }

    protected function mergeUsers(array $users) {
        throw new \Exception('User merge not implemented (yet)');

    }


    /**
     * [getUser description]
     * @param  Account $account [description]
     * @param  boolean $update  [description]
     * @param  boolean $create  [description]
     * @param  boolean $merge   [description]
     * @return [type]           [description]
     */
    public function getUser(Account $account, $update = false, $create = false, $merge = false) {

        $userIDs = $account->getUserIDs();
        $existingUser = $this->repo->getUserByUserIDsecList($userIDs);




        // if ($update) {echo "UPDATE "; } else { echo "update "; }
        // if ($create) {echo "CREATE "; } else { echo "create "; }
        // if ($merge) {echo "MERGE "; } else { echo "merge "; }
        // echo "\n\n";

        // echo "UserIDs\n"; print_r($account->getUserIDs()); echo "\n ";
        // echo "getSourceID\n"; print_r($account->getSourceID()); echo "\n ";
        // echo "getName\n"; print_r($account->getName()); echo "\n ";
        // echo "getMail\n"; print_r($account->getMail()); echo "\n ";

        // if ($account->photo) {
        //     echo "Photo hash: \n"; print_r($account->photo->getHash());
        //     $photo = $account->getPhoto();
        //     // echo "<img style='display:block; ' id='base64image' src='data:image/jpeg;charset=utf-8;base64, " . base64_encode($photo) . "' />";
        // }
        // echo "\n\n";



        // print_r($existingUser);
        // exit;

        if ($existingUser === null) {
            if ($create) {
                return $this->createUser($account);
            }
            return null;
        }



        if (count($existingUser) === 1) {
            if ($update) {
                $this->updateUser($account, $existingUser[0]);
            }

            // header('content-type: text/plain');
            // echo "poot";
            // print_r($existingUser); exit;

            return $existingUser[0];
        }


        if (count($existingUser) > 1) {
            if ($merge) {
                $user = $this->mergeUsers($existingUser);
                if ($update) {
                    $this->updateUser($account, $user);
                }
                return $user;
            }

            // For debugging creae a list of users
            $userlist = [];
            foreach ($existingUser as $u) {
                $userlist[] = $u->getAsArray();
            }

            Logger::warning('Found more than one user (from seckeys), without merging. Returns first match.', array(
                'useridsec' => $userIDs,
                'users' => $userlist
            ));
            return $existingUser[0];

        }

        return null;
    }

}
