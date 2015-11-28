echo Removing unnecessary files from distribution
rm -rf _tests
rm -rf _transifex
rm -f Gruntfile.js
rm -f package.json
rm -f readme.md
rm -f readme.template.html
rm -f .bower.json
./cleanup.sh
echo Removing myself now
rm -f cleanup.sh package.sh
echo Stepping out
cd ..
echo Compressing
zip -qr9 b2evolution.zip b2evolution 
