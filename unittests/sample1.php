<?php
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
// $core->set('head_attributes',array_merge(array('location' => 'whales.txt'),$head_array));
$core->setHeadAttribute('location','whales.txt');
$core->set('id',array('id','dwc:taxonID'));
$core->addField(1,'dwc:scientificName');
$core->addField(2,'dwc:taxonRank');
$core->addField(3,'dwc:higherTaxonID');
$core->addField(4,'dwc:taxonomicStatus');
$core->addField(5,'dwc:acceptedTaxonID');
$core->addField(6,'dwc:basionymID');
$core->addField(7,'dwc:namePublishedIn');
$core->addField(8,'dwc:taxonAccordingTo');
$core->addField(8,'dwc:nomenclaturalStatus');
$core->addField(10,'dwc:nomenclaturalCode');
$core->addField(11,'dwc:taxonRemarks');
$ar->addObj($core);

// Vernacular
$ext = $ar->addPackage("gbifVernacular");
$ext->setHeadAttribute('location','vernacular.txt');
$ext->set('id',array('coreid','dwc:taxonID'));
$ext->addField(1,'gbif:vernacularName');
$ext->addField(2,'dc:language');
$ar->addObj($ext);

// Taxon Description
$ext = $ar->addPackage('gbifTaxonDescription');
// $ext->setHeadAttribute('location','taxon.txt');
$ext->set('id',array('coreid','dwc:taxonID'));
$ext->addField(1,'dc:description');
$ext->addField(2,'dc:type');
$ext->addField(3,'dc:source');
$ar->addObj($ext);

// Alternative Identifiers
$ext = $ar->addPackage('gbifAlternativeIdentifiers');
// $ext->setHeadAttribute('location','vernacular.txt');
$ext->set('id',array('coreid','dwc:taxonID'));
$ext->addField(1,'dc:identifier');
$ext->addField(2,'dc:format');
$ar->addObj($ext);

$data = $ar->generateMeta('XML');

print $data;

// print json_encode($data);

?>