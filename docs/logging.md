# Logging

Some notes about logging with examples.



Values:

* `debug` - will not be logged with default config.
* `info` - will be logged with default config.
* `warning` - Warning.
* `error` - errors that should not happen.



	Logger::info('Successfully parsed OAuth Authorization Request. Next up: resolve client.', array(
		'request' => $request->asArray(),
		'passive' => $passive
	));



