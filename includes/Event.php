<?php
use \Eluceo\iCal\Component\Event;
use \Eluceo\iCal\Property;
use \Eluceo\iCal\Property\RawStringValue;

class BC_Event extends Event {
    public $attach;

    public function buildPropertyBag() {
        $bag = parent::buildPropertyBag();
        if(!empty($this->attach)) {
            $attachmentMime = get_post_mime_type($this->attach);
            $attachmentUrl = wp_get_attachment_url($this->attach);
            $bag->add(new Property(
                'ATTACH',
                new RawStringValue($attachmentUrl),
                [
                    'FMTTYPE' => $attachmentMime
                ])
            );
        }
        return $bag;
    }
}
