<?php
/**
 * Plugin name: Rila Framework
 * Author: Radoslav Georgiev
 */
if( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';	
}

require_once __DIR__ . '/classes/class-plugin.php';
Rila\Plugin::init( __FILE__ );
