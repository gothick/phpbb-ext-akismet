# phpbb-ext-akismet

[![Build Status](https://travis-ci.org/gothick/phpbb-ext-akismet.svg?branch=master)](https://travis-ci.org/gothick/phpbb-ext-akismet)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gothick/phpbb-ext-akismet/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gothick/phpbb-ext-akismet/?branch=master)

**Note**: This is beta software. Don't install it on a production board. Currently it's compatible with phpBB 3.1.x ("Ascraeus"), and it seems to work for me, but your mileage very definitely may vary.

A phpBB Extension that runs all new topics and replies through the popular [Akismet](http://akismet.com) anti-spam service. Anything that Akismet detects as spam will be placed in the moderation queue.

* Install in the normal way.
* Configure under Extensions->Akismet Settings. You'll need an API key for Akismet. (You can opt to pay nothing for one if it's for non-commercial use.)

Admins and moderators will bypass the check automatically. Any moderation action taken by the Extension will appear in the Moderation log. Moderation notification emails will note specifically if the moderation was due to an Akismet check.

## Future Roadmap

* Allow reporting of missed spam and false positivies.
* Allow more configuration options (e.g. only run for users in the Newly Registered Users group, or only for those with fewer than a certain number of existing posts.)
* Show basic statistics of spam detections on an ACP page.
* ACP page to verify the API key and connection to the Akismet servers.
