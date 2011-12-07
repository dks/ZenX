#!/bin/bash
rm -fv UserEngineExample[0-9].php
for fn in 1 3 4 6 7 9
do
	mv -v UserEngineExample$fn.php.bak UserEngineExample$fn.php
done
