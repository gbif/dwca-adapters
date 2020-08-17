<?php
	#!/usr/bin/php

	/**
	 * Adapters
	 * This will generate DwC Archives for the following sources:
	 * GRIN - grin
	 * NCBI - ncbi
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
		print("Please supply the list of archives to generate as arguments: all grin ncbi tol usda");
	}

	$success = false;

	foreach( $argv as $source ) {
	 	switch( strtolower($source) ) {

			case 'all':
				$success = buildUSDA();
				$success = buildTOL();
				$success = buildGRIN();
				$success = buildNCBI();
				break;

			case 'usda':
				$success = buildUSDA();
				break;

			case 'tol':
				$success = buildTOL();
				break;

			case 'grin':
				$success = buildGRIN();
				break;

			case 'ncbi':
				$success = buildNCBI();
				break;
		}
	}

	print json_encode( array( "success" => $success ) );

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
			return(true);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			return(false);
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
			return(true);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			return(false);
		}
	}

	function buildGRIN() {
		echo "Building GRIN";
		try {
		require_once( BASE_PATH . 'sources/grin/class.grin.php' );
			$source = new grin();
			$source->downloadData();
			$source->createCSV();
			$source->createEml();
			$source->createMeta();
			$source->zipArchive();
			return(true);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			return(false);
		}
	}

	function buildNCBI() {
		echo "Building NCBI";
		require_once( BASE_PATH . 'sources/ncbi/class.ncbi.php');
		try {
			$source = new ncbi;
			$source->downloadData();
			$source->memHigherTaxa();
			$source->createCSV();
			$source->createEml();
			$source->createMeta();
			$source->zipArchive();
			return(true);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			return(false);
		}
	}

?>
