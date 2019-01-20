#!/bin/bash
rm -rf /home/starlord_bak
cp -r /home/starlord /home/starlord_bak
rm -rf /home/starlord
mkdir /home/starlord
tar -xvf  /tmp/starlord.tar  -C /home/
rm -f /tmp/starlord.tar
