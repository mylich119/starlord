#!/bin/bash

find ./ -name "*php" | xargs  sed -i  ''  's/PROJECT_NAME_REPLACEMENT/'$1'/g'
mv PROJECT_NAME_REPLACEMENT_vendor  $1_vendor
