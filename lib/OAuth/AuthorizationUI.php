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

    // TODO: Remove scopesinquestion, remainingscopes og organization.

    protected $data;

    // $client, $request, $account, $user, $redirect_uri, $scopesInQuestion, $ae->getRemainingScopes(), $organization
    public function __construct($client, $request, $account, $user, $redirect_uri, $scopesInQuestion, $ae, $organization) {


        $this->storage = StorageProvider::getStorage();

        $this->client = $client;
        $this->request = $request;
        $this->account = $account;
        $this->user = $user;
        $this->redirect_uri = $redirect_uri;
        $this->scopesInQuestion = $scopesInQuestion;
        $this->ae = $ae; // ->getRemainingScopes()
        $this->remainingScopes = $ae->getRemainingScopes();
        $this->organization = $organization;
        
    }


    /*
     * Is this the first time the user is authenticating to a Connect service?
     */
    protected function isFirstTime() {
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
        // $postattrs['scopes'] = $scopestr;
        // $postattrs['return'] = Utils\URL::selfURL();

        if (!$this->isFirstTime()) {
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

        $data['user'] = $userinfo;
        $data['organization'] = $this->organization;
        $data['visualTag'] = json_encode($visualTag);
    }



    /*
     * Get all information related to the client that needs to be displayed 
     */
    public function getClientInfo(&$data) {
        $data['client'] = $this->client->getAsArrayLimited(["id", "name", "descr", "redirect_uri", "scopes"]);
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
            }

        } else if ($this->client->has('owner')) {
            $owner = $this->storage->getUserByUserID($this->client->owner);
            if ($owner !== null) {
                $oinfo = $owner->getBasicUserInfo(true);
                $oinfo['p'] = $owner->getProfileAccess();
                $data['owner'] = $oinfo;
            }

        }
    }



    public function getAuthorizationInfo(&$data) {


        $scopesInspector = new ScopesInspector($this->scopesInQuestion);
        $isMandatory = MandatoryClientInspector::isClientMandatory($this->account, $this->client);
        $needs = $this->ae->needsAuthorization();


        if (!$isMandatory && $this->user->isBelowAgeLimit()) {
            throw new UserCannotAuthorizeException();
        }

        $simpleView = $isMandatory;
        if (!$needs) {
            $simpleView = true;
        }

        $bypass = $simpleView;
        if ($this->isFirstTime()) {
            $bypass = false;
        }

        $data['perms'] = $scopesInspector->getInfo();
        $data['needsAuthorization'] = $needs;
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

        $response = new LocalizedTemplatedHTMLResponse('oauthgrant');
        $response->setData($data);
        return $response;

    }





}
