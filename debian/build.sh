#!/bin/bash

# IMPORTANT: Please launch this script when you are in the same directory of the script.

############## INFORMATION ##############
# Modify this section if you want to
# customize module information
#
MODULE=core										#Module name
VERSION=1.2										#Package version
DEBNAME=owf-$MODULE-$VERSION					#Package name
MODULE_FILES=$(ls -d ../* | grep -v debian) 	#Lists all files needed for the .deb in the parent directory.
#
####### WARNING :
#
#Do NOT forget to modify information into "control" (and eventually "postinst") file(s).
#
#########################################

echo "* CREATING directories..."
mkdir -p $DEBNAME/DEBIAN
mkdir -p $DEBNAME/usr/share/owf/$MODULE/$VERSION

echo "* COPYING files..."
cp control postinst postrm $DEBNAME/DEBIAN/.
chmod 755 $DEBNAME/DEBIAN/postinst $DEBNAME/DEBIAN/postrm
cp -pR $MODULE_FILES $DEBNAME/usr/share/owf/$MODULE/$VERSION/.

echo "* REMOVING .svn files..."
find . -name ".svn" -exec rm -Rf '{}' '+'

echo "* BUILDING package..."
sudo dpkg-deb --build $DEBNAME
if [ $? -eq 0 ]; then
	echo "* PACKAGE CREATED! ;)"
	mv $DEBNAME.deb $DEBNAME/.
else
	echo "* ERROR : CANNOT CREATE PACKAGE! Check it manually."
	exit 1
fi

exit 0	
