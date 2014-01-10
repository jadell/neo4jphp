<?php
namespace Witooh\Neo4j\Cypher;

use Guzzle\Http\Client;
use Witooh\Neo4j\Neo4jException;

class Query {

    protected $uri = '/db/data/cypher';

    protected $queryStr;

    protected $params;
    /**
     * @var \Guzzle\Http\Client
     */
    protected $curl;

    public function __construct($curl)
    {
        $this->curl = $curl;
    }

    /**
     * @param array $objects
     * @return \Witooh\Neo4j\Cypher\Query
     */
    public function create(array $objects)
    {
        $str = " CREATE ";

        $this->queryStr .= $str . implode(',', $objects);
        return $this;
    }

    /**
     * @param array $objects
     * @return $this
     */
    public function match(array $objects)
    {
        $str = " MATCH ";

        $this->queryStr .= $str . implode(',', $objects);
        return $this;
    }

    /**
     * @param string $varA
     * @param string $opr
     * @param string $varB
     * @return string $this
     */
    public function where($varA, $opr, $varB)
    {
        $str = "  AND $varA $opr $varB ";

        $this->queryStr .= $str;
        return $this;
    }


    /**
     * @param string $str
     * @return $this
     */
    public function with($str)
    {
        $this->queryStr .= ' WITH ' . $str;
        return $this;
    }

    /**
     * @param array $object
     * @return $this
     */
    public function set(array $objects)
    {
        $str = " SET ";

        $this->queryStr .= $str . implode(',', $objects);
        return $this;
    }

    /**
     * @param string $varA
     * @param string $opr
     * @param string $varB
     * @return string $this
     */
    public function andWhere($varA, $opr, $varB){
        $str = " WHERE $varA $opr $varB ";

        $this->queryStr .= $str;
        return $this;
    }

    /**
     * @param string $varA
     * @param string $opr
     * @param string $varB
     * @return string $this
     */
    public function orWhere($varA, $opr, $varB){
        $str = " OR $varA $opr $varB ";

        $this->queryStr .= $str;
        return $this;
    }

    /**
     * @param array $objects
     * @return $this
     */
    public function get(array $objects){
        $str = " RETURN ";

        $this->queryStr .= $str . implode(',', $objects);
        return $this;
    }

    /**
     * @param array $objects
     * @return $this
     */
    public function createUnique(array $objects)
    {
        $str = " CREATE UNIQUE ";
        $this->queryStr .= $str . implode(',', $objects);
        return $this;
    }

    /**
     * @param array $objects
     * @return $this
     */
    public function delete(array $objects)
    {
        $str = " DELETE ";
        $this->queryStr .= $str . implode(',', $objects);
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function params(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function run()
    {
        $res = $this->curl->post($this->uri,
            [
                'Accept'=>'application/json; charset=UTF-8',
                'Content-Type'=>'application/json',
            ],
            json_encode([
                'query'=>$this->queryStr,
                'params'=>$this->params,
            ])
        ,['exceptions'=>false])->send();

        if($res->getStatusCode() == 200){
            $data = $res->json();
            return new DataCollection($data['columns'], $data['data']);
        }else{
            throw new Neo4jException($res->json()['message']);
        }
    }

    protected function isParam($str)
    {
        return preg_match('/{[a-zA-Z]+[a-zA-Z0-9_]*}/', $str);
    }

    protected function jsonEncode($str)
    {
        $jsonVal = json_encode($str);
        return preg_replace('/"([a-zA-Z]+[a-zA-Z0-9_]*)":/','$1:',$jsonVal);
    }

    public function toString()
    {
        return $this->queryStr;
    }

    /**
     * @param string $str
     * @return $this
     */
    public function raw($str)
    {
        $this->queryStr = $str;
        return $this;
    }

    /**
     * @return $this
     */
    public function makeQuery()
    {
        $this->queryStr = '';
        $this->params = [];
        return $this;
    }
} 