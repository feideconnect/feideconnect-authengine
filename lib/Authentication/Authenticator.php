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
            Logger::info("Passive authentication rejected due to client configuration", ['client' => $client]);
            $this->failPassive();
        }
        foreach ($this->authSources as $authType => $as) {
            if ($as->isAuthenticated()) {
                if ($this->verifyMaxAge($as->getAuthData("AuthnInstant"), $maxage)) {
                    $this->activeAuthType = $authType;
                    return;
                } else {
                    Logger::info("Passive authentication rejected due to max age", ['client' => $client]);
                    $this->failPassive();
                }
            }
        }
        Logger::debug("Sending passive authentication request", ['client' => $client]);
        $as = $this->authSources['saml'];
        $as->login([
            'isPassive' => true,
            'saml:idp' => Config::getValue("feideIdP"),
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
    public function requireAuthentication($maxage = null, $acr_values = null, $login_hint = null) {

        $accountchooser = new Authentication\AccountChooserProtocol();
        $accountchooser->setClientID($this->clientid);
        $accountchooser->setLoginHint($login_hint);

        if (!$accountchooser->hasResponse()) {
            $requestURL = $accountchooser->getRequest();
            throw new RedirectException($requestURL);
        }
        $authconfig = $accountchooser->getAuthConfig();
        $response = $accountchooser->getResponse();

        if (!isset($this->authSources[$authconfig["type"]])) {
            throw new \Exception("Attempting to authenticate using an authentication source that is not initialized.");
        }

        $this->activeAuthType = $authconfig["type"];
        $as = $this->authSources[$this->activeAuthType];

        $forceauthn = false;
        $authGood = true;

        if ($as->isAuthenticated()) {
            $mismatchingAccounts = false;

            // First check if we are authenticated using the expected IdP, as the user has seleted some.
            if (isset($authconfig['idp'])) {
                $account = $this->getAccount();

                if ($account->idp !== $authconfig['idp']) {
                    // We have logged in with another IdP, and we need to logout.
                    $mismatchingAccounts = true;

                } else if (isset($authconfig['subid']) && $authconfig['subid'] !== $account->realm) {
                    // If the user is logged in with another Feide realm than the correct one.
                    // Logout the user, before logging in again.
                    $mismatchingAccounts = true;

                } else if (isset($response["userids"]) && !$account->hasAnyOfMaskedUserIDs($response["userids"]) ) {
                    // We are authenticated, but requested to authenticate with a specific userid...
                    $mismatchingAccounts = true;
                }

                // If the discovery user interactiuon ends up with enforcing a logout even if the account does not match
                if (isset($response['logout']) && isset($_REQUEST['strict']) && $_REQUEST['strict'] === '1') {
                    $this->logoutAS($as);

                } else if ($mismatchingAccounts) {
                    if (!isset($_REQUEST['strict'])) {
                        $unexpectedUser = new UnexpectedUserUI($account, $response);
                        return $unexpectedUser->show();

                    } else if (isset($_REQUEST['strict']) && $_REQUEST['strict'] === '1') {
                        $this->logoutAS($as);
                    }
                }

            }

            if (!$this->verifyMaxAge($as->getAuthData("AuthnInstant"), $maxage)) {
                Logger::debug('Forcing new login because of maxage requirement');
                $forceauthn = true;
                $authGood = false;
            }
            if (!empty($acr_values) && !in_array($account->getAcr(), $acr_values)) {
                Logger::info('Force login due to acr', [
                    'requested_acrs' => $acr_values,
                    'current_acr' => $account->getAcr(),
                ]);
                $authGood = false;
            }

            if ($authGood) {
                return null;
            }

        }

        $options = array();

        if (isset($authconfig["idp"])) {
            $options["saml:idp"] = $authconfig["idp"];
        }

        $preselectEndpoints = [
            'https://idp-test.feide.no' => 'https://idp-test.feide.no/simplesaml/module.php/feide/preselectOrg.php',
            'https://idp.feide.no' => 'https://idp.feide.no/simplesaml/module.php/feide/preselectOrg.php'
        ];


        /*
         * Make sure we handle preselet org with Feide.
         * Will only execute if not already authenticated, and if Feide is selected and a preselect endpoint is configured.
         */
        if (!$as->isAuthenticated() && !(empty($authconfig["idp"])) && $authconfig["idp"] === Config::getValue('feideIdP') && $preselectEndpoints[$authconfig["idp"]]) {

            $preselectEndpoint = $preselectEndpoints[$authconfig["idp"]];

            // Prevent redirect loop. Only execute if not already executed.
            if (!isset($_REQUEST['preselected']) && isset($authconfig["subid"])) {

                $returnTo = \SimpleSAML_Utilities::addURLparameter(\SimpleSAML_Utilities::selfURL(), array(
                    "preselected" => "1"
                ));
                $preselectAction = \SimpleSAML_Utilities::addURLparameter($preselectEndpoint, array(
                    "HomeOrg" => $authconfig["subid"],
                    "ReturnTo" => $returnTo,
                ));
                throw new RedirectException($preselectAction);

            }


        }

        if ($acr_values != null && !(empty($authconfig["idp"])) && $authconfig["idp"] === Config::getValue('feideIdP')) {
            $options['saml:AuthnContextClassRef'] = $acr_values;
            $options['ErrorURL'] = \SimpleSAML_Utilities::addURLparameter(\SimpleSAML_Utilities::selfURL(), array(
                "error" => 2,
            ));
            Logger::info("added acr values", ['acr_values' => $acr_values ]);
        }

        if ($forceauthn) {
            $options['ForceAuthn'] = true;
        }

        $authSource = $this->authTypes[$authconfig['type']]['authSource'];
        $idp = isset($authconfig['idp']) ? $authconfig['idp'] : null;
        $authCountType = AttributeMapper::getSourcePrefix($authSource, $idp);
        if ($authCountType === 'feide') {
            if (isset($authconfig['subid'])) {
                $authCountType = 'feide.' . str_replace('.', '_', $authconfig['subid']);
            } else {
                $authCountType = 'feide.any';
            }
        }
        $statsd = \FeideConnect\Utils\Statsd::getInstance();
        $statsd->increment('auth_sent.' . $authCountType);

        $as->login($options);

        return null;

    }



    public function logoutAS($as) {
        // The strict paramter indicates that we will logout without asking the user if the discovery response
        // Does not match the authenticated account. However this needs to be reset when we actually perform logout.
        $as->logout(URL::selfURLfilterQuery(['strict']));
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

    /*
     * Returns a list of accounts from all authsources.
     */
    public function getAllAccountsVisualTags() {

        $accounts = [];
        foreach ($this->authSources as $authSourceType => $authSource) {
            if ($authSource->isAuthenticated()) {
                // $this->logoutAS($authSource);
                $acct = $this->getAccountFromAuthSource($authSourceType);
                $accounts[] = $acct->getVisualTag();
            }

        }
        return $accounts;
    }

    /*
     * Returns a single account if authenticated, for the current actice authentication source
     */
    public function getAccount() {

        if (empty($this->activeAuthType) || !isset($this->authSources[$this->activeAuthType])) {
            throw new \Exception("Attempting to getAccount() when there is no active auth source");
        }
        return $this->getAccountFromAuthSource($this->activeAuthType);
    }

    /*
     * Used for debugging.
     */
    public function getRawAttributes() {

        if (empty($this->activeAuthType) || !isset($this->authSources[$this->activeAuthType])) {
            throw new \Exception("Attempting to getRawAttributes() when there is no active auth source");
        }
        $as = $this->authSources[$this->activeAuthType];
        $attributes = $as->getAttributes();
        return $attributes;
    }

    protected static function getCountryByReg($reg) {
        $feds = Config::getValue('federations');
        foreach($feds AS $fed) {
            if ($fed['regauth'] === $reg) {
                return $fed['country'];
            }
        }
        return null;
    }

    protected function getAccountFromAuthSource($activeAuthType) {

        $as = $this->authSources[$activeAuthType];
        $attributes = $as->getAttributes();
        $attributes['idp'] = $as->getAuthData('saml:sp:IdP');
        $attributes['authSource'] = $this->authTypes[$activeAuthType]["authSource"];
        $attributes['AuthnInstant'] = $as->getAuthData("AuthnInstant");

        if (!empty($attributes['idp'])) {
            try {
                $metastore = new \sspmod_cassandrastore_MetadataStore_CassandraMetadataStore([]);
                $entity = $metastore->getEntity('edugain', $attributes['idp']);
                $attributes['idpMeta'] = $entity;
                if (isset($entity['RegistrationInfo']) && isset($entity['RegistrationInfo']['registrationAuthority'])) {
                    $regauth = $entity['RegistrationInfo']['registrationAuthority'];
                    $attributes['country'] = self::getCountryByReg($regauth);
                }

            } catch (\Exception $e) {
                Logger::error('Error fetching metadata for IdP in getAccountFromAuthSource().', array(
                    'idp' => $attributes['idp']
                ));
            }

        }

        $attributeMapper = new AttributeMapper();
        $account = $attributeMapper->getAccount($attributes);
        return $account;
    }


}
