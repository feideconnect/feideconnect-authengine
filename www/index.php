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
use FeideConnect\Logger;
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

} catch (Phroute\Exception\HttpMethodNotAllowedException $e) {
    $response = new TemplatedHTMLResponse('exception');
    $response->setData([
        'code' => '405',
        'head' => 'Method not allowed',
        'message' => 'Unsupported HTTP message used',
    ]);
    $header = $e->getMessage();
    $parts = explode(": ", $header);
    $response->setHeader($parts[0], $parts[1]);
    $response->setStatus(405);

} catch (Exception $e) {
    $response = $e->getResponse();
} catch (\Exception $e) {
    $response = Exception::fromException($e)->getResponse();
}


if (!($response instanceof HTTPResponse)) {
    $response = (new TemplatedHTMLResponse('exception'))->setData([
        "head" => 'No proper HTTP response was returned. This should never have happened :)'
    ]);
}

$response->setHeader('X-Request-Id', Logger::requestId());
echo $response->send();



profiler_status($_SERVER['REQUEST_METHOD']);
