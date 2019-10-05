# phpbb-ext-akismet

[![Build Status](https://travis-ci.org/gothick/phpbb-ext-akismet.svg?branch=master)](https://travis-ci.org/gothick/phpbb-ext-akismet)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gothick/phpbb-ext-akismet/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gothick/phpbb-ext-akismet/?branch=master)

**Note**: You should use the [Official Akismet Extension](https://www.phpbb.com/customise/db/extension/akismet/) ([GitHub](https://github.com/senky/phpbb-ext-akismet)) 
rather than this repository. This is not official phpBB software. It was a homebrew extension I wrote that formed some of the basis for the official extension, and I'm not maintaining it any longer.

A phpBB Extension that runs all new topics and replies through the popular 
[Akismet](http://akismet.com) anti-spam service. Anything that Akismet detects as spam
will be placed in the moderation queue.

* Install in the normal way (use the latest [release](https://github.com/gothick/phpbb-ext-akismet/releases) that's compatible with your version of phpBB).
* Configure under Extensions->Akismet Settings. You'll need an API key for Akismet. 
(You can opt to pay nothing for one if it's for non-commercial use.)

Admins and moderators will bypass the check automatically. Any moderation action taken by 
the Extension will appear in the Moderation log. Moderation notification emails will note
specifically if the moderation was due to an Akismet check. If you have problems, check
the phpBB error log. In the event of a failure the Extension should quietly log a message
and allow the post through.

## Future Roadmap

* Add configuration option for skipping the check if a user has > N approved posts already.
* Allow reporting (to Akismet) of missed spam and false positivies.
* Show basic statistics of spam detections on an ACP page.
* ACP page to verify the API key and connection to the Akismet servers.
