# phpbb-ext-akismet

27 January 2015: This is in the very early stages of development! 
Don't use me yet :D

## Known issues:

* Moderation log entries and notification emails may not appear in the
language of the nominated Akismet user ("Akismet username" in the 
extension configuration.) Either I'm doing something wrong, or it's 
annoyingly difficult to get phpBB's language stuff working in the 
language of someone other than the current $user...

* I use [enable_super_globals()]() for calls to [a third-party 
Akismet client](https://github.com/tijsverkoyen/Akismet), at it makes
significant use of the `$_SERVER` variable. This looks safe to me,
but I imagine it'll raise the odd eyebrow among phpBB 3.1 devs.  
