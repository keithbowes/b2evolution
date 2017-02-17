echo Removing unnecessary files from distribution
rm -rf _tests
rm -rf _transifex
rm -f Gruntfile.js
rm -f package.json
rm -f readme.md
rm -f readme.template.html
rm -f .bower.json
rm -f .gitmodules

echo Removing test skins
rm -rf skins/clean1_skin
rm -rf skins/horizon_blog_skin
rm -rf skins/horizon_main_skin

echo Removing files from merges
find . -name '*~' -o -name '*.bak' -o -name '*.old' -delete
find . -name '*.new' -delete
find . -name '*.orig' -delete

echo Removing files from version control
rm -fr `find . -name 'CVS'`
rm -fr `find . -name '.cvs*'`
rm -fr `find . -name '.git*'`
rm -fr `find . -name '.svn*'`

echo Removing files from Transifex
rm -fr .tx

echo Removing additional files
rm -f TODO
rm -f pods.txt
find . -name 'license.txt' -delete
find . -name 'sample.htaccess' -delete

echo Removing myself now
rm -f cleanup.sh package.sh
