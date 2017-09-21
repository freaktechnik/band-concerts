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

require("Concert.php");

class BC_ConcertSeries {
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

    public static function register() {
        self::registerPostType();
        self::registerTaxonomy();
    }

    private static function registerPostType() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Konzertserien', BC_TEXT_DOMAIN),
                'singular_name' => __('Konzertserie', BC_TEXT_DOMAIN),
                'archives' => __('Konzertberichte', BC_TEXT_DOMAIN),
                'featured_image' => __('Begleitbild', BC_TEXT_DOMAIN),
                'add_new_item' => __('Konzertserie erstellen', BC_TEXT_DOMAIN),
                'new_item' => __('Neue Konzertserie', BC_TEXT_DOMAIN),
                'edit_item' => __('Konzertserie bearbeiten', BC_TEXT_DOMAIN),
                'view_item' => __('Konzertserie anzeigen', BC_TEXT_DOMAIN),
                'all_items' => __('Alle Konzertserien', BC_TEXT_DOMAIN),
                'search_items' => __('Konzertserie suchen', BC_TEXT_DOMAIN),
                'not_found' => __('Keine Konzertserien gefunden', BC_TEXT_DOMAIN),
                'not_found_in_trash' => __('Keine Konzertserien im Papierkorb', BC_TEXT_DOMAIN),
                'set_featured_image' => __('Begleitbild wählen', BC_TEXT_DOMAIN),
                'remove_featured_image' => __('Begleitbild entfernen', BC_TEXT_DOMAIN),
                'use_featured_image' => __('Als Begleitbild wählen', BC_TEXT_DOMAIN),
                'items_list' => __('Konzertserienliste', BC_TEXT_DOMAIN),
                'items_list_navigation' => __('Konzertserienlistennavigation', BC_TEXT_DOMAIN),
                'menu_name' => __('Konzertserien', BC_TEXT_DOMAIN),
                'name_admin_bar' => __('Konzertserie', BC_TEXT_DOMAIN)
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
        register_taxonomy(self::TAXONOMY, BC_Concert::POST_TYPE, [
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
        register_taxonomy_for_object_type(self::TAXONOMY, BC_Concert::POST_TYPE);
    }

    public static function addBox(string $post_type) {
        if(self::POST_TYPE === $post_type) {
            add_meta_box(
                self::REVIEW_FIELD,
                __('Konzertbericht', BC_TEXT_DOMAIN),
                [self::class, 'renderBox'],
                self::POST_TYPE
            );
            BC_Concert::addBox(self::POST_TYPE, self::TAXONOMY);
        }
    }

    public static function renderBox($post) {
        wp_nonce_field(self::NONCE_FIELD, self::NONCE_NAME);

        $content = get_post_meta($post->ID, self::REVIEW_FIELD, true);
        wp_editor($content, self::REVIEW_FIELD_EDITOR, [
            'drag_drop_upload' => true
        ]);
    }

    public static function saveBox($postID) {
        if(!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_FIELD)) {
            return $postID;
        }

        if(get_post_type($postID) == self::POST_TYPE) {
            $data = wp_kses_post($_POST[self::REVIEW_FIELD_EDITOR]);
            update_post_meta($postID, self::REVIEW_FIELD, $data);

            BC_Concert::saveBox($postID, self::TAXONOMY);
        }
    }

    public static function getCurrentItems(): array {
        $ids = BC_Concert::getCurrentParents(self::TAXONOMY);

        $q = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post__in' => $ids,
            'nopaging'
        ]);
        if($q->have_posts()) {
            return $q->get_posts();
        }
        return [];
    }

    public static function getConcertsForSeries($post_id): array {
        return BC_Concert::getPosts(self::TAXONOMY, $post_id);
    }
}
