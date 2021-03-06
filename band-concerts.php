<?php
/* Copyright (c) 2017 Martin Giger

MIT License

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE. */

/*
Plugin Name: Band Concerts
Description: Adds a Concert entrytype.
Version: 1.0.0
Author: Martin Giger
Author URI: https://humanoids.be
License: MIT
Text-Domain: band-concerts
*/
namespace BandConcerts;

/**
 * @var string
 */
define("BC_TEXT_DOMAIN", "band-concerts");


require_once __DIR__."/vendor/autoload.php";

require_once __DIR__."/includes/ConcertSeries.php";
require_once __DIR__."/includes/EventICal.php";
require_once __DIR__."/includes/ReportsWidget.php";

//TODO make currency configurable

class Plugin {
    public function __construct() {
        $this->registerHooks();
    }

    private function registerHooks() {
        add_action('init', [$this, 'onInit']);
        add_action('widgets_init', [$this, 'onWidgets']);
        if(is_admin()) {
            add_action('load-post.php', [$this, 'onLoad']);
            add_action('load-post-new.php', [$this, 'onLoad']);
            add_action('admin_enqueue_scripts', [$this, 'onEnqueue']);
        }
        else {
            add_action('pre_get_posts', [$this, 'onGetPosts']);
        }
        add_shortcode('concert', [$this, 'concertShortcode']);
    }

    public function onInit() {
        Concert::register();
        ConcertSeries::register();
        EventICal::register([ConcertSeries::class, 'getAllItems']);
    }

    public function onLoad() {
        add_action('add_meta_boxes', [$this, 'onBoxes']);
        add_action('save_post', [$this, 'onSave']);
    }

    public function onBoxes(string $post_type) {
        ConcertSeries::addBox($post_type);
    }

    public function onSave($post_id) {
        ConcertSeries::saveBox($post_id);
    }

    public function onEnqueue($context) {
        if($context != 'post.php' && $context != 'post-new.php') {
            return;
        }
        wp_register_script('jquery-ui-timepicker', plugin_dir_url(__FILE__).'admin/js/jquery-ui-timepicker-addon.min.js', [
            'jquery-ui-datepicker',
            'jquery-ui-slider'
        ], "1.6.4", false);
        wp_register_script('jquery-ui-timepicker-i18n', plugin_dir_url(__FILE__).'admin/js/jquery-ui-timepicker-addon-i18n.min.js', [
            'jquery-ui-timepicker'
        ], "1.6.4", false);
        wp_enqueue_script(Concert::SCRIPT, plugin_dir_url(__FILE__).'admin/js/concerts.js', [
            'jquery-ui-timepicker-i18n'
        ], "1.0.3", false);
        wp_enqueue_script(ConcertSeries::SCRIPT, plugin_dir_url(__FILE__).'admin/js/concertseries.js', [
            'jquery'
        ], '1.0.0', false);

        wp_register_style('jquery-ui', plugin_dir_url(__FILE__).'admin/css/jquery-ui.min.css', [], "1.12.1", "all");
        wp_enqueue_style('jquery-ui-timepicker', plugin_dir_url(__FILE__).'admin/css/jquery-ui-timepicker-addon.min.css', [
            'jquery-ui'
        ], "1.6.4", "all");
        wp_enqueue_style('bc_concert_admin', plugin_dir_url(__FILE__).'admin/css/styles.css', [], "1.0.0", "all");
    }

    public function onWidgets() {
        register_widget('\BandConcerts\ReportsWidget');
    }

    public function onGetPosts(\WP_Query $query) {
        if($query->is_singular() && $query->query_vars['post_type'] == Concert::POST_TYPE) {
            $query->set('suppress_filters', true);
            $query->set('post_status', [ 'any', 'auto-draft', 'draft' ]);
            $query->set('perm', 'readable');
        }
    }

    public static function getCurrentConcerts(): array {
        return ConcertSeries::getCurrentItems();
    }

    public static function concertShortcode($atts) {
        // Attributes
        $atts = shortcode_atts(
            [
                'id' => null,
                'expanded' => false,
                'with_details' => false,
                'wrapper' => 'span'
            ],
            $atts,
            'concert'
        );
        return ConcertSeries::shortcode($atts['id'], $atts['expanded'], $atts['with_details'], $atts['wrapper']);
    }
}

new Plugin();
