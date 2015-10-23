<?php

namespace FeideConnect\Authentication;

use FeideConnect\Authentication;
use FeideConnect\Utils\URL;
use FeideConnect\Config;
use FeideConnect\Logger;
use FeideConnect\Exceptions\RedirectException;

/**
 * This class handles all authentication, and uses SimpleSAMLphp for that task.
 * It will also handle all local user creation. All new users will be stored in the user repository.
 *
 */
class Authenticator {

    protected $authSources, $authTypes, $clientid, $activeAuthType;


    public function __construct() {




        $this->authTypes = Config::getValue("authTypes");
        $this->authSources = [];
        $this->clientid = null;

        $this->activeAuthType = null;

        foreach ($this->authTypes as $authtype => $authTypeConfig) {
            $this->authSources[$authtype] = AuthSource::create($authTypeConfig["authSource"]);
            // echo "Creating auth source [" . $authtype . "] using [" . $authTypeConfig["authSource"] . "] ";
        }
    }

    public function setClientID($clientid) {
        $this->clientid = $clientid;
    }

    protected function verifyMaxAge($authninstant, $maxage) {
        if ($maxage === null) {
            return true;
        }
        $now = time();
        $allowSkew = 20; // 20 seconds clock skew accepted.
        $authAge = $now - $authninstant;

        if ($authAge < ($maxage + $allowSkew)) {
            // Already authenticated with a authnetication session which is sufficiently fresh.
            return true;
        }
        Logger::info('OAuth Processing authentication. User is authenticated but with a too old authninstant.', array(
            'now' => $now,
            'authninstant' => $authninstant,
            'maxage' => $maxage,
            'allowskew' => $allowSkew,
            'authage' => $authAge
        ));
        return false;
    }

    protected function failPassive() {
        throw new RedirectException(\SimpleSAML_Utilities::addURLparameter(\SimpleSAML_Utilities::selfURL(), array(
            "error" => 1,
        )));
    }

    public function passiveAuthentication($client, $maxage = null) {
        if ($client->requireInteraction()) {
            $this->failPassive();
        }
        foreach ($this->authSources as $authType => $as) {
            if ($as->isAuthenticated()) {
                if ($this->verifyMaxAge($as->getAuthData("AuthnInstant"), $maxage)) {
                    $this->activeAuthType = $authType;
                    return;
                } else {
                    $this->failPassive();
                }
            }
        }
        $as = $this->authSources['saml'];
        $as->login([
            'isPassive' => true,
            'saml:idp' => Config::getValue("defaultIdP"),
            'ErrorURL' => \SimpleSAML_Utilities::addURLparameter(\SimpleSAML_Utilities::selfURL(), array(
                "error" => 1,
            )),
        ]);
    }

    /**
     * Require authentication of the user. This is meant to be used with user frontend access.
     *
     * @return void
     */
    public function requireAuthentication($maxage = null) {

        $accountchooser = new Authentication\AccountChooserProtocol();

        $accountchooser->setClientID($this->clientid);
        // $accountchooser->debug();
        if (!$accountchooser->hasResponse()) {
            $requestURL = $accountchooser->getRequest();
            throw new RedirectException($requestURL);
        }
        $authconfig = $accountchooser->getAuthConfig();

        if (!isset($this->authSources[$authconfig["type"]])) {
            throw new \Exception("Attempting to authenticate using an authentication source that is not initialized.");
        }

        $this->activeAuthType = $authconfig["type"];
        $as = $this->authSources[$this->activeAuthType];

        // echo '<pre>About to authenticate using ' . $this->activeAuthType . "\n\n"; print_r($authconfig); exit;


        $forceauthn = false;

        if ($as->isAuthenticated()) {
            // First check if we are authenticated using the expected IdP, as the user has seleted some.
            if (isset($authconfig['idp'])) {
                $account = $this->getAccount();
                // var_dump($account); var_dump($authconfig); exit;
                if ($account->idp !== $authconfig['idp']) {
                    $this->logoutAS($as);
                } else if (isset($authconfig['subid']) && $authconfig['subid'] !== $account->realm) {
                    // TODO: This can cause problems for users that does not want to login to the service...
                    // $this->logoutAS($as);
                }

            }

            if ($this->verifyMaxAge($as->getAuthData("AuthnInstant"), $maxage)) {
                return;
            }

            $forceauthn = true;

        }

        $options = array();

        if (isset($authconfig["idp"])) {
            $options["saml:idp"] = $authconfig["idp"];
        }

        // echo '<pre>Options:' ; print_r($options); exit;

        // echo "about to auth " . var_export($options, true); exit;

        if ($forceauthn) {
            $options['ForceAuthn'] = true;
            $as->login($options);
        } else {
            $as->requireAuth($options);
        }

        return;

    }



    public function logoutAS($as) {
        //$as->logout('/loggedout');
        $as->logout();
    }

    public function logout() {


        // $this->authTypes = Config::getValue("authTypes");
        // $this->authSources = [];

        foreach ($this->authSources as $type => $authSource) {
            if ($authSource->isAuthenticated()) {
                $this->logoutAS($authSource);
            }
        }

    }


    public function getAccount() {



        if (empty($this->activeAuthType) || !isset($this->authSources[$this->activeAuthType])) {
            throw new \Exception("Attempting to getAccount() when there is no active auth source");
        }
        $as = $this->authSources[$this->activeAuthType];

        $attributes = $as->getAttributes();
        $attributes['idp'] = $as->getAuthData('saml:sp:IdP');
        $attributes['authSource'] = $this->authTypes[$this->activeAuthType]["authSource"];
        $attributes['AuthnInstant'] = $as->getAuthData("AuthnInstant");


        $attributeMapper = new AttributeMapper();




        // print_r($as); exit;
        $account = $attributeMapper->getAccount($attributes);


        return $account;

    }


}
