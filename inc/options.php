<?php



	if(class_exists('WPlize')) {

		$aux_options = new WPlize('WP_Apertium');


		$aux_options->update_option('language','en');
		$aux_options->update_option('translation_languages','es,ca');
		
		$aux_options->update_option('title_id','title-');
		$aux_options->update_option('content_id','entry-');

		//$aux_options->update_option('','');

	}


?>
