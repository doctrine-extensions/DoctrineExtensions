#!/bin/sh

cd ../
ROOT=$(pwd)
VENDOR="$ROOT/vendor"

if [ ! -d "$VENDOR/doctrine-orm/lib" ]; then
	cd $ROOT
	git submodule init
	git submodule update
else
    # Doctrine ORM
    cd $VENDOR/doctrine-orm && git pull
    
    # Doctrine DBAL
    cd $VENDOR/doctrine-dbal && git pull
    
    # Doctrine common
    cd $VENDOR/doctrine-common && git pull
    
    # Doctrine MongoDB
    cd $VENDOR/doctrine-mongodb-odm && git pull
    cd $VENDOR/doctrine-mongodb && git pull
fi
