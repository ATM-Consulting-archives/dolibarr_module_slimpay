<?php

require_once 'lib/vendor/autoload.php';

require_once ('config.php');

use \HapiClient\Http;
use \HapiClient\Hal;

// hbmqwlrpwz5idas
		 	//  f%Oj5AvCJlMYiD~8m6#UG~c~sZ3q

// The HAPI Client
$hapiClient = new Http\HapiClient(
    $conf->global->SLIMPAY_URLAPI,
    '/',
    'https://api.slimpay.net/alps/v1',
    new Http\Auth\Oauth2BasicAuthentication(
        '/oauth/token',
        $conf->global->SLIMPAY_USER,
        $conf->global->SLIMPAY_PASSWORD
    )
);

//

// The Relations Namespace
$relNs = 'https://api.slimpay.net/alps#';



$rel = new Hal\CustomRel($relNs . 'get-creditors');
$follow = new Http\Follow($rel, 'GET', [
    'reference' => $conf->global->SLIMPAY_CREDITORREF
]);
$res = $hapiClient->sendFollow($follow);

var_dump($res);

// The Resource's state
$state = $res->getState();

var_dump($state);




// Follow create-orders
$rel = new Hal\CustomRel($relNs . 'create-orders');
$follow = new Http\Follow($rel, 'POST', null, new Http\JsonBody(array(
    'locale' => 'fr',
    'reference' => null,
    'started' => true,
    'creditor' => array(
        'reference' => $conf->global->SLIMPAY_CREDITORREF
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


print 'create-orders<BR>';
var_dump($res, $state);
/*
// Follow get-creditor
$rel = new Hal\CustomRel($relNs . 'get-creditor');
$follow = new Http\Follow($rel, 'GET');
$res = $hapiClient->sendFollow($follow, $res);

// The Resource's state
$state = $res->getState();
print 'get-creditor<BR>';
var_dump($res, $state);


// Follow get-subscriber
$rel = new Hal\CustomRel($relNs . 'get-subscriber');
$follow = new Http\Follow($rel, 'GET');
$res = $hapiClient->sendFollow($follow, $res);

// The Resource's state
$state = $res->getState();
print 'get-subscriber<BR>';
var_dump($res, $state);*/
