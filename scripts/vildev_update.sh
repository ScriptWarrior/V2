#!/bin/bash
# this small script makes an update of V2 installation from the source branch given as the second param, to the location supplied as the first arg, destination dir is the last one:
REPO_LOCATION=$1
BRANCH=$2
DEST_DIR=$3
if [ $# -ne 3 ]; then
	echo "Usage: $0 repo_location branch_name dest_public_html_dir"
	exit
fi
if [ ! -d $REPO_LOCATION ]; then
	echo "Cannot change directory to $REPO_LOCATION: not a directory"
	exit
fi
if [ ! -d $DEST_DIR ]; then
	echo "Cannot copy files into $DEST_DIR, not a directory, check path and try again"
	exit
fi

# make dirs
cd $REPO_LOCATION && git checkout $BRANCH || exit
DIRS=(modules lib translations config js css img templates doc templates_c etc entities pub)
for directory in ${DIRS[*]}; do
	if [ ! -d "$DEST_DIR/$directory" ]; then
		echo "mkdir $DEST_DIR/$directory"
		mkdir "$DEST_DIR/$directory"
	fi;
done

### copy all V2 files into given location (ommiting config)
## if updating, don't dare to overwrite configs :D
if [ ! -f "$DEST_DIR/config/db.conf.php" ]; then
	echo "Config file $DEST_DIR/config/db.conf.php doesn't exist, copying it, remember to set DB params."
	cp config/db.conf.php $DEST_DIR/config/
fi;
if [ ! -f "$DEST_DIR/config/mailing_conf.php" ]; then
	echo "Config file $DEST_DIR/config/mailing_conf.php doesn't exist, copying it, remember to set DB params."
	cp config/mailing_conf.php $DEST_DIR/config/
fi;
if [ ! -f "$DEST_DIR/V2.INSTALL.IGNORE" ]; then	
	touch $DEST_DIR/V2.INSTALL.IGNORE
	echo "templates_c" >>  $DEST_DIR/V2.INSTALL.IGNORE
fi;

for file in index.php V2.class.php mod_abstract.class.php .htaccess modules/* doc/* lib/* js/* pub/* entities/* translations/* img/* templates/* css/* etc/*; do
  	IGNORE=NO
	for IGNORE_FILE in `cat $DEST_DIR/V2.INSTALL.IGNORE`; do
		if [[ "$file" == "$IGNORE_FILE" && -f "$DEST_DIR/$IGNORE_FILE" ]]; then
			IGNORE=yes
		fi;
	done;
	if [ "$IGNORE" == "yes" ]; then
		echo "Ignoring $DEST_DIR/$file"
	else
		cp -rfv $file "$DEST_DIR/$file"
	fi;	
done;