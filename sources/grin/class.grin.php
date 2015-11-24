<?php

	require_once('sources/grin/dbf_class.php');
	 
	class grin {

		public $source_name = 'family.dbf';
		public $source_path = 'sources/grin/source/';
		public $output_path = 'sources/grin/output/';
		public $filename = 'grin_taxa.txt';
		public $vernacular_filename = 'grin_vernacular.txt';
		public $meta_filename = 'meta.xml';
		public $eml_filename = 'eml.xml';
		public $zip_filename = 'grin_archive.zip';
		public $delimiter = "\t";
		public $cr = "\n";		

		private function unzip( $filename ) {
			unzip( $this->source_path . $filename, $this->source_path ); 
		}
		
		public function downloadData() {

			$fp = fopen( $this->source_path . 'common.zip', 'w');
			fwrite($fp, file_get_contents('http://www.ars-grin.gov/misc/tax/common.zip') );
			fclose($fp);			
			$this->unzip('common.zip');

			$this->transform('common.dbf', 'common.csv');

			$fp = fopen( $this->source_path . 'family.zip', 'w');
			fwrite($fp, file_get_contents('http://www.ars-grin.gov/misc/tax/family.zip') );
			fclose($fp);
			$this->unzip('family.zip');
			$this->transform('family.dbf', 'family.csv');
			
			$fp = fopen( $this->source_path . 'genus.zip', 'w');
			fwrite($fp, file_get_contents('http://www.ars-grin.gov/misc/tax/genus.zip') );
			fclose($fp);
			$this->unzip('genus.zip');
			$this->transform('genus.dbf', 'genus.csv');

			$fp = fopen( $this->source_path . 'species.zip', 'w');
			fwrite($fp, file_get_contents('http://www.ars-grin.gov/misc/tax/species.zip') );
			fclose($fp);
			$this->unzip('species.zip');			
			$this->transform('species.dbf', 'species.csv');

		}
		
		public function transform( $source, $target ) {
			$dbf = new dbf_class($this->source_path . $source);
			$num_rec=$dbf->dbf_num_rec;
			$field_num=$dbf->dbf_num_field;
	
			$fp = fopen($this->source_path . $target, 'w');
			for($i=0; $i<$num_rec; $i++){
				if ($row = $dbf->getRow($i)) {
					fputcsv($fp, $row );
				}
			}
			fclose($fp);
		}
		
		public function createCSV() {		

			// Creates Taxa CSV
			$fp_taxa = fopen($this->output_path . $this->filename, 'w');
			fputs($fp_taxa, implode( $this->delimiter, array('TaxonID', 'ScientificName', 'AuthorYearOfScientificName', 'Taxon Rank', 'Accepted Name Usage ID', 'Parent Name Usage ID', 'Name Published In', 'TaxonRemarks') ) . $this->cr );

			// Creates Vernacular CSV
			$fp_common = fopen($this->output_path . $this->vernacular_filename, 'w');
			fputs($fp_common, implode( $this->delimiter, array('Common Name', 'Parent ID', 'Language'))  . $this->cr );
		
			$fp_source = @fopen($this->source_path . 'common.csv', 'r');
				while (($data = fgetcsv($fp_source, 10000, ",")) !== FALSE) {
					fputs($fp_common, implode( $this->delimiter, (array) $data )  . $this->cr );				
				}
			fclose($fp_source);
			
			// Family csv added into memory for looup
			$fp_source = @fopen($this->source_path . 'family.csv', 'r');
			while (($data = fgetcsv($fp_source, 100000, ",")) !== FALSE) {
#				$mem_family[$data[0]] = $data;
				
				# Check if IS accepted name, if so clear the accepted id
				if ($data[1] == $data[0]) $data[1] = '';
				# Write Record
				if ($data[7] != '') {
					# SubTribe Found
					$this->taxa[$data[2] . $data[5]. $data[6]. $data[7]] = $data[0];
					$pID = $this->taxa[$data[2] . $data[5]. $data[6]];
					if ($pID == '') $pID = $this->taxa[$data[2] . $data[5]];
					if ($pID == '') $pID = $this->taxa[$data[2]];
					$tmpRec = array("f" . $data[0], $data[7], '', 'subtribe', '', "f" . $pID, '', '', $data[8] );
				} elseif ($data[6] != '') {
					# Tribe Found
					$this->taxa[$data[2] . $data[5]. $data[6]] = $data[0];
					$pID = $this->taxa[$data[2] . $data[5]];
					if ($pID == '') $pID = $this->taxa[$data[2]];
					$tmpRec = array("f" . $data[0], $data[6], '', 'tribe', '', "f" . $pID, '', '', $data[8] );				
				} elseif ($data[5] != '') {
					# SubFamily Found
					$this->taxa[$data[2] . $data[5]] = $data[0];
					$tmpRec = array("f" . $data[0], $data[5], '', 'subfamily', '', "f" . $this->taxa[$data[2]], '', '', $data[8] );				
				} else {
					# Family Found
					$this->taxa[$data[2]] = $data[0];
					if ($data[1] != '') $data[1] = "f" . $data[1];
					$tmpRec = array("f" . $data[0], $data[2], $data[3], 'family', $data[1], '', '', $data[8] );				
				}
				
				fputs($fp_taxa, implode( $this->delimiter, (array) $tmpRec )  . $this->cr );				
				
			}
			fclose($fp_source);
			unset($this->taxa);

			// Genus csv added into memory for looup
			$fp_source = @fopen($this->source_path . 'genus.csv', 'r');
			while (($data = fgetcsv($fp_source, 100000, ",")) !== FALSE) {

				# Check if IS accepted name, if so clear the accepted id
				if ($data[1] == $data[0]) $data[1] = '';
				
				# Write Record
				if ($data[9] != '') {
					# subseries Found
					$this->taxa[$data[4] . $data[6]. $data[7] . $data[8] . $data[9]] = $data[0];
					$pID = $this->taxa[$data[4] . $data[6]. $data[7] . $data[8]];
					if ($pID == '') $pID = $this->taxa[$data[4] . $data[6]. $data[7]];
					if ($pID == '') $pID = $this->taxa[$data[4] . $data[6]];
					if ($pID == '') $pID = $this->taxa[$data[4]];
					$pID = "g" . $pID;
					$tmpRec = array("g" . $data[0], $data[9], '', 'subseries', '', $pID, '', '', $data[14] );
				} elseif ($data[8] != '') {
					# section Found
					$this->taxa[$data[4] . $data[6]. $data[7] . $data[8]] = $data[0];
					$pID = $this->taxa[$data[4] . $data[6]. $data[7]];
					if ($pID == '') $pID = $this->taxa[$data[4] . $data[6]];
					if ($pID == '') $pID = $this->taxa[$data[4]];
					$pID = "g" . $pID;
					$tmpRec = array("g" . $data[0], $data[8], '', 'section', '', $pID, '', '', $data[14] );				
				} elseif ($data[7] != '') {
					# series Found
					$this->taxa[$data[4] . $data[6]. $data[7]] = $data[0];
					$pID = $this->taxa[$data[4] . $data[6]];
					if ($pID == '') $pID = $this->taxa[$data[4]];
					$pID = "g" . $pID;
					$tmpRec = array("g" . $data[0], $data[7], '', 'series', '', $pID, '', '', $data[14] );				
				} elseif ($data[6] != '') {
					# subgenus Found
					$this->taxa[$data[4] . $data[6]] = $data[0];
					$tmpRec = array("g" . $data[0], $data[6], '', 'subgenus', '', "g" . $this->taxa[$data[4]], '', '', $data[14] );				
				} else {
					# Genus Found
					$this->taxa[$data[4]] = $data[0];
					if ($data[1] != '')$data[1] = "g" . $data[1];
					$tmpRec = array("g" . $data[0], $data[4], $data[5], 'genus', $data[1], "f" . $data[10], '', $data[14] );
				}
				
				# Write Record
				fputs($fp_taxa, implode( $this->delimiter, (array) $tmpRec )  . $this->cr );				
				
				if ($data[13] != '') {
					fputs($fp_common, implode( $this->delimiter, array($data[13], "g" . $data[0], 'English') )  . $this->cr );				
				}
			}
			fclose($fp_source);
			
			$fp_source = @fopen($this->source_path . 'species.csv', 'r');
			while (($data = fgetcsv($fp_source, 100000, ",")) !== FALSE) {
				# Check if IS accepted name, if so clear the accepted id
				if ($data[1] == $data[0]) $data[1] = '';
				# Check for Autonym if no variety authoriship we use the species authorship
				if (($data[5] == $data[8]) && ($data[21] == '')) {
					$data[21] = $data[6];
				}
				# determine rank
				$rank='';
				if ($data[17] != '') {
					$rank = 'form';
				} elseif ($data[14] != '') {	
					$rank = 'subvariety';
				} elseif ($data[11] != '') {	
					$rank = 'variety';
				} elseif ($data[8] != '') {	
					$rank = 'subspecies';
				} else {	
					$rank = 'species';
				}
				# Store Record to file
				# Note: we link every record only to the genus, so infraspecific names will not point to the species!
				$tmpRec = array($data[0], $data[20], $data[21], $rank, $data[1], 'g' . $data[2], $data[22], $data[23] );
				fputs($fp_taxa, implode( $this->delimiter, (array) $tmpRec )  . $this->cr );				
			}
			fclose($fp_source);
			
			fclose($fp_taxa);
			fclose($fp_common);			
		}
		
		public function createEml() {
			$tpl = file_get_contents( $this->source_path . $this->eml_filename );
			$tpl = str_replace("{title}", 'GRIN', $tpl);
			$tpl = str_replace("{givenName}", '', $tpl);
			$tpl = str_replace("{surName}", '', $tpl);
			$tpl = str_replace("{organizationName}", 'Germplasm Resources Information Network', $tpl);
			$tpl = str_replace("{city}", '', $tpl);
			$tpl = str_replace("{administrativeArea}", '' , $tpl);
			$tpl = str_replace("{postalCode}", '', $tpl);
			$tpl = str_replace("{country}", 'US', $tpl);
			$tpl = str_replace("{electronicMailAddress}", '', $tpl);
			$tpl = str_replace("{onlineUrl}", 'http://www.ars-grin.gov/', $tpl);
			$tpl = str_replace("{logoUrl}", '', $tpl);
			$tpl = str_replace("{pubDate}", date("Y/m/d"), $tpl);
			$tpl = str_replace("{abstract}", '', $tpl);
			$tpl = str_replace("{recordLinkUrl}", '' , $tpl);

			$fp = fopen( $this->output_path . $this->eml_filename, "w" );
			fwrite( $fp, $tpl );
			fclose( $fp );
		}

		public function createMeta() {
			copy($this->source_path . 'sample.meta.xml', $this->output_path . 'meta.xml');
		}
		
		public function zipArchive() {

			$filename = $this->output_path . $this->zip_filename;
			
			$zip = new zip_file( $filename );
			$zip->set_options(array('inmemory' => 1, 'recurse' => 0, 'storepaths' => 0));

			$zip->add_files($this->output_path . $this->eml_filename);
			$zip->add_files($this->output_path . $this->meta_filename);
			$zip->add_files($this->output_path . $this->filename);
			$zip->add_files($this->output_path . $this->vernacular_filename);			
			$zip->create_archive();
			$zip->save_file( $filename );

			@unlink($this->output_path . $this->filename);
			@unlink($this->output_path . $this->eml_filename);
			@unlink($this->output_path . $this->meta_filename);
			@unlink($this->output_path . $this->vernacular_filename);

		}
		
	}

?>
