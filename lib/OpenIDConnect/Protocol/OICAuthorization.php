<?php


namespace FeideConnect\OpenIDConnect\Protocol;

use FeideConnect\OAuth\Exceptions\OAuthException;
use FeideConnect\OAuth\Protocol\OAuthAuthorization;
use FeideConnect\OAuth\Messages;
use FeideConnect\OAuth\AccessTokenPool;
use FeideConnect\OAuth\OAuthUtils;
use FeideConnect\Data\Models\AuthorizationCode;


use FeideConnect\Logger;

class OICAuthorization extends OAuthAuthorization {


    function __construct(Messages\Message $request) {

        parent::__construct($request);

        if (!($this->request instanceof Messages\AuthorizationRequest)) {
            throw new \Exception("Invalid request object type");
        }

    }

    function evaluateStepUp($aevaluator) {
        return null;
    }


    protected function getIDToken() {
        $openid = new \FeideConnect\OpenIDConnect\OpenIDConnect();
        $iat = $this->account->getAuthInstant();
        // echo '<pre>iat'; print_r($iat); exit;
        $idtoken = $openid->getIDtoken($this->user->userid, $this->client->id, $iat);
        return $idtoken;
    }

    protected function processToken() {

        $redirect_uri = $this->aevaluator->getValidatedRedirectURI();
        $scopesInQuestion = $this->aevaluator->getScopesInQuestion();

        $idtoken = $this->getIDToken();
        $tokenresponse = OAuthUtils::generateTokenResponse($this->client, $this->user, $scopesInQuestion,
                                                           "OpenID Connect implicit grant",
                                                           $this->request->state, $idtoken);


        return $tokenresponse->sendRedirect($redirect_uri, true);

    }



    protected function processCode() {

        $scopesInQuestion = $this->aevaluator->getScopesInQuestion();

        if (empty($this->request->redirect_uri)) {
            throw new \Exception("Missing OpenID Connect required parameter [redirect_uri] at the authorization endpoint");
        }

        $redirect_uri = $this->aevaluator->getValidatedRedirectURI();


        $idtoken = $this->getIDToken();
        $code = AuthorizationCode::generate($this->client, $this->user, $redirect_uri, $scopesInQuestion, $idtoken);
        $this->storage->saveAuthorizationCode($code);

        $authorizationresponse = Messages\AuthorizationResponse::generate($this->request, $code);

        Logger::info('OAuth Authorization Code is now stored, and may be fetched via the token endpoint.', array(
            'user' => $this->user,
            'client' => $this->client,
            'code' => $code,
            'authorizationresponse' => $authorizationresponse,
        ));

        return $authorizationresponse->sendRedirect($this->request->redirect_uri);

    }


    public function process() {

        // TODO: Implement strictly require the redirect_uri to be present.

        if ($this->request->isPassiveRequest()) {
            $this->isPassive = true;
        }

        if ($this->request->loginPromptRequested()) {

            // If forcer authentication is requested by prompt=login, we will transform this into an
            // requirement about a less than 60 (+skey) seconds old authentication session.
            $this->maxage = 60;

        } else if ($this->request->max_age && is_int($this->request->max_age)) {

            $this->maxage = $this->request->max_age;
            if ($this->maxage < 10) {
                $this->maxage = 10;
            }

        }


        return parent::process();

    }



}