Nette Web Project
=================

This is a simple application using the [Nette](https://nette.org). 

Requirements
------------

PHP 7.1 or higher.

Installation
------------

The best way to install is using git and Composer:

	git clone https://github.com/Dukecz/Nubium.git
	cd Nubium
	composer install

Make directories `temp/` and `log/` writable.

Web Server Setup
----------------

For Apache or Nginx, setup a virtual host to point to the `www/` directory of the project and you
should be ready to go.

**It is CRITICAL that whole `app/`, `log/` and `temp/` directories are not accessible directly
via a web browser. See [security warning](https://nette.org/security-warning).**
