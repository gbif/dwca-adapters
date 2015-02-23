<?php
/**
 * 
 */

/**
 * Archive
 */

class Archive
{
	public $fields = array(),$id,$head_element='extension';

/**
 * sets the values to the properties
 * @param string $name
 * @param string $value
 * @return bool
 */
	public function set($name,$value) {
		$this->$name = $value;
		return true;
	}

/**
 * Adds the fields
 * @param string $field : field name
 * @param integer $index : order index of the fields
 */

	public function addField ( $index, $field_name ) {
		$this->fields[$index] = $field_name;
	}

/**
 * Sets the head attribute
 * @param string $attrib_name : attribute name
 * @param integer $attrib_value : attribute value
 */

	public function setHeadAttribute ( $attrib_name,  $attrib_value) {
		$this->head_attributes[$attrib_name] = $attrib_value;
	}

/**
 * gets the values of the properties
 * @param string $name
 * @return mixed|boolean : value of the property or false in case it is absent
 */

	public function get($name) {
		if( !is_null($this->$name) ) {
			return $this->$name;
		} else {
			return false;
		}
	}

}
?>