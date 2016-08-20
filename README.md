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
  jQuery and Boostrap.

Features include:
* Single admin system: There is no user rights management system to worry about. They don't have any.
* SQLite database: There is no database server to worry about.
* Exclusive use of prepared statements with bound values and parameters as protection against SQL injection.
* Minimal public-facing code base: Most of the code lives outside the web root.
* A Dublin Core-based subtractive content object model: The default content object defines the
  properties commonly used by content objects. Particular flavours (subclasses) of content simply
  turn off properties that they don't need. This enables different flavours of content to coexist in
  the same database table, simplifying queries and reducing query load (but you can create new 
  content types that live in their own database tables if you really want to).
