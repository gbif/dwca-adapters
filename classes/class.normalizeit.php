<?php

/**
* Class normalize_csv
* @author Michael Giddens
* @link http://www.silverbiology.com
*/

class NormalizeIT {

	private $taxanomic_ranks = array(
			'DOMAIN'
		,	'SUPERKINGDOM'
		,	'KINGDOM'
		,	'SUBKINGDOM'
		,	'INFRAKINGDOM'
		,	'BRANCH'
		,	'SUPERPHYLUM'
		,	'SUPERDIVISION'
		,	'PHYLUM'
		,	'DIVISION'
		,	'SUBPHYLUM'
		,	'INFRAPHYLUM'
		,	'MICROPHYLUM'
		,	'SUPERCLASS'
		,	'CLASS'
		,	'SUBCLASS'
		,	'INFRACLASS'
		,	'PARVCLASS'
		,	'LEGION'
		,	'COHORT'
		,	'MAGNORDER'
		,	'SUPERORDER'
		,	'ORDER'
		,	'SUBORDER'
		,	'INFRAORDER'
		,	'PARVORDER'
		,	'SUPERFAMILY'
		,	'FAMILY'
		,	'SUBFAMILY'
		,	'TRIBE'
		,	'SUBTRIBE'
		,	'ALLIANCE'
		,	'GENUS'
		,	'SUBGENUS'
		,	'SUPERSPECIES'
		,	'SPECIES'
		,	'SUBSPECIES'
		,	'INFRASPECIES'
	);	

	private $extra_mapping = array();
	private $taxon_mapping = array();

	public $lowest_rank = "INFRASPECIES";
	
	public $file_delimiter = ',';
	private $delimiter = ',';

	private $filename_input = "input.csv";
	private $filename_output = "output.csv";

	private $new_columns = array('localID', 'parentID', 'ScientificName', 'Rank');
	private $new_taxanomic = array();
	
	private $extra_columns = array();

	private $tree = array();
	private $tree_index = 0;
	private $tree_data = array();

	/**
	 * Find the correct spot and creates a node in the taxanomic tree.
	 * @param array $row records from csv file
	 */
	private function build_ranks( $row ) {

		$parentID = 0;
		$ptr = &$this->tree;

		foreach( $this->taxon_mapping as $id => $rank ) {
			$value = $row[$id];
			if (trim($value) != '') {
				if (isset( $ptr[$value] ) ) {
					$parentID = $ptr[$value]['id'];
					$ptr = &$ptr[$value];
				} else {
					$ptr[$value] = array(
								'parentID' => $parentID
							,	'rank' => $rank
							,	'id' => ++$this->tree_index
					);
					$parentID = $ptr[$value]['id'];
					$ptr = &$ptr[$value];
				}
			}
		}

		// This fills in all the missing row data and passes the parent_id
		$new_row = $this->add_extra_data( $row );
		$new_row[0] = -1; // ID is set to blank until we know the last tree index.
		$new_row[1] = $parentID; // parentID
		$new_row[2] = ''; // Filler
		$new_row[3] = ''; // Filler
		ksort($new_row);
		$this->tree_data[] = $new_row;
		
	}


	/**
	 * Shifts the row data to the new column position.
	 * @param array $row records from csv file
	 * @return array Same record but in new column position
	 */
	private function add_extra_data( $row ) {
	
		$new_row = array();
		for($i=0; $i < count($row); $i++) {		
			$new_index = $this->extra_mapping[$i]['index'];
			if ($new_index != -1 ) {
				$new_row[ $new_index ] = $row[$i];
			}
		}
		
		return( $new_row );
	}


	/**
	 * Finds the columns that are not ranks.
	 * @param array $old Original Columns
	 * @param array $new New Columns
	 */
	private function build_header_mapping( $old, $new ) {
		foreach( $old as $column ) {
			$index = array_search( $column, $new );
			if (!$index ) {
				$index = -1;
			}
			array_push( $this->extra_mapping, array( 'column' => $column, 'index' => $index ) );
		}
	}

	/**
	 * Finds the columns that are ranks.
	 * @param array $old Original Columns
	 * @param array $new New Columns
	 */
	private function build_taxon_mapping( $old, $new ) {
		foreach( $old as $column ) {
			$index = array_search( $column, $new );
			if (!$index ) {
				$index = -1;
			} else {
				$this->taxon_mapping[ $index ] = $column;
			}
		}
	}

	/**
	 * Identifies which columns are ranks and which are not
	 */
	private function build_new_header() {

		$columns = fgetcsv( $this->handle_input, 10000, $this->file_delimiter);

		if ( count( $columns ) ) {
			$i = 0;
			foreach( $columns as $column ) {			
				if ( in_array( strtoupper( $column ), $this->taxanomic_ranks )  && (array_search(strtoupper($column), $this->taxanomic_ranks) <= array_search($this->lowest_rank, $this->taxanomic_ranks)) ) {
					$this->new_taxanomic[$i] = $column;
				} else {
					array_push( $this->extra_columns, $column );
				}
				$i++;
			}
		}

		$this->new_columns = array_merge( $this->new_columns, $this->extra_columns );
		$this->build_header_mapping( $columns, $this->new_columns );
		$this->build_taxon_mapping( $columns, $this->new_taxanomic );		
		
	}	
	
	/**
	 * Writes the Taxanomic Tree to the output CSV.
	 */
	private function write_tree( $children, $value="") {
		
		if ($value != '') {
			$tmp = array( $children['id'], $children['parentID'], $value, $children['rank'] );
			fputcsv( $this->handle_output, $tmp, $this->delimiter );
		}

		$haystack = array('parentID', 'id', 'rank');	
		foreach( $children as $n => $child) {
			if (!in_array( $n, $haystack ) ) {
				$this->write_tree($child, $n, $output);
			}
		}
		
	}

	/**
	 * Writes the records to the output CSV.
	 */
	private function write_records( $index=0 ) {
		
		if (count($this->tree_data)) {
			foreach( $this->tree_data as $record ) {
				$record[0] = $index++;
				fputcsv( $this->handle_output, $record, $this->delimiter );
			}
		}
		
	}

	/**
	 * This will create the new output CSV file
	 */
	public function write_file( $output_file, $include_header=1 ) {

		$this->handle_output = fopen( $output_file, "w");

		// Writes column header in 1st row.
		if ( $include_header ) {
			$this->write_header();
		}
		
		// Write Taxa tree and records to Output File
		$this->write_tree( $this->tree );
		$this->write_records( $this->tree_index+1 );
	
		fclose($this->handle_output);
	
	}

	/**
	 * This function writes the column header to the csv file.
	 */
	public function write_header() {

		// Write New Header to Output File
		fputcsv( $this->handle_output, $this->new_columns, $this->delimiter );
	
	}
	
	/**
	 * This function prints the header and forces the data to be streamed as the response.
	 */
	public function download_file( $actual_file, $output_file = "output.csv" ) {

		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"$output_file\"");

		$fp = fopen( $actual_file, 'r');
		fpassthru($fp);
		fclose($fp);
		
	}
		
	/**
	 * This is the function that process the input csv file and does the work.
	 */
	public function process( $input_file, $output_type='file' ) {

		// Assign File
		$this->handle_input = fopen( $input_file, "r");

		// Build Header
		$this->build_new_header();

		/**
		*	This will run through the whole data and build the Higer Taxa with
		* parentID relationship to be used in the final species list.
		*/
		while (($row = fgetcsv($this->handle_input, 10000, $this->file_delimiter)) !== FALSE) {
			$this->build_ranks( $row ); // Builds the Taxa Tree
		}

		fclose($this->handle_input);
	}
	
}
?>