<?php

	header('Content-type: text/javascript');


	@include_once('../inc/WPlize.php');

	if(class_exists('WPlize')) {
		@include_once('../../../../wp-config.php');
		$options = new WPlize('WP_Apertium');
		$content = $options->get_option('content_id');
		$title = $options->get_option('title_id');

	} else {
		$content = 'entry-';
		$title = 'title-';
	}


?>


if(typeof(WP_Apertium) == 'undefined')
{
	function WPApertium() {
		
		this.translate = _translate;
		this.unselectButtons = _unselectButtons;
		this.showLanguages = _showLanguages;
		this.hideNotes = _hideNotes;
		this.hideLanguages = _hideLanguages;
		this.title = '<?=$title?>';
		this.content = '<?=$content?>';

		function _translate(langCode, listOfCodes, id) {
			this.unselectButtons(id);
			this.hideNotes(listOfCodes,id);
			jQuery('#'+langCode+'-note-'+id).removeClass('hidden');
			jQuery('#'+langCode+'-button-'+id).addClass('selectedLang');
			jQuery('#'+this.content+id).html(jQuery('#'+langCode+'-content-'+id).html());
			jQuery('#'+this.title+id).html(jQuery('#'+langCode+'-title-'+id).html());
			return;
		}

		function _unselectButtons(id) {
			jQuery.each(jQuery('#listOfLanguages-'+id).children() ,function () {	this.className= 'unselectedLang'; });
		}

		function _showLanguages(id) {
			jQuery('#translateButton-'+id).addClass('hidden');
			jQuery('#listOfLanguages-'+id).removeClass('hidden');
			return;
		}

		function _hideNotes(listOfCodes, id) {
			var lcodes = listOfCodes.split(",");
			jQuery.each(lcodes,function () { jQuery('#'+this+'-note-'+id).addClass('hidden'); });
			return;
		}

		function _hideLanguages(listOfCodes,id) {
			this.hideNotes(listOfCodes,id);	
			jQuery('#translateButton-'+id).removeClass('hidden');
			jQuery('#listOfLanguages-'+id).addClass('hidden');
			return;
		}
	}

	apertium = new WPApertium();
}
