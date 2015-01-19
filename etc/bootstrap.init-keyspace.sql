CREATE KEYSPACE 
	IF NOT EXISTS 
	feideconnect
	WITH REPLICATION = { 'class' : 'SimpleStrategy', 'replication_factor' : 3 };
