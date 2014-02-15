<?php
trigger_error('The neo4jphp PHAR archive is no longer supported and will be removed in the future. Use Composer to install the library.', E_USER_DEPRECATED);

Phar::mapPhar('neo4jphp.phar');
spl_autoload_register(function ($className) {
	if (strpos($className, 'Everyman\Neo4j\\') !== 0) {
		return;
	}
	$libPath = 'phar://neo4jphp.phar/lib/';
	$classFile = str_replace('\\',DIRECTORY_SEPARATOR,$className).'.php';
	$classPath = $libPath.$classFile;
	if (file_exists($classPath)) {
		require($classPath);
	}
});

if ('cli' === php_sapi_name() && basename(__FILE__) === basename($_SERVER['argv'][0])) {
	$command = empty($_SERVER['argv'][1]) ? '-help' : $_SERVER['argv'][1];
	$me = new Phar('neo4jphp.phar');
	$meta = $me->getMetaData();

	if ($command == '-help') {
		echo <<<HELP
Neo4jPHP version {$meta['version']}

{$_SERVER['argv'][0]} [-help|-license|-readme|-version|<host>] <port>
    -help            Display help text
    -license         Display software license
    -readme          Display README
    -version         Display version information
    <host> (<port>)  Test connection to Neo4j instance on host (port defaults to 7474)

HELP;

	} else if ($command == '-license') {
		echo file_get_contents('phar://neo4jphp.phar/LICENSE')."\n\n";

	} else if ($command == '-readme') {
		echo file_get_contents('phar://neo4jphp.phar/README.md')."\n\n";

	} else if ($command == '-version') {
		echo "Neo4jPHP version {$meta['version']}\n\n";

	} else {
		$port = empty($_SERVER['argv'][2]) ? 7474 : $_SERVER['argv'][2];
		$client = new Everyman\Neo4j\Client($command, $port);
		print_r($client->getServerInfo());
	}

	exit(0);
}
__HALT_COMPILER();
