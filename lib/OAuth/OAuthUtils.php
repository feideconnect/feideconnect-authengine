<?php

namespace FeideConnect\OAuth;

use FeideConnect\Data\Models;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\Types\Timestamp;
use FeideConnect\Logger;

class OAuthUtils {
    public static function generateToken($client, $user, $scopes, $apigkScopes, $expires_in, $acr = null) {
        $storage = StorageProvider::getStorage();

        $validUntil = (new Timestamp())->addSeconds($expires_in);
        $accesstoken = Models\AccessToken::generate($client, $user, null, $scopes, $validUntil);
        $accesstoken->acr = $acr;

        if (!empty($apigkScopes)) {
            $subtokens = [];
            foreach ($apigkScopes as $apigkid => $scopes) {
                $subtoken = Models\AccessToken::generate($client, $user, $apigkid, $scopes, $validUntil);
                $subtokens[$apigkid] = $subtoken->access_token;
                $storage->saveToken($subtoken);
            }
            $accesstoken->subtokens = $subtokens;
        }

        $storage->saveToken($accesstoken);
        // TODO Verify that this saveToken was successfull before continuing.
        return $accesstoken;
    }

    public static function generateTokenResponse($client, $user, $scopes, $apigkScopes, $flow, $state = null, $idtoken = null, $acr = null) {

        $expires_in = 3600*8; // 8 hours
        if (in_array('longterm', $scopes, true)) {
            $expires_in = 3600*24*680; // 680 days
        }

        $accesstoken = self::generateToken($client, $user, $scopes, $apigkScopes, $expires_in, $acr);

        $tokenresponse = Messages\TokenResponse::generate($accesstoken, $state, $idtoken);

        $logdata = array(
            'flow' => $flow,
            'client' => $client,
            'accesstoken' => $accesstoken,
            'tokenresponse' => $tokenresponse,
        );
        if ($user !== null) {
            $logdata['user'] = $user;
        }
        Logger::info('OAuth Access Token is now issued.', $logdata);

        $statsd = \FeideConnect\Utils\Statsd::getInstance();
        $stats_flow = str_replace(' ', '_', strtolower($flow));
        $statsd->increment('token_issued.' . $stats_flow);

        return $tokenresponse;
    }
}
