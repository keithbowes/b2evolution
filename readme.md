# b2evolution blog/CMS

## More than a blog !

Why restrain yourself to a single blog when your website could be so much more?

b2evolution includes everything you need. Right out of the box. Plugins optional.

More info: http://b2evolution.net

## This is the i7 Branch !

We maintain this GitHub repo ahead of public releases. This branch is synchronized with the internal i7 Branch. This will become b2evolution version 6. For b2evolution version 5, please switch to the "i6" branch.

Note we have moved the previous "master" branch to "old-master". The current "master" branch (this branch) includes an import on the CVS history from SoureForge as well as a sync to the latest developement version located on our developement SVN repository.

We are planing to transition the SVN development branches to GitHub also but haven't decided on a branching model and workflow yet.

## Usage info

### Requirements

PHP 5.2+. MySQL 5+. Optimized for Apache 2+.

More info: http://b2evolution.net/man/installation-upgrade/system_requirements

### Download

#### Bower

Just type `bower install b2evolution` .

#### Manual Download

You can download releases either from GitHub or from b2evolution.net :

- https://github.com/b2evolution/b2evolution/releases
- http://b2evolution.net/downloads/

### Installation

Please open the file index.html at the root of the distribution and follow the instructions.

Basically the installation involves creating a MySQL DB and entering the access credential into the installation script.

More info: http://b2evolution.net/man/installation-upgrade/new-install/installation

### Upgrade

#### Automatic

b2evolution includes an automatic upgrade feature which you can use to automatically download the lastest stable version and perform the upgrade operations.

#### Manual

You can download any newer version (including beta releases), overwrite the files of your current install (after backup) and then run the install script.

The installation script will detect that the b2evolution database is already installed (any version) and offer to upgrade it to the current version.

More info: http://b2evolution.net/man/installation-upgrade/upgrading/
