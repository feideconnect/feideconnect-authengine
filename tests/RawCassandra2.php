<?php

namespace tests;

class RawCassandra2 extends \FeideConnect\Data\Repositories\Cassandra2 {
    public function rawExecute($query, $params) {
        $statement = new \Cassandra\SimpleStatement($query);
        $options = new \Cassandra\ExecutionOptions([
            'arguments' => $params,
            'consistency' => \Cassandra::CONSISTENCY_QUORUM,
        ]);
        $this->db->execute($statement, $options);
    }
    public function rawQuery($query, $params) {
        $statement = new \Cassandra\SimpleStatement($query);
        $options = new \Cassandra\ExecutionOptions([
            'arguments' => $params,
            'consistency' => \Cassandra::CONSISTENCY_QUORUM,
        ]);
        $response = $this->db->execute($statement, $options);
        return $response;
    }
}
