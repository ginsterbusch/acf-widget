acf-widget
==========

ACF Widget for WordPress. Simple and clean, easily customizable contact form widget for WordPress with both support for AJAX and complete server-side form processsing (eg. for clients which got JS disabled or got no JS at all). Additionally offering simple translation options w/o gettext (to speed up the processsing), including custom drop-in replacements for both the translation as well as the contact form itself, which have to be placed inside the currently active theme directory. Aside of this, the plugin is completely implemented in OOP. 

Basically a plugin for both developers and advanced users, while still being fairly simple and easy to use, also for normal users.

Work-in-progress:
- Improve custom fields implementation
- Filter and Action hooks for adding CAPTCHA, Math Question and similar anti-spam solutions

Future plans:
- Improve form validation (maybe use the WordPress'ish in-house jQuery Form plugins)
- Implement framework-free, pure JS form validation and compatiblity with several frameworks (eg. jQuery or Zepto) and loaders / feature detectors (eg. HeadJS)
