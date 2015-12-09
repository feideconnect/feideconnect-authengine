<?php

namespace FeideConnect\Authentication;

use FeideConnect\Data\StorageProvider;
use FeideConnect\HTTP\LocalizedTemplatedHTMLResponse;
use FeideConnect\HTTP\JSONResponse;

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
     * @param [type] $expected             Expected user (visual Tag format)
     */
    public function __construct($authenticatedAccount, $expected) {

        // $this->client = $client;
        $this->authenticatedAccount = $authenticatedAccount;
        $this->expected = $expected;

        $this->storage = StorageProvider::getStorage();

    }


    public function show() {


        $data = [
            "current" => $this->authenticatedAccount->getVisualTag(),
            "expected" => $this->expected,
            "urlcontinue" =>  Utils\URL::selfURL() . '&strict=0',
            "urllogout" =>  Utils\URL::selfURL() . '&strict=1',
        ];
        $data["current"]["photo"] = $this->authenticatedAccount->getPhoto();

        // var_dump($data);

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
