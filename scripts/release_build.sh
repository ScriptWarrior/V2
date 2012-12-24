#!/bin/bash
## Release (public tarball) building script for V2
cd /home/acid/src/PHP
rm -rf v2
cp -a vildev2 v2

# what gets removed
rm ./v2/scripts/release_build.sh
rm ./v2/scripts/*_html_install.sh

v=`cat ./vildev2/doc/VILDEV-VERSION|sed 's/ /_/g'`
tar -cf vildev2-$v.tar ./v2
bzip2 vildev2-$v.tar
echo "vildev2-$v.tar.bz2 created!"