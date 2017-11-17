<?php

namespace FeideConnect\Authentication;

use FeideConnect\Data\StorageProvider;
use FeideConnect\HTTP\LocalizedTemplatedHTMLResponse;
use FeideConnect\HTTP\JSONResponse;
use FeideConnect\Localization;
use FeideConnect\Utils;
use FeideConnect\Config;
use FeideConnect\Logger;


/*
 * The user has authenticated with another account than the one he/she was supposed to.
 * We need to ask the user to confirm that he/she wants to logout and in again.
 *
 */

class UnexpectedUserUI {

    protected $authenticatedAccount;
    protected $expected;
    protected $storage;

    /**
     * @param [type] $authenticatedAccount Authenticated Account
     * @param [type] $response             Expected user
     */
    public function __construct($authenticatedAccount, $response) {

        $this->storage = StorageProvider::getStorage();
        $this->authenticatedAccount = $authenticatedAccount;
        $this->expected = $this->parseResponse($response);

    }


/*
Example input:
$response = {
     "type": "saml",
     "id": "https:\/\/idp-test.feide.no",
     "subid": "spusers.feide.no",
     "logout": 1,
     "rememberme": true
 },
*/
    private function parseResponse($response) {
        $view = [];
        if (isset($response['userids']) && is_array($response['userids'])) {
            $view["userids"] = $response["userids"];
        } else {
            $view["userids"] = [];
        }
        $x = $this->getDiscoResponseProvider($response);
        if ($x) {
            $view["title"] = $x["title"];
        } else if ($response['id'] === Config::getValue('feideIdP') && isset($response['subid'])) {
            $realm = filter_var($response['subid'], FILTER_SANITIZE_URL);
            $orgid = 'fc:org:' . $realm;
            $org = $this->storage->getOrg($orgid);
            if ($org) {
                $view["org"] = $org->getOrgInfo();
            } else {
                $view['showDefaultTitle'] = true;
            }

        } else {
            $view['showDefaultTitle'] = true;
        }
        return $view;
    }


    private function getDiscoResponseProvider($response) {
        $data = Config::getValue('disco');
        $ldata = Localization::localizeList($data, ['title', 'descr']);
        foreach($ldata AS $entry) {
            if (isset($entry['id']) && $entry['id'] !== $response['id']) {
                continue;
            }
            if (isset($entry['type']) && $entry['type'] !== $response['type']) {
                continue;
            }
            if (isset($entry['subid']) && $entry['subid'] !== $response['subid']) {
                continue;
            }
            return $entry;
        }
        return null;
    }


    public function show() {

        $data = [
            "current" => $this->authenticatedAccount->getVisualTag(),
            "expected" => $this->expected,
            "urlcontinue" =>  Utils\URL::selfURL() . '&strict=0',
            "urllogout" =>  Utils\URL::selfURL() . '&strict=1',
        ];
        // $data["current"]["photo"] = $this->authenticatedAccount->getPhoto();


        Logger::info('OAuth display dialog about conflicting requested and authenticated user.', array(
            'currentUserID' => $data["current"]["userids"],
            'expectedUserID' => $data["expected"]["userids"]
        ));

        if (isset($_REQUEST['debug'])) {
            return (new JSONResponse($data))->setCORS(false);
        }

        $response = new LocalizedTemplatedHTMLResponse('unexpecteduser');
        $response->setData($data);
        return $response;

    }

}
