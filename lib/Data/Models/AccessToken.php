<?php

namespace FeideConnect\Data\Models;

class AccessToken extends \FeideConnect\Data\Model {


	protected static $_properties = array(
		"access_token", "clientid", "userid", "issued", 
		"scope", "token_type", "validuntil"
	);


}