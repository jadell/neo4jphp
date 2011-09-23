<?php
spl_autoload_register(function ($className) {
	$libPath = __DIR__.'/lib/';
	$classFile = str_replace('\\',DIRECTORY_SEPARATOR,$className).'.php';
	$classPath = $libPath.$classFile;
	if (file_exists($classPath)) {
		require($classPath);
	}
});
__HALT_COMPILER();
