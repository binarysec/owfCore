#!/bin/bash

# IMPORTANT:
# Please launch this script when you are in the same directory of the script or enter module path as first argument.
# Do NOT forget to modify information into "control" (and eventually "postinst") file(s).

############## INFORMATION ##############
# Modify this section if you want to
# customize module information
#
MODULE=core							#Module name
VERSION=1.3							#Package version
DEBNAME=owf-$MODULE-$VERSION		#Package name
MODULEDIR="../"						#Module directory

if [ ! -z $1 ]; then
	if [ -d $1 ]; then
		MODULEDIR=$1
	else
		echo "Not a directory. Exiting."
		exit 10
	fi
fi
echo "Module directory is : $MODULEDIR"
MODULE_FILES=$(ls -d $MODULEDIR/* | grep -v debian) 	#Lists all files needed for the .deb in the module directory.

echo "* CREATING directories..."
mkdir -p $MODULEDIR/debian/$DEBNAME/DEBIAN
mkdir -p $MODULEDIR/debian/$DEBNAME/usr/share/owf/$MODULE/$VERSION

echo "* COPYING files..."
sed -e 's/<MODULE_VERSION>/'$VERSION'/g' $MODULEDIR/debian/control-generic > $MODULEDIR/debian/$DEBNAME/DEBIAN/control
sed -e 's/<MODULE_VERSION>/'$VERSION'/g' $MODULEDIR/debian/postinst-generic > $MODULEDIR/debian/$DEBNAME/DEBIAN/postinst
sed -e 's/<MODULE_VERSION>/'$VERSION'/g' $MODULEDIR/debian/postrm-generic > $MODULEDIR/debian/$DEBNAME/DEBIAN/postrm
chmod 755 $DEBNAME/DEBIAN/postinst $DEBNAME/DEBIAN/postrm
cp -aR $MODULE_FILES $MODULEDIR/debian/$DEBNAME/usr/share/owf/$MODULE/$VERSION/.

echo "* REMOVING .svn files..."
find $MODULEDIR/debian/$DEBNAME -name ".svn" -exec rm -Rf '{}' '+'

echo "* BUILDING package..."
sudo dpkg-deb --build $DEBNAME
if [ $? -eq 0 ]; then
	echo "* PACKAGE CREATED! ;)"
	mv $DEBNAME.deb $MODULEDIR/debian/$DEBNAME/.
else
	echo "* ERROR : CANNOT CREATE PACKAGE! Check it manually."
	exit 1
fi

exit 0	
