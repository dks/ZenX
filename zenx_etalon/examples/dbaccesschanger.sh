#!/bin/bash
if [ $# -ne 4 ] 
then
	echo "Must have four parameters!"
	echo -e "host - connection hostname\nuser - connection username\npass - connection password\nbase - database to connect to" | nl
fi
if [ $# -eq 4 ] 
then
	sed -i.bak -e "s/\"host\",\"user\",\"pass\",\"base\"/\"$1\",\"$2\",\"$3\",\"$4\"/g" UserEngineExample*.php
fi
