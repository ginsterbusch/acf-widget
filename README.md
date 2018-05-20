acf-widget
==========

### NOTE: As of **2017-04-27**, development of this plugin is officially halted. I suggest using [CF7](https://contactform7.com/) instead ;)

Advanced Contact Form Widget for WordPress (unrelated with Advanced Custom Fields!). Simple and clean, easily customizable contact form widget for WordPress with both support for AJAX and complete server-side form processsing (eg. for clients which got JS disabled or got no JS at all). Additionally offering simple translation options w/o gettext based on JSON (to speed up the processsing), including custom drop-in replacements for both the translation as well as the contact form itself, which have to be placed inside the currently active theme directory. Aside of this, the plugin is completely implemented in OOP. 

Basically a plugin for both developers and advanced users, while still being fairly simple and easy to use, also for normal users.


## Feature list (TL;DR):

- Contact form widget for WordPress
- Unobtrusive Javascript-based mail sending and form validation (both dynamic using AJAX and static server-side)
- custom additional fields and styling per widget
- simple, global translation options either using JSON file inside the plugin directory, override via the theme directory plus customization in the admin area


## Work-in-progress:
- Filter and Action hooks for adding CAPTCHA, Math Question and similar anti-spam solutions

## Future plans:
- Improve form validation (possibly using the WordPress'ish in-house jQuery Form plugins)
