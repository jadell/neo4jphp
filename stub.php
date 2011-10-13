<?php
Phar::mapPhar('neo4jphp.phar');
spl_autoload_register(function ($className) {
	$libPath = 'phar://neo4jphp.phar/lib/';
	$classFile = str_replace('\\',DIRECTORY_SEPARATOR,$className).'.php';
	$classPath = $libPath.$classFile;
	if (file_exists($classPath)) {
		require($classPath);
	}
});

if ('cli' === php_sapi_name() && basename(__FILE__) === basename($_SERVER['argv'][0])) {
	if (empty($_SERVER['argv'][1])) {
		$me = new Phar('neo4jphp.phar');
		$meta = $me->getMetaData();
		echo "Version {$meta['version']}\n\n";
		echo file_get_contents('phar://neo4jphp.phar/README.md')."\n\n";
	} else {
		$host = $_SERVER['argv'][1];
		$port = empty($_SERVER['argv'][2]) ? 7474 : $_SERVER['argv'][2];
		$client = new Everyman\Neo4j\Client(new Everyman\Neo4j\Transport($host, $port));
		print_r($client->getServerInfo());
	}

	exit(0);
}
__HALT_COMPILER();
