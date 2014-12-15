<?php

namespace FeideConnect\Authentication;

use FeideConnect\Logger;
use FeideConnect\Data\Models;

/**
 * This class handles
 *
 *	* Lookup existing user(s) from an account
 *	* Merge users
 *	* Update user objects
 *	* Create user objects
 *
 * Activity is initiated by user authentication of one account.
 * 
 */


class UserMapper {

	protected $repo;

	function __construct($repo) {
		$this->repo = $repo;
	}


	protected function createUser(Account $account) {

		$uuid = \FeideConnect\Data\Model::genUUID();
		$user = new Models\User($this->repo);
		$user->userid = $uuid;

		$user->created = time();

		$user->userid_sec = $account->getUserIDs();
		$user->setUserInfo($account->getSourceID(), $account->getName(), $account->getMail());
		$user->selectedsource = $account->getSourceID();

		$user->userid_sec_seen = array();
		foreach($user->userid_sec AS $u) {
			$user->userid_sec_seen[$u] = time();
		}

		$this->repo->saveUser($user);

		// TODO add source id. userid-sec-seen and more.

		Logger::info('Creating a new user based upon an account', array(
			'uuid' => $uuid
		));

		return $user;

	}

	protected function updateUser(Account $account) {

	}

	protected function mergeUsers(array $users) {


	}


	/**
	 * [getUser description]
	 * @param  Account $account [description]
	 * @param  boolean $update  [description]
	 * @param  boolean $create  [description]
	 * @param  boolean $merge   [description]
	 * @return [type]           [description]
	 */
	function getUser(Account $account, $update = false, $create = false, $merge = false) {

		$userIDs = $account->getUserIDs();
		$existingUser = $this->repo->getUserByUserIDsecList($userIDs);


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
					$updated = $this->updateUser($account, $user);
				}
				return $user;
			}
			Logger::warning('Found more than one user (from seckeys), without merging. Returns first match.', array(
				'useridsec' => $useridsec,
				'userids' => $userids
			));
			return $existingUser[0];

		}

		return null;
	}

}