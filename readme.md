![b2evolution CMS](media/shared/global/logos/b2evolution_1016x208_wbg.png)

# b2evolution CMS

This is b2evolution, a modern, object-oriented blogging system.
This is b2evolution version **7.2.5-stable**.

## Differences to upstream

This is [a fork](https://github.com/keithbowes/b2evolution) of the [upstream project](https://github.com/b2evolution/b2evolution).  The primary changes in this fork are:

1.  Sorting of categories.  Categories are sorted alphabetically rather than based on ID.
2.  I still use xg.php (where upstream seems to have settled for xgmac.sh and completely removed xg.php), though I've made several changes.

I'm currently working on putting the bloated system on a diet, to return to simple days of it being a blogging system that didn't take up most of the space my host gives me.  If you want a full CMS, you should perhaps use the upstream system instead.

## Requirements

Basically, all you need is a standard web host with PHP 7+ with the mysqli extension, MySQL 5+ (5.5+ recommended) or MariaDB 5+ (10+ recommended), and a web server (Apache 2.4+ recommended for the automatic use of clean slugs).

## Downloading


### With Bower

If you're familiar with bower, just type: `bower install b2evolution`

This will install the **latest** GitHub release of b2evolution (which may be a beta version).

### Manual Download

You can download releases from Github:

- <https://github.com/keithbowes/b2evolution>
- https://github.com/b2evolution/b2evolution/releases
- https://b2evolution.net/downloads/

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
