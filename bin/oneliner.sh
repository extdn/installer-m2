#!/usr/bin/env bash
curl -sSL https://github.com/extdn/installer-m2/raw/v1.0.0-beta4/build/extdn_installer.phar -o extdn_installer.phar
chmod +x extdn_installer.phar
./extdn_installer.phar $@
RET_VALUE=$?
echo ""
echo "The ExtDN installer was downloaded into the current directory"
echo "You can delete it by running rm -i extdn_installer.phar"

exit ${RET_VALUE}