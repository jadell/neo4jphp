<?php
namespace Everyman\Neo4j;
use Everyman\Neo4j\Transport as BaseTransport;
//db part of the rest endpoint
DI::register("dbPath", "/db/data");

//register node creation callback
DI::register(
	"Node",
	function (Client $client, $properties=array()) {
	    return new Node($client);
    }
);

//register relationship creation callback
DI::register(
	"Relationship",
	function (Client $client, $properties=array()) {
	    return new Relationship($client);
	}
);

//register transport creation callback
DI::register(
	"transport",
    function($host='localhost', $port=7474) {
        if (extension_loaded("curl")) {
            return new BaseTransport\Curl($host, $port);
        }
        return new BaseTransport\Stream($host, $port);
    },
    true
);


//ensure consistency between the default ssl
//varification with curl and with http stream
DI::register(
    "httpStreamOptions",
    array("ssl" => array(
        "verify_peer" => true,
        "allow_self_signed" => false
    ))
);