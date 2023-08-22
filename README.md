# htmxthing

Little demo app for [</> htmx](https://htmx.org/) with basic PHP components.

*This is just a demo, do not put this in production, please!*

## Requirements
 - PHP
 - Composer
 - Apache that can serve PHP and interpret `.htaccess`
 - Mysql(-like) Database

## Install
 - `composer install`
 - Copy `.env.dist.php` to `.env.php` and edit the `$conf` variable to suit your needs, most probably you'll only need to edit the database related config.
 - Configure url in webserver so it can access this directory and point to that url.
