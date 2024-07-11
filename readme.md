![b2evolution CMS](media/shared/global/logos/b2evolution_1016x208_wbg.png)

# b2evolution CMS

This is b2evolution, a modern, object-oriented blogging system.
This is b2evolution version **7.2.5-stable**.

## Objectives

This is [a fork](https://github.com/keithbowes/b2evolution) of the [upstream project](https://github.com/b2evolution/b2evolution), focusing on a small project used for blogs and CMSes.  The primary goals this fork are:

- [X] Sort categories alphabetically rather than based on ID.
- [X] Restore xg.php for translations.
- [ ] Remove automatic updating (just download and extract the tarball).
- [ ] Remove the v5 skin templates.
- [ ] Remove things marked deprecated or obsolete.
- [ ] Backport the accessibility improvements from my EdK skin.
- [ ] Remove hacks for old versions of PHP.
- [ ] Remove antispam support (there are plugins that can be used for that).
- [ ] Remove support for trackbacks, pingbacks, and linkbacks. Only the standard WebMention should be supported.
- [ ] Remove Avatar support. There are plugins that can do that.
- [ ] Remove nonfunctional plugins.
- [ ] Remove support for versioned posts. That just wastes database space.
- [ ] Remove non-HTTP caching. That wastes disk space for no good reason.
- [ ] Remove support for (X)HTML comments. Only Markdown should be supported.
- [ ] Replace any remaining XHTML with HTML5.
- [ ] Rework plugins not to use sessions.
- [ ] Remove sessions support. This really uses up database space and can lead to a site being unavailable.
- [ ] Remove support for non-blogging features:
    - [ ] Forums
    - [ ] Email campaigns
    - [ ] Polls
    - [ ] SVN client (replace with a Git client?)
    - [ ] WHOIS
    …

The original goal was to remove the bloat from the ever expanding upstream project, but now that it's been discontinued, that seems less urgent, but there are definitely some things I'd like to remove. 

## Requirements

Basically, all you need is a standard web host with PHP 7+ with the mysqli extension, MySQL 5+ (5.5+ recommended) or MariaDB 5+ (10+ recommended), and a web server (Apache 2.4+ recommended for the automatic use of clean slugs).

## Downloading


### With Bower

If you're familiar with bower, just type: `bower install b2evolution`

This will install the **latest** GitHub release of b2evolution (which may be a beta version).

### Manual Download

You can download releases from Github:

- <https://github.com/keithbowes/b2evolution> - This fork
- https://github.com/b2evolution/b2evolution/releases - Upstream (abandoned)
- https://b2evolution.net/downloads/ - Project website

## Installation

Upload everything to your web server and call the installation script that you will find at `/install/index.php` on your website. Then you just need to enter your database connection details and the installer will take care of everything for you.

Now, you might ask for more details here... Totally legitimate! Please check out our [Getting Stated - Installation Guide](https://b2evolution.net/man/getting-started).

Hint: It is possible to install b2evolution in less than 3 minutes. Probably not the first time though. (And the same is true for anyone else claiming a 5 minute install process.)

## Upgrading

### Automatic upgrade

b2evolution includes an automatic upgrade feature which you can use to automatically download the lastest stable version and perform the upgrade operations.

### Manual upgrade

You can download any newer version (including beta releases), overwrite the files of your current installation (after backup) and then run the install script.

The installation script will detect that the b2evolution database is already installed (any version) and offer to upgrade it to the current version.

## Github
There are [several other upgrade options](https://b2evolution.net/man/upgrading).

All bug fixes and all new code are made available through Github before being packaged as releases. If you are interested in cutting-edge versions, we recommend you [follow us on Github](https://github.com/keithbowes/b2evolution).
