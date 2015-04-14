#!/bin/bash

HOST="127.0.0.1"
INIT="etc/bootstrap.init-keyspace.sql"
KEYSPACE=feideconnect


while [[ $# > 1 ]]
do
key="$1"

case $key in
    -h|--host)
    HOST="$2"
    shift
    ;;


    -t|--type)
    INIT="etc/bootstrap.init-keyspace-$2.sql"
    shift
    ;;


    -k|--keyspace)
    KEYSPACE="$2"
    shift
    ;;

    *)
            # unknown option
    ;;
esac
shift
done


echo $INITKEY;



echo ""
echo "=======  Bootstrapping Cassandra ======";
echo HOST     = "${HOST}"
echo INIT     = "${INIT}"
echo KEYSPACE     = "${KEYSPACE}"
echo ""

echo " -------------------------"
echo "   Usage:"
echo ""
echo "  bin/bootstrap.sh -h 158.38.0.1 -k feideconnect"
echo "  bin/bootstrap.sh -h 127.0.0.1 -t ci"
echo "  bin/bootstrap.sh -h 127.0.0.2 -t test -k aetest"
echo " -------------------------"
echo ""
echo ""

echo "   We are now about to run the following commands, are you ok with that?"
echo ""
echo "cqlsh $HOST -f $INIT"
echo "cqlsh $HOST -k $KEYSPACE -f etc/bootstrap.sql"
echo ""

echo "   [ Hit enter to continue ]"
read confirm

cqlsh $HOST -f $INIT
cqlsh $HOST -k $KEYSPACE -f etc/bootstrap.sql

# cqlsh 158.38.213.74 -f etc/bootstrap.init-keyspace.sql
# cqlsh 158.38.213.74 -f etc/bootstrap.sql
# cqlsh 158.38.213.74 -f etc/bootstrap.2_1.sql

