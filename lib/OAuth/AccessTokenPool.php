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


    private function getSelectedCandidate($scopesInQuestion, $expires_in) {
        $candidates = $this->getCandidates($scopesInQuestion);
        if (empty($candidates)) {
            return null;
        }

        usort($candidates, ['\FeideConnect\Data\Models\AccessToken', 'lifetimeCmp']);
        $candidate = $candidates[0];
        if ($candidate->validuntil->getInSeconds() < $expires_in/2) {
            return null;
        }
        return $candidate;
    }


    public function getToken($scopesInQuestion, $expires_in) {
        $candidate = $this->getSelectedCandidate($scopesInQuestion, $expires_in);

        if ($candidate !== null) {
            return $candidate;
        }

        $accesstoken = Models\AccessToken::generate($this->client, $this->user, $scopesInQuestion, $expires_in);
        $this->storage->saveToken($accesstoken);

        return $accesstoken;
    }

}
