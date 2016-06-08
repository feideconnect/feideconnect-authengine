<?php

namespace FeideConnect\OAuth;

use FeideConnect\OAuth\Exceptions\UserCannotAuthorizeException;
use FeideConnect\Data\StorageProvider;
use FeideConnect\Data\MandatoryClientInspector;

use FeideConnect\HTTP\LocalizedTemplatedHTMLResponse;
use FeideConnect\HTTP\JSONResponse;

use FeideConnect\Utils;
use FeideConnect\Config;
use FeideConnect\Logger;

class AuthorizationUI {

    protected $fixedBypass = null;
    protected $fixedFirstTime = null;
    protected $fixedSimpleView = null;
    protected $fixedMandatory = null;

    protected $storage;

    protected $client;
    protected $request;
    protected $account;
    protected $user;

    protected $redirect_uri;
    protected $needsAuthorization;
    protected $scopesInQuestion;
    protected $remainingScopes;

    protected $organization;

    public function __construct($client, $request, $account, $user, $ae) {

        $this->storage = StorageProvider::getStorage();

        $this->client = $client;
        $this->request = $request;
        $this->account = $account;
        $this->user = $user;

        $this->authorizationEvaluator = $ae;
        $this->redirect_uri = $ae->getValidatedRedirectURI();
        $this->needsAuthorization = $ae->needsAuthorization();
        $this->scopesInQuestion = $ae->getScopesInQuestion();
        $this->remainingScopes = $ae->getRemainingScopes();
        $this->apigkScopes = $ae->getAPIGKscopes();

        $this->organization = $account->getOrg();
        
    }

    public function setFixedBypass($en) {
        $this->fixedBypass = $en;
        return $this;
    }

    public function setFixedFirstTime($en) {
        $this->fixedFirstTime = $en;
        return $this;
    }

    public function setFixedSimpleView($en) {
        $this->fixedSimpleView = $en;
        return $this;
    }

    public function setFixedMandatory($en) {
        $this->fixedMandatory = $en;
        return $this;
    }


    /*
     * Is this the first time the user is authenticating to a Connect service?
     */
    protected function isFirstTime() {
        if ($this->fixedFirstTime !== null) {
            return $this->fixedFirstTime;
        }
        return !($this->user->usageterms);
    }


    /*
     * Post data is a set of key -> value pairs that will be parmaeters to the HTML form that 
     * represent the Grant dialog.
     */
    public function getPostData(&$data) {
        $postattrs = $_REQUEST;
        $postattrs['client_id'] = $this->client->id;
        $postattrs['verifier'] = $this->user->getVerifier();
        $postattrs['approved_scopes'] = join(" ", $this->scopesInQuestion);
        foreach ($this->apigkScopes as $apigk => $scopes) {
            $postattrs['gk_approved_scopes_' . $apigk] = join(" ", $scopes);
        }
        // $postattrs['return'] = Utils\URL::selfURL();

        if (!$this->isFirstTime()) {
            $postattrs['bruksvilkar'] = 'yes';
        }
        if ($this->user->isFeideUser()) {
            $postattrs['bruksvilkar'] = 'yes';
        }

        $postdata = array();
        foreach ($postattrs as $k => $v) {
            $postdata[] = array('key' => $k, 'value' => $v);
        }

        $data['postdata'] = $postdata;
        $data['posturl']  = Utils\URL::selfURLhost() . '/oauth/authorization';
    }


    /*
     * Get all neccessary info related to the authenticated user.
     * Includes information to display about the user
     * and information to store in the visual tag for the accountchooser.
     */
    public function getUserinfo(&$data) {


        // AccountChooser Response Protocol message.
        $acresponse = [];
        if (isset($_REQUEST['acresponse'])) {
            $acresponse = json_decode($_REQUEST['acresponse'], true);
        }

        $userinfo = $this->user->getBasicUserInfo(true);
        $userinfo['userid'] = $this->user->userid;
        $userinfo['p'] = $this->user->getProfileAccess();

        $visualTag = $this->account->getVisualTag();
        $visualTag["photo"] = Config::dir('userinfo/v1/user/media/' . $userinfo["p"], "", "core");
        $visualTag['rememberme'] = false;
        // echo '<pre>'; var_dump($acresponse); exit;
        if (isset($acresponse["rememberme"]) && $acresponse["rememberme"]) {
            $visualTag['rememberme'] = true;
        }

        $userinfo['isFeideUser'] = $this->user->isFeideUser();

        $data['user'] = $userinfo;
        $data['organization'] = $this->organization;
        $data['hasOrg'] = ($this->organization !== null);
        $data['visualTag'] = json_encode($visualTag);
    }



    /*
     * Get all information related to the client that needs to be displayed 
     */
    public function getClientInfo(&$data) {
        $data['client'] = $this->client->getAsArrayLimited(["id", "name", "descr", "redirect_uri", "scopes", "supporturl", "privacypolicyurl", "homepageurl"]);
        $data['client']['host'] = Utils\URL::getURLhostPart($this->redirect_uri);
        $data['client']['isSecure'] = Utils\URL::isSecure($this->redirect_uri); // $oauthclient->isRedirectURISecured();

    }


    /*
     * Get all information related to the client owner that needs to be displayed 
     */
    public function getClientOwnerInfo(&$data) {

        if ($this->client->has('organization')) {
            $org = $this->storage->getOrg($this->client->organization);
            if ($org !== null) {
                $orginfo = $org->getAsArray();
                $orginfo["logoURL"] = Config::dir("orgs/" . $org->id . "/logo", "", "core");

                $data['ownerOrg'] = true;
                $data['org'] = $orginfo;
                $data['ownerDisplay'] = $orginfo["name"]["nb"];
            }

        } else if ($this->client->has('owner')) {
            $owner = $this->storage->getUserByUserID($this->client->owner);
            if ($owner !== null) {
                $oinfo = $owner->getBasicUserInfo(true);
                $oinfo['p'] = $owner->getProfileAccess();
                $data['owner'] = $oinfo;
                $data['ownerDisplay'] = $oinfo["name"];
            }

        }
    }


    public function getAuthorizationInfo(&$data) {

        $scopesInspector = new ScopesInspector($this->scopesInQuestion, $this->authorizationEvaluator);
        $isMandatory = MandatoryClientInspector::isClientMandatory($this->account, $this->client);

        if ($this->fixedMandatory !== null) {
            $isMandatory = $this->fixedMandatory;
        }

        if (!$isMandatory && $this->user->isBelowAgeLimit()) {
            throw new UserCannotAuthorizeException();
        }

        $simpleView = $isMandatory;
        if (!$this->needsAuthorization) {
            $simpleView = true;
        }

        $bypass = $simpleView;
        if ($this->isFirstTime() && !$this->user->isFeideUser()) {
            $bypass = false;
        }

        if ($this->fixedBypass !== null) {
            $bypass = $this->fixedBypass;
        }

        if ($this->fixedSimpleView !== null) {
            $simpleView = $this->fixedSimpleView;
        }

        $data['perms'] = $scopesInspector->getView();
        $data['permsLongTerm'] = $scopesInspector->isLongTerm();
        $data['needsAuthorization'] = $this->needsAuthorization;
        $data['simpleView'] = $simpleView;
        $data['bodyclass'] = '';
        if ($simpleView) {
            $data['bodyclass'] = 'simpleGrant';
        }
        if ($bypass) {
            $data['bodyclass'] .= ' bypass';
        }
        $data['firsttime'] = $this->isFirstTime();
        $data['validated'] = $isMandatory;
    }



    public function process() {
               
        $data = [];
        $data['rememberme'] = false;
        $data['HOST'] = Utils\URL::selfURLhost();
        $data["apibase"] = Config::getValue("endpoints.core");

        $this->getPostData($data);
        $this->getUserinfo($data);
        $this->getClientinfo($data);
        $this->getClientOwnerInfo($data);
        $this->getAuthorizationInfo($data);

        Logger::info('OAuth About to present authorization dialog.', array(
            'client' => $this->client,
            'user' => $this->user,
            'scopes' => $this->scopesInQuestion,
        ));

        return $data;

    }


    public function show() {

        $data = $this->process();

        if (isset($_REQUEST['debug'])) {
            return (new JSONResponse($data))->setCORS(false);
        }
        if (isset($_REQUEST['debugperms'])) {
            return (new JSONResponse($data['perms']))->setCORS(false);
        }

        $response = new LocalizedTemplatedHTMLResponse('oauthgrant');
        $response->setReplacements(['notvalidated10short', 'notvalidated10', 'validated10short', 'validated10'], [
            "ORG" => $this->organization
        ]);
        $response->setData($data);
        return $response;

    }



}
