<?php

namespace tests;

class RawCassandra2 extends \FeideConnect\Data\Repositories\Cassandra2 {
    public function rawExecute($query, $params) {
        $this->db->querySync(
            $query,
            $params,
            \Cassandra\Request\Request::CONSISTENCY_QUORUM,
            [
                'names_for_values' => true
            ]
        );
    }
    public function rawQuery($query, $params) {
        $response = $this->db->querySync(
            $query,
            $params,
            \Cassandra\Request\Request::CONSISTENCY_QUORUM,
            [
                'names_for_values' => true
            ]
        );
        return $response->fetchAll();
    }
}
