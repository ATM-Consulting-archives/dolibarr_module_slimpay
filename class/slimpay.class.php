<?php
/* <SlimPay>
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
dol_include_once('/slimpay/lib/vendor/autoload.php');

require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

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
class Slimpay extends CommonObject
{
	public $urlValidation = '';
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	function __construct(&$db) {
		$this->db = $db;
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
	 * createOrderFromInvoice
	 *
	 * @param Facture $invoice Invoice Source
	 * @param User $user User
	 * @param boolean $setAsPayed set as paied directly
	 * @return int <0 if KO, or 1 if OK
	 */
	public function createOrderFromInvoice(Facture $invoice, User $user, $setAsPayed = false) {
		global $conf, $mysoc;

		$error = 0;

		$result = $invoice->fetch_thirdparty($invoice->socid);
		if ($result < 0) {
			$error ++;
			$this->errors[] = get_class($this) . '::' . __METHOD__ . ' Cannot Fetch Thridparty From invocie';
		}

		$authConnect = new Http\Auth\Oauth2BasicAuthentication('/oauth/token', $conf->global->SLIMPAY_USER, $conf->global->SLIMPAY_PASSWORD);

		$hapiClient = new Http\HapiClient($conf->global->SLIMPAY_URLAPI, '/', 'https://api.slimpay.net/alps/v1', $authConnect);

		// Paiement par carte bleue
		$items = array ();
		if ($invoice->mode_reglement_id == 6) {
			$items = array (
					array (
							'type' => cardTransaction,
							'cardTransaction' => array (
									'amount' => $invoice->total_ttc,
									'executionDate' => null,
									'operation' => 'authorizationDebit',
									'reference' => null
							)
					)
			);
		} elseif ($invoice->mode_reglement_id == 3) {
			// Payment by SEPA MANDAT
			$items = array (
					array (
							'autoGenReference' => true,
							'type' => 'signMandate',
							'mandate' => array (
									'createSequenceType' => "OOFF",
									'standard' => 'SEPA',
									'signatory' => array (
											'companyName' => $invoice->thirdparty->name,
											'email' => $invoice->thirdparty->email,
											'familyName' => $invoice->thirdparty->name,
											'givenName' => $invoice->thirdparty->name,
											'honorificPrefix' => null,
											'organizationName' => null,
											'telephone' => $invoice->thirdparty->phone,
											'bankAccount' => array (
													'bic' => null,
													'iban' => null
											),
											'billingAddress' => array (
													'city' => $invoice->thirdparty->town,
													'country' => $invoice->thirdparty->country_code,
													'postalCode' => $invoice->thirdparty->zip,
													'street1' => $invoice->thirdparty->address,
													'street2' => null
											)
									)
							)
					),
					array (
							'type' => 'directDebit',
							'directDebit' => array (
									'amount' => $invoice->total_ttc,
									'executionDate' => null,
									'label' => $mysoc->name . ' - ' . $invoice->ref,
									'paymentReference' => $invoice->ref
							)
					)
			);
		}

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
				'items' => $items
		)));

		dol_syslog(get_class($this) . '::' . __METHOD__ . '$follow=' . var_export($follow, true), LOG_DEBUG);

		$res = $hapiClient->sendFollow($follow);

		try {
			$res = $hapiClient->sendFollow($follow);
		} catch ( Exception $e ) {
			$this->errors[] = $e->getMessage();
			$error ++;
		}

		// The Resource's state
		$state = $res->getState();

		dol_syslog(get_class($this) . '::' . __METHOD__ . '$res=' . var_export($res, true) . ' $state=' . var_export($state, true), LOG_DEBUG);

		if (is_array($state) && array_key_exists('reference', $state) && ! empty($state['reference'])) {

			// Valid Invoice
			$result = $invoice->setBankAccount($conf->global->SLIMPAY_DEFAULTBANK);
			if ($result < 0) {
				$this->errors[] = $this->error;
				$error ++;
			}

			$result = $invoice->validate($user,'',$conf->global->GRAPEFRUIT_SHIPPING_CREATE_FROM_ORDER_WHERE_BILL_PAID_WAREHOUSE);
			if ($result < 0) {
				$this->errors = array_merge($this->errors, $invoice->errors);
				$error ++;
			}

			$invoice->array_options['options_slimpay_refext'] = $state['reference'];
			$invoice->array_options['options_slimpay_urlval'] = $res->getLink('https://api.slimpay.net/alps#user-approval')->getHref();
			if (empty($error)) {
				$result = $invoice->insertExtraFields($user, true);
				if ($result < 0) {
					$this->errors = array_merge($this->errors, $invoice->errors);
					$error ++;
				}
			}
		} else {
			$this->errors[] = get_class($this) . '::' . __METHOD__ . ' Problem with SlimPay';
			$error ++;
		}

		// Pass invoice payed if no error
		if (empty($error) && ! empty($conf->global->SLIMPAY_INVOICEPAYEDONSUCCES) && ! empty($conf->banque->enabled) && $setAsPayed) {
			$result = $this->setAsPaidInvoice($invoice, $user, $invoice->array_options['options_slimpay_refext']);
			if ($result < 0) {
				$error ++;
			}
		}

		// If error during payment process
		// Delete invoice
		if (! empty($error) && ! empty($conf->global->SLIMPAY_DELETEINVONFAILURE)) {
			$result = $invoice->delete($invoice->id);
			if ($result < 0) {
				$this->errors[] = array_merge($this->errors, $invoice->errors);
				$error ++;
			}
		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1;
		}
	}

	/**
	 * callUrl
	 *
	 * @param string $requestType
	 */
	public function callUrl($requestType = '') {
		if (! empty($conf->global->$requestType)) {
			$curl_handle = curl_init();

			$options = array (
					CURLOPT_URL => $conf->global->$requestType,
					CURLOPT_RETURNTRANSFER => true
			);

			curl_setopt_array($curl_handle, $options);
			$buffer = curl_exec($curl_handle);
			$result = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
			curl_close($curl_handle);

			if ($result != 200) {
				$error ++;
				$this->error = $buffer;
				dol_syslog(get_class($this) . "::" . __METHOD__ . " ERROR " . $result . " script lauch url:" . $urltocall, LOG_ERR);
			}
		}
		if (empty($error)) {
			return 1;
		} else {
			return - 1;
		}
	}

	/**
	 *
	 * @param Facture $invoice
	 * @param User $user
	 * @param string $ref
	 * @return int <0 if KO, or 1 if OK
	 */
	public function setAsPaidInvoice(Facture $invoice, User $user, $ref = '') {
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
		$paiement = new Paiement($this->db);
		$paiement->datepaye = dol_now();
		$paiement->amounts = array (
				$invoice->id => $invoice->total_ttc
		); // Array with all payments dispatching
		$paiement->paiementid = $invoice->mode_reglement_id;
		$paiement->num_paiement = (empty($ref) ? $invoice->array_options['options_slimpay_refext'] : $ref);
		$paiement->note = null;

		if (! $error) {
			$paiement_id = $paiement->create($user, 1);
			if ($paiement_id < 0) {
				$this->errors[] = $paiement->error;
				$error ++;
			}
		}

		if (! $error) {
			$label = '(CustomerInvoicePayment)';
			$result = $paiement->addPaymentToBank($user, 'payment', $label, $invoice->fk_account, '', '');
			if ($result < 0) {
				$this->errors[] = $paiement->error;
				$error ++;
			}
		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1;
		}
	}

	/**
	 *
	 * @param string $invoice_ref
	 * @return int <0 if KO, or 1 if OK
	 */
	public function checkPaymentState($invoice_slimpayref = '') {
		global $conf;

		dol_syslog(get_class($this) . '::' . __METHOD__ . '$invoice_slimpayref=' . $invoice_slimpayref, LOG_DEBUG);

		$authConnect = new Http\Auth\Oauth2BasicAuthentication('/oauth/token', $conf->global->SLIMPAY_USER, $conf->global->SLIMPAY_PASSWORD);

		$hapiClient = new Http\HapiClient($conf->global->SLIMPAY_URLAPI, '/', 'https://api.slimpay.net/alps/v1', $authConnect);

		// The Relations Namespace
		$relNs = 'https://api.slimpay.net/alps#';

		// Follow get-orders
		$rel = new Hal\CustomRel($relNs . 'get-orders');
		$follow = new Http\Follow($rel, 'GET', [
				'creditorReference' => $conf->global->SLIMPAY_CREDITORREF,
				'reference' => $invoice_slimpayref
		]);
		try {
			$res = $hapiClient->sendFollow($follow);
		} catch ( Exception $e ) {
			$this->errors[] = $e->getMessage();
			$error ++;
		}

		// The Resource's state
		$state = $res->getState();


		dol_syslog(get_class($this) . '::' . __METHOD__ . '$state=' . var_export($state,true), LOG_DEBUG);

		$this->state_invoice = $state['state'];

		if (empty($error)) {
			return 1;
		} else {
			return - 1;
		}
	}
}