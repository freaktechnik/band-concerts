<?php
use \Eluceo\iCal\Component\{Calendar,Event};
use \Eluceo\iCal\Property\Event\Organizer;

require_once "ConcertSeries.php";

//TODO add settings for all these things.

class BC_EventICal {
    const CAL_FEED = 'bc-ical';
    const CONTENT_TYPE = 'text/calendar';

    // Default event duration in Minutes
    public static $eventDuration = 'PT90M';
    public static $organizerName = 'MG Mühlethurnen';
    public static $color = '#047983';

    private $cal;
    private $duration;
    private $organizer;

    public static function register(callable $getPosts) {
        add_filter('feed_content_type', [self::class, 'onFeedContentType']);
        add_feed(self::CAL_FEED, function() use ($getPosts) {
            $posts = $getPosts();
            $calendar = new self($posts);
            $calendar->emit();
        });
    }

    public static function onFeedContentType(string $contentType, string $feedType = ''): string {
        if($feedType === self::CAL_FEED) {
            return self::CONTENT_TYPE;
        }
        return $contentType;
    }

    public function __construct(array $allPosts) {
        $this->cal = new Calendar(get_site_url());
        $this->cal->setCalendarColor(self::$color);
        $this->cal->setName(get_bloginfo('name').' Anlässe');

        $this->duration = new DateInterval(self::$eventDuration);
        $this->organizer = new Organizer('MAILTO:events@mgmuehlethurnen.ch', [ 'CN' => self::$organizerName ]);

        foreach($allPosts as $post) {
            $this->addPost($post);
        }
    }

    private static function getCategories(int $postID) {
        if(BC_ConcertSeries::isConcert($postID)) {
            return [ 'Konzert' ];
        }
        else {
            return [ 'Anlass' ];
        }
    }

    private function addPost(WP_Post $post) {
        $concerts = BC_ConcertSeries::getConcertsForSeries($post->ID);
        foreach($concerts as $concert) {
            if(!empty($concert['fee']) && $concert['fee'] != '-1') {
                $entry = 'Eintritt: '.(empty($concert['fee']) ? 'frei, Kollekte' : $concert['fee'].' CHF');
            }
            $event = new Event($concert['id']);

            $event->setDtStart(new DateTime($concert['date']));
            $date = new DateTime($concert['date']);
            $date->add($this->duration);
            $event->setDtEnd($date);

            $event->setSummary($post->post_title);

            $postContent = $post->post_content;
            if(isset($entry)) {
                $event->setDescription($entry);
                $postContent .= "<p>".$entry."</p>";
            }
            $event->setDescriptionHTML(apply_filters('the_content', $postContent));

            $event->setLocation($concert['location']);
            $event->setUrl(get_permalink($post));

            $event->setCreated(new DateTime($post->post_date));
            $event->setModified(new DateTime($post->post_modified));

            $event->setCategories(self::getCategories($post->ID));
            $event->setOrganizer($this->organizer);
            $event->setStatus(Event::STATUS_CONFIRMED);

            $this->cal->addComponent($event);
        }
    }

    public function emit() {
        header('Content-Type: '.self::CONTENT_TYPE.'; charset=utf-8');
        header('Content-Disposition: attachment; filename="events.ics"');
        echo $this->cal->render();
    }
}
