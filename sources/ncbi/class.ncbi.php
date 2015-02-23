<?php

	class ncbi {

		public $archive_file = 'taxdmp.zip';
		public $source_name = 'names.csv';
		public $nodes_file = 'nodes.csv';
		public $source_path = '';
		public $output_path = '';
		public $filename = 'ncbi_data.txt';
		public $vernacular_filename = 'vernacular.txt';
		public $meta_filename = 'meta.xml';
		public $eml_filename = 'eml.xml';
		public $zip_filename = 'ncbi_archive.zip';
		public $delimiter = "\t";
		public $cr = "\n";

		function __construct() {
			$this->source_path = BASE_PATH . "sources/ncbi/source/";
			$this->output_path = BASE_PATH . "sources/ncbi/output/";
   	}

		public function downloadData() {
			
			$fp = fopen( $this->source_path . $this->archive_file, 'w');
			fwrite($fp, file_get_contents('ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdmp.zip') );
			fclose($fp);

#			system( sprintf("unzip %s %s", $this->source_path . $this->archive_file, $this->source_path) );
			unzip( $this->source_path . $this->archive_file, $this->source_path );
			
			$this->transform('nodes.dmp', 'nodes.csv');
			$this->transform('names.dmp', 'names.csv');
		}

		public function transform( $source_name, $filename ) {
			$fp = fopen($this->source_path . $filename, 'w');

			$handle = @fopen($this->source_path . $source_name, 'r');
			if ($handle) {
				while (!feof($handle)) {
					$buffer = fgets($handle, 4096);
					$buffer = explode('|', $buffer);
					array_walk($buffer,'trim_space');
					fputcsv($fp, $buffer);
				}
				fclose($handle);
			}
			fclose($fp);
		}

		public function memHigherTaxa() {
			$fp = fopen($this->source_path . $this->nodes_file, 'r');
			while (($data = fgetcsv($fp, 10000, ",")) !== FALSE) {
				$this->rank[$data[0]] = array('r' => $data[2], 'pID' => $data[1]);
			}
			fclose($fp);
		}
		
		public function createCSV() {
			$j = 0;
			$fp = fopen($this->output_path . $this->filename, 'w');
			fputs($fp, implode( $this->delimiter, array('TaxonID', 'ScientificName', 'Accepted Name Usage ID', 'Taxonomic Rank', 'Taxonomic Status', 'Parent Name Usage ID'))  . $this->cr );
			
			$fp_vernacular = fopen($this->output_path . $this->vernacular_filename, 'w');
			fputs($fp_vernacular, implode( $this->delimiter, array('TaxonID', 'VernacularName'))  . $this->cr );

			$fp_source = @fopen($this->source_path . $this->source_name, 'r');
			while (($data = fgetcsv($fp_source, 10000, ",")) !== FALSE) {
				switch( $data[3] ) {
					case 'scientific name':
						fputs($fp, implode( $this->delimiter, array($data[0], $data[1], '', $this->rank[$data[0]]['r'], '', $this->rank[$data[0]]['pID'] ))  . $this->cr );
						break;
					
					case 'in-part':
						if (isset($inparts[$data[1]])) {
							$inparts[$data[1]][2] .= " " . $data[0];
						} else {
							$inparts[$data[1]] = array("e" . $j++, $data[1], $data[0], '', $data[3]);
						}
						break;
						
					case 'common name':
					case 'genbank common name':
						fputs($fp_vernacular, implode( $this->delimiter, array($data[0], $data[1]))  . $this->cr );
						break;
						
					case 'includes':
					case 'authority':
					case 'misspelling':
					case 'misnomer':
					case 'genbank synonym':
					case 'unpublished name':
					case 'anamorph':
					case 'genbank anamorph':
					case 'teleomorph':
					case 'acronym':
					case 'genbank acronym':
					case 'authority':
					case 'synonym':
					case 'equivalent name':
						fputs($fp, implode( $this->delimiter, array("e" . $j++, $data[1], $data[0], '', $data[3]))  . $this->cr );
						break;

				}
			}

			if (is_array($inparts)) {
			foreach( $inparts as $inpart ) {
				fputs($fp, implode( $this->delimiter, array($inpart[0], $inpart[1], $inpart[2], '', $inpart[3]))  . $this->cr );				
			}
			}
			
			fclose($fp_source);
			fclose($fp);
			fclose($fp_vernacular);
		}
		
		public function createMeta() {
			copy($this->source_path . 'sample.meta.xml', $this->output_path . 'meta.xml');
		}

		public function createEml() {
			$tpl = file_get_contents( $this->source_path . $this->eml_filename );
			$tpl = str_replace("{title}", 'NCBI' , $tpl);
			$tpl = str_replace("{givenName}", '' , $tpl);
			$tpl = str_replace("{surName}", '', $tpl);
			$tpl = str_replace("{organizationName}", 'National Center for Biotechnology Information', $tpl);
			$tpl = str_replace("{city}", '' , $tpl);
			$tpl = str_replace("{administrativeArea}", '' , $tpl);
			$tpl = str_replace("{postalCode}", '' , $tpl);
			$tpl = str_replace("{country}", 'USA' , $tpl);
			$tpl = str_replace("{electronicMailAddress}", '' , $tpl);
			$tpl = str_replace("{onlineUrl}", 'http://www.ncbi.nlm.nih.gov/' , $tpl);
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
	
			$zip->add_files($this->output_path . $this->eml_filename, $this->eml_filename);
			$zip->add_files($this->output_path . $this->meta_filename, $this->meta_filename);
			$zip->add_files($this->output_path . $this->filename, $this->filename);
			$zip->add_files($this->output_path . $this->vernacular_filename, $this->vernacular_filename);
			$zip->create_archive();
			$zip->save_file( $filename );

			@unlink($this->output_path . $this->meta_filename);
			@unlink($this->output_path . $this->eml_filename);
			@unlink($this->output_path . $this->filename);
			@unlink($this->output_path . $this->vernacular_filename);	
			$tmpFiles = array('citations.dmp', 'delnodes.dmp', 'division.dmp', 'gc.prt', 'gencode.dmp'
				, 'merged.dmp', 'names.dmp', 'nodes.dmp', 'readme.txt', 'names.csv', 'nodes.csv', 'taxdmp.zip');
			foreach($tmpFiles as $tmpFile) {
				@unlink($this->source_path . $tmpFile);
			}
		}

	}

	function trim_space(&$str) {
		$str = trim($str);
	}

?>