<?php

/**
 * A simple, clean and secure PHP Login Script embedded into a small framework.
 * Also available in other versions: one-file, minimal, advanced. See php-login.net for more info.
 *
 * MVC FRAMEWORK VERSION
 *
 * @author Panique
 * @link http://www.php-login.net/
 * @link https://github.com/panique/php-login/
 * @license http://opensource.org/licenses/MIT MIT License
 */

// Load application config (error reporting, database credentials etc.)
require 'application/config/config.php';

// The auto-loader to load the php-login related internal stuff automatically
require 'application/config/autoload.php';

// 1. get the content Id (here: an Integer) and sanitize it properly
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// 2. get the content from a flat file (or API, or Database, or ...)
$data = (object)  array('name' => APP_NAME,
						'title' => APP_TITLE,
						'description' => APP_DESCRIPTION,
						'poster' => APP_POSTER,
						'type' => 'article',
						'url' => APP_URL );

// 3. return the page
return Utility::makePage($data); 



?>
