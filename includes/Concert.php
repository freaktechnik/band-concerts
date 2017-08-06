<?php
/**
 * @var string
 */
$text_domain = 'band-concerts';

class BC_Concert {
    /**
     * @var string
     */
    const POST_TYPE = "concert";
    /**
     * @var string
     */
    const BOX = 'bc_concerts';
    /**
     * @var string
     */
    const NONCE_FIELD = 'bc_concerts_inner_nonce';
    /**
     * @var string
     */
    const NONCE_NAME = 'bc_concerts_inner';
    /**
     * @var string
     */
    const LOCATION_FIELD = 'bc_concert_location';
    /**
     * @var string
     */
    const DATE_FIELD = 'bc_concert_date';
    /**
     * @var string
     */
    const FEE_FIELD = 'bc_concert_fee';
    /**
     * @var string
     */
    const SCRIPT = 'bc_concerts_box_script';

    public static function register() {
        global $text_domain;
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Konzerte', $text_domain),
                'singular_name' => __('Konzert', $text_domain)
            ],
            'public' => false,
            'show_in_nav_menus' => false,
            'supports' => [
                'revisions'
            ],
            'has_archive' => false
        ]);
    }

    public static function addBox(string $post_type, string $taxonomy_name) {
        global $text_domain;
        add_meta_box(
            self::BOX,
            __('Auftritte', $text_domain),
            [self::class, 'renderBox'],
            $post_type,
            'advanced',
            'default',
            [ $taxonomy_name ]
        );
    }

    private static function getPosts($taxonomy_name, $post_id) {
        $postsQuery = new WP_Query([
            'post_type' => self::POST_TYPE,
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy_name,
                    'field' => 'name',
                    'terms' => $post_id
                ]
            ]
        ]);
        $posts = [];
        while($postsQuery->have_posts()) {
            $postsQuery->the_post();
            $id = get_the_ID();
            $posts[] = [
                'id' => esc_attr($id),
                'date' => esc_attr(get_post_meta($id, self::DATE_FIELD, true)),
                'location' => esc_attr(get_post_meta($id, self::LOCATION_FIELD, true)),
                'fee' => esc_attr(get_post_meta($id, self::FEE_FIELD, true))
            ];
        }
        wp_reset_postdata();

        return $posts;
    }

    public static function renderBox($post, array $opts) {
        global $text_domain;
        $taxonomy_name = $opts[0];
        wp_nonce_field(self::NONCE_FIELD, self::NONCE_NAME);

        $posts = self::getPosts($taxonomy_name, $post->ID);
        $postIDs = [];
        ?>
        <button id="bc_add_concert" class="button"><?php _e('Auftritt hinzufügen', $text_domain) ?></button>
        <input type="hidden" value="" name="bc_removed_concerts" id="bc_removed_concerts">
        <input type="hidden" value="<?php echo count($posts) ?>" name="bc_concerts_count" id="bc_concerts_count">
        <ul id="bc_concerts_list">
            <?php
            foreach($posts as $i => $concert) {
                $concert_id = 'bc_concert'.$i.'_';
                $postIDs[] = $i;
            ?>
            <li id="bc_concert_<?php echo $i ?>">
                <input class="bc_concert_id" name="<?php echo $concert_id ?>id" value="<?php echo $concert['id'] ?>" type="hidden">
                <p>
                    <label><?php _e('Datum', $text_domain) ?> <input type="text" name="<?php echo $concert_id ?>date" class="bc_concert_date" value="<?php echo $concert['date'] ?>"></label>
                </p>
                <p>
                    <label><?php _e('Ort', $text_domain) ?> <input type="text" name="<?php echo $concert_id ?>location" value="<?php echo $concert['location'] ?>"></label>
                </p>
                <p>
                    <label><?php _e('Eintritt', $text_domain) ?> <input type="number" min="0" step="1" name="<?php echo $concert_id ?>fee" value="<?php echo $concert['fee'] ?>">CHF</label>
                </p>
                <button class="bc_remove_concert button"><?php _e('Auftritt entferenen') ?></button>
            </li>
            <?php
            }
            ?>
        </ul>
        <input type="hidden" value="<?php implode(',', $postIDs) ?>" name="bc_concerts_ids" id="bc_concerts_ids">
        <?php
        wp_enqueue_script(self::SCRIPT);
    }

    public static function saveBox($post_id, string $taxonomy_name) {
        if(!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_FIELD)) {
            return $postID;
        }

        $removedPosts = explode(',', sanitize_text_field($_POST['bc_removed_concerts']));
        if(!empty($removedPosts)) {
            foreach($removedPosts as $removedID) {
                wp_delete_post($removedID, true);
            }
        }

        $concerts = explode(',', sanitize_text_field($_POST['bc_concerts_ids']));

        foreach($concerts as $i) {
            $concert_id = 'bc_concert'.$i.'_';

            $date = sanitize_text_field($_POST[$concert_id.'date']);
            $location = sanitize_text_field($_POST[$concert_id.'location']);
            $fee = intval($_POST[$concert_id.'fee']);

            //TODO ensure date matches the pattern

            $props = [
                'post_type' => self::POST_TYPE,
                'tax_input' => [],
                'meta_input' => [],
                'post_date' => $date,
                'post_date_gmt' => get_gmt_from_date($date)
            ];

            $props['tax_input'][$taxonomy_name] = $post_id;
            $props['meta_input'][self::LOCATION_FIELD] = $location;
            $props['meta_input'][self::FEE_FIELD] = $fee;

            if(isset($_POST[$concert_id.'id'])) {
                $concert_post_id = sanitize_text_field($_POST[$concert_id.'id']);
                $props['ID'] = $concert_post_id;
            }
            wp_insert_post($props);
        }
    }

    public static function getCurrentParents(string $taxonomy_name): array {
        $postsQuery = new WP_Query([
            'post_type' => self::POST_TYPE,
            'date_query' => [
                'after' => '-1 day'
            ]
        ]);
        $posts = [];
        while($postsQuery->have_posts()) {
            $postsQuery->the_post();
            $terms = get_the_terms();
            if(!in_array($terms[$taxonomy_name]->name, $posts)) {
                $posts[] = $terms[$taxonomy_name]->name;
            }
        }
        wp_reset_postdata();
        return $posts;
    }
}
