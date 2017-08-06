<?php
require("Concert.php");

/**
 * @var string
 */
$text_domain = 'band-concerts';

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
        global $text_domain;
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Konzertserien', $text_domain),
                'singular_name' => __('Konzertserie', $text_domain),
                'archives' => __('Konzertberichte', $text_domain),
                'featured_image' => __('Begleitbild', $text_domain),
                'add_new_item' => __('Konzertserie erstellen', $text_domain),
                'new_item' => __('Neue Konzertserie', $text_domain),
                'edit_item' => __('Konzertserie bearbeiten', $text_domain),
                'view_item' => __('Konzertserie anzeigen', $text_domain),
                'all_items' => __('Alle Konzertserien', $text_domain),
                'search_items' => __('Konzertserie suchen', $text_domain),
                'not_found' => __('Keine Konzertserien gefunden', $text_domain),
                'not_found_in_trash' => __('Keine Konzertserien im Papierkorb', $text_domain),
                'set_featured_image' => __('Begleitbild wählen', $text_domain),
                'remove_featured_image' => __('Begleitbild entfernen', $text_domain),
                'use_featured_image' => __('Als Begleitbild wählen', $text_domain),
                'items_list' => __('Konzertserienliste', $text_domain),
                'items_list_navigation' => __('Konzertserienlistennavigation', $text_domain),
                'menu_name' => __('Konzertserien', $text_domain),
                'name_admin_bar' => __('Konzertserie', $text_domain)
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
        global $text_domain;
        register_taxonomy(self::TAXONOMY, BC_Concert::POST_TYPE, [
            'labels' => [
                'name' => __('Konzert Serien', $text_domain),
                'singular_name' => __('Konzert Serie', $text_domain)
            ],
            'public' => false,
            'show_in_nav_menus' => false,
            'hierarchical' => false,
            'meta_box_cb' => 'post_categories_meta_box',
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
        global $text_domain;
        if(self::POST_TYPE === $post_type) {
            add_meta_box(
                self::REVIEW_FIELD,
                __('Konzertbericht', $text_domain),
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

        $data = wp_kses_post($_POST[self::REVIEW_FIELD_EDITOR]);
        update_post_meta($postID, self::REVIEW_FIELD, $data);

        BC_Concert::saveBox($postID, self::TAXONOMY);
    }

    public static function getCurrentItems(): array {
        $ids = BC_Concert::getCurrentParents(self::TAXONOMY);

        $q = new WP_Query([
            'post__in' => $ids
        ]);
        $posts = [];
        while($q->have_posts()) {
            $q->the_post();
            $posts[] = get_post();
        }
        wp_reset_postdata();
        return $posts;
    }
}
