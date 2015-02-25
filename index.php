<?php
	#!/usr/bin/php
	
	/**
	 * Adapters
	 * This will generate DwC Archives for the following sources:
	 * - Catalog of Life: col
	 * - GRIN - grin
	 * - ITIS - itis
	 * - Tree of Life - tol
	 * - USDA - usda
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
		print("Please supply the list of archives to generate as arguments: all col grin itis tol usda");
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
				$success = buildCOL();
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

			case 'col':
				$success = buildCOL();
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
	
	function buildCOL() {
		require_once('sources/col/class.col.php');
		$ext = '.txt';
		global $col;
		$source = new col( $col['server'], $col['database'], $col['username'], $col['pass'], $col['port'] );
		$source->delimiter = chr(9);  // Tab
		
		$source->memoryHigherTaxa();
		
		$taxa_filename = 'col-highertaxa.txt';
		$source->writeHigherTaxa( $taxa_filename );
		
		$tmp->database_full_name = 'Catalogue of Life 2009 Higher Classification';
		$tmp->database->contact_person = '';
		$tmp->database->organization = 'Catalogue of Life 2009';
		$tmp->database->website = 'http://www.catalogueoflife.org/';
		$tmp->database->release_date = '01/01/2009';
		$tmp->database->abstract = '';

		$source->createEml( $tmp );
		$source->createMetaHigherTaxa( $taxa_filename );				
		$source->zipHigherTaxa( $taxa_filename );

		$databases = $source->getDatabases();
		$archives = array();
		if (is_array($databases)) {
		foreach( $databases as $database ) {
			
			$col_source = str_replace( array(' '), '_', strtolower($database->database_name));
			$col_source = str_replace( array('(', ')'), '', $col_source);
			$col_source = str_replace( array('&'), 'and', $col_source);
			
			$source->createEml( $database );

			$tmpFilename = 'reference_' . $col_source . $ext;
			if ( $source->createReferences($database->record_id, $tmpFilename) ) {
				$archives[$col_source]['reference'] = $tmpFilename;
			}

			$tmpFilename = 'vernacular_' . $col_source . $ext;
			if ( $source->createCommonNames($database, 'Species', $tmpFilename) ) {
				$archives[$col_source]['vernacular'] = $tmpFilename;
			}
			
			$tmpFilename = 'taxa_' . $col_source . $ext;
			if ( $source->createSpecies( $database->record_id , $tmpFilename ) ) {
				$archives[$col_source]['taxa'] = $tmpFilename;
			}

			$tmpFilename = 'distribution_' . $col_source . $ext;
			if ( $source->createDistribution( $database , $tmpFilename ) ) {
				$archives[$col_source]['distribution'] = $tmpFilename;
			}

			if ( isset( $archives[$col_source] ) ) {
				if ( $source->createMeta( $col_source, $archives[$col_source] )) {
					$source->zipArchive( $col_source, $archives[$col_source] );
				} else {
					$source->removeFiles( $archives[$col_source] );
				}
			}
		}
		}

		// This is a generic eml file in the output folder
		$tmp->database_full_name = 'Catalogue of Life 2009';
		$tmp->database->contact_person = '';
		$tmp->database->organization = '';
		$tmp->database->website = 'http://www.catalogueoflife.org/';
		$tmp->database->release_date = '01/01/2009';
		$tmp->database->abstract = '';
		$source->createEml( $tmp );

		return(true);
	}
	
?>
