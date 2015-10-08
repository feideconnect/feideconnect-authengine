<?php

namespace FeideConnect\OAuth;

use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Models;

class AccessTokenPool {

    protected $client, $user, $stoarge;
    protected $tokens;

    public function __construct($client, $user = null) {
        $this->client = $client;
        $this->user = $user;
        $this->storage = StorageProvider::getStorage();

        $this->getTokens();
    }


    private function getTokens() {
        $this->tokens = [];

        $userid = '00000000-0000-0000-0000-000000000000';
        if ($this->user !== null) {
            $userid = $this->user->userid;
        }

        // echo "about to get access token "; print_r($userid); print_r($this->client->id); exit;

        $ct = $this->storage->getAccessTokens($userid, $this->client->id);

        foreach ($ct as $t) {
            if ($t->stillValid()) {
                $this->tokens[] = $t;
            }
        }
    }

    public function getAllTokens() {
        return $this->tokens;
    }

    private function getCandidates($scopesInQuestion) {
        $candidates = [];

        foreach ($this->tokens as $token) {
            if ($token->hasExactScopes($scopesInQuestion)) {
                $candidates[] = $token;
            }
        }
        return $candidates;
    }


    private function getSelectedCandidate($scopesInQuestion) {
        $candidates = $this->getCandidates($scopesInQuestion);
        if (empty($candidates)) {
            return null;
        }

        // TODO Implement policy on which token to select.
        // TODO Also require that the token is valid significantly long into the future
        return $candidates[0];
    }


    public function getToken($scopesInQuestion, $refreshToken, $expires_in) {
        $candidate = $this->getSelectedCandidate($scopesInQuestion);

        if ($candidate !== null) {
            return $candidate;
        }

        $accesstoken = Models\AccessToken::generate($this->client, $this->user, $scopesInQuestion, $refreshToken, $expires_in);
        $this->storage->saveToken($accesstoken);

        return $accesstoken;
    }




}
