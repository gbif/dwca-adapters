<?php

	class itis {

		private $db;
		private $field_name = 'Tables_in_';
		private $mysql_host;
		private $mysql_name;
		private $mysql_user;
		private $mysql_pass;
		public $source_zip = 'itisMySQLBulk.zip';
 		public $source_name = 'ITIS.sql';
		public $output_path = "sources/itis/output/";
		public $source_path = "sources/itis/source/";
		public $meta_filename = 'meta.xml';
		public $zip_filename = 'itis_archive.zip';

		public $filename_species = "species.txt";
		public $filename_geography = "geography.txt";
		public $filename_vernacular = "vernacular.txt";

		public $eml_filename = 'eml.xml';
		public $delimiter = "\t";
		public $cr = "\n";

		public function __construct( $mysql_host, $mysql_name, $mysql_user, $mysql_pass, $port=3306 ) {
			$this->field_name .= $mysql_name;
			$this->mysql_host = $mysql_host;
			$this->mysql_name = $mysql_name;
			$this->mysql_user = $mysql_user;
			$this->mysql_pass = $mysql_pass;
			$connection_string="server=$mysql_host; database=$mysql_name; username=$mysql_user; password=$mysql_pass;";
			$this->db = new MysqliDatabase($connection_string);
		}

		public function downloadData( $remote_file ) {
			return;
			print "Downloading ".$remote_file."\n";
			$fp = fopen( $this->source_path . $this->source_zip, 'w');
			fwrite($fp, file_get_contents($remote_file) );
			fclose($fp);
			print "Download completed.\n";
		}

		public function unpackDownload() {
			unzip( $this->source_path . $this->source_zip, $this->source_path );
		}
		
		private function dropTables() {
			$table_list = '';
			$query = ' SHOW tables ';
			$res = $this->db->query_all($query);
			if( count($res) && is_array($res) ) {
				$tables = array();
				foreach($res as $table) {
					$tables[] = $table->{$this->field_name};
				}
			$table_list = implode(',',$tables);
			}
			if($table_list != '') {
				$query = 'DROP TABLE IF EXISTS ' . $table_list;
				$this->db->query($query);
			}
		}

		public function importData( $remote_file ) {
			$f = BASE_PATH . $this->source_path . $this->source_name;
			if ( file_exists( $f ) ) {
				$this->dropTables();
				$query = sprintf("mysql -h %s -u %s -p%s %s < %s", $this->mysql_host, $this->mysql_user, $this->mysql_pass, $this->mysql_name, $f );
				print $query;
				$ar = array();
				exec( $query, $ar );
			} else {
				print "Error: Can not find ". $this->source_name ." at: $f";
				exit();
			}
		}

		public function createGeography() {

			$query = "
				SELECT t.tsn AS TaxonID, ln.completename AS ScientificName, a.taxon_author AS AuthorYearOfScientificName, g.geographic_value AS Region FROM taxonomic_units t
				JOIN geographic_div g ON t.tsn = g.tsn
				JOIN longnames ln ON t.tsn = ln.tsn
				LEFT OUTER JOIN taxon_authors_lkp a ON t.taxon_author_id = a.taxon_author_id
			";

			$ref = $this->db->query($query);
			$fp = fopen($this->output_path . $this->filename_geography, 'w');
			fputs($fp, implode( $this->delimiter, array('TaxonID', 'ScientificName', 'AuthorYearOfScientificName', 'Region')) . $this->cr );
#			fputcsv($fp, array('TaxonID', 'ScientificName', 'AuthorYearOfScientificName', 'Region') );
			
			while ($rec = (array) $ref->fetch_object() ) {
				fputs($fp, implode( $this->delimiter, $rec) . $this->cr );
#				fputcsv($fp, $rec);
			}
			fclose($fp);

		}
		
		private function getLanguageCode( $str ) {
			$codes = array();
			$codes['Afrikaans'] = 'af';
			$codes['Arabic'] = 'ar';
			$codes['Chinese'] = 'zh';
			$codes['Djuka'] = $str;
			$codes['Dutch'] = 'nl';
			$codes['English'] = 'en';
			$codes['French'] = 'fr';
			$codes['Galibi'] = $str;
			$codes['German'] = 'de';
			$codes['Greek'] = 'el';
			$codes['Hausa'] = 'ha';
			$codes['Hawaiian'] = $str;
			$codes['Hindi'] = 'hi';
			$codes['Icelandic'] = 'is';
			$codes['Iglulik Inuit'] = 'iu';
			$codes['Italian'] = 'it';
			$codes['Japanese'] = 'ja';
			$codes['Korean'] = 'ko';
			$codes['Portuguese'] = 'pt';
			$codes['Spanish'] = 'es';
			$codes['unspecified'] = '';

			return($codes[$str]);
		}
		
		public function createVernacular() {

			$query = "
				SELECT t.tsn, TRIM(CONCAT(unit_name1, ' ', unit_name2, ' ', unit_ind3, ' ', unit_name3)) AS ScientificName, v.vernacular_name, language, t.name_usage 
				FROM taxonomic_units  t
				JOIN vernaculars v ON t.tsn = v.tsn
			";

			$ref = $this->db->query($query);
			$fp = fopen($this->output_path . $this->filename_vernacular, 'w');
			fputs($fp, implode( $this->delimiter, array('TaxonID', 'ScientificName', 'Common Name', 'Language', 'Usage')) . $this->cr );
			while ($rec = (array) $ref->fetch_object() ) {
				$rec['language'] = $this->getLanguageCode( $rec['language'] );
				fputs($fp, implode( $this->delimiter, $rec) . $this->cr );
#				fputcsv($fp, $rec);
			}
			fclose($fp);
			
		}

		public function denormalize( $fp, $id, $flag = 0 ) {

			$query = "
				SELECT 
						ln.*, t.parent_tsn
					, tut.rank_name AS TaxonRank 
				FROM taxonomic_units t 
				JOIN longnames ln ON t.tsn = ln.tsn 
				JOIN taxon_unit_types tut ON t.rank_id = tut.rank_id 
			";

			if ($flag) {
				$query .= "WHERE t.parent_tsn = $id AND t.rank_id = tut.rank_id AND t.kingdom_id = tut.kingdom_id";
			} else {
				$query .= "WHERE t.rank_id = $id AND t.kingdom_id = tut.kingdom_id";
			}
#print $query;
			$ref = $this->db->query($query);
			while ($rec = (array) $ref->fetch_object() ) {
#				print_r( $rec );
				fputcsv($fp, $rec);
				$this->denormalize( $fp, $rec['tsn'], 1);				
			}
			
		}
		
		public function createSpecies() {
#			print "<pre>";
			$fp = fopen($this->output_path . $this->filename_species, 'w');
			fputs($fp, implode( $this->delimiter, array('TaxonID', 'ScientificName', 'HigherTaxonID', 'AuthorYearOfScientificName', 'Rank', 'Taxonomic Status', 'Accepted Name Usage ID')) . $this->cr );
#			$this->denormalize( $fp, 10 );

			$query = "
				SELECT 
							t.tsn
						,	ln.completename, t.parent_tsn, a.taxon_author
						, tut.rank_name AS TaxonRank 
						, t.name_usage
						, group_concat(DISTINCT sl.tsn_accepted SEPARATOR ' ') AS acceptedNameUsageID
				FROM taxonomic_units t 
				JOIN longnames ln ON t.tsn = ln.tsn 
				JOIN taxon_unit_types tut ON t.rank_id = tut.rank_id AND t.kingdom_id = tut.kingdom_id
				LEFT OUTER JOIN taxon_authors_lkp a ON t.taxon_author_id = a.taxon_author_id
				LEFT OUTER JOIN synonym_links sl ON sl.tsn = t.tsn
        GROUP BY t.tsn
			";
			$i=0;
			$ref = $this->db->query($query);
			while ($rec = (array) $ref->fetch_object() ) {
#				fputcsv($fp, $rec);
				if ($rec['parent_tsn'] == 0) $rec['parent_tsn'] = '';
				if ($rec['acceptedNameUsageID'] == '0') $rec['acceptedNameUsageID'] = '';				
				fputs($fp, implode( $this->delimiter, $rec) . $this->cr );
			}
			fclose($fp);
		}

		public function createMeta() {
			copy($this->source_path . 'sample.meta.xml', $this->output_path . 'meta.xml');
		}

		public function createEml() {
			$tpl = file_get_contents( $this->source_path . $this->eml_filename );
			$tpl = str_replace("{title}", 'ITIS' , $tpl);
			$tpl = str_replace("{givenName}", '' , $tpl);
			$tpl = str_replace("{surName}", '', $tpl);
			$tpl = str_replace("{organizationName}", 'Integrated Taxonomic Information System', $tpl);
			$tpl = str_replace("{city}", '' , $tpl);
			$tpl = str_replace("{administrativeArea}", '' , $tpl);
			$tpl = str_replace("{postalCode}", '' , $tpl);
			$tpl = str_replace("{country}", '' , $tpl);
			$tpl = str_replace("{electronicMailAddress}", '' , $tpl);
			$tpl = str_replace("{onlineUrl}", 'https://www.itis.gov/' , $tpl);
			$tpl = str_replace("{logoUrl}", '' , $tpl);
			$tpl = str_replace("{pubDate}", date("Y/m/d") , $tpl);
			$tpl = str_replace("{abstract}", '' , $tpl);
			$tpl = str_replace("{recordLinkUrl}", '' , $tpl);
	
			@unlink($this->output_path . $this->eml_filename);
			$fp = fopen( $this->output_path . $this->eml_filename, "w" );
			fwrite( $fp, $tpl );
			fclose( $fp );
	
		}

		public function zipArchive() {

			$filename = $this->output_path . $this->zip_filename;
			$zip = new zip_file( $filename );
			$zip->set_options(array('inmemory' => 1, 'recurse' => 0, 'storepaths' => 0));
	
			$zip->add_files($this->output_path . $this->meta_filename);
			$zip->add_files($this->output_path . $this->eml_filename);
			$zip->add_files($this->output_path . $this->filename_species);
			$zip->add_files($this->output_path . $this->filename_geography);
			$zip->add_files($this->output_path . $this->filename_vernacular);
			$zip->create_archive();
			$zip->save_file( $filename );

			@unlink($this->output_path . $this->filename_species);
			@unlink($this->output_path . $this->filename_geography);
			@unlink($this->output_path . $this->filename_vernacular);
			@unlink($this->output_path . $this->meta_filename);
			@unlink($this->output_path . $this->eml_filename);			

		}
		
	}

?>