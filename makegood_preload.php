<?php
	// Need autoload so we can actually use PHPUnit.
	require ( __DIR__ . '/../../../vendor/autoload.php');
	// And we need to autoload our own vendor stuff.
	require ( __DIR__ . '/vendor/autoload.php');
	// phpBB tests are set up so that we *must* be in the phpbb directory
	chdir( __DIR__ . '/./../../../..');
