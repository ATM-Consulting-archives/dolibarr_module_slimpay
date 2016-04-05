<?php

require_once 'lib/vendor/autoload.php';

use \HapiClient\Http;
use \HapiClient\Hal;

// hbmqwlrpwz5idas
		 	//  f%Oj5AvCJlMYiD~8m6#UG~c~sZ3q

// The HAPI Client
$hapiClient = new Http\HapiClient(
    'https://api-sandbox.slimpay.net',
    '/',
    'https://api.slimpay.net/alps/v1',
    new Http\Auth\Oauth2BasicAuthentication(
        '/oauth/token',
        'hbmqwlrpwz5idas',
        'f%Oj5AvCJlMYiD~8m6#UG~c~sZ3q'
    )
);

// The Relations Namespace
$relNs = 'https://api.slimpay.net/alps#';

// Follow create-orders
$rel = new Hal\CustomRel($relNs . 'create-orders');
$follow = new Http\Follow($rel, 'POST', null, new Http\JsonBody(array(
    'locale' => null,
    'reference' => null,
    'started' => true,
    'creditor' => array(
        'reference' => 'hbmqwlrpwz5idas'
    ),
    'subscriber' => array(
        'reference' => 'subscriber012222'
    ),
    'items' => array(
        array(
            'type' => 'cardTransaction',
            'cardTransaction' => array(
                'amount' => '100',
                'executionDate' => null,
                'operation' => 'authorizationDebit',
                'reference' => null
            )
        )
    )
)
));
$res = $hapiClient->sendFollow($follow);

// The Resource's state
$state = $res->getState();

var_dump($res, $state);
