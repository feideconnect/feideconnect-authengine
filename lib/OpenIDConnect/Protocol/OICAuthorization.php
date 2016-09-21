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


    public function __construct(Messages\Message $request) {

        parent::__construct($request);

        if (!($this->request instanceof Messages\AuthorizationRequest)) {
            throw new \Exception("Invalid request object type");
        }

    }

    protected function evaluateStepUp($aevaluator) {
        return null;
    }


    protected function getIDToken() {
        $openid = new \FeideConnect\OpenIDConnect\OpenIDConnect();
        $iat = $this->account->getAuthInstant();
        // echo '<pre>iat'; print_r($iat); exit;
        $idtoken = $openid->getIDtoken($this->user->userid, $this->client->id, $iat);
        if (isset($this->request->nonce)) {
            $idtoken->set('nonce', $this->request->nonce);
        }
        return $idtoken;
    }

    protected function processToken() {

        $redirect_uri = $this->aevaluator->getValidatedRedirectURI();
        $scopesInQuestion = $this->aevaluator->getScopesInQuestion();
        $apigkScopes = $this->aevaluator->getAPIGKscopes();

        $idtoken = $this->getIDToken();
        $tokenresponse = OAuthUtils::generateTokenResponse(
            $this->client,
            $this->user,
            $scopesInQuestion,
            $apigkScopes,
            "OpenID Connect implicit grant",
            $this->request->state,
            $idtoken->getEncoded()
        );


        return $tokenresponse->getRedirectResponse($redirect_uri, true);

    }



    protected function processCode() {

        $scopesInQuestion = $this->aevaluator->getScopesInQuestion();
        $apigkScopes = $this->aevaluator->getAPIGKscopes();

        if (empty($this->request->redirect_uri)) {
            throw new \Exception("Missing OpenID Connect required parameter [redirect_uri] at the authorization endpoint");
        }

        $redirect_uri = $this->aevaluator->getValidatedRedirectURI();


        $idtoken = $this->getIDToken();
        $code = AuthorizationCode::generate($this->client, $this->user, $redirect_uri, $scopesInQuestion, $apigkScopes, $idtoken);
        $this->storage->saveAuthorizationCode($code);

        $authorizationresponse = Messages\AuthorizationResponse::generate($this->request, $code);

        Logger::debug('OAuth Authorization Code is now stored, and may be fetched via the token endpoint.', array(
            'user' => $this->user,
            'client' => $this->client,
            'code' => $code,
            'authorizationresponse' => $authorizationresponse,
        ));

        return $authorizationresponse->getRedirectResponse($this->request->redirect_uri);

    }


    public function process() {

        if ($this->request->isPassiveRequest()) {
            $this->isPassive = true;
        }

        if ($this->request->loginPromptRequested()) {
            // If forcer authentication is requested by prompt=login, we will transform this into an
            // requirement about a less than 60 (+skew) seconds old authentication session.
            $this->maxage = 60;

        } else if ($this->request->max_age && is_int($this->request->max_age)) {
            $this->maxage = $this->request->max_age;
            if ($this->maxage < 10) {
                $this->maxage = 10;
            }
        }

        $res = $this->preProcess();
        if ($res !== null) {
            return $res;
        }

        switch ($this->request->response_type) {
            case 'id_token token':

                return $this->processToken();

            case 'code':

                return $this->processCode();

        }

        throw new OAuthException('invalid_request', 'Unsupported response_type ' . $this->request->response_type . ". Supported values are 'id_token token' and 'code'");

    }



}
