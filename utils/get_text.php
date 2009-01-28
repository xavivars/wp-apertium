<?php
    header('Content-type:text/xml');

    if(isset($_GET['id'])&&is_numeric($_GET['id'])) {

        @include_once('../../../../wp-config.php');
        @include_once('../inc/WPlize.php');

        if(class_exists('WPlize') && class_exists('WP_Apertium')) {
            $id = $_GET['id'];
            $options = new WPlize('WP_Apertium');
            $content = $options->get_option('content_id');
            $title = $options->get_option('title_id');
        
            $wp_apertium = new WP_Apertium();
            $wp_apertium->set_ajax();

            if($wp_apertium->get_translations($id)) {
                echo '<?xml version="1.0"?>';
                echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
                echo '<all id="apertium_content-'.$id.'" class="apertium_content">';
                $wp_apertium->print_translations($id);
                echo '</all>';
            }
        }
    }

?>
