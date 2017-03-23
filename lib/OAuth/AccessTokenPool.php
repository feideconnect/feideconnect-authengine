<?php

namespace FeideConnect\OAuth;

use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Types\Timestamp;
use FeideConnect\Data\Models;
use FeideConnect\Utils\Misc;

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

    private function getCandidates($scopesInQuestion, $acr) {
        $candidates = [];

        foreach ($this->tokens as $token) {
            if ($token->hasExactScopes($scopesInQuestion)) {
                if ($token->acr == $acr) {
                    $candidates[] = $token;
                }
            }
        }
        return $candidates;
    }


    private function getSelectedCandidate($scopesInQuestion, $apigkScopes, $expires_in, $acr) {
        $candidates = $this->getCandidates($scopesInQuestion, $acr);
        if (empty($candidates)) {
            return null;
        }

        usort($candidates, ['\FeideConnect\Data\Models\AccessToken', 'lifetimeCmp']);
        $candidate = $candidates[0];
        if ($candidate->validuntil->getInSeconds() < $expires_in/2) {
            return null;
        }
        $requestApigkids = array_keys(Misc::ensureArray($apigkScopes));
        $tokenApigkids = array_keys(Misc::ensureArray($candidate->subtokens));

        if (!Misc::containsSameElements($requestApigkids, $tokenApigkids)) {
            return null;
        }

        if (!empty($candidate->subtokens)) {
            foreach ($candidate->subtokens as $apigkid => $subtokenid) {
                $subtoken = $this->storage->getAccessToken($subtokenid);
                if (!$subtoken->hasExactScopes($apigkScopes[$apigkid])) {
                    return null;
                }
            }
        }
        return $candidate;
    }


    public function getToken($scopesInQuestion, $apigkScopes, $expires_in, $acr = null) {
        $candidate = $this->getSelectedCandidate($scopesInQuestion, $apigkScopes, $expires_in, $acr);

        if ($candidate !== null) {
            return $candidate;
        }

        $validUntil = (new Timestamp())->addSeconds($expires_in);
        $accesstoken = Models\AccessToken::generate($this->client, $this->user, null, $scopesInQuestion, $validUntil);
        $accesstoken->acr = $acr;

        if (!empty($apigkScopes)) {
            $subtokens = [];
            foreach ($apigkScopes as $apigkid => $scopes) {
                $subtoken = Models\AccessToken::generate($this->client, $this->user, $apigkid, $scopes, $validUntil);
                $subtokens[$apigkid] = $subtoken->access_token;
                $this->storage->saveToken($subtoken);
            }
            $accesstoken->subtokens = $subtokens;
        }

        $this->storage->saveToken($accesstoken);

        return $accesstoken;
    }

}
