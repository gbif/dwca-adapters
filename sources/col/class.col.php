<?php

	require_once(BASE_PATH . 'classes/class.mysqlidatabase.php');
	require_once(BASE_PATH . 'classes/class.normalizeit.php');

	class col {

		private $db;
		private $mysql_host;
		private $mysql_name;
		private $mysql_user;
		private $mysql_pass;
		public $source_name = '';
		public $output_path = '';
		public $filename = "itis_data.csv";
		public $meta_filename = 'meta.xml';
		public $eml_filename = 'eml.xml';
		public $zip_filename = 'itis_archive.zip';
		public $vernacular_files = array();
		public $taxa_files = array();
		public $norm;
		public $delimiter = ",";
		public $cr = "\n";

		public function __construct( $mysql_host, $mysql_name, $mysql_user, $mysql_pass, $port=3306 ) {
			$this->mysql_host = $mysql_host;
			$this->mysql_name = $mysql_name;
			$this->mysql_user = $mysql_user;
			$this->mysql_pass = $mysql_pass;
			$connection_string="server=$mysql_host; database=$mysql_name; username=$mysql_user; password=$mysql_pass; port=$port;";
			$this->db = new MysqliDatabase($connection_string);
			$this->source_path = BASE_PATH . "sources/col/source/";
			$this->output_path = BASE_PATH . "sources/col/output/";
		}

		public function getDatabases() {
			$query = "SELECT * FROM `databases` ORDER BY database_name;";
			return( $this->db->query_all($query) );
		}

		public function createEml( $database ) {
			$tpl = file_get_contents( $this->source_path . $this->eml_filename );
			$tpl = str_replace("{title}", mb_convert_encoding( htmlspecialchars($database->database_full_name), 'UTF-8', 'cp1252') , $tpl);
			$tpl = str_replace("{givenName}", mb_convert_encoding( htmlspecialchars(@$database->contact_person), 'UTF-8', 'cp1252') , $tpl);
			$tpl = str_replace("{surName}", '', $tpl);
			$tpl = str_replace("{organizationName}", mb_convert_encoding( htmlspecialchars($database->organization), 'UTF-8', 'cp1252'), $tpl);
			$tpl = str_replace("{city}", '' , $tpl);
			$tpl = str_replace("{administrativeArea}", '' , $tpl);
			$tpl = str_replace("{postalCode}", '' , $tpl);
			$tpl = str_replace("{country}", '' , $tpl);
			$tpl = str_replace("{electronicMailAddress}", '' , $tpl);
			$tpl = str_replace("{onlineUrl}", mb_convert_encoding( htmlspecialchars( str_replace("#http", "http", $database->web_site)), 'UTF-8', 'cp1252') , $tpl);
			$tpl = str_replace("{logoUrl}", '' , $tpl);
			$tpl = str_replace("{pubDate}", mb_convert_encoding( htmlspecialchars($database->release_date), 'UTF-8', 'cp1252') , $tpl);
			$tpl = str_replace("{abstract}", mb_convert_encoding( htmlspecialchars($database->abstract), 'UTF-8', 'cp1252') , $tpl);
			$tpl = str_replace("{recordLinkUrl}", 'http://www.catalogueoflife.org/show_species_details.php?record_id={ID}' , $tpl);

			@unlink($this->output_path . $this->eml_filename);
			$fp = fopen( $this->output_path . $this->eml_filename, "w" );
			fwrite( $fp, $tpl );
			fclose( $fp );

		}

		public function createReferences( $database_id = 0, $tmpFilename ) {
			$query = sprintf("
				SELECT 
					r.record_id
				, t.name_code
				, replace(replace(trim(t.name), '\r\n', ' '),'\n', ' ') AS name
				, t.taxon
				, t.record_id as parent_id
				, sr.reference_type
				, replace(replace(trim(r.author), '\r\n', ' '),'\n', ' ') AS author
				, replace(replace(trim(r.year), '\r\n', ' '),'\n', ' ') AS year
				, replace(replace(replace(trim(r.title), '\r\n', ' '),'\n', ' '),'\t', ' ') AS title
				, replace(replace(replace(trim(r.source), '\r\n', ' '),'\n', ' '),'\t', ' ') AS source
				FROM `scientific_name_references` sr 
				JOIN taxa t ON t.name_code = sr.name_code
				JOIN `references` r ON r.record_id = sr.reference_id
				WHERE t.database_id = %s;", $database_id);

			$recs = $this->db->query_all($query);
			
			if ( is_array($recs) ) {			
				$fp = fopen($this->output_path . $tmpFilename, 'w');
#				fputcsv($fp, array('RefID', 'NameCode', 'ScientificName', 'TaxonRank', 'TaxonID', 'Type', 'Creator', 'Date', 'Title', 'Source'), $this->delimiter );
				fputs($fp, implode( $this->delimiter, array('RefID', 'NameCode', 'ScientificName', 'TaxonRank', 'TaxonID', 'Type', 'Creator', 'Date', 'Title', 'Source') )  . $this->cr );
				foreach( $recs as $rec) {
#					fputcsv($fp, (array) $rec, $this->delimiter);
					fputs($fp, implode( $this->delimiter, (array) $rec )  . $this->cr );
				}
				fclose($fp);
				unset( $recs );
				return(1);
			} else {
				return(0);
			}
			
		}

		public function createSpecies( $database_id = 0, $tmpFilename ) {
			$query = sprintf("
				SELECT 
						t.record_id AS TaxonID
					,	t.name_code
					, t.lsid AS LSID
					, t.parent_id AS HigherTaxonID
					, t.taxon AS TaxonRank
					, replace(replace(trim(t.name), '\r\n', ' '),'\n', ' ') AS ScientificName
					, replace(replace(replace(trim(n.author), '\r\n', ' '),'\n', ' '),'\t', '') AS ScientificNameAuthorship
					, s.sp2000_status as TaxonomicStatus
                    , n.accepted_name_code AS AcceptedTaxonID
					, sp.specialist_name					
					, t2.name_code AS parent_name_code
				FROM taxa t
				 LEFT OUTER JOIN sp2000_statuses s on t.sp2000_status_id = s.record_id 
				 LEFT OUTER JOIN scientific_names n on t.name_code = n.name_code
				 LEFT OUTER JOIN specialists sp ON sp.record_id = n.specialist_id
				 LEFT OUTER JOIN taxa t2 ON t2.record_id = t.parent_id
				WHERE t.database_id = %s
				ORDER BY t.name, TaxonomicStatus", $database_id);

			$prev['name_code'] = '';
			$ref = $this->db->query($query);
			if ( mysqli_num_rows($ref->Result) > 0 ) {
				$fp = fopen($this->output_path . $tmpFilename, 'w');
				fputs($fp, implode( $this->delimiter, array('TaxonID', 'NameCode', 'LSID', 'HigherTaxonID', 'TaxonRank', 'ScientificName', 'ScientificNameAuthorship', 'TaxonomicStatus', 'AcceptedTaxonID', 'CibliographicCitation') )  . $this->cr );
				while ($rec = (array) $ref->fetch_object() ) {
#					fputcsv($fp, $rec, $this->delimiter);
/*
					if ($rec['TaxonomicStatus'] == 'synonym') {
						$rec['AcceptedTaxonID'] = $rec['HigherTaxonID'];
						$rec['HigherTaxonID'] = "";
					}
*/					
					if ($rec['parent_name_code'] != '') {
						$rec['HigherTaxonID'] = $rec['parent_name_code'];
						unset($rec['parent_name_code']);
					} else {
						unset($rec['parent_name_code']);
					}
					if ($prev['name_code'] != $rec['name_code']) {
						fputs($fp, implode( $this->delimiter, (array) $rec )  . $this->cr );
					}
					$prev = $rec;
				}
				fclose($fp);			
				return( 1 );
			} else {
				return( 0 );
			}

			return( $this->db->query_all($query) );
		}

		public function memoryHigherTaxa() {
			$query = "
				SELECT 
						record_id
					,	lsid
					,	parent_id
					,	taxon
					,	name
					, is_species_or_nonsynonymic_higher_taxon as TaxonomicStatus
				FROM taxa
				WHERE database_id = 0";

			$ref = $this->db->query($query);
			if ( mysqli_num_rows($ref->Result) > 0 ) {
				while ($rec = (array) $ref->fetch_object() ) {
					$rec['TaxonomicStatus'] = ($rec['TaxonomicStatus'] == 0) ? "synonym" : "accepted";
					$this->higherTaxa[ $rec['record_id'] ] = $rec;
				}
			}
		}
		
		public function writeHigherTaxa( $tmpFilename ) {
			$fp = fopen($this->output_path . $tmpFilename, 'w');
			fputs($fp, implode( $this->delimiter, array('TaxonID', 'LSID', 'HigherTaxonID', 'TaxonRank', 'ScientificName', 'TaxonomicStatus') )  . $this->cr );
			foreach( $this->higherTaxa as $row ) {
				fputs($fp, implode( $this->delimiter, (array) $row )  . $this->cr );
			}
			fclose($fp);
		}
		
		public function createHigherTaxa( $database, $tmpFilename ) {
			
			$query = sprintf("
				SELECT 
					parent_id
				FROM taxa
				WHERE database_id = %s
				AND taxon = 'Species'
        GROUP BY parent_id", $database->record_id );
			
			$recs = $this->db->query_all($query);
			if ( is_array($recs) ) {			
				$fp = fopen($this->output_path . $tmpFilename, 'w');
#				fputcsv($fp, array('TaxonID', 'NameCode', 'LSID', 'HigherTaxonID', 'TaxonRank', 'ScientificName', 'ScientificNameAuthorship', 'TaxonomicStatus', 'Scrutiny'), $this->delimiter );
				fputs($fp, implode( $this->delimiter, array('TaxonID', 'NameCode', 'LSID', 'HigherTaxonID', 'TaxonRank', 'ScientificName', 'ScientificNameAuthorship', 'TaxonomicStatus', 'Scrutiny') )  . $this->cr );
				do {
					unset( $tmpList );
					foreach( $recs as $rec) {
						$tID = $this->higherTaxa[ $rec->parent_id ]['parent_id'];
						if ($tID != 0) {
							$tmpList[ $tID ]->parent_id = $tID;
						}
#				    fputcsv($fp, (array) $this->higherTaxa[ $rec->parent_id ], $this->delimiter);
						fputs($fp, implode( $this->delimiter, (array) $this->higherTaxa[ $rec->parent_id ] )  . $this->cr );
						
					}
					$recs = $tmpList;
				} while( is_array( $tmpList ));
				fclose($fp);
				return(1);
			} else {
				return(0);
			}
			
		}

		public function createDistribution( $database, $tmpFilename ) {
			
			$query = sprintf("
				SELECT s.record_id
					, t.record_id AS taxon_id
					, d.name_code
					, replace(replace(replace(trim(d.distribution), '\r\n', ' '),'\n', ' '),'\t', '') AS distribution
				FROM scientific_names s
					JOIN taxa t ON t.name_code = s.name_code
					JOIN distribution d ON d.name_code = t.name_code
				AND s.database_id = '%s';
			", $database->record_id );
			
			$recs = $this->db->query_all($query);
			if ( is_array($recs) ) {			
				$fp = fopen($this->output_path . $tmpFilename, 'w');
#		    fputcsv($fp, array('DistributionID', 'TaxonID', 'Locality'), $this->delimiter );
				fputs($fp, implode( $this->delimiter, array('DistributionID', 'TaxonID', 'NameCode', 'Locality') )  . $this->cr );
				foreach( $recs as $rec) {					
					$list = split("; ", $rec->distribution );
					foreach( $list as $item ) {
						$rec->distribution = trim($item);
#				    fputcsv($fp, (array) $rec, $this->delimiter);
						fputs($fp, implode( $this->delimiter, (array) $rec )  . $this->cr );
					}
				}
				fclose($fp);
				return(1);
			} else {
				return(0);
			}
		}
		
		public function createCommonNames( $database, $taxon, $tmpFilename ) {
/*			
			$query = sprintf("											 
				SELECT c.record_id, t.record_id AS HigherTaxonID, c.name_code, t.name, t.taxon, c.common_name, c.language 
				FROM common_names c, taxa t
				WHERE c.reference_id = t.record_id AND c.database_id = %s
				AND t.taxon = '%s'				
			", $database->record_id, $taxon);
*/
			$query = sprintf("											 
				SELECT c.record_id, c.name_code, c.common_name, c.language, c.country FROM common_names c
				LEFT OUTER JOIN scientific_names s ON s.name_code = c.name_code
				WHERE c.database_id = '%s';
			", $database->record_id);
			
			$ref = $this->db->query($query);
			if ( mysqli_num_rows($ref->Result) > 0 ) {
				$this->vernacular_files[] = $tmpFilename;			
				$fp = fopen($this->output_path . $tmpFilename, 'w');
#				fputcsv( $fp, array("RecordID", "Parent ID", "Scientific Name", "Rank", "Vernacular Name", "Language"), $this->delimiter );
#				fputs($fp, implode( $this->delimiter, array("RecordID", "Parent ID", "Scientific Name", "Rank", "Vernacular Name", "Language") )  . $this->cr );
				fputs($fp, implode( $this->delimiter, array("Record ID", "Parent ID", "Vernacular Name", "Language", "Country") )  . $this->cr );
				while ($rec = (array) $ref->fetch_object() ) {
#					fputcsv($fp, $rec, $this->delimiter);
					fputs($fp, implode( $this->delimiter, (array) $rec )  . $this->cr );
				}
				fclose($fp);			
				return( 1 );
			} else {
				return( 0 );
			}
		}
				
		public function createChecklist( $node, $fp ) {
			$query = sprintf("
				SELECT 
						t.record_id AS TaxonID
						, t.parent_id AS HigherTaxonID
						, t.lsid AS LSID
						, t.name AS ScientificName
						, n.author as ScientificNameAuthorship
						, t.taxon AS TaxonRank
						, s.sp2000_status as TaxonomicStatus
				FROM taxa t
				 LEFT OUTER JOIN sp2000_statuses s on t.sp2000_status_id = s.record_id 
				 LEFT OUTER JOIN scientific_names n on t.name_code = n.name_code
				WHERE parent_id = %s
			", $node['TaxonID']);

			$ref = $this->db->query($query);
			while ($rec = (array) $ref->fetch_object() ) {
#				fputcsv($fp, $rec, $this->delimiter);
				fputs($fp, implode( $this->delimiter, (array) $rec )  . $this->cr );
				$this->createChecklist($rec, $fp );
			}
		}

		public function createMetaHigherTaxa( $filename ) {
			$tpl = file_get_contents( $this->source_path . 'meta-highertaxa.xml' );
			$tpl = str_replace("{filename}", $filename, $tpl);
			@unlink($this->output_path . $this->meta_filename);
			$fp = fopen( $this->output_path . $this->meta_filename, "w" );
			fwrite( $fp, $tpl );
			fclose( $fp );
		}
		
		public function createMeta( $source, $archive ) {

			if (!count($archive["taxa"])) {
				return( false );	
			}
			
			$tpl = file_get_contents( $this->source_path . $this->meta_filename );
			if ( isset($archive["taxa"])) {
				$tpl = str_replace("{filename}", $archive["taxa"], $tpl);
			}
			
			if ( isset($archive["distribution"])) {
				$highertaxa_tpl = file_get_contents( $this->source_path . 'tpl_distribution.xml' ); 
				$highertaxa_tpl = str_replace("{filename}", $archive["distribution"], $highertaxa_tpl);				
				$tpl = str_replace("{distribution}", $highertaxa_tpl, $tpl);				
			} else {
				$tpl = str_replace("{distribution}", '', $tpl);				
			}

			if ( isset($archive["reference"])) {
				$highertaxa_tpl = file_get_contents( $this->source_path . 'tpl_reference.xml' ); 
				$highertaxa_tpl = str_replace("{filename}", $archive["reference"], $highertaxa_tpl);				
				$tpl = str_replace("{reference}", $highertaxa_tpl, $tpl);				
			} else {
				$tpl = str_replace("{reference}", '', $tpl);				
			}

			if ( isset($archive["vernacular_genus"]) || isset($archive["vernacular_species"]) || isset($archive["vernacular"]) ) {				
				$vernacular_tpl = file_get_contents( $this->source_path . 'tpl_vernacular.xml' );
				$location = '';
				if ( isset( $archive["vernacular"] )) {
					$location .= "<location>" . $archive["vernacular"] . "</location>\r\n";
				}
				if ( isset( $archive["vernacular_genus"] )) {
					$location .= "<location>" . $archive["vernacular_genus"] . "</location>\r\n";
				}
				if ( isset( $archive["vernacular_species"] )) {
					$location .= "<location>" . $archive["vernacular_species"] . "</location>\r\n";
				}
				$vernacular_tpl = str_replace("{location}", $location, $vernacular_tpl);				
				$tpl = str_replace("{vernacular}", $vernacular_tpl, $tpl);				
			} else {
				$tpl = str_replace("{vernacular}", '', $tpl);				
			}
			
			@unlink($this->output_path . $this->meta_filename);
			$fp = fopen( $this->output_path . $this->meta_filename, "w" );
			fwrite( $fp, $tpl );
			fclose( $fp );
			
			return( true );
		}
		
		public function zipHigherTaxa( $taxa_filename ) {

			$filename = $this->output_path . "col2009_highertaxa.zip";
			
			$zip = new zip_file( $filename );
			$zip->set_options(array('inmemory' => 1, 'recurse' => 0, 'storepaths' => 0));
	
			$zip->add_files($this->output_path . $this->eml_filename);
			$zip->add_files($this->output_path . $this->meta_filename);
			$zip->add_files($this->output_path . $taxa_filename);
			
			$zip->create_archive();
			$zip->save_file( $filename );
			@unlink($this->output_path . $taxa_filename);
		}
		
		public function zipArchive( $source, $archive ) {

			$filename = $this->output_path . "col2009_archive_" . $source . ".zip";
			
			$zip = new zip_file( $filename );
			$zip->set_options(array('inmemory' => 1, 'recurse' => 0, 'storepaths' => 0));
	
			$zip->add_files($this->output_path . $this->eml_filename);
			$zip->add_files($this->output_path . $this->meta_filename);
			
			if (isset($archive['taxa'])) {
				$zip->add_files($this->output_path . $archive['taxa']);
			}

			if (isset($archive['reference'])) {
				$zip->add_files($this->output_path . $archive['reference']);
			}

			if (isset($archive['distribution'])) {
				$zip->add_files($this->output_path . $archive['distribution']);
			}

			if (isset($archive['vernacular'])) {
				$zip->add_files($this->output_path . $archive['vernacular']);
			}

			if (isset($archive['vernacular_genus'])) {
				$zip->add_files($this->output_path . $archive['vernacular_genus']);
			}

			if (isset($archive['vernacular_species'])) {
				$zip->add_files($this->output_path . $archive['vernacular_species']);
			}
			
			$zip->create_archive();
			$zip->save_file( $filename );
			
			$this->removeFiles( $archive );
		}

		public function removeFiles( $archive ) {
			@unlink($this->output_path . $archive['reference']);
			@unlink($this->output_path . $archive['taxa']);
			@unlink($this->output_path . $archive['distribution']);
			@unlink($this->output_path . $archive['vernacular']);
			@unlink($this->output_path . $archive['vernacular_genus']);
			@unlink($this->output_path . $archive['vernacular_species']);
			@unlink($this->output_path . $this->eml_filename);
			@unlink($this->output_path . $this->meta_filename);
		}

	}

?>