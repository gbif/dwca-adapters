<?php

/**
 *
 */

class tol {

	public $source_name = 'tolskeletaldump.xml';
	public $source_path = '';
	public $output_path = '';
	public $eml_filename = 'eml.xml';
	public $filename = 'tol_data.txt';
	public $meta_filename = 'meta.xml';
	public $zip_filename = 'tol_archive.zip';
	public $delimiter = "\t";
	public $cr = "\n";

	function __construct() {
		$this->source_path = BASE_PATH . "sources/tol/source/";
		$this->output_path = BASE_PATH . "sources/tol/output/";
	}

	public function downloadData() {
		
		$fp = fopen( $this->source_path . "tolskeletaldump.zip", 'w');
		fwrite($fp, file_get_contents('http://tolweb.org/data/tolskeletaldump.zip') );
		fclose($fp);

		unzip( $this->source_path . "tolskeletaldump.zip", $this->source_path );
		unlink($this->source_path . "tolskeletaldump.zip");
	}

	public function getChildren( $fp, $nodes, $parentId, $level ) {
	
		foreach( $nodes as $name => $node ) {
			switch( $name ) {
				case 'NODES':
					$this->getChildren( $fp, $node, $parentId, $level+1 );
					break;

				default:
				case 'NODE':
					if (isset($node["@attributes"]) ) {
						fputs($fp, implode( $this->delimiter, array_merge( $node["@attributes"], array( "NAME" => $node["NAME"], "PARENTID" => $parentId ) ))  . $this->cr );					
#						fputcsv($fp, array_merge( $node["@attributes"], array( "NAME" => $node["NAME"], "PARENTID" => $parentId ) ) );
						
						if (isset( $node["NODES"] ) ) {
							$this->getChildren( $fp, $node["NODES"], $node["@attributes"]["ID"], $level+1 );
						}
					} else {
						foreach( $node as $name => $nodes ) {
							if(isset($nodes["@attributes"])) {
								fputs($fp, implode( $this->delimiter, array_merge( $nodes["@attributes"], array( "NAME" => $nodes["NAME"], "PARENTID" => $parentId ) ))  . $this->cr );					
#								fputcsv($fp, array_merge( $nodes["@attributes"], array( "NAME" => $nodes["NAME"], "PARENTID" => $parentId ) ) );
							}
							if (isset( $nodes["NODES"] ) ) {
								$this->getChildren( $fp, $nodes["NODES"], $nodes["@attributes"]["ID"], $level + 1 );
							}
						}
					}
					break;
			}
		}
	}

	public function createCSV() {
		require_once('xml2json.php');
		$xmlStringContents = file_get_contents($this->source_path . $this->source_name);
		$tol = json_decode( xml2json::transformXmlStringToJson($xmlStringContents), true );
		$fp = fopen($this->output_path . $this->filename, 'w');
		$header = array('CONFIDENCE', 'LEAF', 'CHILDCOUNT', 'PHYLESIS', 'HASPAGE', 'EXTINCT', 'TaxonID', 'ScientificName', 'HigherTaxonID');
		fputs($fp, implode( $this->delimiter, $header )  . $this->cr );					
#		fputcsv($fp, $header);
		$this->getChildren( $fp, $tol["TREE"], 0, 0 );
		fclose($fp);
	}
		
	public function createMeta() {
		copy($this->source_path . 'sample.meta.xml', $this->output_path . 'meta.xml');
	}

	public function createEml() {
		$tpl = file_get_contents( $this->source_path . $this->eml_filename );
		$tpl = str_replace("{title}", 'The Tree of Life Web Project' , $tpl);
		$tpl = str_replace("{givenName}", '' , $tpl);
		$tpl = str_replace("{surName}", '', $tpl);
		$tpl = str_replace("{organizationName}", '', $tpl);
		$tpl = str_replace("{city}", '' , $tpl);
		$tpl = str_replace("{administrativeArea}", '' , $tpl);
		$tpl = str_replace("{postalCode}", '' , $tpl);
		$tpl = str_replace("{country}", '' , $tpl);
		$tpl = str_replace("{electronicMailAddress}", '' , $tpl);
		$tpl = str_replace("{onlineUrl}", 'http://tolweb.org/' , $tpl);
		$tpl = str_replace("{logoUrl}", '' , $tpl);
		$tpl = str_replace("{pubDate}", date("Y/m/d") , $tpl);
		$tpl = str_replace("{abstract}", '' , $tpl);
		$tpl = str_replace("{recordLinkUrl}", 'http://tolweb.org/{NAME}/{ID}' , $tpl);

		@unlink($this->output_path . $this->eml_filename);
		$fp = fopen( $this->output_path . $this->eml_filename, "w" );
		fwrite( $fp, $tpl );
		fclose( $fp );

	}

	public function zipArchive() {

		$filename = $this->output_path . $this->zip_filename;
		@unlink($filename); // remove old zip file before making the new version
		
		$zip = new zip_file( $filename );
		$zip->set_options(array('inmemory' => 1, 'recurse' => 0, 'storepaths' => 0));

		$zip->add_files($this->output_path . $this->eml_filename, $this->eml_filename);
		$zip->add_files( $this->output_path . $this->meta_filename, $this->meta_filename );
		$zip->add_files( $this->output_path . $this->filename, $this->filename );
		$zip->create_archive();
		$zip->save_file( $filename );
		
		unlink($this->source_path . $this->source_name);
		unlink($this->output_path . $this->eml_filename);
		unlink($this->output_path . $this->meta_filename);
		unlink($this->output_path . $this->filename);
	}

}

?>