<?php

	header('Content-type: text/javascript');


	@include_once('../inc/WPlize.php');

	if(class_exists('WPlize')) {
		@include_once('../../../../wp-config.php');
		$options = new WPlize('WP_Apertium');
		$content = $options->get_option('content_id');
		$title = $options->get_option('title_id');

	}

    if(empty($content))  $content = 'entry-';
	if(empty($title)) $title = 'title-';

    $wp_apertium_path = plugins_url('/wp-apertium').'/utils/get_text.php';

?>


if(typeof(WP_Apertium) == 'undefined')
{
	function WPApertium() {

        this.path = '<?=$wp_apertium_path?>';
		this.translate = _translate;
		this.unselectButtons = _unselectButtons;
		this.showLanguages = _showLanguages;
		this.hideNotes = _hideNotes;
		this.hideLanguages = _hideLanguages;
        this.jsTranslations = _jsTranslations;
        this.ajaxTranslations = _ajaxTranslations;
        this.loadMetaData = _loadMetaData;
        this.init = _init;
        this.setAjax = _setAjax;
		this.title = '<?=$title?>';
		this.content = '<?=$content?>';
        this.translations = Array();
        this.defaultLanguages = Array();
        this.currentLanguages = Array();
        this.otherLanguages = Array();
        this.ajax = false;
        this.tmp_var = 0;

		function _translate(langCode, listOfCodes, id) {
			this.unselectButtons(id);
			this.hideNotes(listOfCodes,id);
			jQuery('#'+langCode+'-note-'+id).removeClass('hidden');
			jQuery('#'+langCode+'-button-'+id).addClass('selectedLang');
			jQuery('#'+this.content+id).html(this.translations[id][langCode]['content']);
			jQuery('#'+this.title+id).html(this.translations[id][langCode]['title']);
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

        function _jsTranslations() {
            jQuery('.apertium_text').each(function (i) {

                aux_all=this.id.split('-');
                aux_lang=aux_all[0];aux_text=aux_all[1];aux_id=aux_all[2];
                txt = jQuery('#'+aux_lang+'-'+aux_text+'-'+aux_id).html();

                if(typeof apertium.translations[aux_id] == 'undefined')
                    wp_apertium.translations[aux_id] = Array();

                if(typeof apertium.translations[aux_id][aux_lang] == 'undefined')
                    wp_apertium.translations[aux_id][aux_lang] = Array();

                if(typeof apertium.translations[aux_id][aux_lang][aux_text] == 'undefined')
                    wp_apertium.translations[aux_id][aux_lang][aux_text] = Array();

                apertium.translations[aux_id][aux_lang][aux_text] = txt;
                jQuery('#'+aux_lang+'-'+aux_text+'-'+aux_id).html('');
            });
        }

        function _ajaxTranslations(xml) {
            jQuery(xml).find('text').each(function (i) {

                aux_all=this.getAttribute('id').split('-');
                aux_lang=aux_all[0];aux_text=aux_all[1];aux_id=aux_all[2];
                txt = jQuery('#'+aux_lang+'-'+aux_text+'-'+aux_id,xml).html();

                /**** FALLBACK. THIS IS NOT ALWAYS NEEEDED. WHY???? ****/
                if(txt == null)
                    this.innerHTML;

                if(typeof apertium.translations[aux_id] == 'undefined')
                    wp_apertium.translations[aux_id] = Array();

                if(typeof apertium.translations[aux_id][aux_lang] == 'undefined')
                    wp_apertium.translations[aux_id][aux_lang] = Array();

                if(typeof apertium.translations[aux_id][aux_lang][aux_text] == 'undefined')
                    wp_apertium.translations[aux_id][aux_lang][aux_text] = Array();

                wp_apertium.translations[aux_id][aux_lang][aux_text] = txt;
            });
        }
        
        function _loadMetaData() {
            jQuery('.apertium_default_language').each(function (i) {
                aux_id=this.id.split('-')[1];
                wp_apertium.defaultLanguages[aux_id] = jQuery.trim(jQuery('#apertium_default_language-'+aux_id).html());
                wp_apertium.currentLanguages[aux_id] = jQuery.trim(jQuery('#apertium_current_language-'+aux_id).html());
                if(wp_apertium.currentLanguages[aux_id]=='')
                    wp_apertium.currentLanguages[aux_id] = wp_apertium.defaultLanguages[aux_id];
                wp_apertium.otherLanguages[aux_id] = jQuery.trim(jQuery('#apertium_other_languages-'+aux_id).html());
                if((wp_apertium.currentLanguages[aux_id]) == (wp_apertium.defaultLanguages[aux_id])) {
                    wp_apertium.hideLanguages(wp_apertium.otherLanguages[aux_id],aux_id);
                }
            });
            
        }

        function _init() {
            this.loadMetaData();
            if(this.ajax) {
                jQuery('.apertium_default_language').each(function (i) {
                    aux_id=this.id.split('-')[1];

                    jQuery.get('<?=$wp_apertium_path?>',
                        {
                            id: aux_id
                        },
                        function(xml) {
                            wp_apertium.ajaxTranslations(xml);
                        },
                        'html'
                    );

                });

            } else {
                this.jsTranslations();

                jQuery('.apertium_default_language').each(function (i) {
                    aux_id=this.id.split('-')[1];
                    wp_apertium.translate(wp_apertium.defaultLanguages[aux_id],wp_apertium.otherLanguages[aux_id],aux_id);
                });
                jQuery('.apertium_current_language').each(function (i) {
                    aux_id=this.id.split('-')[1];
                    wp_apertium.showLanguages(aux_id);
                    wp_apertium.translate(wp_apertium.currentLanguages[aux_id],wp_apertium.otherLanguages[aux_id],aux_id);
                });
            }
        }

        function _setAjax() {
            this.ajax = true;
        }

        wp_apertium = this;
	}
}

