<?php

    //$destination_folder = './Store'; 
    //$zipfile_path = 'mobilebible.zip';
class Zip extends ZipArchive{

    private $hotelbmsZipPath;
    private $hotelbmsZipDestinationFolder;
   
    function __construct($destination_folder, $zipfile_path){
        $this->hotelbmsZipPath = $zipfile_path;
        $this->hotelbmsZipDestinationFolder = $destination_folder;

    }
   
    function extractZip(){
        return $this->extractTo($this->hotelbmsZipDestinationFolder);
       
    }
    function extractZipOpen(){
       
    return  $this->open($this->hotelbmsZipPath);   
    }

    function __destruct(){
        $this->close();
    }

    public static function checkExtraction($obj){
        if ($obj->extractZipOpen() === TRUE) {
          $obj->extractZip();
                return true;
          } else {
               return false;
        }
    }
   

}

 

?>