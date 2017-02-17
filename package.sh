currentbasename=${PWD##*/} 	# Assign current basename to variable
version=$(git describe --always --tag) # Must be run before cleanup.sh is called
date=$(date +'%Y-%m-%d')

./cleanup.sh

echo Stepping out
cd ..

echo Compressing...
zip -qr9 b2evolution-${version}-${date}.zip ${currentbasename}
