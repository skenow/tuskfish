# tuskfish

A single user micro CMS. Tuskfish is designed to provide a minimalist yet capable framework for
publishing different kinds of content. It is suitable for use by individuals and small
organisations. It provides the publishing tools that you need and nothing that you don't.

The project emphasis is on creating the simplest and most lightweight code base possible:
* A small, simple code base is easy to understand and maintain as PHP evolves.
* Security is a lot easier to manage in a small project.
* Avoiding use of external libraries as far as possible to reduce attack surface, maintenance overhead
  and code bloat. External libraries in use are: Boostrap 4, jQuery, Bootstrap-datepicker,
  Bootstrap-fileinput and HTMLPurifier.

Features include:
* Publish a mixed stream of articles, file downloads, images, audio, video, static pages and collections with one simple form.
* Organise your content with tags, collections and content types.
* Bootstrap-based templates with responsive, mobile-first themes.
* Native PHP template engine; easily create new template sets.
* PHP 7, HTML5 and SQLite database.
* Single admin system: There is no user rights management system to worry about. They don't have any.
* SQLite database: There is no database server to worry about.
* Exclusive use of prepared statements with bound values and parameters as protection against SQL injection.
* Minimal public-facing code base: Most of the code lives outside the web root.
* Lightweight core library ~ 220 KB in size.

System requirements
* PHP 7.2+
* SQLite3 extension.
* PDO extension.
* pdo_sqlite extension.
* [Optional]: curl extension + a Yubikey hardware token are required if you want to use two-factor Yubikey authentication.
* Apache webserver.

INSTALLATION
* Please see the installation guide at: https://tuskfish.biz/installation.php

USER MANUAL
* Available at: https://tuskfish.biz/article.php?id=15

DEVELOPER GUIDE
* Available at: https://tuskfish.biz/article.php?id=47

API
* Available at https://tuskfish.biz/api/
