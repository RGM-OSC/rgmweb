<?php
/*
#########################################
#
# Copyright (C) 2016 EyesOfNetwork Team
# original author: Jean-Philippe LEVY
# APPLICATION: eonweb for eyesofnetwork project
#
# Copyright (C) 2019 RGM Team
# contributor: Eric Belhomme

# LICENCE :
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
#########################################
*/

/**
 * Translator class for all eonweb's pages
 *
 * usage examples :
 * PHP : 		echo getLabel("label...");
 * Javascript : document.write(dictionnary["label.message.logout.success"]);
 * JS in PHP : 	echo '<script>document.write('.getLabel("label.message.logout.success").')</script>';
 */
class Translator
{
	
	private $dictionnary_content;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{

		global $database_eonweb;
		$lang = 0;
		
		// # Languages files
		
		// Check if user default lang is defined
		if(isset($_COOKIE['user_id'])){
			$lang = mysqli_result(sqlrequest($database_eonweb,"select user_language from users where user_id='".$_COOKIE['user_id']."'"),0);
		}
		
		// Check if isset browser lang
		if($lang) {
			$GLOBALS['langformat']=$lang;	
		}
		elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
			
			// Language detection
			$lang = explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$lang = strtolower(substr(chop($lang[0]),0,2));
			$GLOBALS['langformat']=$lang;	
		}
	}

	/**
	 * Get File
	 */
	/*
	public function getFile($file,$file_custom)
	{
		$lang=$GLOBALS['langformat'];

		$path_tmp=$file."-$lang.json";
		$path_tmp_custom=$file_custom.".json";
		$path_tmp_custom_lang=$file_custom."-$lang.json";
		$file=$file.".json";

		if(file_exists($path_tmp_custom_lang)) { $file=$path_tmp_custom_lang; }
		elseif(file_exists($path_tmp)) { $file=$path_tmp; }
		elseif(file_exists($path_tmp_custom)) { $file=$path_tmp_custom; }

		return $file;
	}
	*/
	/**
	 * Init File
	 * Merge JSON language files by superseding default (english) language with
	 * locale language.
	 */
	public function initFile($file, $file_custom)
	{				
		global $path_messages_custom;
		$lang=$GLOBALS['langformat'];
		$messages = array();
		
		// Get file to use
		if (file_exists($file.".json")) {
			$message = json_decode(file_get_contents($file.".json"),true);
		}
		if (file_exists($file_custom.".json")) {
			$tmp = json_decode(file_get_contents($file_custom.".json"),true);
			if (count($tmp) > 0)
				$message = array_merge($message, $tmp);
		}
		if (file_exists($file."-$lang.json")) {
			$tmp = json_decode(file_get_contents($file."-$lang.json"),true);
			if (count($tmp) > 0)
				$message = array_merge($message, $tmp);
		}
		if (file_exists($file_custom."-$lang.json")) {
			$tmp = json_decode(file_get_contents($file_custom."-$lang.json"),true);
			if (count($tmp) > 0)
				$message = array_merge($message, $tmp);
		}
		$this->dictionnary_content = json_encode($message);
		
		return $this->dictionnary_content;
	}
	 
	/**
	 * PHP Dictionnary
	 */
	public function createPHPDictionnary()
	{
		$dictionnary = json_decode($this->dictionnary_content, true);		
		return $dictionnary;
	}
	
	/**
	 * JS Dictionnary
	 */
	public function createJSDictionnary()
	{
		echo "<script>";
		echo "var dictionnary = ".$this->dictionnary_content;
		echo "</script>\n";
	}
}
?>
