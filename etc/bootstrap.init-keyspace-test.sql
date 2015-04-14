CREATE KEYSPACE 
	IF NOT EXISTS 
	aetest
	WITH REPLICATION = { 'class' : 'SimpleStrategy', 'replication_factor' : 1 };
