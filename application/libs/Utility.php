<?php

/**
 * Utility class
 *
 * usefull utilities for the app
 */
class Utility
{
    
  public static function generatePin($group_num = 1, $pair_num = 4)
  {
    $letters = '0123456789';
    $key = '';
    for($i = 1; $i <= $group_num; $i++){
    $key .= substr($letters, rand(0, (strlen($letters) - $pair_num)), $pair_num) . '-';
    } 
    $key[strlen($key)-1] = ' ';
    return trim($key);
  }

  public static function generateNum()
  {
    return rand(1,substr(time(),2)); 
    //rand().time();
  }

  public static function checkNull($val='')
  {
    if ($val == 'NULL') {
       return 0;
     }
     return ($val)? $val: 0;
  }

  public static function strip_zeros_from_date( $marked_string="" ) {
  // first remove the marked zeros
  $no_zeros = str_replace('^0', '', $marked_string);
  // then remove any remaining marks
  $cleaned_string = str_replace('^', '', $no_zeros);
  return $cleaned_string;
  }


  public static function datetime_to_text($datetime="") {
  $unixdatetime = strtotime($datetime);
  return strftime("%B %d, %Y at %I:%M %p", $unixdatetime);
 }

  public static function unixdatetime_to_text($unixdatetime="") {
  if(empty($unixdatetime)) {
    return 'Classified';
  } else {
    return strftime("%B %d, %Y at %I:%M %p", $unixdatetime); 
  }
  
 }


  public static function unixdatetime_to_date_dir($unixdatetime="") {
  return strftime("%Y/^%m/^%d", $unixdatetime);
  }

  public static function unixdatetime_to_date_dir_alt($unixdatetime="") {
  return strftime("^%d/^%m/%Y", $unixdatetime);
  }

  public static function unixdatetime_to_date($unixdatetime="") {
  return strftime("%d-%m-%Y", $unixdatetime);
 }

 public static function unixdatetime_to_date_my($unixdatetime="") {
  return strftime("%m-%Y", $unixdatetime);
 }

 public static function getTimestamp($date='now',$time='')
  { 
    switch ($time) {
      case 'midnight':
      $timestamp = strtotime($date);
      return strtotime("midnight", $timestamp);
        break;

     case 'tomorrow':
      $timestamp = strtotime($date);
      return strtotime("tomorrow", $timestamp) - 1;
        break;
      
      default:
       return false;
        break;
    }
     return false;
  }

 public static function monthEnd($date='',$delimiter='-',$format='mm-yyyy',$return='date')
 {
   $dt = explode($delimiter, $date);

   if($format =='dd-mm-yyyy'){
      $month = isset($dt[1])? $dt[1]: 1;
      $year = isset($dt[2])? $dt[2]: 1979;
    }elseif($format=='mm-yyyy'){
      $month = isset($dt[0])? $dt[0]: 1;
      $year = isset($dt[1])? $dt[1]: 1979;
    }else{
      $month = 1;
      $year = 1979;
   }
   switch ($return) {
     case 'date':
        return cal_days_in_month(CAL_GREGORIAN, $month, $year).$delimiter.$month.$delimiter.$year; 
       break;

     case 'day':
       return cal_days_in_month(CAL_GREGORIAN, $month, $year); 
       break;
     
     default:
      return false;
       break;
   }

 }


   

  public static function timeRemaining($timestamp='',$tomorrow='+1 day')
  {
    // $future_date = new DateTime();
    // $interval = $future_date->diff($date);
    // $format = $interval->format("%d days, %h hours, %i minutes, %s seconds")
    $date = strtotime($tomorrow,$timestamp);
    $seconds = $date-time();
    $days = floor($seconds/86400);
    $days = ($days > 0)? $days: 0;
    $seconds %= 86400;
    $hours = floor($seconds/3600);
    $hours = ($hours > 0)? $hours: 0;
    $seconds %= 3600;
    $minutes = floor($seconds/60);
    $minutes = ($minutes > 0)? $minutes: 0;
    $seconds %= 60;
    $seconds = ($seconds > 0)? $seconds: 0;
    $result = "$days day(s), $hours hour(s), $minutes minute(s) and $seconds second(s) left";
    return $result;
  }


   public static function checkTimeRemaining($timestamp='',$tomorrow='+1 day')
  {
    $date = strtotime($tomorrow,$timestamp);
    $seconds = $date-time();
    return ($seconds > 0)? $seconds: false;
  }

  public static function checkTimeRemainingAlt($timestamp='',$tomorrow='+1 day')
  {
    $date = strtotime($tomorrow,$timestamp);
    $seconds = $date-time();
    return ($seconds > 0)? self::timeRemaining($timestamp,$tomorrow) : false;
  }


   /**
   *
   * @since 1.0.0
   *
   * Counts the elements of a given array
   * @param $array
   * @param $depth
   * @return int
   */ 
  public static function getArrCount ($arr, $depth = 1) {
  if (!is_array ( $arr) || ! $depth) return 0 ;
  $res= count( $arr);
  foreach ( $arr as $in_ar )
  $res+= self::getArrCount($in_ar , $depth -1);
  return $res ;
  }


  public static function stdToArray($std='') {
    if(!empty($std)){
    $array = json_decode(json_encode($std), true);
      return $array;
    } else {
      return false;
    }
  }
  

      /**
     *
     * @since 1.0.0
     * 
     * Generate an array of dates for a given number of days
     * @param days
     * @return array of dates
     *
     */
     
    public static function createDatesArray ($days) {
    //CLEAR OUTPUT FOR USE
    $output = array() ;
    //SET CURRENT DATE
    $month = date ("m" );
    $day = date ("d" );
    $year = date ( "Y" );
    //LOOP THROUGH DAYS
    for ( $i = 1; $i <= $days; $i ++){
    $output[] = date ( 'Y-m-d' , mktime (0, 0, 0, $month, ($day - $i ), $year ));
    }
    //RETURN DATE ARRAY
    return $output;
    } 


    /**
     *
     * @since 1.0.0
     * 
     * Generate an array of future dates for a given start date and number of days
     * @param days
     * @return array of dates
     *
     */
     
     public static function createDatesArrayStart ($day,$month,$year,$days) {
    //CLEAR OUTPUT FOR USE
    $output = array() ;
    //LOOP THROUGH DAYS
    $monthDays = cal_days_in_month(0, $month, $year) - 1;
    $output[] = date ( 'Y-m-d' , mktime (0, 0, 0, $month, ($day), $year ));
    for ( $i = 1; $i <= $monthDays; $i ++){
    $output[] .= date ( 'Y-m-d' , mktime (0, 0, 0, $month, ($day + $i ), $year ));
    }
    //RETURN DATE ARRAY
    return $output;
    } 


    /**
     *
     * @since 1.0.0
     * 
     * Generate an array of future dates for a given start date and number of days
     * @param days
     * @return array of dates
     *
     */
     
    public static function createMonthsArrayStart ($day,$month,$year,$months) {
    //CLEAR OUTPUT FOR USE
    $output = array() ;
    //LOOP THROUGH DAYS
    $monthDays =  $months - 1;
    $output[] = date ( 'Y-m-d' , mktime (0, 0, 0, ($month),$day, $year ));
    for ( $i = 1; $i <= $monthDays; $i ++){
    $output[] .= date ( 'Y-m-d' , mktime (0, 0, 0, ($month + $i ),$day, $year ));
    }
    //RETURN DATE ARRAY
    return $output;
    } 


    public static function timeExpires ($static_timestamp,$current_timestamp) {
   
     if (($current_timestamp - $static_timestamp) < 600) {
     return true;
     } else {
      return false;
     }
   
    } 

    public static function rand_uniqid($in, $to_num = false, $pad_up = false, $passKey = null) {
    $index = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    if ($passKey !== null) {
        // Although this function's purpose is to just make the
        // ID short - and not so much secure,
        // you can optionally supply a password to make it harder
        // to calculate the corresponding numeric ID

        for ($n = 0; $n<strlen($index); $n++) {
            $i[] = substr( $index,$n ,1);
        }

        $passhash = hash('sha256',$passKey);
        $passhash = (strlen($passhash) < strlen($index))
            ? hash('sha512',$passKey)
            : $passhash;

        for ($n=0; $n < strlen($index); $n++) {
            $p[] =  substr($passhash, $n ,1);
        }

        array_multisort($p,  SORT_DESC, $i);
        $index = implode($i);
    }

    $base  = strlen($index);

    if ($to_num) {
        // Digital number  <<--  alphabet letter code
        $in  = strrev($in);
        $out = 0;
        $len = strlen($in) - 1;
        for ($t = 0; $t <= $len; $t++) {
            $bcpow = bcpow($base, $len - $t);
            $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
        }

        if (is_numeric($pad_up)) {
            $pad_up--;
            if ($pad_up > 0) {
                $out -= pow($base, $pad_up);
            }
        }
        $out = sprintf('%F', $out);
        $out = substr($out, 0, strpos($out, '.'));
    } else {
        // Digital number  -->>  alphabet letter code
        if (is_numeric($pad_up)) {
            $pad_up--;
            if ($pad_up > 0) {
                $in += pow($base, $pad_up);
            }
        }

        $out = "";
        for ($t = floor(log($in, $base)); $t >= 0; $t--) {
            $bcp = bcpow($base, $t);
            $a   = floor($in / $bcp) % $base;
            $out = $out . substr($index, $a, 1);
            $in  = $in - ($a * $bcp);
        }
        $out = strrev($out); // reverse
    }

    return $out;
}


public static function genRandomKey($type){
$rand = rand();
switch ($type) {
    case 'key':
    $output = base64_encode($rand);
        
        break;
    
    case 'password':
    $output = sha1($rand);
        break;
}
return $output;
}



/*
    @@ Generating a cryptographically strong pseudorandom value for preventing CSRF and XSRF attacks.
*/
public static function crypto_rand_secure($min, $max) {
    $range = $max - $min;
        if($range < 0) return $min; ## Not so random
    $log = log($range, 2);
    $bytes = (int) ($log / 8) + 1; ## Length in bytes
    $bits = (int) $log + 1; ## Length in bits
    $filter = (int) (1 << $bits) - 1; ## Set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; ## Discard irrelevant bits
        } while ($rnd >= $range);

    return $min + $rnd;
}

##############################################################################################################

public static function get_key($length) {
    $token = '';
    $codeAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codeAlphabet .= 'abcdefghijklmnopqrstuvwxyz';
    $codeAlphabet .= '0123456789';
        for($i=0; $i<$length; $i++) {
            $token .= $codeAlphabet[self::crypto_rand_secure(0, strlen($codeAlphabet))];
        }

    return $token;
}


##############################################################################################################  
   /**
    * generateRandStr - Generates a string made up of randomized
    * letters (lower and upper case) and digits, the length
    * is a specified parameter.
    */
   public static function generateRandStr($length){
      $randstr = "";
      for($i=0; $i<$length; $i++){
         $randnum = mt_rand(0,61);
         if($randnum < 10){
            $randstr .= chr($randnum+48);
         }else if($randnum < 36){
            $randstr .= chr($randnum+55);
         }else{
            $randstr .= chr($randnum+61);
         }
      }
      return $randstr;
   }

/**
 *
 * @since 1.0.0
 *
 * Outputs a given string to json format
 * @param $status
 * @param $txt
 * @return json format
 */ 
  public static function msg($status,$txt)
  {
    return '{"status":'.$status.',"txt":"'.$txt.'"}';
  }



   /**
     *
     * @since 1.0.0
     * 
     * Format size unit of a file by using the size in bytes to calculate
     * @param bytes
     * @return humman readable size
     *
     */

    public static function formatSizeUnits($bytes)
        {
            if ($bytes >= 1073741824)
            {
                $bytes = number_format($bytes / 1073741824, 2) . ' GB';
            }
            elseif ($bytes >= 1048576)
            {
                $bytes = number_format($bytes / 1048576, 2) . ' MB';
            }
            elseif ($bytes >= 1024)
            {
                $bytes = number_format($bytes / 1024, 2) . ' KB';
            }
            elseif ($bytes > 1)
            {
                $bytes = $bytes . ' bytes';
            }
            elseif ($bytes == 1)
            {
                $bytes = $bytes . ' byte';
            }
            else
            {
                $bytes = '0 bytes';
            }

            return $bytes;
    }




    public static function arrayToStd($arr='') {
      if(!empty($arr)){
      $std = json_decode(json_encode($arr));
        return $std;
      } else {
        return false;
      }
    }




    public static function DeleteFolder($path)
    {
        if (is_dir($path) === true)
        {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($files as $file)
            {
                if (in_array($file->getBasename(), array('.', '..')) !== true)
                {
                    if ($file->isDir() === true)
                    {
                        rmdir($file->getPathName());
                    }

                    else if (($file->isFile() === true) || ($file->isLink() === true))
                    {
                        unlink($file->getPathname());
                    }
                }
            }

            return rmdir($path);
        }

        else if ((is_file($path) === true) || (is_link($path) === true))
        {
            return unlink($path);
        }

        return false;
    }


/**
 *
 * @since 1.0.0
 * 
 * getIP - returns the IP of the visitor
 * @return client remote address
 *
 *
 */
    public static function getClientIP() {

        if (isset($_SERVER)) {

            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
                return $_SERVER["HTTP_X_FORWARDED_FOR"];

            if (isset($_SERVER["HTTP_CLIENT_IP"]))
                return $_SERVER["HTTP_CLIENT_IP"];

            return $_SERVER["REMOTE_ADDR"];
        }

        if (getenv('HTTP_X_FORWARDED_FOR'))
            return getenv('HTTP_X_FORWARDED_FOR');

        if (getenv('HTTP_CLIENT_IP'))
            return getenv('HTTP_CLIENT_IP');

        return getenv('REMOTE_ADDR');
    }




public static function dispStatusAlt($status='')
{
   switch ($status) {
        case 1:
        $res = 'No';
        break; 
        case 2:
        $res = 'Yes';
        break;
       
       default:
        $res = 'Error';
       break;
   }
   return $res;
}



public static function dispStatus($status='')
{
   switch ($status) {
        case 1:
        $res = 'Pending';
        break; 
        case 2:
        $res = 'Completed';
        break;
       
       default:
        $res = 'Error';
       break;
   }
   return $res;
}


public static function dispStatusColor($status='')
{
   switch ($status) {
     case 1:
        $res = '#ffeaea'; //light-red
        break;
        case 2:
        $res = '#f0ffff'; //light-green
        break;
     default:
        $res = '#ffffff'; //white
       break;
   }
   return $res;
}




public static function dispUserStatus($s='')
{
   switch ($s) {
        case 1:
        $res = 'Locked';
        break;
        case 2:
        $res = 'Active';
        break; 
        case 3:
        $res = 'Blocked';
        break;
     default:
        $res = 'Classified';
       break;
   }

   return $res;
}



public static function dispUserStatusColor($status='')
{
   switch ($status) {
     case 1:
        $res = '#ffeaea'; //light-red
        break;
      case 2:
        $res = '#f0ffff'; //light-green
        break;
         case 3:
        $res = '#ffeaea'; //light-red
        break;
     default:
        $res = '#ffffff'; //white
       break;
   }
   return $res;
}



public static function dispAdminStatus($s='')
{
   switch ($s) {
        case 1:
        $res = 'No';
        break;
        case 2:
        $res = 'Yes';
        break; 
     default:
        $res = 'Classified';
       break;
   }

   return $res;
}



/*
 * Get Headers function
   * @param str #url
   * @return array
   */
  public function getHeaders($url)
  {
    $ch = curl_init($url);
    curl_setopt( $ch, CURLOPT_NOBODY, true );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
    curl_setopt( $ch, CURLOPT_HEADER, false );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
    curl_exec( $ch );
    $headers = curl_getinfo( $ch );
    curl_close( $ch );

    return $headers;
  }
   

   /**
   * Download
   * @param str $url, $path
   * @return bool || void
   */
  public static function downloadZipFile($filename,$files=array())
  {
    
    $file = self::zipFiles($files);
    
      header('Content-Description: '.$filename.'');
      header("Content-Type: application/zip");
      //header("Content-Type: application/force-download");
      //header("Content-Type: application/octet-stream");
      //header("Content-Type: application/download");
      header('Content-Disposition: filename="'.$filename.'.zip"');
     
     readfile($file);
     unlink($file);
     return true;
  }



/**
 *
 * @since 1.0.0
 * 
 * Zip files before downloading
 * @param $incoming_file,$parameter
 *
 */
  public static function zipFiles($files=array()) {

    # create new zip opbject
    $zip = new ZipArchive();
    $temp_dir = 'data/temp/';
    if (!file_exists($temp_dir)) {
          mkdir($temp_dir, 0777, true);
        }   
    # create a temp file & open it
    $tmp_file = tempnam($temp_dir,'');
    $zip->open($tmp_file, ZipArchive::CREATE);

    # loop through each file
    foreach($files as $file){

        # download file
        $download_file = file_get_contents($file);

        #add it to the zip
        $zip->addFromString(basename($file),$download_file);

    }

    # close zip
    $zip->close();
    $new_file = $temp_dir.time().'.zip';

    if(copy($tmp_file, $new_file)){
       unlink($tmp_file);
    }else{
      throw new Exception("Error Processing Request", 1);
    }
    

    return $new_file;



  }



  public static function makePage($data) {
    // 2. generate the HTML with open graph tags
    $html  = '<!doctype html>'.PHP_EOL;
    $html .= '<html>'.PHP_EOL;
    $html .= '<head>'.PHP_EOL;
    $html .= '<meta name="author" content="'.$data->name.'"/>'.PHP_EOL;
     $html .= '<meta property="og:type" content="'.$data->type.'"/>'.PHP_EOL;
    $html .= '<meta property="og:title" content="'.$data->title.'"/>'.PHP_EOL;
    $html .= '<meta property="og:description" content="'.$data->description.'"/>'.PHP_EOL;
    $html .= '<meta property="fb:moderator" content=""/>'.PHP_EOL; 
    $html  .= '<meta property="fb:app_id" content=""/>'.PHP_EOL; 
    $html .= '<meta property="og:url" content="'.$data->url.'"/>'.PHP_EOL;
    $html .= '<meta property="og:image" content="'.$data->poster.'"/>'.PHP_EOL;
    $html .= '<meta https-equiv="refresh" content="0;url='.$data->url.'">'.PHP_EOL;
    $html .= '</head>'.PHP_EOL;
    $html .= '<body></body>'.PHP_EOL;
    $html .= '</html>';
    // 3. return the page
    echo $html;
}



}





?>