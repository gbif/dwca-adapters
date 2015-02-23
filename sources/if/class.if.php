<?php

	/**
	 *
	 */
	 
	class indexFungorum {

		public $archive_file = 'if.zip';
		public $source_name = 'GNI-IFout.txt';
		public $source_path = 'sources/if/source/';
		public $output_path = 'sources/if/output/';
		public $filename = 'if_data.csv';
		public $meta_filename = 'meta.xml';
		public $zip_filename = 'if_archive.zip';

		/**
		 *
		 */
		public function downloadData() {
			$fp = fopen( $this->source_path . $this->archive_file, 'w');
			fwrite($fp, file_get_contents('???') );
			fclose($fp);
			$zip = new ZipArchive;
			$res = $zip->open($this->source_path . $this->archive_file);
			if ($res === TRUE) {
				$zip->extractTo($this->source_path);
				$zip->close();
			}
		}

		/**
		 *
		 */
		public function createCSV() {
			$fp = fopen($this->output_path . 'tmp.csv', 'w');
			fputcsv($fp, array('TaxonID', 'ScientificName', 'ScientificNameAuthorship', 'BasionymID', 'Genus', 'Family', 'Order', 'Class', 'Phylum', 'Kingdom') );
			$fp_source = @fopen($this->source_path . $this->source_name, 'r');
			fgetcsv($fp_source, 10000, ","); // Skip Header Row
			while (($data = fgetcsv($fp_source, 10000, ",")) !== FALSE) {
				list($genus) = split(" ", $data[0]);
				fputcsv($fp, array($data[33], $data[0], trim($data[1] . ' ' . $data[12]), $data[34], $genus, $data[44], $data[45], $data[47], $data[49], $data[50]) );
			}
			fclose($fp_source);
			fclose($fp);
		}
		
		/**
		 *
		 */
		public function createMeta() {
			require_once('classes/class.dwcArchive.php');
			
			$terms_array = array(
				  'dwc' => 'http://rs.tdwg.org/dwc/terms/'
				, 'gbif' => 'http://rs.gbif.org/terms/'
				, 'dc' => 'http://purl.org/dc/terms/'
				);
			
			$ar = new dwcArchive();
			$ar->set('terms',$terms_array);
			
			// core
			$core = $ar->addPackage("dwcCore");
			$core->set('head_element','core');
			$core->setHeadAttribute('location', $this->filename);
			$core->setHeadAttribute('fieldsTerminatedBy', ',');
			$core->setHeadAttribute('fieldsEnclosedBy', '"');
			$core->setHeadAttribute('ignoreHeaderLines', 1);
			
			$core->set('id', array('id','dwc:taxonID', 0));
			$core->addField(1,'dwc:scientificName');
			$core->addField(2,'dwc:scientificNameAuthorship');
			$core->addField(3,'dwc:basionymID');
			$core->addField(4,'dwc:genus');
			$core->addField(5,'dwc:Family');
			$core->addField(6,'dwc:Order');
			$core->addField(7,'dwc:Class');
			$core->addField(8,'dwc:Phylum');
			$core->addField(9,'dwc:Kingdom');
			$ar->addObj($core);
			
			$data = $ar->generateMeta('XML');
			file_put_contents( $this->output_path . $this->meta_filename, $data );
			
		}
		
		/**
		 *
		 */
		public function zipArchive() {
			$zip = new ZipArchive();
			$filename = $this->output_path . $this->zip_filename;
			
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
				exit("cannot open <$filename>\n");
			}
			
			$zip->addFile($this->output_path . $this->meta_filename, $this->meta_filename);
			$zip->addFile($this->output_path . $this->filename, $this->filename);
			$zip->close();
#			unlink($this->output_path . $this->meta_filename);
#			unlink($this->output_path . $this->filename);
#			unlink($this->output_path . $this->vernacular_filename);		

		}

	}

	/**
	 *
	 */
	function trim_space(&$str) {
		$str = trim($str);
	}

?>