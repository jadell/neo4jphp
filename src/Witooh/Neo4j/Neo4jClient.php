<?php
namespace Witooh\Neo4j;

use App;

class Neo4jClient {

    /**
     * @var \Witooh\Neo4j\Cypher\Query
     */
    protected $cypher;
    /**
     * @var \Witooh\Neo4j\Index\Index
     */
    protected $index;

    public function __construct()
    {
        $this->cypher = App::make('neo4j.cypher.query');
        $this->index = App::make('neo4j.index');
    }

    /**
     * @return \Witooh\Neo4j\Cypher\Query
     */
    public function cypher()
    {
        return $this->cypher->makeQuery();
    }

    public function index()
    {
        return $this->index;
    }
} 