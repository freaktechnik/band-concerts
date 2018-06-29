<?php
namespace BandConcerts;

require_once __DIR__."/ConcertSeries.php";

class ReportsWidget extends \WP_Widget {
    function __construct() {
        parent::__construct(
            'bc_reports_widget',
            __('BC Reports'),
            [ 'description' => __('List of recent reports and articles') ]
        );
    }
    /**
     * Prints the widget.
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'].apply_filters('widget_title', $instance['title']).$args['after_title'];
        }

        $count = $instance['count'] ?? 3;
        $articles = [];
        // get $count latest articles
        $q = new \WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'order' => 'date'
        ]);
        if($q->have_posts()) {
            foreach($q->get_posts() as $post) {
                $post->parsedTime = strtotime($post->post_date);
                $articles[] = $post;
            }
        }
        // get $count latest concert series with report
        $limit = null;
        if(count($articles) >= $count) {
            $limit = $articles[$count - 1]->post_date;
        }
        $reports = ConcertSeries::getSeriesWithReport($limit);
        /** @var WP_Post[] $articles */
        $articles = array_merge($articles, array_slice($reports, 0, $count));
        // store the three latest entries in $articles
        usort($articles, function($a, $b) {
            return $b->parsedTime - $a->parsedTime;
        });
        $articles = array_slice($articles, 0, $count);
        if(count($articles) > 0) {
            ?><ul><?php
            foreach($articles as $article)
            {
                ?><li><a href="<?php echo get_permalink($article); if($article->post_type == ConcertSeries::POST_TYPE) { echo "#review"; } ?>"><?php echo get_the_title($article); ?></a></li><?php
            }
            ?></ul><?php
        }
        else {
            ?><em><?php _e('No reports.') ?></em><?php
        }
        echo $args['after_widget'];
    }
    /**
     * Prints the widget settings in the customizer.
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $titleFieldId = esc_attr($this->get_field_id('title'));
        $countFieldId = esc_attr($this->get_field_id('count'));
?><p>
    <label for="<?php echo $titleFieldId; ?>"><?php _e(esc_attr('Title:')); ?></label>
    <input class="widefat" id="<?php echo $titleFieldId; ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
    <label for="<?php echo $countFieldId; ?>"><?php _e('Entry count:'); ?></label>
    <input class="widefat" id="<?php echo $countFieldId; ?>" name="<?php echo esc_attr($this->get_field_name('count')); ?>" type="number" min="0" step="1" value="<?php echo esc_attr($instance['count'] ?? 3); ?>">
</p><?php
    }
    /**
     * Saves the new settings from the customizer.
     */
     public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? strip_tags($new_instance['title']) : '';
        $instance['count'] = !empty($new_instance['count']) ? intval(strip_tags($new_instance['count'])) : 0;
        return $instance;
    }
}
