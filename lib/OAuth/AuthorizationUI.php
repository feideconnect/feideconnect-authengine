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


    // $client, $request, $account, $user, $redirect_uri, $scopesInQuestion, $ae->getRemainingScopes(), $organization
    function __construct($client, $request, $account, $user, $redirect_uri, $scopesInQuestion, $ae, $organization) {

        $this->client = $client;
        $this->request = $request;
        $this->account = $account;
        $this->user = $user;
        $this->redirect_uri = $redirect_uri;
        $this->scopesInQuestion = $scopesInQuestion;
        $this->ae = $ae; // ->getRemainingScopes()
        $this->remainingScopes = $ae->getRemainingScopes();
        $this->organization = $organization;
        $this->storage = StorageProvider::getStorage();


    }


    function show() {



        $postattrs = $_REQUEST;
        $postattrs['client_id'] = $this->client->id;
        $postattrs['verifier'] = $this->user->getVerifier();
        // $postattrs['scopes'] = $scopestr;
        // $postattrs['return'] = Utils\URL::selfURL();

        $firsttime = !($this->user->usageterms); // || true;
        if (!$firsttime) {
            $postattrs['bruksvilkar'] = 'yes';
        }


        $postdata = array();
        foreach($postattrs AS $k => $v) {
            $postdata[] = array('key' => $k, 'value' => $v);
        }


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
        if ($firsttime) {
            $bypass = false;
        }

        // echo '<p>Is mandatory'; var_dump($isMandatory);
        // echo '<p>needs'; var_dump($needs);
        // echo '<p>firsttime'; var_dump($firsttime);
        // echo '<p>Simple view '; var_dump($simpleView);
        // echo '<p>bypass '; var_dump($bypass);
        // // echo '<p>Client '; var_dump($this->client);
        // exit;


        // echo 'Realm is ' . $account->getRealm(); exit;


        // $isMandatory = MandatoryClientInspector::isClientMandatory($account, $client);


        $userinfo = $this->user->getBasicUserInfo(true);
        $userinfo['userid'] = $this->user->userid;
        $userinfo['p'] = $this->user->getProfileAccess();

        // echo '<pre>'; print_r($userinfo); exit;

        $visualTag = $this->account->getVisualTag();
        $visualTag["photo"] = '/user/media/' . $userinfo["p"];
        $visualTag["photo"] = Config::dir('userinfo/v1/user/media/' . $userinfo["p"], "", "core");
        $visualTag['rememberme'] = false;



        // echo 'Visual tag'; var_dump($visualTag); exit;


        $acresponse = [];
        if (isset($_REQUEST['acresponse'])) {
            $acresponse = json_decode($_REQUEST['acresponse'], true);
        }
        // echo '<pre>'; var_dump($acresponse); exit;
        if (isset($acresponse["rememberme"]) && $acresponse["rememberme"]) {
            $visualTag['rememberme'] = true;
        }



        // echo '<pre>'; print_r($visualTag); exit;


        $data = [
            'perms' => $scopesInspector->getInfo(),
            'user' => $userinfo,
            // 'posturl_' => Utils\URL::selfURLNoQuery(), // Did not work with php-fpm, needs to check out.
            'posturl' => Utils\URL::selfURLhost() . '/oauth/authorization',
            'postdata' => $postdata,
            'client' => $this->client->getAsArrayLimited(["id", "name", "descr", "redirect_uri", "scopes"]),
            'HOST' => Utils\URL::selfURLhost(),
            'visualTag' => json_encode($visualTag),
            'rememberme' => false,
        ];


        // var_dump($visualTag); exit;
        
        $data['needsAuthorization'] = $needs;


        $data['client']['host'] = Utils\URL::getURLhostPart($this->redirect_uri);
        $data['client']['isSecure'] = Utils\URL::isSecure($this->redirect_uri); // $oauthclient->isRedirectURISecured();

        $data['simpleView'] = $simpleView;
        $data['bodyclass'] = '';
        if ($simpleView) {
            $data['bodyclass'] = 'simpleGrant';
        }
        if ($bypass) {
            $data['bodyclass'] .= ' bypass';
        }
        $data['firsttime'] = $firsttime;
        $data['organization'] = $this->organization;
        $data['validated'] = $isMandatory;

        $data["apibase"] = Config::getValue("endpoints.core");


        // echo '<pre>'; 
        // var_dump($this->account);
        // var_dump($visualTag);
        // exit;


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




        Logger::info('OAuth About to present authorization dialog.', array(
            'authorizationDialogData' => $data
        ));



        if (isset($_REQUEST['debug'])) {
            return (new JSONResponse($data))->setCORS(false);
        }
        
        $response = new LocalizedTemplatedHTMLResponse('oauthgrant');
        $response->setData($data);
        return $response;

    }





}
