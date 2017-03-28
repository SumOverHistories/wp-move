# Move WordPress to another domain

This script makes it easy for you to move your WordPress Installation.

## Getting Started

copy the wp-move folder to your WordPress root directory.

### Execute the script

Open up the /wp-move/index.php in your browser.
You will see the current URL of your installation.
Now you just provide the new URL and choose if you want add to HTTPS by clicking on the the checkbox.
Another click on submit starts to rewrite the database.

### The affected tables

the following tables are affected by this script:
- wp_options
- wp_posts
- wp_postmeta
- wp_blogs (only for multisites)

### HTTPS support

You can always rewrite the URLs to or from https without choosing another domain.
For that to happen, simply provide the current domain and use the designated checkbox.

### Multisite / Multilanguage support

It supports the default WordPress Multisites and WPML. Haven't tested it with any other.

### Not a WordPress plugin

This script reads your wp-config.php to obtain database credentials and your table prefix, so make sure they are correct.
It is no WordPress Plugin for good reasons.

## Be careful!

Never ever deploy this script on a live server as it comes without any security measurements!
Anyone could get control over your database!
Add the wp-move folder to your .gitignore, if you intend to leave it in your project!

## Disclaimer

Use it at your own risk. I do not take any responsibility for failures.