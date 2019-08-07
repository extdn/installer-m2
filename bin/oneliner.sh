#!/usr/bin/env bash
curl -sSL https://raw.githubusercontent.com/extdn/installer-m2/master/build/extdn_installer.phar -o extdn_installer.phar
chmod +x extdn_installer.phar
./extdn_installer.phar $@
rm -i extdn_installer.phar