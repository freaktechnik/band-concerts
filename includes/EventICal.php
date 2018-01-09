<?php
require_once "ConcertSeries.php";
require_once "Event.php";

use \Eluceo\iCal\Component\Calendar;
use \Eluceo\iCal\Property\Event\Organizer;


//TODO add settings for all these things.

class BC_EventICal {
    const CAL_FEED = 'bc-ical';
    const CONTENT_TYPE = 'text/calendar';

    // Default event duration in Minutes
    public static $eventDuration = 'PT90M';
    public static $organizerName = 'MG Mühlethurnen';
    public static $color = '#047983';
    public static $timezone = "Europe/Zurich";

    private $cal;
    private $duration;
    private $organizer;
    private $tz;

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

    //TODO add ability to only get a single concert, should also get an unique cal id.
    public function __construct(array $allPosts, $title = 'Anlässe') {
        date_default_timezone_set(self::$timezone);
        $this->cal = new Calendar(get_site_url());
        $this->cal->setCalendarColor(self::$color);
        $this->cal->setName(get_bloginfo('name').' '.$title);
        $this->cal->setPublishedTTL("P1W");

        $this->duration = new DateInterval(self::$eventDuration);
        $this->organizer = new Organizer('MAILTO:events@mgmuehlethurnen.ch', [ 'CN' => self::$organizerName ]);
        $this->tz = new DateTimeZone(self::$timezone);

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

    private function makeEvent(WP_Post $post, $concert) {
        if($concert['fee'] != '-1') {
            $entry = 'Eintritt: '.(empty($concert['fee']) ? 'frei, Kollekte' : $concert['fee'].' CHF');
        }
        $event = new BC_Event($concert['id']);

        $event->setDtStart(new DateTime($concert['date'], $this->tz));
        $date = new DateTime($concert['date'], $this->tz);
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

        $event->setCreated(new DateTime($post->post_date, $this->tz));
        $event->setModified(new DateTime($post->post_modified, $this->tz));

        $event->setCategories(self::getCategories($post->ID));
        $event->setOrganizer($this->organizer);
        $event->setStatus(BC_Event::STATUS_CONFIRMED);
        $event->setTimeTransparency(BC_Event::TIME_TRANSPARENCY_TRANSPARENT);

        $flyer = get_post_meta($post->ID, BC_ConcertSeries::FLYER_FIELD, true);
        if(!empty($flyer)) {
            $event->attach = $flyer;
        }

        return $event;
    }

    private function addPost(WP_Post $post) {
        $concerts = BC_ConcertSeries::getConcertsForSeries($post->ID);
        foreach($concerts as $concert) {
            $event = $this->makeEvent($post, $concert);
            $this->cal->addComponent($event);
        }
    }

    public function emit($filename = 'events.ics') {
        header('Content-Type: '.self::CONTENT_TYPE.'; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        echo $this->cal->render();
    }
}
