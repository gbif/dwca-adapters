<?php
	#!/usr/bin/php
	
	/**
	 * Adapters
	 * This will generate DwC Archives for the following sources:
	 * GRIN - grin
	 * ITIS - itis
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
	require_once('classes/class.mysqlidatabase.php');
	
	if ($argc < 1) {
		print("Please supply the list of archives to generate as arguments: all grin itis ncbi tol usda");
	}
	
	$success = false;

	foreach( $argv as $source ) {
	 	switch( strtolower($source) ) {

			case 'all':
				$success = buildUSDA();
				$success = buildTOL();
				$success = buildGRIN();
				$success = buildNCBI();
				$success = buildITIS();
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
				
			case 'itis':
				$success = buildITIS();
				break;
		}
	}

	print json_encode( array( "success" => $success ) );
	
	/*
		Operations:
	*/
	function buildUSDA() {
		require_once( BASE_PATH . 'sources/usda/class.usda.php' );
		$source = new usda();
		$source->downloadData();
		$source->createHigherTaxa();
		$source->createCSV();
		$source->createEml();
		$source->createMeta();
		$source->zipArchive();
		return(true);
	}
	
	function buildTOL() {
		require_once( BASE_PATH . 'sources/tol/class.tol.php' );
		$source = new tol();
		$source->downloadData();
		$source->createCSV();
		$source->createEml();
		$source->createMeta();
		$source->zipArchive();
		return(true);
	}
	
	function buildGRIN() {
		require_once( BASE_PATH . 'sources/grin/class.grin.php' );
		$source = new grin();
		$source->downloadData();
		$source->createCSV();
		$source->createEml();
		$source->createMeta();
		$source->zipArchive();
		return(true);
	}

	function buildNCBI() {
		require_once( BASE_PATH . 'sources/ncbi/class.ncbi.php');
		$source = new ncbi;
		$source->downloadData();
		$source->memHigherTaxa();
		$source->createCSV();
		$source->createEml();
		$source->createMeta();
		$source->zipArchive();
		return(true);
	}
	
	function buildITIS() {
		require_once( BASE_PATH . 'sources/itis/class.itis.php');
		global $itis;
		$source = new itis( $itis['server'], $itis['database'], $itis['username'], $itis['pass'] );
		
		# try and install the source db
		# Note: Zip File is ~25MB to download
		$source->downloadData( $itis['remote_file'] );
		$source->unpackDownload();
		# Note: This Drops all tables and reloads with the new sql file.
		# ~10 Minutes to preform.
		$source->importData( $itis['remote_file'] );
		
		$source->createVernacular();
		$source->createGeography();
		$source->createSpecies();
		$source->createEml();
		$source->createMeta();
		$source->zipArchive();
		return(true);
	}
	
?>
