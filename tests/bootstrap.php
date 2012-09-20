<?php
function loaderTestAutoloader($sClass)
{
	$sLibPath = __DIR__.'/../lib/';
	$sClassFile = str_replace('\\',DIRECTORY_SEPARATOR,$sClass).'.php';
	$sClassPath = $sLibPath.$sClassFile;
	
	if (file_exists($sClassPath)) {
		require($sClassPath);
	}
}

chdir(__DIR__.'/..');
spl_autoload_register('loaderTestAutoloader');
error_reporting(-1);
ini_set('display_errors', 1);


include __DIR__ . "/../lib/Everyman/Neo4j/Bootstrap.php";