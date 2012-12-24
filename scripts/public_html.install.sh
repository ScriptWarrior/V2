#!/usr/bin/bash
# V2 web engine installer
source v2.install.config
CURR_DIR=`pwd`
cd $VILDEV_PATH
./scripts/vildev_update.sh $VILDEV_PATH master $INSTALL_DIR
echo "$PROJECT_NAME core succesfuly installed at $INSTALL_DIR"
cd $CURR_DIR