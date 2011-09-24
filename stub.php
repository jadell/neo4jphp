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
__HALT_COMPILER();
