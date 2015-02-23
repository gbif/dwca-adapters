<?php

function unzip($file, $output_path ){
    $zip = zip_open($file);
	if (!is_resource($zip)){
		print("Unable to unzip file '{$file}': See errorcode ". $zip. " in http://de.php.net/manual/en/zip.constants.php.\n");
		return("Unable to unzip file '{$file}'");
	}

    $e='';

    while( $zip_entry = zip_read($zip) ) {
       $zdir= dirname(zip_entry_name($zip_entry));
       $zname= $output_path . basename(zip_entry_name($zip_entry));
       print("Unzipping file ".$zname."\n");

       if(!zip_entry_open($zip,$zip_entry,"r")) {$e.="Unable to proccess file '{$zname}'";continue;}
       if(!is_dir($zdir)) mkdirr($zdir,0777);

       $zip_fs=zip_entry_filesize($zip_entry);
       if(empty($zip_fs)) continue;

       $zz=zip_entry_read($zip_entry,$zip_fs);
       $z=fopen($zname,"w");
       fwrite($z,$zz);
       fclose($z);
       zip_entry_close($zip_entry);

    }
    zip_close($zip);

    return($e);
}

function mkdirr($pn,$mode=null) {

  if(is_dir($pn)||empty($pn)) return true;
  $pn=str_replace(array('/', ''),DIRECTORY_SEPARATOR,$pn);

  if(is_file($pn)) {trigger_error('mkdirr() File exists', E_USER_WARNING);return false;}

  $next_pathname=substr($pn,0,strrpos($pn,DIRECTORY_SEPARATOR));
  if(mkdirr($next_pathname,$mode)) {if(!file_exists($pn)) {return mkdir($pn,$mode);} }
  return false;
}

?>