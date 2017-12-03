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

/**
 * @var string
 */

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
    const SCRIPT = 'bc-concerts-box-script';

    public static function register() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Konzerte', BC_TEXT_DOMAIN),
                'singular_name' => __('Konzert', BC_TEXT_DOMAIN)
            ],
            'public' => false,
            'show_in_nav_menus' => false,
            'has_archive' => false
        ]);
    }

    public static function addBox(string $post_type, string $taxonomy_name) {
        add_meta_box(
            self::BOX,
            __('Auftritte', BC_TEXT_DOMAIN),
            [self::class, 'renderBox'],
            $post_type,
            'advanced',
            'default',
            [ $taxonomy_name ]
        );
    }

    public static function getPosts($taxonomy_name, $post_id): array {
        $postsQuery = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => [
                'any',
                'future',
                'draft',
                'auto-draft',
                'private',
                'pending',
                'publish'
            ],
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy_name,
                    'field' => 'slug',
                    'terms' => strval($post_id)
                ]
            ],
            'orderby' => 'date',
            'order' => 'ASC',
            'nopaging' => true
        ]);
        $posts = [];
        if($postsQuery->have_posts()) {
            foreach($postsQuery->get_posts() as $p) {
                $posts[] = [
                    'id' => esc_attr($p->ID),
                    'parent_id' => $post_id,
                    'date' => esc_attr($p->post_date),
                    'location' => esc_attr(get_post_meta($p->ID, self::LOCATION_FIELD, true)),
                    'fee' => esc_attr(get_post_meta($p->ID, self::FEE_FIELD, true)) ?? -1
                ];
            }
        }

        return $posts;
    }

    public static function renderBox($post, array $opts) {
        $taxonomy_name = $opts['args'][0];
        wp_nonce_field(self::NONCE_FIELD, self::NONCE_NAME);

        $posts = self::getPosts($taxonomy_name, $post->ID);
        $postIDs = [];
        ?>
        <input type="hidden" value="" name="bc_removed_concerts" id="bc_removed_concerts">
        <input type="hidden" value="<?php echo count($posts) ?>" name="bc_concerts_count" id="bc_concerts_count">
        <ul id="bc_concerts_list">
            <?php
            foreach($posts as $i => $concert) {
                $concert_id = 'bc_concert'.$i.'_';
                $postIDs[] = $i;
            ?>
            <li id="bc_concert_<?php echo $i ?>" class="bc_concert">
                <div>
                    <input class="bc_concert_id" name="<?php echo $concert_id ?>id" value="<?php echo $concert['id'] ?>" type="hidden">
                    <p class="bc_concert_row">
                        <label><?php _e('Datum', BC_TEXT_DOMAIN) ?> <input type="text" name="<?php echo $concert_id ?>date" class="bc_concert_date" value="<?php echo $concert['date'] ?>"></label>
                    </p>
                    <p class="bc_concert_row">
                        <label><?php _e('Ort', BC_TEXT_DOMAIN) ?> <input type="text" name="<?php echo $concert_id ?>location" value="<?php echo $concert['location'] ?>"></label>
                    </p>
                    <p class="bc_concert_row fee">
                        <label><?php _e('Eintritt (CHF)', BC_TEXT_DOMAIN) ?> <input type="number" min="-1" step="1" name="<?php echo $concert_id ?>fee" value="<?php echo $concert['fee'] ?>"></label>
                    </p>
                </div>
                <button class="bc_remove_concert button"><span class="dashicons dashicons-trash"></span></button>
            </li>
            <?php
            }
            ?>
        </ul>
        <input type="hidden" value="<?php echo implode(',', $postIDs) ?>" name="bc_concerts_ids" id="bc_concerts_ids">
        <button id="bc_add_concert" class="button"><?php _e('Auftritt hinzufügen', BC_TEXT_DOMAIN) ?></button>
        <?php
    }

    public static function saveBox($post_id, string $taxonomy_name) {
        if(!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce($_POST[self::NONCE_NAME], self::NONCE_FIELD)) {
            return $postID;
        }

        $removedPostsRaw = sanitize_text_field($_POST['bc_removed_concerts']);
        if(!empty($removedPostsRaw)) {
            $removedPosts = explode(',', $removedPostsRaw);
            foreach($removedPosts as $removedID) {
                wp_delete_post($removedID, true);
            }
        }

        $concertIDs = sanitize_text_field($_POST['bc_concerts_ids']);
        if(strlen($concertIDs) > 0) {
            $concerts = explode(',', $concertIDs);
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
                    'post_date_gmt' => get_gmt_from_date($date),
                    'post_name' => sanitize_title($post_id.$date.$location.$fee)
                ];

                $props['tax_input'][$taxonomy_name] = strval($post_id);
                $props['meta_input'][self::LOCATION_FIELD] = $location;
                $props['meta_input'][self::FEE_FIELD] = $fee;

                if(isset($_POST[$concert_id.'id'])) {
                    $concert_post_id = sanitize_text_field($_POST[$concert_id.'id']);
                    $props['ID'] = $concert_post_id;
                }
                wp_insert_post($props);
            }
        }
    }

    public static function getCurrentParents(string $taxonomy_name): array {
        $postsQuery = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'any',
            'nopaging' => true,
            'date_query' => [
                'after' => 'today'
            ]
        ]);
        $ps = [];
        if($postsQuery->have_posts()) {
            foreach($postsQuery->get_posts() as $p) {
                $terms = get_the_terms($p, $taxonomy_name);
                if(!in_array($terms[0]->name, $ps)) {
                    $ps[] = intval($terms[0]->name);
                }
            }
        }
        return $ps;
    }
}
