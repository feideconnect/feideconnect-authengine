<?php

/**
 * Feide Connect Auth Engine - main endpoint
 *
 * This file includes routing for the Feide Connect Auth Engine.
 */
namespace FeideConnect;

use FeideConnect\OAuth\Exceptions\APIAuthorizationException;
use FeideConnect\Exceptions\Exception;
use FeideConnect\Exceptions\RedirectException;

use FeideConnect\HTTP\HTTPResponse;
use FeideConnect\HTTP\EmptyResponse;
use FeideConnect\HTTP\TemplatedHTMLResponse;
use FeideConnect\Router;
use Phroute\Phroute;

require_once(dirname(dirname(__FILE__)) . '/lib/_autoload.php');


try {
    /*
     * Phroute does not support dealing with OPTIONS and CORS in an elegant way,
     * so here we handle this separately.
     */

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        $response = new EmptyResponse();
        $response->setCORS(true)->setCachable(true);

    } else {
        $router = new Router();
        $response = $router->dispatch();

    }



} catch (Phroute\Exception\HttpRouteNotFoundException $e) {
    $data = array();
    $data['code'] = '404';
    $data['head'] = 'Not Found';
    $data['message'] = $e->getMessage();

    $response = (new TemplatedHTMLResponse('exception'))->setData($data);
    $response->setStatus(404);

} catch (RedirectException $e) {
    $response = $e->getHTTPResponse();

} catch (APIAuthorizationException $e) {
    $response = $e->getJSONResponse();

    Logger::error('Error processing request: ' . $e->getMessage(), array(
        'stacktrace' => $e->getTrace(),
        'errordetails' => $e->getData(),
    ));


} catch (Exception $e) {
    $data = $e->prepareErrorMessage();
    $response = (new TemplatedHTMLResponse('exception'))->setData($data);

    Logger::error('Feide Connect Exception: ' . $e->getMessage(), array(
        'stacktrace' => $e->getTrace(),
        'errordetails' => $data,
    ));

} catch (\Exception $e) {
    $data = array();
    $data['code'] = '500';
    $data['head'] = 'Internal Error';
    $data['message'] = $e->getMessage();

    Logger::error('General Exception: ' . $e->getMessage(), array(
        'stacktrace' => $e->getTrace(),
        'errordetails' => $data,
    ));

    $response = (new TemplatedHTMLResponse('exception'))->setData($data);
}


if (!($response instanceof HTTPResponse)) {
    $response = (new TemplatedHTMLResponse('exception'))->setData([
        "head" => 'No proper HTTP response was returned. This should never have happened :)'
    ]);
}

echo $response->send();



profiler_status($_SERVER['REQUEST_METHOD']);
