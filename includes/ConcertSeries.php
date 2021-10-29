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

namespace BandConcerts;

require_once("Concert.php");

use \WP_Query;
use \WP_Post;

class ConcertSeries {
    /**
     * @var string
     */
    const TAXONOMY = 'concert_series';
    /**
     * @var string
     */
    const POST_TYPE = 'concertseries';
    /**
     * @var string
     */
    const REVIEW_FIELD = 'bc_review';
    /**
     * @var string
     */
    const REVIEW_FIELD_EDITOR = 'bc_review_editor';
    /**
     * @var string
     */
    const NONCE_NAME = 'bc_review_box_nonce';
    /**
     * @var string
     */
    const NONCE_FIELD = 'bc_review_box';
    /**
     * @var string
     */
    const TYPE_FIELD = 'bc_type';

    const TYPE_CONCERT = 'concert';
    const TYPE_EVENT = 'event';

    const FLYER_FIELD = 'bc_flyer';

    const SCRIPT = 'bc_series_flyer';

    public static function register() {
        self::registerPostType();
        self::registerTaxonomy();
    }

    private static function registerPostType() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Aktivitäten', BC_TEXT_DOMAIN),
                'singular_name' => __('Aktivität', BC_TEXT_DOMAIN),
                'archives' => __('Aktivitäten', BC_TEXT_DOMAIN),
                'featured_image' => __('Begleitbild', BC_TEXT_DOMAIN),
                'add_new_item' => __('Aktivität erstellen', BC_TEXT_DOMAIN),
                'new_item' => __('Neue Aktivität', BC_TEXT_DOMAIN),
                'edit_item' => __('Aktivität bearbeiten', BC_TEXT_DOMAIN),
                'view_item' => __('Aktivität anzeigen', BC_TEXT_DOMAIN),
                'all_items' => __('Alle Aktivitäten', BC_TEXT_DOMAIN),
                'search_items' => __('Aktivität suchen', BC_TEXT_DOMAIN),
                'not_found' => __('Keine Aktivitäten gefunden', BC_TEXT_DOMAIN),
                'not_found_in_trash' => __('Keine Aktivitäten im Papierkorb', BC_TEXT_DOMAIN),
                'set_featured_image' => __('Begleitbild wählen', BC_TEXT_DOMAIN),
                'remove_featured_image' => __('Begleitbild entfernen', BC_TEXT_DOMAIN),
                'use_featured_image' => __('Als Begleitbild wählen', BC_TEXT_DOMAIN),
                'items_list' => __('Aktivitätenliste', BC_TEXT_DOMAIN),
                'items_list_navigation' => __('Aktivitätenlistennavigation', BC_TEXT_DOMAIN),
                'menu_name' => __('Aktivitäten', BC_TEXT_DOMAIN),
                'name_admin_bar' => __('Aktivität', BC_TEXT_DOMAIN)
            ],
            'public' => true,
            'show_in_nav_menus' => false,
            'supports' => [
                'title',
                'editor',
                'revisions',
                'thumbnail'
            ],
            'has_archive' => true,
            'rewrite' => [
                'slug' => 'concert'
            ]
        ]);
    }

    private static function registerTaxonomy() {
        register_taxonomy(self::TAXONOMY, Concert::POST_TYPE, [
            'labels' => [
                'name' => __('Konzert Serien', BC_TEXT_DOMAIN),
                'singular_name' => __('Konzert Serie', BC_TEXT_DOMAIN)
            ],
            'public' => false,
            'show_in_nav_menus' => false,
            'hierarchical' => false,
            'capabilites' => [
                'manage_terms',
                'edit_terms',
                'delete_terms',
                'assign_terms'
            ],
            'sort' => false
        ]);
        register_taxonomy_for_object_type(self::TAXONOMY, Concert::POST_TYPE);
    }

    public static function addBox(string $post_type) {
        if(self::POST_TYPE === $post_type) {
            add_meta_box(
                self::REVIEW_FIELD,
                __('Konzertbericht', BC_TEXT_DOMAIN),
                [self::class, 'renderBox'],
                self::POST_TYPE
            );
            add_meta_box(
                self::TYPE_FIELD,
                __('Konzerttyp', BC_TEXT_DOMAIN),
                [self::class, 'renderTypeBox'],
                self::POST_TYPE,
                'side'
            );
            add_meta_box(
                self::FLYER_FIELD,
                __('Flyer', BC_TEXT_DOMAIN),
                [self::class, 'renderFlyerBox'],
                self::POST_TYPE,
                'side'
            );
            Concert::addBox(self::POST_TYPE, self::TAXONOMY);
        }
    }

    public static function renderBox($post) {
        wp_nonce_field(self::NONCE_FIELD, self::NONCE_NAME);

        $content = get_post_meta($post->ID, self::REVIEW_FIELD, true) ?? '';
        wp_editor($content, self::REVIEW_FIELD_EDITOR, [
            'drag_drop_upload' => true
        ]);
    }

    public static function renderTypeBox($post) {
        $content = get_post_meta($post->ID, self::TYPE_FIELD, true);
        ?><select name="<?php echo self::TYPE_FIELD ?>" id="<?php echo self::TYPE_FIELD ?>" class="bc_type" value="<?php echo $content ?? 'concert' ?>">
            <option value="<?php echo self::TYPE_CONCERT ?>"<?php if($content !== self::TYPE_EVENT) echo ' selected'; ?>><?php _e('Konzert', BC_TEXT_DOMAIN) ?></option>
            <option value="<?php echo self::TYPE_EVENT ?>"<?php if($content === self::TYPE_EVENT) echo ' selected'; ?>><?php _e('Anlass', BC_TEXT_DOMAIN) ?></option>
        </select><?php
    }

    public static function renderFlyerBox($post) {
        $iframe = esc_url(get_upload_iframe_src('pdf', $post->ID));
        $content = get_post_meta($post->ID, self::FLYER_FIELD, true);
        $contentSrc = wp_get_attachment_thumb_url($content);
        $hasImage = !empty($contentSrc);
        ?>
        <div class="bc-prev-container">
            <?php if($hasImage) { ?>
            <img src="<?php echo esc_url($contentSrc) ?>" alt="Flyer" style="max-width:100%;">
            <?php } ?>
        </div>
        <p class="hide-if-no-js">
            <a class="bc-upload<?php if($hasImage) { echo ' hidden'; } ?>" href="<?php echo $iframe ?>">
                <?php _e('Flyer hochladen', BC_TEXT_DOMAIN) ?>
            </a>
            <a class="bc-delete<?php if(!$hasImage) { echo ' hidden'; } ?>" href="#">
                <?php _e('Flyer entfernen', BC_TEXT_DOMAIN) ?>
            </a>
        </p>
        <input class="bc-flyer-id" name="bc_flyer_id" type="hidden" value="<?php echo esc_attr($content); ?>" />
        <?php
    }

    public static function saveBox($postID) {
        if(!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_FIELD)) {
            return $postID;
        }

        if(get_post_type($postID) == self::POST_TYPE) {
            $data = wp_kses_post($_POST[self::REVIEW_FIELD_EDITOR]);
            update_post_meta($postID, self::REVIEW_FIELD, $data);

            update_post_meta($postID, self::TYPE_FIELD, $_POST[self::TYPE_FIELD]);

            $content = sanitize_text_field($_POST['bc_flyer_id']);
            update_post_meta($postID, self::FLYER_FIELD, $content);

            Concert::saveBox($postID, self::TAXONOMY);
        }
    }

    public static function getCurrentItems(): array {
        $ids = Concert::getCurrentParents(self::TAXONOMY);

        $q = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post__in' => $ids,
            'nopaging' => true,
            'posts_per_page' => -1
        ]);
        if($q->have_posts()) {
            return $q->get_posts();
        }
        return [];
    }

    public static function getAllItems(): array {
        $q = new WP_Query([
            'post_type' => self::POST_TYPE,
            'nopaging' => true
        ]);
        if($q->have_posts()) {
            return $q->get_posts();
        }
        return [];
    }

    public static function getConcertsForSeries($post_id): array {
        return Concert::getPosts(self::TAXONOMY, $post_id);
    }

    public static function isConcert($postID) {
        $content = get_post_meta($postID, self::TYPE_FIELD, true);
        return $content !== self::TYPE_EVENT;
    }

    public static function getSeriesForConcert($post_id): WP_Post {
        $terms = get_the_terms($post_id, self::TAXONOMY);
        $id = $terms[0]->name;
        return get_post($id);
    }

    public static function getSeriesWithReport(string $after = null): array {
        $ids = Concert::getPastParents(self::TAXONOMY, $after);
        $pastPostsWithReport = [];
        foreach ($ids as $id) {
            $report = get_post_meta($id, self::REVIEW_FIELD, true);
            if (!empty($report)) {
                $pastPostsWithReport[] = $id;
            }
        }
        if (count($pastPostsWithReport) == 0) {
            return [];
        }
        $q = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post__in' => $pastPostsWithReport,
            'ignore_sticky_posts' => true,
            'nopaging' => true,
        ]);
        $ps = [];
        if($q->have_posts()) {
            foreach($q->get_posts() as $post) {
                $concerts = self::getConcertsForSeries($post->ID);
                if(!count($concerts)) {
                    continue;
                }
                $latestConcertDate = NULL;
                foreach($concerts as $i) {
                    $date = strtotime($i['date']);
                    if(empty($latestConcertDate) || $date > $latestConcertDate) {
                        $latestConcertDate = $date;
                    }
                }
                $post->parsedTime = $latestConcertDate;
                $ps[] = $post;
            }
            usort($ps, function($a, $b) {
                return $b->parsedTime - $a->parsedTime;
            });
        }
        return $ps;
    }

    public static function shortcode(string $id = null, $expanded, $withDetails, $wrapper = 'span'): string
    {
        if($id) {
            $q = new WP_Query([
                'post_type' => self::POST_TYPE,
                'p' => $id,
                'nopaging' => true
            ]);
            if($q->have_posts()) {
                $cs = $q->get_posts()[0];
                $concerts = self::getConcertsForSeries($cs->ID);
                $ret = '';
                foreach($concerts as $concert) {
                    $dateFormat = $concert['unco'] ? 'j. F Y' : 'j. F Y, H:i';
                    $ret .= '<'.$wrapper.' class="bc-series-shortcode"><a href="'.get_permalink($cs).'#event'.$concert['id'].'">'.get_the_date($dateFormat, $concert['id']).', '.get_the_title($cs).'</a></'.$wrapper.'>';
                }
                if(!empty($ret)) {
                    return $ret;
                }
            }
        }
        return '';
    }
}
