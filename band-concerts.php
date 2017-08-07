<?php
/*
Plugin Name: Band Concerts
Description: Adds a Concert entrytype.
Version: 1.0.0
Author: Martin Giger
Author URI: https://humanoids.be
License: MIT
Text-Domain: band-concerts
*/

/**
 * @var string
 */
define("BC_TEXT_DOMAIN", "band-concerts");

require("includes/ConcertSeries.php");

//TODO newsletter
//TODO make currency configurable
//TODO easy way to get concerts to series

class BandConcertPlugin {
    public function __construct() {
        $this->registerHooks();
    }

    private function registerHooks() {
        add_action('init', [$this, 'onInit']);
        if(is_admin()) {
            add_action('load-post.php', [$this, 'onLoad']);
            add_action('load-post-new.php', [$this, 'onLoad']);
            add_action('admin_enqueue_scripts', [$this, 'onEnqueue']);
        }
    }

    public function onInit() {
        BC_Concert::register();
        BC_ConcertSeries::register();
    }

    public function onLoad() {
        add_action('add_meta_boxes', [$this, 'onBoxes']);
        add_action('save_post', [$this, 'onSave']);
    }

    public function onBoxes(string $post_type) {
        BC_ConcertSeries::addBox($post_type);
    }

    public function onSave($post_id) {
        BC_ConcertSeries::saveBox($post_id);
    }

    public function onEnqueue() {
        wp_register_script('jquery-ui-timepicker', plugin_dir_url(__FILE__).'admin/js/jquery-ui-timepicker-addon.min.js', [
            'jquery-ui-datepicker',
            'jquery-ui-slider'
        ], "1.6.4", false);
        wp_register_script('jquery-ui-timepicker-i18n', plugin_dir_url(__FILE__).'admin/js/jquery-ui-timepicker-addon-i18n.min.js', [
            'jquery-ui-timepicker'
        ], "1.6.4", false);
        wp_register_script(BC_Concert::SCRIPT, plugin_dir_url(__FILE__).'admin/js/concerts.js', [
            'jquery-ui-timepicker-i18n'
        ], "1.0.1", true);
        wp_register_style('jquery-ui', plugin_dir_url(__FILE__).'admin/css/jquery-ui.min.css', [], "1.12.1", "all");
        wp_enqueue_style('jquery-ui-timepicker', plugin_dir_url(__FILE__).'admin/css/jquery-ui-timepicker-addon.min.css', [
            'jquery-ui'
        ], "1.6.4", "all");
    }

    public static function getCurrentConcerts(): array {
        return BC_ConcertSeries::getCurrentItems();
    }
}

new BandConcertPlugin();
