#! /bin/bash

cqlsh 158.38.213.74 -f etc/bootstrap.init-keyspace.sql
cqlsh 158.38.213.74 -f etc/bootstrap.sql
cqlsh 158.38.213.74 -f etc/bootstrap.2_1.sql

