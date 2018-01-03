# tuskfish

A single user micro CMS. Tuskfish is designed to provide a minimalist yet capable framework for
publishing different kinds of content. It is suitable for use by individuals and small
organisations. It provides the publishing tools that you need and nothing that you don't.

The project emphasis is on creating the simplest and most lightweight code base possible:
* A small, simple code base is easy to understand and maintain as PHP evolves.
* Security is a lot easier to manage in a small project.
* Avoiding use of external libraries as far as possible greatly reduces attack surface and code
  bloat (most libraries are far bigger than Tuskfish in their own right and every library you add
  comes with its own security vulnerabilities). The only external libraries currently in use are
  jQuery, Boostrap and HTMLPurifier.

Features include:
* Publish articles, file downloads, images, audio, video, static pages and collections with one simple form.
* Organise your content with tags and collections.
* Single admin system: There is no user rights management system to worry about. They don't have any.
* SQLite database: There is no database server to worry about.
* Exclusive use of prepared statements with bound values and parameters as protection against SQL injection.
* Minimal public-facing code base: Most of the code lives outside the web root.
* Bootstrap ready.
