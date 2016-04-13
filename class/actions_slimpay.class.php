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

/**
 * \file class/actions_slimpay.class.php
 * \ingroup slimpay
 * \brief This file is an example hook overload class file
 * Put some comments here
 */

/**
 * Class ActionsSlimpay
 */
class ActionsSlimpay
{
	/**
	 *
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array ();

	/**
	 *
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 *
	 * @var array Errors
	 */
	public $errors = array ();

	/**
	 * Constructor
	 */
	public function __construct(&$db) {
		$this->db=$db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param array() $parameters Hook metadatas (context, etc...)
	 * @param CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string &$action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return int < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager) {
		global $conf,$langs, $db;

		if (in_array('ordercard', explode(':', $parameters['context'])) && $conf->global->SLIMPAY_ONEVENT == 'SLIMPAY_ONINVOICECREATION') {
			$confirm = GETPOST('confirm');
			if ($action == 'confirm_validate' && $confirm == 'yes') {
				if (empty($object->mode_reglement_id)) {
					setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('PaymentMode')), 'errors');
					return -1;
				} else {
					$slimpay = new Slimpay($this->db);
					$result=$slimpay->callUrl('SLIMPAY_URLBEFORE');
					if ($result<0) {
						setEventMessage($slimpay->error, 'errors');
					}else {

					}

				}
			}
		}

		return 0;
	}
}