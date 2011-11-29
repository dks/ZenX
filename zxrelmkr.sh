#!/bin/bash
#1 - Copy everything to RELEASE
cp -prv zenx_etalon ZXREL
#2 - Restore Empty Passwords
cd ZXREL/examples
./dbrestoredefaults.sh
#3 - Run PHPDOC
cd ..
./makedoc
#4 - Clear Comments
cd ZenX
./rmcomments.sh
#5 - Remove Backups, Manuals and Scripts 
rm -fv *.bak
rm -fv rmcomments.sh
cd ..
rm -fvr tutorials
rm -fv makedoc
rm -fv img/*
rm -fv dat/*
#6 - Zip Documentation and Erase it
sed -i -e 's/<td style="text-align: right">/&Visit <a href="http:\/\/dk-lab.org" target="_blank">dk-lab.org<\/a> for new examples and updated info/g' doc/packages.html
zip -r ../`date +%y%m%d`zxdoc.zip doc/*
rm -fvr doc
#6 - Create ZIP & LZMA
zip -r ../`date +%y%m%d`zenx.zip *
tar -cvf ../`date +%y%m%d`zenx.tar *
cd ..
lzma `date +%y%m%d`zenx.tar
#7 - Erase RELEASE
rm -fvr ZXREL
