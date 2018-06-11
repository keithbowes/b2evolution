![b2evolution CMS](media/shared/global/logos/b2evolution_1016x208_wbg.png)

# b2evolution CMS

This is b2evolution, a modern, object-oriented blogging system.

## Differences to upstream

This is [a fork](https://github.com/keithbowes/b2evolution) of the [upstream project](https://github.com/b2evolution/b2evolution).  The primary changes in this fork are:

1.  Sorting of categories.  Categories are sorted alphabetically rather than based on ID.
1.  I still use xg.php (where upstream seems to have settled for xgmac.sh and completely removed xg.php), though I've made several changes.

I'm currently working on putting the bloated system on a diet, to return to simple days of it being a blogging system that didn't take up most of the space my host gives me.  If you want a full CMS, you should perhaps use the upstream system instead.

## Requirements

Basically, all you need is a standard web host with PHP 5.3+ (5.6.x or 7.x recommended) with the mysqli extension, MySQL 5+ (5.5+ recommended) or MariaDB 5+ (10+ recommended), and a web server (Apache 2.4+ recommended for the automatic use of clean slugs).

## Downloading

### Manual Download

You can download releases from GitHub:

- <https://github.com/keithbowes/b2evolution>

## Installation

Upload everything to your web server and call the installation script that you will find at `/install/index.php` on your website. Then you just need to enter your database connection details and the installer will take care of everything for you.

## Upgrading

### Automatic upgrade

b2evolution includes an automatic upgrade feature which you can use to automatically download the lastest stable version and perform the upgrade operations.

### Manual upgrade

You can download any newer version (including beta releases), overwrite the files of your current installation (after backup) and then run the install script.

The installation script will detect that the b2evolution database is already installed (any version) and offer to upgrade it to the current version.

## GitHub

All bug fixes and all new code are made available through GitHub before being packaged as releases. If you are interested in cutting-edge versions, we recommend you [follow us on GitHub](https://github.com/keithbowes/b2evolution).
