# band-concerts
WordPress plugin to manage concerts as series with multiple appearances. Each
appearance has a location, time and fee.

## Features
Concert series that can have multiple occurrences. A concert series has the following properties:
 - Description
 - Main image
 - Flyer
 - Review
 - Type (event or concert)

Every occurrence has the following properties:
 - Start time
 - End time (optional)
 - Not yet decided/all day toggle
 - Location
 - Fee (-1 for no fee, 0 for collection)
 - Facebook event link

It can further generate an iCal file with all events or just an export of a single event.
The calendar is registered as feed with the name `bc-ical`.

## Installation

 - `composer install`
 - Upload everything to your `wp-contents/plugins`

## License
See [LICENSE](./LICENSE)
