<?php


namespace FeideConnect\Controllers;

use FeideConnect\HTTP\JSONResponse;

class Status {


    public static function status() {
        $STATUS_VARS = [
            'GIT_COMMIT',
            'JENKINS_BUILD_NUMBER',
            'DOCKER_SERVICE',
            'DOCKER_HOST',
            'DOCKER_INSTANCE',
        ];

        if (!empty($_SERVER['HTTP_X_DP_STATUS_TOKEN']) and
            $_SERVER['HTTP_X_DP_STATUS_TOKEN'] === getenv('FC_STATUS_TOKEN')) {
            $data = [];
            foreach ($STATUS_VARS as $var) {
                $val = getenv($var);
                if ($val) {
                    $data[$var] = $val;
                }
            }
            return new JSONResponse(['info' => $data]);
        }
        $res = new JSONResponse(['error' => 'Missing or invalid status token']);
        $res->setStatus(403);
        return $res;
    }
}
