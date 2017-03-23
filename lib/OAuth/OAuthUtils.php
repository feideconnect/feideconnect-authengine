<?php

namespace FeideConnect\OAuth;

use FeideConnect\Logger;

class OAuthUtils {
    public static function generateTokenResponse($client, $user, $scopes, $apigk_scopes, $flow, $state = null, $idtoken = null, $acr = null) {

        $expires_in = 3600*8; // 8 hours
        if (in_array('longterm', $scopes, true)) {
            $expires_in = 3600*24*680; // 680 days
        }

        $pool = new AccessTokenPool($client, $user);
        $accesstoken = $pool->getToken($scopes, $apigk_scopes, $expires_in, $acr);
        // TODO Verify that this saveToken was successfull before continuing.

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
