<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once '../lib/vendor/autoload.php';

require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

use \HapiClient\Http;
use \HapiClient\Hal;

/**
 * \file class/slimpay.class.php
 * \ingroup slimpay
 * \brief This file is an example hook overload class file
 * Put some comments here
 */

/**
 * Class Slimpay
 */
class Slimpay
{

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 *
	 * @return int <0 if KO, or 1 if OK
	 */
	public function testSlimApyConnection() {
		global $conf;

		$authConnect = new Http\Auth\Oauth2BasicAuthentication('/oauth/token', $conf->global->SLIMPAY_USER, $conf->global->SLIMPAY_PASSWORD);

		$hapiClient = new Http\HapiClient($conf->global->SLIMPAY_URLAPI, '/', 'https://api.slimpay.net/alps/v1', $authConnect);

		// The Relations Namespace
		$relNs = 'https://api.slimpay.net/alps#';

		$rel = new Hal\CustomRel($relNs . 'get-creditors');
		$follow = new Http\Follow($rel, 'GET', [
				'reference' => $conf->global->SLIMPAY_CREDITORREF
		]);
		try {
			$res = $hapiClient->sendFollow($follow);
		} catch ( Exception $e ) {
			$this->errors[] = $e->getMessage();
			return - 1;
		}

		// The Resource's state
		$state = $res->getState();

		if (is_array($state) && array_key_exists('reference', $state) && ! empty($state['reference'])) {
			return 1;
		}
	}

	/**
	 *
	 * @return int <0 if KO, or 1 if OK
	 */
	public function CreateOrder(Facture $invoice) {
		global $conf;

		$invoice->fetch_thirdparty($invoice->socid);

		$authConnect = new Http\Auth\Oauth2BasicAuthentication('/oauth/token', $conf->global->SLIMPAY_USER, $conf->global->SLIMPAY_PASSWORD);

		$hapiClient = new Http\HapiClient($conf->global->SLIMPAY_URLAPI, '/', 'https://api.slimpay.net/alps/v1', $authConnect);

		// The Relations Namespace
		$relNs = 'https://api.slimpay.net/alps#';

		$rel = new Hal\CustomRel($relNs . 'create-orders');
		$follow = new Http\Follow($rel, 'POST', null, new Http\JsonBody(array (
				'locale' => 'fr',
				'reference' => null,
				'started' => true,
				'creditor' => array (
						'reference' => $conf->global->SLIMPAY_CREDITORREF
				),
				'subscriber' => array (
						'reference' => $invoice->thirdparty->name
				),
				'items' => array (
						array (
								'type' => 'cardTransaction',
								'cardTransaction' => array (
										'amount' => $invoice->total_ttc,
										'executionDate' => null,
										'operation' => 'authorizationDebit',
										'reference' => null
								)
						)
				)
		)));
		$res = $hapiClient->sendFollow($follow);

		try {
			$res = $hapiClient->sendFollow($follow);
		} catch ( Exception $e ) {
			$this->errors[] = $e->getMessage();
			return - 1;
		}

		// The Resource's state
		$state = $res->getState();

		var_dump($state);
		if (is_array($state) && array_key_exists('reference', $state) && ! empty($state['reference'])) {

			$invocie_ref=$state['reference'];

			return 1;
		}
	}
}