#!/bin/bash
ls | xargs sed -i.bak -e 's/\(^\s*\/*\*\).*//' -n -e 's/^[^$]/&/p'
