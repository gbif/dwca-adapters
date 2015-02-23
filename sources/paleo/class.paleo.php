<?php
/**
 *
 */

	require_once('sources/class.mysqlidatabase.php');

	/**
	 *
	 */
	 
	class paleo {

		private $db;
		private $mysql_host;
		private $mysql_name;
		private $mysql_user;
		private $mysql_pass;
		public $source_name = 'valid_taxa.csv';
		public $source_path = "sources/paleo/source/";
		public $output_path = "sources/paleo/output/";
		public $filename = "paleo_data.csv";
		public $meta_filename = 'meta.xml';
		public $zip_filename = 'paleo_archive.zip';
		public $vernacular_files = array();
		public $taxa_files = array();
		public $norm;

		/**
		 *
		 */
		public function __construct( $mysql_host, $mysql_name, $mysql_user, $mysql_pass, $port=3306 ) {
//			$this->field_name .= $mysql_name;
			$this->mysql_host = $mysql_host;
			$this->mysql_name = $mysql_name;
			$this->mysql_user = $mysql_user;
			$this->mysql_pass = $mysql_pass;
			$connection_string="server=$mysql_host; database=$mysql_name; username=$mysql_user; password=$mysql_pass; port=$port;";			
			$this->db = new MysqliDatabase($connection_string);
		}

		public function loadData() {
			
			$fp_source = @fopen($this->source_path . $this->source_name, 'r');
			fgetcsv($fp_source, 10000, ",");
			$i= 0;
			$this->db->query("TRUNCATE records;");
			while (($data = fgetcsv($fp_source, 10000, ",")) !== FALSE) {
				$author = trim($data[11] . ' ' . $data[12]);
				$author2 = trim($data[13] . ' ' . $data[14]);
				
				if ($author2 != '') {
					$author .= ", " . $author2;
				}
				
				$author .= ' ' . $data[16];
				$author = trim($author);
				$query = sprintf("INSERT INTO records (TaxonID, TaxonRank, ScientificName, HigherScientificName, ScientificNameAuthorship) VALUES('%s', '%s', '%s', '%s', '%s');"
					, $data[4], mysql_escape_string($data[7])
					, mysql_escape_string($data[5])
					, mysql_escape_string($data[19]), mysql_escape_string($author) );
#print $query . "<br>";				

				$this->db->query($query);
				$i++;
#				if ($i==10)
#				exit();
				
			}
			fclose($fp_source);

		}
		
		/**
		 *
		 */
		public function createHigherTaxa( $database ) {
			
			$filename = 'highertaxa_' . str_replace( array(' '), '_', strtolower($database->database_name)) . '.csv';
			
			$query = sprintf("SELECT record_id, kingdom, phylum, class, `order`, family, superfamily, is_accepted_name FROM families WHERE database_id = %s"
				, $database->record_id );
			
			$recs = $this->db->query_all($query);
			if ( is_array($recs) ) {			
#				@unlink($this->output_path . 'tmp.csv');
				$fp = fopen($this->output_path . $filename, 'w');
		    fputcsv($fp, array('TaxonID', 'Kingdom', 'Phylum', 'Class', 'Order', 'Family', 'SuperFamily', 'IsAcceptedName') );
				foreach( $recs as $rec) {
			    fputcsv($fp, (array) $rec);
				}
				fclose($fp);
/*				
				$this->norm = new NormalizeIT();
				$this->norm->lowest_rank = 'FAMILY';
				$this->norm->process( $this->output_path . 'tmp.csv' );
				$this->norm->write_file( $this->output_path . $filename );
				$this->highertaxa_files[] = 'highertaxa_' . str_replace( array(' '), '_', strtolower($database->database_name)) . '.csv';
*/				
			}
			
		}
		
		public function createCommonNames( $database, $taxon ) {
			$query = sprintf("
				SELECT t.name, t.taxon, c.record_id, c.common_name, c.database_id, c.language 
				FROM common_names c, taxa t
				WHERE c.reference_id = t.record_id AND c.database_id = %s
				AND t.taxon = '%s'
			", $database->record_id, $taxon);
			$ref = $this->db->query($query);
			if ( mysql_num_rows($ref) > 0 ) {
				$this->vernacular_files[] = 'vernacluar_' . strtolower($taxon) . '_' . str_replace( array(' '), '_', strtolower($database->database_name)) . '.csv';
/*				
				$fp = fopen($this->output_path . 'vernacluar_' . strtolower($taxon) . '_' . str_replace( array(' '), '_', strtolower($database->database_name)) . '.csv', 'w');
				while ($rec = (array) $ref->fetch_object() ) {
					fputcsv($fp, $rec);
				}
				fclose($fp);
*/				
			}
		}
				
		public function createSpecies( $parent_id=0 ) {

			static $i = 0;
			
			$query = sprintf("
				SELECT 
					p.parent_no AS HigherTaxonID, p.child_no as TaxonID, p.child_name AS ScientificName, concat(author1init, ' ', author1last) AS Author, concat(author2init, ' ', author2last) AS Author2, pubyr, basis, status
				FROM 
					animalia p
				WHERE p.child_no NOT IN (SELECT parent_no FROM animalia);
			");
#print $query;
			$res = $this->db->query_all($query);
			if (is_array($res)) {
			foreach($res as $rec) {
				$this->getHigherTaxa($rec->TaxonID);
				$i++;
				if ($i == 10)
				exit();
			}
			}
//			print_r($res);
/*			
			while ($rec = $Ret->fetch_object() ) {
				print_r($rec);
			}
*/
		}
		
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
			foreach ($this->taxa_files as $file) {
				$core = $ar->addPackage("dwcCore");
				$core->set('head_element','core');
				$core->setHeadAttribute('location', $file);
				$core->set('id', array('id','dwc:taxonID', 0));
				$core->addField(1,'dwc:parentNameUsageID');
				$core->addField(3,'dwc:higherTaxon');
				$core->addField(4,'dwc:taxonRank');
				$core->addField(5,'dwc:scientificNameAuthorship');
				$core->addField(6,'dwc:taxonomicStatus');
				$ar->addObj($core);
			}

			// Vernacular
			foreach ($this->vernacular_files as $file) {
				$ext = $ar->addPackage("gbifVernacular");
				$ext->setHeadAttribute('location', $file);
				$ext->set('id',array('coreid','dwc:taxonID', 2));
				$ext->addField(0,'dwc:higherTaxon');
				$ext->addField(1,'dwc:taxonRank');
				$ext->addField(3,'gbif:vernacularName');
				$ext->addField(5,'dc:language');
				$ar->addObj($ext);
			}
			
			$data = $ar->generateMeta('XML');
			file_put_contents( $this->output_path . $this->meta_filename, $data );
			
		}
		
		public function zipArchive() {
			$zip = new ZipArchive();
			$filename = $this->source_path . $this->zip_filename;
			
			if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE) {
					exit("cannot open <$filename>\n");
			}
			
			$zip->addFile($this->source_path . $this->meta_filename);
			$zip->addFile($this->source_path . $this->filename);
			$zip->close();
		}

	}

?>