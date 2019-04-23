<?php

	class usda {

		private $families = array();
		public $source_path = '';
		public $output_path = '';
		public $filename = "usda_data.csv";
		public $dwc_filename = 'family.txt';
		public $species_filename = 'species.txt';
		public $vernacular_filename = 'vernacular.txt';
		public $geography_filename = 'geography.txt';
		public $meta_filename = 'meta.xml';
		public $eml_filename = 'eml.xml';
		public $zip_filename = 'usda_archive.zip';
		public $delimiter = "\t";
		public $cr = "\n";

		function __construct() {
			$this->source_path = BASE_PATH . "sources/usda/source/";
			$this->output_path = BASE_PATH . "sources/usda/output/";
			$this->taxa = array();
   	}
		
		public function downloadData() {
			$fp = fopen( $this->source_path . $this->filename, 'w');
			$basic_url = "https://plants.usda.gov/java/downloadData?fileName=plantlst.txt&static=true";
			$url = "https://plants.usda.gov/java/AdvancedSearchServlet?alepth_ind=any&anerb_tolr_cd=any&author_ranks=any&authorname=&bloat_pot_cd=any&bloom_prd_cd=any&c_n_ratio_cd=any&caco3_tolr_cd=any&category=any&class=any&cold_strat_ind=any&coppice_pot_ind=any&county=any&division=any&download=on&drght_tolr_cd=any&dsp_authorname_separate=on&dsp_category=on&dsp_class=on&dsp_division=on&dsp_famcomname=on&dsp_family=on&dsp_familySym=on&dsp_genus=on&dsp_itis_tsn=on&dsp_kingdom=on&dsp_orders=on&dsp_pfa=on&dsp_statefips=on&dsp_subclass=on&dsp_subdivision=on&dsp_subkingdom=on&dsp_superdivision=on&dsp_symbol=on&dsp_synonyms=on&dsp_vernacular=on&dur=any&epithet_rank=any&fall_cspc_ind=any&famcomname=any&family=any&familySym=any&fed_nox_status_ind=any&fed_te_status=any&fednoxname=&fedtename=&fert_rqmt_cd=any&fire_resist_ind=any&fire_tolr_cd=any&flwr_color_cd=any&flwr_cspc_ind=any&foddr_suit_ind=any&folg_color_cd=any&folg_prsty_sumr_cd=any&folg_prsty_wntr_cd=any&folg_txt_cd=any&frost_free_day_min_rng=any&frut_body_suit_ind=any&frut_seed_abund_cd=any&frut_seed_color_cd=any&frut_seed_cspc_ind=any&frut_seed_end_cd=any&frut_seed_prst_ind=any&frut_seed_start_cd=any&fuel_wood_suit_cd=any&genus=&gras_low_grw_ind=any&grwhabt=any&grwth_form_cd=any&grwth_habit_cd=any&grwth_prd_actv_cd=any&grwth_rate_cd=any&hedg_tolr_cd=any&history=1&ht_at_mtrty_rng=any&ht_max_base_age_rng=any&hybrids=any&image_ind=any&includeAuthors=on&invasive_pubs=any&itis_tsn=&kingdom=any&leaf_retnt_ind=any&lfspn_cd=any&lmbr_suit_ind=any&moist_use_cd=any&n_fix_pot_cd=any&nat_wet_ind=any&nativestatuscode=any&navl_stor_suit_ind=any&nreg_wet_status=any&nurs_stk_suit_ind=any&orders=any&palat_animl_brs_cd=any&palat_animl_grz_cd=any&palat_human_ind=any&pfa=pfa&plantchar_ind=any&plantfact_ind=any&plantguide_ind=any&plnt_den_high_rng=any&plnt_den_low_rng=any&plpwd_suit_ind=any&plywd_vnr_suit_ind=any&post_suit_ind=any&precip_tolr_max_rng=any&precip_tolr_min_rng=any&protein_pot_cd=any&prpg_bare_root_ind=any&prpg_bulb_ind=any&prpg_corm_ind=any&prpg_ctnr_ind=any&prpg_cut_ind=any&prpg_seed_ind=any&prpg_sod_ind=any&prpg_sprig_ind=any&prpg_tubr_ind=any&rank=g&rank=v&rank=b&rank=sv&rank=s&rank=f&rgrwth_rate_cd=any&root_dpth_min_rng=any&rsprt_able_ind=any&sciname=&seed_per_lb_rng=any&seed_sprd_rate_cd=any&seedling_vgor_cd=any&shade_tolr_cd=any&slin_tolr_cd=any&sm_grain_ind=any&soil_adp_c_txt_ind=any&soil_adp_f_txt_ind=any&soil_adp_m_txt_ind=any&soil_ph_tolr_max_rng=any&soil_ph_tolr_min_rng=any&state_nox_status=any&state_te_status=any&statefips=any&statenoxname=&statetename=&subclass=any&subdivision=any&subkingdom=any&submit=display&submit.x=69&submit.y=8&superdivision=any&symbol=&synonyms=all&temp_tolr_min_rng=any&tox_cd=any&url=on&veg_sprd_rate_cd=any&vernacular=&viewby=sciname&vs_comm_avail=any&wet_region=any&xmas_tree_suit_ind=any";

			$ctx = stream_context_create(array(
					'http' => array(
							'timeout' => 120
							)
					)
			); 
			fwrite($fp, file_get_contents($url, 0 , $ctx) );
			fclose($fp);
		}

		public function addTaxa( $rec ) {
			static $index = 1;			
			$parent = '';
			if ($rec->kingdom != '') {
				if (!isset( $this->taxa[$rec->kingdom] ) ) {
					$this->taxa[ $rec->kingdom ] = array(
						  "rank" => "kingdom"
						,	"parent" => $parent
						,	"value" => $rec->kingdom
						,	"index" => $index++
						,	"common" => ""
					);
				}
			}

			$parent = $rec->kingdom . $parent;
			if ($rec->subkingdom != '') {
				if (!isset( $this->taxa[$rec->subkingdom . $parent] ) ) {
					$this->taxa[ $rec->subkingdom . $parent ] = array(
						  "rank" => "subkingdom"
						,	"parent" => $parent
						,	"value" => $rec->subkingdom
						,	"index" => $index++
						,	"common" => ""
					);
				}
			}

			$parent = $rec->subkingdom . $parent;
			if ($rec->superdivision != '') {
				if (!isset( $this->taxa[$rec->superdivision . $parent] ) ) {
					$this->taxa[ $rec->superdivision . $parent ] = array(
						  "rank" => "superdivision"
						,	"parent" => $parent
						,	"value" => $rec->superdivision
						,	"index" => $index++
						,	"common" => ""
					);
				}
			}

			$parent = $rec->superdivision . $parent;
			if (trim($rec->division) != '') {
				if (!isset( $this->taxa[$rec->division . $parent] ) ) {
					$this->taxa[ $rec->division . $parent ] = array(
						  "rank" => "division"
						,	"parent" => $parent
						,	"value" => $rec->division
						,	"index" => $index++
						,	"common" => ""
					);
				}
			}

			$parent = $rec->division . $parent;
			if ($rec->subdivision != '') {
				if (!isset( $this->taxa[$rec->subdivision . $parent] ) ) {
					$this->taxa[ $rec->subdivision . $parent ] = array(
						  "rank" => "subdivision"
						,	"parent" => $parent
						,	"value" => $rec->subdivision
						,	"index" => $index++
						,	"common" => ""
					);
				}
			}

			$parent = $rec->subdivision . $parent;
			if ($rec->class != '') {
				if (!isset( $this->taxa[$rec->class . $parent] ) ) {
					$this->taxa[ $rec->class . $parent ] = array(
						  "rank" => "class"
						,	"parent" => $parent
						,	"value" => $rec->class
						,	"index" => $index++
						,	"common" => ""
					);
				}
			}

			$parent = $rec->class . $parent;
			if ($rec->subclass != '') {
				if (!isset( $this->taxa[$rec->subclass . $parent] ) ) {
					$this->taxa[ $rec->subclass . $parent ] = array(
						  "rank" => "subclass"
						,	"parent" => $parent
						,	"value" => $rec->subclass
						,	"index" => $index++
						,	"common" => ""
					);
				}
			}

			$parent = $rec->subclass . $parent;
			if ($rec->order != '') {
				if (!isset( $this->taxa[$rec->order . $parent] ) ) {
					$this->taxa[ $rec->order . $parent ] = array(
						  "rank" => "order"
						,	"parent" => $parent
						,	"value" => $rec->order
						,	"index" => $index++
						,	"common" => ""
					);
				}
			}

			$parent = $rec->order . $parent;
			if ($rec->family != '') {
				if (!isset( $this->taxa[$rec->family . $parent] ) ) {
					$this->taxa[ $rec->family . $parent ] = array(
						  "rank" => "family"
						,	"parent" => $parent
						,	"value" => $rec->family
						,	"index" => $rec->family_symb
						, "common" => str_replace(" family", "", $rec->family_common)
					);
				}
			}

		}
		
		public function createHigherTaxa() {
			if (file_exists( $this->source_path . $this->filename ) ) {
				$fp_source = fopen($this->source_path . $this->filename, "r");
				$data = fgetcsv($fp_source, 10000, ","); // Skip first line
				while (($data = fgetcsv($fp_source, 10000, ",")) !== FALSE) {
					$tmp->kingdom = $data[38];
					$tmp->subkingdom = $data[37];
					$tmp->superdivision = $data[36];
					$tmp->division = $data[35];
					$tmp->subdivision = $data[34];
					$tmp->class = $data[33];
					$tmp->subclass = $data[32];
					$tmp->order = $data[31];
					$tmp->family = $data[28];
					$tmp->family_symb = $data[29];
					$tmp->family_common = $data[30];
					$this->addTaxa( $tmp );
				}				
				
				$fp_species = fopen($this->output_path . $this->species_filename, "w");
				fputs($fp_species, implode( $this->delimiter, array( 'TaxonID', 'ScientificName', 'HigherTaxonID', 'TaxonRank', 'ITIS ID', "Accepted Name Usage ID", "Taxonomic Status" ))  . $this->cr );
				$fp_vernacular = fopen($this->output_path . $this->vernacular_filename, "w");
				fputs($fp_vernacular, implode( $this->delimiter, array( 'TaxonID', 'ScientificName', 'Common Name', 'Language'))  . $this->cr );
				foreach( $this->taxa as $rec) {					
					fputs($fp_species, implode( $this->delimiter, array( $rec["index"], $rec["value"], @$this->taxa[$rec["parent"]]["index"], $rec["rank"] ))  . $this->cr );
					if ($rec["common"] != '') {
						// Save common name to file
						fputs($fp_vernacular, implode( $this->delimiter, array( $rec["index"], $rec["value"], $rec["common"], "en")) . $this->cr );
					}
				}
			}
		}
		
		public function createCSV() {
			if (file_exists( $this->source_path . $this->filename ) ) {
				$fp_source = fopen($this->source_path . $this->filename, "r");
				$data = fgetcsv($fp_source, 10000, ","); // Skip first line

				$fp_species = fopen($this->output_path . $this->species_filename, "a");
				$fp_vernacular = fopen($this->output_path . $this->vernacular_filename, "a");
				$fp_geography = fopen($this->output_path . $this->geography_filename, "w");
				fputs($fp_geography, implode( $this->delimiter, array( 'Taxon ID', 'Country Code', 'Location ID', 'Locality', 'Occurrence Status'))  . $this->cr );

				while (($data = fgetcsv($fp_source, 100000, ",")) !== FALSE) {
					
					if ($data[1] == '') {
						// Accepted Species
						fputs($fp_species, implode( $this->delimiter, array( $data[0], $data[3], $data[29], "", $data[39] ) )  . $this->cr );
						
						if ($data[23] != '') {
							// Save common name to file
							fputs($fp_vernacular, implode( $this->delimiter, array( $data[0], $data[3], $data[23], "en")) . $this->cr );
						}
						
						// Write Geography regions to file.
						if ($data[25] != '') {
							$tmp = explode("), ", $data[25]); // Split between groups like US (...), CA (...)
							if (!is_array($tmp)) {
								// Only one group so strip end and put in the array
								$tmp[] = str_replace(")", "", $data[21]);
							}
							if( is_array($tmp) ) {
							foreach( $tmp as $group ) {
								$t2 = explode(" (", $group);
								$country = $t2[0];
								$states = explode(",", $t2[1]);
								if (is_array($states)) {
								foreach( $states as $state ) {
									$state = trim(str_replace(")", "", $state));
									fputs($fp_geography, implode( $this->delimiter, array( $data[0], substr($country, 0,2), substr($country, 0,2) . "-" . $state, $state, "present")) . $this->cr );
								}
								}
							}
							}
						}

					} else {
						// Synonym
						fputs($fp_species, implode( $this->delimiter, array( $data[1], $data[3], $data[29], "", $data[39], $data[0], "synonym" ) )  . $this->cr );
					}

				}
				fclose($fp_species);
				fclose($fp_source);
		
				return( true );
			} else {
				return( false );
			}
		}
		
		public function createMeta() {
			copy($this->source_path . 'sample.meta.xml', $this->output_path . 'meta.xml');
		}
	
		public function createEml() {
			$tpl = file_get_contents( $this->source_path . $this->eml_filename );
			$tpl = str_replace("{title}", 'USDA Plants' , $tpl);
			$tpl = str_replace("{givenName}", '' , $tpl);
			$tpl = str_replace("{surName}", '', $tpl);
			$tpl = str_replace("{organizationName}", 'United States Department of Agriculture', $tpl);
			$tpl = str_replace("{city}", '' , $tpl);
			$tpl = str_replace("{administrativeArea}", '' , $tpl);
			$tpl = str_replace("{postalCode}", '' , $tpl);
			$tpl = str_replace("{country}", 'USA' , $tpl);
			$tpl = str_replace("{electronicMailAddress}", '' , $tpl);
			$tpl = str_replace("{onlineUrl}", 'https://plants.usda.gov/' , $tpl);
			$tpl = str_replace("{logoUrl}", '' , $tpl);
			$tpl = str_replace("{pubDate}", date("Y/m/d") , $tpl);
			$tpl = str_replace("{abstract}", '' , $tpl);
			$tpl = str_replace("{recordLinkUrl}", 'https://plants.usda.gov/java/profile?symbol={ID}' , $tpl);

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
			$zip->add_files($this->output_path . $this->dwc_filename, $this->dwc_filename);
			$zip->add_files($this->output_path . $this->species_filename, $this->species_filename);
			$zip->add_files($this->output_path . $this->vernacular_filename, $this->vernacular_filename);
			$zip->add_files($this->output_path . $this->geography_filename, $this->geography_filename);

			$zip->create_archive();
			$zip->save_file( $filename );
			
			@unlink($this->source_path . $this->filename); // Original CSV file downloaded
			@unlink($this->output_path . $this->species_filename);
			@unlink($this->output_path . $this->vernacular_filename);
			@unlink($this->output_path . $this->geography_filename);
			@unlink($this->output_path . $this->eml_filename);
			@unlink($this->output_path . $this->meta_filename);
			
		}
		
	}

?>