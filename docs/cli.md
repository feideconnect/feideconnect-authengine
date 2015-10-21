# Command line clients


Examples of usage.

	bin/feideconnect.php


Get information about users

	bin/feideconnect.php users
	bin/feideconnect.php user feide:andreas@uninett.no
	bin/feideconnect.php user uuid:1234-1234-2345-4567




## Listing information about an organization


Get info about one organization by ID:

	f org fc:org:uninett.no

## Updating information about organizations

Setting a logo

	bin/feideconnect.php org fc:org:uio.no setlogo var/uio.png

Updating uiinfo from `var/orginfo.json`

	bin/feideconnect.php orgs update	


Updating the service set for an organization:

	f org fc:org:uninett.no service avtale 1

These are the service tags that have been used so far: `["auth","avtale","pilot"]`

