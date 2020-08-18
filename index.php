<?php
	#!/usr/bin/php

	/**
	 * Adapters
	 * This will generate DwC Archives for the following sources:
	 * GRIN - grin
	 * Tree of Life - tol
	 * USDA - usda
	 *
	 * all - all
	 */

	error_reporting(E_ALL & ~E_NOTICE);
	#error_reporting(E_ALL);

	set_time_limit( 0 );
	ini_set('memory_limit', '30000M');
	require_once('config.php');
	require_once('classes/functions.php');
	require_once('classes/zip/archive.php');

	if ($argc < 1) {
		print("Please supply the list of archives to generate as arguments: all grin tol usda");
	}

	$failures = 0;

	foreach( $argv as $source ) {
	 	switch( strtolower($source) ) {

			case 'all':
				$failures += buildUSDA();
				$failures += buildTOL();
				$failures += buildGRIN();
				break;

			case 'usda':
				$failures = buildUSDA();
				break;

			case 'tol':
				$failures = buildTOL();
				break;

			case 'grin':
				$failures = buildGRIN();
				break;
		}
	}

	print json_encode( array( "failures" => $failures ) );
	exit($failures);

	/*
		Operations:
	*/
	function buildUSDA() {
		echo "Building USDA";
		require_once( BASE_PATH . 'sources/usda/class.usda.php' );
		try {
			$source = new usda();
			$source->downloadData();
			$source->createHigherTaxa();
			$source->createCSV();
			$source->createEml();
			$source->createMeta();
			$source->zipArchive();
			return(0);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			return(1);
		}
	}

	function buildTOL() {
		echo "Building TOL";
		require_once( BASE_PATH . 'sources/tol/class.tol.php' );
		try {
			$source = new tol();
			$source->downloadData();
			$source->createCSV();
			$source->createEml();
			$source->createMeta();
			$source->zipArchive();
			return(0);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			return(1);
		}
	}

	function buildGRIN() {
		echo "Building GRIN";
		echo "GRIN build disabled as the source archive has gone";
		return(0);
		require_once( BASE_PATH . 'sources/grin/class.grin.php' );
		try {
			$source = new grin();
			$source->downloadData();
			$source->createCSV();
			$source->createEml();
			$source->createMeta();
			$source->zipArchive();
			return(0);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			return(1);
		}
	}

?>
