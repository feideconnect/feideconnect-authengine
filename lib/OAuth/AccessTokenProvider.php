<?php

namespace FeideConnect\OAuth;
use FeideConnect\Data\StorageProvider;

use FeideConnect\Data\Models\User;
use FeideConnect\Data\Models\Client;
use FeideConnect\Data\Models\AccessToken;


// DEPRECATED???

class AccessTokenProvider {


	protected $storage, $user, $client, $tokens;

	function __construct(User $user, Client $client) {
		$this->user = $user;
		$this->client = $client;
		$this->storage = StorageProvider::getStorage();

		$t = $this->storage->getAccessTokens($user->userid, $client->id);
		$this->tokens = new AccessTokenSet($t);

	}

	public function getTokens() {
		return $this->tokens;
	}


	public function provideToken() {

		$candidates = $this->tokens->dup();
		


	}


}