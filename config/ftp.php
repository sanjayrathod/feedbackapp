<?php
	return array(
		'default' => 'source',

		'connections' => array(
			'source'  => array(
				'host'     => 'test.rebex.net',
	            'username' => 'demo',
	            'password' => 'password',
	            'port'     => 21,
            	'passive'  => false,
            	'secure'   => false,
            	'copy_file' => 'readme.txt'
			),
			'destination'  => array(
				'host'     => 'ftp.dlptest.com',
				'username' => 'dlpuser@dlptest.com',
	           	'password' => 'fLDScD4Ynth0p4OJ6bW6qCxjh',
	           	'port'     => 21,
            	'passive'  => false,
            	'secure'   => false
			)
		),
		'local_download_path' => public_path() . DIRECTORY_SEPARATOR . 'source'
	);