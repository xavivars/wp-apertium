<?php
/*
Plugin Name: WP-Apertium
Version: 0.8
Plugin URI: http://xavi.infobenissa.com/utilitats/wp-apertium
Author: Xavier Ivars i Ribes
Author URI: http://xavi.infobenissa.com
Description: Apertium MT into Wordpress
*/ 
/*  
    Copyright 2008  Xavier Ivars i Ribes  (email : xavi.ivars@gmail.com)
    Copyright 2008 Enrique Benimeli Bofarull (email: ebenimeli@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
                
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
            
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
/* 
	Changelog
	2008-10-31
		- Version 0.8: First release	
*/

if(!class_exists('WPlize'))
	include_once('inc/WPlize.php');

if (!class_exists('WP_Apertium') && class_exists('WPlize')) {
	
if(!function_exists('plugins_url')) {
// WP 2.5 compatibility
 /** Return the plugins url
 *
 *
 * @package WordPress
 * @since 2.6
 *
 * Returns the url to the plugins directory
 *
 * @param string $path Optional path relative to the plugins url
 * @return string Plugins url link with optional path appended
 */
	function plugins_url($path = '') {
		$scheme = ( is_ssl() ? 'https' : 'http' );
		$url = WP_PLUGIN_URL;
		if ( 0 === strpos($url, 'http') ) {
		 	if ( is_ssl() )
				$url = str_replace( 'http://', "{$scheme}://", $url );
		}
		
		if ( !empty($path) && is_string($path) && strpos($path, '..') === false )
			$url .= '/' . ltrim($path, '/');
		return $url;
	}
}

if(!function_exists('is_ssl')) {
	/**
	* Determine if SSL is used.
	*
	* @since 2.6
	*
	* @return bool True if SSL, false if not used.
	*/
	function is_ssl() {
		return ( isset($_SERVER['HTTPS']) && 'on' == strtolower($_SERVER['HTTPS']) ) ? true : false; 
	}

}

	class WP_Apertium {

		// Runtime vars
		var $cache_dir;
		var $plugin_dir;
		var $plugin_url;
		var $options;
		var $language;
		var $translation_languages;
		var $local;
		var $path;

		// Translation vars
		var $post_id;
		var $post_translations;

		// Other vars
		var $locale;

		function WP_Apertium() { //constructor

			include_once('inc/options.php');

			$this->plugin_dir = WP_PLUGIN_DIR.'/wp-apertium/';
			$this->cache_dir = $this->plugin_dir.'/cache/';
			$this->plugin_url = plugins_url('/wp-apertium');
			$this->options = new WPlize('WP_Apertium');
			$this->local = $this->options->get_option('local');
			$this->path = $this->options->get_option('path');
			$this->translation_languages = split(',',$this->options->get_option('translation_languages'));
			$this->language = $this->options->get_option('language');
			$this->post_translations = array();
		}
		
		/**
		*
		* Load locale
		*
		**/
		function load_locale() {
			include_once($this->plugin_dir.'inc/locale.php');
			$this->locale = $_names;
			unset($_names);
		}

		/**
		*
		* Returns language names
		*
		**/
		function get_name($code) {

			if(!is_array($this->locale))
				$this->load_locale();

			$ret = false;

			if(is_array($this->locale[$this->language])) {
				if(isset($this->locale[$this->language][$code]))
					$ret =  $this->locale[$this->language][$code];
			}

			if(!$ret) {
				if(isset($this->locale['default'][$code]))
					$ret =  $this->locale['default'][$code];
			}

			if(!$ret)
				$ret = $code;

			return $ret;
		}

		/**
		*
		* Is executed when the plugin is activated.
		* It creates (if doesn't exist) a cache dir
		* It tests the local installation
		*
		**/
		function activate() {
			if(!file_exists($this->cache_dir)) {
				mkdir($this->cache_dir,0777);
			}
			$this->test_local();

			$aux = $this->options->get_option('title_id');
			if(empty($aux))
				$this->options->update_option('title_id','title-');

			$aux = $this->options->get_option('content_id');
			if(empty($aux))
				$this->options->update_option('content_id','entry-');

			$aux = $this->options->get_option('language');
			if(empty($aux))
				$this->options->update_option('language','ca');

			$aux = $this->options->get_option('translation_languages');
			if(empty($aux))
				$this->options->update_option('translation_languages','es,fr,en');

		}

		/**
		*
		* Executed when the plugin is deactivated.
		*
		**/
		function deactivate() {

		}

		/**
		*
		* Tests if there's an available local install of apertium
		*
		**/
		function test_local() {

			if ($this->local) {
				$ap_path = $this->path;

				if(empty($ap_path)) $ap_path = 'apertium';
	
				if(function_exists('system'))
					@system($ap_path.' > /dev/null 2>&1',$ret);
				else 
					$ret = 127;
	
				if($ret == 127) {
					$this->local = false;
					$this->options->update_option('local',false);
				}
			}
	
			return $this->local;
		}


		/**
		*
		* This is the main function.
		* Checks if there are translations, and prints the translation menu if needed
		*
		**/
		function translations($id) {
			if($this->get_translations($id))
			{
				echo '<div id="apertium_content-'.$id.'">';
				$this->print_menu($id);
				$this->print_translations($id);
				echo '</div>';
			}
		}

		/**
		*
		* Prints menu
		*
		**/
		function print_menu($id) {

			$codeStr = $this->options->get_option('translation_languages');	
			$code = $this->language;
			$name = $this->get_name($code);

			?>	

			<div id="translateButton-<?=$id?>" class="languages">
				<div id="showListButton" onclick="apertium.showLanguages('<?=$id?>');"><?=$this->get_name('translate')?></div>
			</div>
	
			<div id="listOfLanguages-<?=$id?>" class="languages hidden">
				<div 	id="<?=$code?>-button-<?=$id?>" class="unselectedLang" 
					onclick="apertium.translate('<?=$code?>','<?=$codeStr?>','<?=$id?>');" 
					title="<?=$name?>">
					<?=$code?>
				</div>

			<?php
			foreach ($this->translation_languages as $code) { ?>

				<div 	id="<?=$code?>-button-<?=$id?>" class="unselectedLang" 
					onclick="apertium.translate('<?=$code?>','<?=$codeStr?>','<?=$id?>');" 
					title="<?=$this->get_name($code)?>">
					<?=$code?>
				</div>

			<?php } ?>

				<div class="unselectedLang" onclick="apertium.hideLanguages('<?=$codeStr?>','<?=$id?>');">&raquo;</div>
			</div>

			<?php 
		}

		/**
		*
		* Prints translation languages
		*
		**/
		function print_translations($id) {

			?>
	
			<div xml:lang="<?=$this->language?>" id="<?=$this->language?>-content-<?=$id?>" class="hidden"><?=$this->post_translations[$this->language]['content']?></div>
			<div xml:lang="<?=$this->language?>" id="<?=$this->language?>-title-<?=$id?>" class="hidden"><?=$this->post_translations[$this->language]['title']?></div>

			<?php
		
			foreach ($this->translation_languages as $lang) {
			
			?>
				<div id="<?=$lang?>-note-<?=$id?>" class="apertiumNote hidden">
					<?=$this->get_name('poweredby')?> 
					<a href="http://xavi.infobenissa.com/utilitats/wp-apertium/" title="WP-Apertium">WP-Apertium</a>.
					<?=$this->get_name('translatedto')?> <b><?=$this->get_name($lang)?></b> 
					<?=$this->get_name('translatedby')?> <a href="http://www.apertium.org">Apertium</a>
				</div>
				<div xml:lang="<?=$lang?>" id="<?=$lang?>-content-<?=$id?>" class="hidden">
					<?=$this->post_translations[$lang]['content']?>
				</div>
				<div xml:lang="<?=$lang?>" id="<?=$lang?>-title-<?=$id?>" class="hidden">
					<?=$this->post_translations[$lang]['title']?>
				</div>	
			<?php	
			}
		}


		/**
		*
		* Looks for cache files and creates them if necessary
		*
		**/
		function get_translations($id) {
			$this->post_id = $id;
			$ret = false;

			foreach($this->translation_languages as $lang) {
				$this->post_translations[$lang] = '';
			}
			$this->post_translations[$this->language]='';
			

			$cache_folder = $this->cache_dir.'/'.$id.'/';

			if(is_dir($this->cache_dir)) {
			
				if(!file_exists($cache_folder)) {
					mkdir($cache_folder,0777);
				}

				// crear cache idioma local
				$this->original_cache($cache_folder);

				foreach($this->translation_languages as $lang) {
					if($lang != $this->language) {
						$content_file = $cache_folder.$lang.'.content';
						$title_file = $cache_folder.$lang.'.title';

						$content_original = $cache_folder.$this->language.'.content';
						$title_original= $cache_folder.$this->language.'.title';
					
						if(!file_exists($content_file)) {
							
							$result = $this->translate($content_original,$lang);

							$this->create_cache($cache_folder,$lang,'.content',$result);

							$result = $this->translate($title_original,$lang);
							$this->create_cache($cache_folder,$lang,'.title',$result);

							unset($result);
						}
					}
				}

				$ret=$this->load_translations($cache_folder);
			}

			return $ret;
		}	

		/**
		*
		* Loads cache from content
		*
		**/
		function load_translations($cache_folder) {

			$content_file = $cache_folder.$this->language.'.content';
			$title_file = $cache_folder.$this->language.'.title';

			$this->post_translations[$this->language]=array();
			$this->post_translations[$this->language]['content'] = @file_get_contents($content_file);
			$this->post_translations[$this->language]['title'] = @file_get_contents($title_file);

			foreach($this->translation_languages as $lang) {
				$this->post_translations[$lang] = array();

				$content_file = $cache_folder.$lang.'.content';
				$title_file = $cache_folder.$lang.'.title';

				$this->post_translations[$lang]['content'] = @file_get_contents($content_file);
				$this->post_translations[$lang]['title'] = @file_get_contents($title_file);

				if(empty($this->post_translations[$lang]['content']) || empty($this->post_translations[$lang]['title'])) {
					return false;	
				}
			}
			return true;
		}

		/**
		*
		* Saves content to cache
		*
		**/
		function create_cache($cache_folder,$lang,$name,$content) {
			$fic = $cache_folder.$lang.$name;
		
			$fh = fopen($fic,'w');
			fwrite($fh,$content);
			fclose($fh);
			unset($fh);
		}

		/**
		*
		* Creates original cache files
		*
		**/
		function original_cache($cache_folder) {
                        $content = get_the_content();
                        $content = apply_filters('the_content', $content);
			$content = $this->apos($content);
                        $this->create_cache($cache_folder,$this->language,'.content',$content);
                        
                        $title = get_the_title();
			$title = $this->apos($title);
                        $this->create_cache($cache_folder,$this->language,'.title',$title);
		}

		/**
		*
		* Replaces apostrophes
		*
		**/
		function apos($text) {
			$text = str_replace('&#8217;',"'",$text);
			$text = str_replace('&raquo;',"'",$text);
			$text = str_replace('&#39;',"'",$text);
			$text = str_replace('&apos;',"'",$text);
			$text = str_replace('’',"'",$text);

			return text;
		}		

		/**
		*
		* Translates a text
		*
		**/
		function translate($file,$meta_lang) {
			$unkown = $this->options->get_option('unknown');
			$ret = false;
			if($this->local)
				$ret = $this->translate_local($file,$meta_lang,$unknown);
			else
				$ret = $this->translate_webservice($file,$meta_lang,$unknown);

			return $ret;
		}

		/**
		*
		* Translates a text using a webservice
		* Uses common/traddoc.php interface
		* 
		**/
		function translate_webservice($file, $dir, $markUnknown) {

			// curl example: http://es.php.net/manual/es/function.curl-setopt.php#24709
			$submit_url = "http://www.apertium.org/common/traddoc.php";

			$formvars = array("direction"=> $this->language.'-'.$dir);
			$fomvars['mark']=($markUnknown)?"1":"0";
			$formvars['doctype'] = "html";
			$formvars['userfile'] = "@$file"; // "@" causes cURL to send as file and not string (I believe)

			$ch = curl_init($submit_url);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1); // follow redirects recursively
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $formvars);
			$ret = curl_exec($ch);
			curl_close ($ch);
			unset($ch);
			unset($formvars);

			return $ret;
		}

		/**
		*
		* Translates a text in a local install
		*
		**/
		function translate_local($file, $dir, $markUnknown) {
			
			$unknown=($markUnknown)?" -u ":"";
			$dir = $this->language.'-'.$dir;

			$cmd = 'LANG=en_US.UTF-8 '.$this->path." $unknown -f html $dir $file";
			$trad = shell_exec($cmd);

			return $trad;
		}

		/**
		*
		* Executed when a post is saved
		* Cache is cleared (changes may have been done in content)
		*
		**/
		function save_post($id) {
			if(!(wp_is_post_revision($id) || wp_is_post_autosave($id))) {
				$cache_folder = $this->cache_dir.'/'.$id.'/';
				
				if(file_exists($cache_folder) && is_dir($cache_folder)) {
				    if ($gd = opendir($cache_folder)) {
					while (($fic = readdir($gd)) !== false) {
						if(($fic != '.')&&($fic != '..')))
							unlink($cache_folder.$fic);
					}
					closedir($gd);
					rmdir($cache_folder);
				    }
				}
			}
		}

		/**
		*
		* Adds a Header in the <head> html tag
		* It includes css and js files
		*
		**/
		function add_header_code() {
			?>
				<!-- WP_Apertium Copyright 2008  Xavier Ivars i Ribes  (http://xavi.infobenissa.com) -->
				<link type="text/css" rel="stylesheet" href="<?=$this->plugin_url?>/css/wp_apertium.css" media="screen" />
				<script type="text/javascript" src="<?=$this->plugin_url?>/js/wp_apertium.js.php"></script>
			<?php
		}
	}
} 

if (class_exists("WP_Apertium")) {
	$wp_apertium = new WP_Apertium();

	function apertium_translations($id) {
		global $wp_apertium;
		$wp_apertium->translations($id);
	}


	// backward compatibility with apertium-blog-translation
	if (!function_exists('apertiumPostTranslation')) { 
		function apertiumPostTranslation($id) {
			apertium_translations($id);
		}
	}
}

//Actions and Filters
if (isset($wp_apertium)) {

	wp_enqueue_script('jquery');

	//Actions
	add_action('wp_head', array(&$wp_apertium, 'add_header_code'));
	add_action('save_post', array(&$wp_apertium, 'save_post'));
	//Filters

	// Hooks
	register_activation_hook(__FILE__,array(&$wp_apertium, 'activate'));
	register_deactivation_hook(__FILE__,array(&$wp_apertium, 'deactivate'));

}

                                                                                                                                    
?>
