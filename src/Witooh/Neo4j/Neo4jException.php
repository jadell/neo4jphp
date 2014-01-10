<?php
namespace Witooh\Neo4j;

class Neo4jException extends \Exception {

    protected $messages;

    /**
     * @param array $messages
     */
    public function __construct($messages)
    {
        parent::__construct($messages, 501);
    }
} 