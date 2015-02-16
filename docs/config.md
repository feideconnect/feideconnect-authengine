# Config

How to use the Config classes.


Reads config from `etc/config.json`:

## getValue()

	Config::getValue('logging.filename', '/var/log/feideconnect-authengine.log');

getValue gets key from config object. Dot notation for diving into the object. Second argument is fallback default return value.

## readJSONfile(file) 

Reads `file` from etc/ directory and returns as JSON.