<?php
/**
 *
 * Written by: David Oti.
 * Created: October 19, 2013
 * Last Updated: September 4, 2015
 */
 
class Uploader
{	
    ///////file upload methods//////////////////////////////////

	 private $watermark_image_path = "watermark.png"; //The watermark image

	 private $path = "";


  ########################################################################################### 
          // FOR FILE UPLOAD PURPOSE
  ##########################################################################################  

  public function __construct() {

      // Set attributes
      $this->file_name = ''; //original name of file
      $this->file_tmp_name = ''; //temporary file for upload
      $this->file_size = ''; //size of file
      $this->file_error = ''; //file error(s)
      $this->file_type = ''; //file type
      $this->slug = ''; //unique slug of file

      //set system settings
      $this->ext_type = ''; //Accepted extension types
      $this->mime_type = ''; //Accepted mime types
      $this->location =  ''; //location where files should be uploaded
      $this->max_size = 1000000; //Allowed maximum size
      $this->dynamic_doctrim = true; // true:Allow dynamic folder generation
      $this->dynamic_naming = true; // true:Allow dynamic renaming of files
      $this->old_file = false; //set old file for unlinking
      $this->old_file_ext = false; //set old file for unlinking

    }


    public function upload(){

      if(!empty($this->location)){

         if(isset($this->file_tmp_name) && $this->file_error == 0){
          //1mb max file size  
            if ( $this->max_size < $this->file_size ) {

             throw new Exception('File size too large. should not be above '.Utility::formatSizeUnits($this->max_size, $default = true));
             return false;
            }
            
            //returns a renamed file with extension
            $this->ext = $this->getExt($this->file_name);
            
            //check if file mime type is allowed
            $this->allowed_mime = $this->accessAllowedTypesMime($this->file_type,$this->mime_type); 
            if($this->allowed_mime == false){
              
              throw new Exception('File format not allowed.');
               return false;
            }

            //check if file extension is allowed
            $this->allowed_ext = $this->accessAllowedTypesExt($this->ext,$this->ext_type);

            if($this->allowed_ext == false){
              
              throw new Exception('File format not allowed. Please check if the file is named properly. You can solve this error by renaming the file you are uploading (e.g joe.extension,  Do not use special characters like (.) to name your file `use only alphabets,numbers,signs e.g jeo123$.extension`');
               return false;
            }
            
            //check strictly if file extension is allowed
            $this->allowed_ext_strict = $this->checkAllowedExt($this->file_name,$this->ext_type);

            if($this->allowed_ext_strict == false){
              
              throw new Exception('File format not allowed.');
               return false;
            }

            if($this->dynamic_doctrim == true){

            //dynamically set a date location for our file with the current date
            $this->date_dynamic_location = Utility::unixdatetime_to_date(time()).'/';
            //dynamically set a year location for our file
            $this->year_dynamic_location = date('Y').'/';
            //dynamically set an extension location for our using current file extention
            $this->ext_dynamic_location = $this->ext.'/';

            // Set abstract path to directory
            $this->file_path_abstract = $this->path.$this->ext_dynamic_location.$this->year_dynamic_location.$this->date_dynamic_location;

            // set the full loaction
            $this->file_path = $this->location.$this->ext_dynamic_location.$this->year_dynamic_location.$this->date_dynamic_location;

            }else{

           // Set abstract path to directory
            $this->file_path_abstract = $this->path;
            // set the full loaction
            $this->file_path = $this->location;

            }

            // set the full location including the file
            $this->file_and_location = $this->file_path.$this->slug. "." .$this->ext;

            //make the directory if it does not exist
            // if (!file_exists($file_path) && !is_dir($file_path)) {
           //    mkdir($file_path, 7777, true);         
            //  } 

           if (!file_exists($this->file_path)) {
                mkdir($this->file_path, 0777, true);
              }
            

            if($this->dynamic_naming == true){
              //check if file name already exist in folder, if yes then rename file to a new name
              if (file_exists($this->file_and_location)){
              $this->file_and_location = $this->exist($this->slug,$this->file_path,$this->ext);
               }

             }else{ 
               if ($this->old_file != false && $this->old_file_ext != false) {
                  if (file_exists($this->old_file.$this->old_file_ext)) {
                     unlink($this->old_file.$this->old_file_ext);
                   }
                }
             }
           
            if(move_uploaded_file($this->file_tmp_name, $this->file_and_location)){
            
            //Atempt to create and stamp file if its an image
              $this->legit_img = array('JPEG','jpeg','jpg','JPG');

              if(in_array($this->ext,$this->legit_img)) {

               $this->createImage($this->file_and_location, $this->ext, 400);
            
               }     
            
                $parameters = array('name' => $this->slug, 
                                     'ext' =>  $this->ext,
                                     'size' => $this->file_size,
                                     'path' => $this->file_path_abstract
                                    );
                 //throw new Exception($parameters['path']);
                  return $parameters;
            
                }

                 throw new Exception('Unable to save.  Please try again later.');
               return false;

              } else {
              
              unlink($this->file_tmp_name);

             throw new Exception('Unable to save. Please try again later.');
           return false;
          }


        }else {

           throw new Exception('An error was encountered! Please try again or contact us if error(s) continues.');
          return false;

        }

    }




    private function accessAllowedTypesMime($file_type,$mime_format = array()) {  

    if(in_array($file_type, $mime_format)) {
     return true; 
       } else {
     return false; //file not supported
     }

   }

 
   private function accessAllowedTypesExt($ext,$ext_format = array()) {   
   if(in_array($ext, $ext_format)) {
     return true;  
       } else {
    return false; //file not supported/corrupt
     }
   }

   
    //----------Check allowed extension----------
   private function checkAllowedExt($file,$ext_format = array())
   {
    $temp = strtolower($file);
    $ext = pathinfo($temp, PATHINFO_EXTENSION);
    if (in_array($ext, $ext_format)) {
    return true;
    } else {
    return false; //file not supported
       }
    } 
     



   private function getExt($string) {
   
   list($txt, $ext) = explode(".", $string);
   //return file ext
   return $ext; 
   }



	  /**
     * Resize avatar image (while keeping aspect ratio and cropping it off sexy)
     * Originally written by:
     * @author Jay Zawrotny <jayzawrotny@gmail.com>
     * @license Do whatever you want with it.
     *
     * @param string $source_image The location to the original raw image.
     * @param string $destination_filename The location to save the new image.
     * @param int $width The desired width of the new image
     * @param int $height The desired height of the new image.
     * @param int $quality The quality of the JPG to produce 1 - 100
     * @param bool $crop Whether to crop the image or not. It always crops from the center.
     * @return bool success state
     */
    public function resizeAvatarImage(
        $source_image, $destination_filename='', $width = 144, $height = 144, $quality = 85, $crop = true)
    {
        $image_data = getimagesize($source_image);
        if (!$image_data) {
            return false;
        }

        // set to-be-used function according to filetype
        switch ($image_data['mime']) {
            case 'image/gif':
                $get_func = 'imagecreatefromgif';
                $suffix = ".gif";
            break;
            case 'image/jpeg';
                $get_func = 'imagecreatefromjpeg';
                $suffix = ".jpg";
            break;
            case 'image/png':
                $get_func = 'imagecreatefrompng';
                $suffix = ".png";
            break;
        }

        $img_original = call_user_func($get_func, $source_image );
        $old_width = $image_data[0];
        $old_height = $image_data[1];
        $new_width = $width;
        $new_height = $height;
        $src_x = 0;
        $src_y = 0;
        $current_ratio = round($old_width / $old_height, 2);
        $desired_ratio_after = round($width / $height, 2);
        $desired_ratio_before = round($height / $width, 2);

        if ($old_width < $width OR $old_height < $height) {
             // the desired image size is bigger than the original image. Best not to do anything at all really.
            return false;
        }

        // if crop is on: it will take an image and best fit it so it will always come out the exact specified size.
        if ($crop) {
            // create empty image of the specified size
            $new_image = imagecreatetruecolor($width, $height);

            // landscape image
            if ($current_ratio > $desired_ratio_after) {
                $new_width = $old_width * $height / $old_height;
            }

            // nearly square ratio image
            if ($current_ratio > $desired_ratio_before AND $current_ratio < $desired_ratio_after) {

                if ($old_width > $old_height) {
                    $new_height = max($width, $height);
                    $new_width = $old_width * $new_height / $old_height;
                } else {
                    $new_height = $old_height * $width / $old_width;
                }
            }

            // portrait sized image
            if ($current_ratio < $desired_ratio_before) {
                $new_height = $old_height * $width / $old_width;
            }

            // find ratio of original image to find where to crop
            $width_ratio = $old_width / $new_width;
            $height_ratio = $old_height / $new_height;

            // calculate where to crop based on the center of the image
            $src_x = floor((($new_width - $width) / 2) * $width_ratio);
            $src_y = round((($new_height - $height) / 2) * $height_ratio);
        }
        // don't crop the image, just resize it proportionally
        else {
            if ($old_width > $old_height) {
                $ratio = max($old_width, $old_height) / max($width, $height);
            } else {
                $ratio = max($old_width, $old_height) / min($width, $height);
            }

            $new_width = $old_width / $ratio;
            $new_height = $old_height / $ratio;
            $new_image = imagecreatetruecolor($new_width, $new_height);
        }

        // create avatar thumbnail
        imagecopyresampled($new_image, $img_original, 0, 0, $src_x, $src_y, $new_width, $new_height, $old_width, $old_height);

        // save it as a .jpg file with our $destination_filename parameter
        return imagejpeg($new_image, $destination_filename, $quality);

        // delete "working copy" and original file, keep the thumbnail
        imagedestroy($new_image);
        imagedestroy($img_original);

        if (file_exists($destination_filename)) {
            return true;
        }
        // default return
        return false;
    }


	
	private function exist($filename,$file_location,$ext) {
     $i = 0;
	 if(isset($filename) & isset($file_location) & isset($ext)) {
	 if(file_exists($file_location.$filename.'.'.$ext)) {
	 $i++;
	 while(file_exists($file_location.$filename. '-' .$i.'.'.$ext)) {
	 $i++;
	  }
	  $new_file = ($i == 0) ? $file_location.$filename.'.'.$ext : $file_location.$filename. '-' .$i.'.'.$ext; 	
	}
	return $new_file;
	
	 }else {
		return false; 
	 }
	}
   
    
 
    //----------Open image file----------
    private function openImage($file)
    {
    	// Get extension and return it
    	$ext = pathinfo($file, PATHINFO_EXTENSION);
    	switch(strtolower($ext)) {
    		case 'jpg':
    		case 'jpeg':
    			$im = @imagecreatefromjpeg($file);
    			break;
    		case 'gif':
    			$im = @imagecreatefromgif($file);
    			break;
    		case 'png':
    			$im = @imagecreatefrompng($file);
    			break;
    		default:
    			$im = false;
    			break;
    	}
    	return $im;
    }


    //----------Create image----------
    private function createImage($file, $ext, $width)
    {
    	$im = '';
    	$im = $this->openImage($file);

    	if (empty($im)) {
    		return false;
    	}

    	$old_x = imagesx($im);
       	$old_y = imagesy($im);

        $new_w = (int)$width;

    	if (($new_w <= 0) or ($new_w > $old_x)) {
    		$new_w = $old_x;
       	}

       	$new_h = ($old_x * ($new_w / $old_x));

        if ($old_x > $old_y) {
            $thumb_w = $new_w;
            $thumb_h = $old_y * ($new_h / $old_x);
        }
        if ($old_x < $old_y) {
            $thumb_w = $old_x * ($new_w / $old_y);
            $thumb_h = $new_h;
        }
        if ($old_x == $old_y) {
    		$thumb_w = $new_w;
    		$thumb_h = $new_h;
        }

    	$thumb = imagecreatetruecolor($thumb_w,$thumb_h);
       
        //Attempts to fill image color to white if background is transperent
        $this->FillImageColor($thumb,$im,$ext);

    	     imagecopyresampled($thumb,$im,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);
    	 
    	 //You can attempt to watermark image here
    	//Attempt to stamp watermark on image file
        //$this->createWatermark($thumb,$thumb_w,$thumb_h);

    	//choose which image program to use
    	switch(strtolower($ext)) {
    		case 'jpg':
    		case 'jpeg':
    			imagejpeg($thumb,$file);
    			break;
    		case 'gif':
    			imagegif($thumb,$file);
    			break;
    		case 'png':
    			imagepng($thumb,$file);
    			break;
    		default:
    			return false;
    			break;
    	}

    	
    	imagedestroy($im);
       imagedestroy($thumb);
}



  //fill image background to white if its transperent for gif/png images
  private function FillImageColor($temp_gdim,$source_gdim,$ext) {

     if ($ext == 'png') {

            $bg = imagecolorallocate($temp_gdim, 255, 255, 255);
            imagefill($temp_gdim, 0, 0, $bg);

         } elseif ($ext == 'gif') {

      $trnprt_indx = imagecolortransparent($source_gdim);
        if ($trnprt_indx >= 0) {

          //its transparent
            $trnprt_color = imagecolorsforindex($source_gdim, $trnprt_indx);
            $trnprt_indx = imagecolorallocate($temp_gdim, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
            imagefill($temp_gdim, 0, 0, $trnprt_indx);
            imagecolortransparent($temp_gdim, $trnprt_indx);
      }
    } 
  }



    //Create watermark on image file
   private function createWatermark($im,$width,$height) {

    $watermark = imagecreatefrompng($this->watermark_image_path);
    list($w_width, $w_height) = getimagesize($this->watermark_image_path);        
    $pos_x = $width - $w_width; 
    $pos_y = $height - $w_height;
    imagecopy($im, $watermark, $pos_x, $pos_y, 0, 0, $w_width, $w_height);   

   }



// //Crop image file to a specified aspect ratio
// private function cropImage($source_path,$ext) {	
// /*
//  * Crop-to-fit PHP-GD
//  *
//  * Resize and center crop an arbitrary size image to fixed width and height
//  * e.g. convert a large portrait/landscape image to a small square thumbnail
//  */

// define('DESIRED_IMAGE_WIDTH', 100);
// define('DESIRED_IMAGE_HEIGHT', 100);

// /*
//  * Add file validation code here
//  */

// list($source_width, $source_height, $source_type) = getimagesize($source_path);

// $source_gdim = $this->openImage($source_path);

// $source_aspect_ratio = $source_width / $source_height;
// $desired_aspect_ratio = DESIRED_IMAGE_WIDTH / DESIRED_IMAGE_HEIGHT;

// if ($source_aspect_ratio > $desired_aspect_ratio) {
//     /*
//      * Triggered when source image is wider
//      */
//     $temp_height = DESIRED_IMAGE_HEIGHT;
//     $temp_width = ( int ) (DESIRED_IMAGE_HEIGHT * $source_aspect_ratio);
// } else {
//     /*
//      * Triggered otherwise (i.e. source image is similar or taller)
//      */
//     $temp_width = DESIRED_IMAGE_WIDTH;
//     $temp_height = ( int ) (DESIRED_IMAGE_WIDTH / $source_aspect_ratio);
// }


// /*
//  * Resize the image into a temporary GD image
//  */

// $temp_gdim = imagecreatetruecolor($temp_width, $temp_height);

// //Attempts to fill image color to white if background is transperent
// $this->FillImageColor($temp_gdim,$source_gdim,$ext);

// imagecopyresampled(
//     $temp_gdim,
//     $source_gdim,
//     0, 0,
//     0, 0,
//     $temp_width, $temp_height,
//     $source_width, $source_height
// );


// /*
//  * Copy cropped region from temporary image into the desired GD image
//  */

// $x0 = ($temp_width - DESIRED_IMAGE_WIDTH) / 2;
// $y0 = ($temp_height - DESIRED_IMAGE_HEIGHT) / 2;
// $desired_gdim = imagecreatetruecolor(DESIRED_IMAGE_WIDTH, DESIRED_IMAGE_HEIGHT);
// imagecopy(
//     $desired_gdim,
//     $temp_gdim,
//     0, 0,
//     $x0, $y0,
//     DESIRED_IMAGE_WIDTH, DESIRED_IMAGE_HEIGHT
// );


// /*
//  * Render the image
//  * Alternatively, you can save the image in file-system or database
//  */

// //choose which image program to use
// 	switch(strtolower($ext)) {
// 		case 'jpg':
// 		case 'jpeg':
// 			imagejpeg($desired_gdim,$source_path);
// 			break;
// 		case 'gif':
// 			imagegif($desired_gdim,$source_path);
// 			break;
// 		case 'png':
// 			imagepng($desired_gdim,$source_path);
// 			break;
// 		default:
// 			return false;
// 			break;
// 	}
// /*
//  * Add clean-up code here
//  */
//   imagedestroy($desired_gdim);
// }	









  // //Convert png/gif image to jpeg
  // private function convertImage($convert_filename,$ext) {
 //    $im = '';
  // $im = $this->openImage($convert_filename);

  // if (empty($im)) {
  //  return false;
  // }
    
  // //check file extension
 //   if ($ext == "png" || $ext == "gif"){
       
 //   if ($ext=="png") $new_pic = imagecreatefrompng($convert_filename);
 //   if ($ext=="gif") $new_pic = imagecreatefromgif($convert_filename);

 //   // Create a new true color image with the same size
 //   $w = imagesx($new_pic);
 //   $h = imagesy($new_pic);
 //   $white = imagecreatetruecolor($w, $h);
    
 //   // Fill the new image with white background
 
 //   $bg = imagecolorallocate($white, 255, 255, 255);
 //   imagefill($white, 0, 0, $bg);
     
   
 //   // Copy original transparent image onto the new image
 //   imagecopy($white, $new_pic, 0, 0, 0, 0, $w, $h);
    
 //   $new_pic = $white;
    
 //   imagejpeg($new_pic, $convert_filename);
 //   imagedestroy($new_pic);
    
 //   }
   
  // }




	

}


?>