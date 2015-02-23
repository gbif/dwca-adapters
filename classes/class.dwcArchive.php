<?php

/**
 *
 */

/**
 * Includes the class files
 */
require_once('class.Archive.php');
require_once('class.dwcCore.php');
require_once('class.gbifVernacular.php');
require_once('class.gbifTaxonDistribution.php');
require_once('class.gbifTaxonDescription.php');
require_once('class.gbifAlternativeIdentifiers.php');
require_once('class.gbifTypes.php');

/**
 *
 */
class dwcArchive
{

	public $terms;
	public $obj_array = array();
	public $node = 'archive';
	public $node_attributes = array(array('xmlns','http://rs.tdwg.org/dwc/terms/xsd/archive/'));
	public $xml;
	public $class_names = array('dwcCore', 'gbifAlternativeIdentifiers', 'gbifTaxonDescription', 'gbifTaxonDistribution', 'gbifTypes', 'gbifVernacular');
	public $head_attributes = array(
				  'encoding' => 'UTF-8'
				, 'linesTerminatedBy' => '\n'
				, 'fieldsTerminatedBy' => '\t'
				, 'fieldsEnclosedBy' => ''
				, 'ignoreHeaderLines' => '0'
				, 'rowType' => 'http://rs.tdwg.org/dwc/terms/Taxon'
				);

	public function get ( $name ) {
		if(!is_null($this->$name)) {
			return $this->$name;
		} else {
			return false;
		}
	}

	public function set($name,$value) {
		$this->$name = $value;
	}

	public function addPackage ($class_name) {
		if(in_array($class_name,$this->class_names)) {
			$obj = new $class_name;
			return $obj;
		} else {
			return false;
		}
	}

	public function addObj($obj) {
		if($obj != '') {
			$this->obj_array[] = $obj;
			return true;
		}
		return false;
	}

/**
 * Generates the metadata
 * @param string $mode
 */
	public function generateMeta ($mode = 'XML') {
		switch(@strtoupper($mode)) {
			case 'XML':

			$terms = $this->get('terms');
$xml_str = <<<EOT
<?xml version='1.0'?>
<archive xmlns="http://rs.tdwg.org/dwc/terms/xsd/archive/"></archive>
EOT;
			$this->xml = simplexml_load_string($xml_str);
			if(count($this->obj_array)) {
				foreach($this->obj_array as $obj) {
					$head_attributes = array_merge($this->head_attributes,$obj->head_attributes);
					$child = $this->addChild($this->xml, $obj->head_element, $head_attributes);
					if(count($obj->id) and is_array($obj->id)) {
						list($id_title,$id_field, $id_index) = $obj->id;
						$id_attributes = array();
						$id_attributes['index'] = $id_index;
						$id_attributes['term'] = $this->getLink($id_field);
						$this->addChild($child, $id_title, $id_attributes);
						unset($id_attributes);
					}
					if(count($obj->fields) and is_array($obj->fields)) {
						foreach($obj->fields as $index => $field ) {
							$field_attributes = array();
							$field_attributes['index'] = $index;
							$field_attributes['term'] = $this->getLink($field);
							$this->addChild($child, 'field', $field_attributes);
							unset($field_attributes);
						}
					}
				}
			}

			$str = $this->xml->asXML();

			return $str;

				break;
			default:
				return false;
				break;
		}
	}

	public function addChild($xml, $element, $attributes) {
		$child = $xml->addChild($element);
		if( is_array($attributes) && count($attributes) ) {
			foreach($attributes as $name => $value) {
				$child->addAttribute($name,$value);
			}
		}
		return $child;
	}

/**
 * Prepares the link for the item
 * @param string $field
 * @return string|boolean
 */
	public function getLink($field) {
		$terms = $this->get('terms');
		if($field != '') {
			$ary = explode(':',$field);
			$field = $terms[$ary[0]] . $ary[1];
			return $field;
		}
		return false;
	}

}


?>