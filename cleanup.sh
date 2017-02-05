echo Removing files from merges
find . -name '*~' -delete
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
