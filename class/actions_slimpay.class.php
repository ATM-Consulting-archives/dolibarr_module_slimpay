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
		$this->db = $db;
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
		global $conf, $langs;

		if (in_array('ordercard', explode(':', $parameters['context'])) && $conf->global->SLIMPAY_ONEVENT == 'SLIMPAY_ONINVOICECREATION') {
			$confirm = GETPOST('confirm');
			if ($action == 'validate') {
				if (empty($object->mode_reglement_id)) {
					setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('PaymentMode')), 'errors');
					return - 1;
				}
			}
			if ($action == 'confirm_validate' && $confirm == 'yes') {
				if (empty($object->mode_reglement_id)) {
					setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('PaymentMode')), 'errors');
					return - 1;
				}
			}
		}

		return 0;
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param array() $parameters Hook metadatas (context, etc...)
	 * @param CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string &$action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return int < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
		global $conf, $langs;

		if (in_array('ordercard', explode(':', $parameters['context'])) && $conf->global->SLIMPAY_ONEVENT == 'SLIMPAY_ONINVOICECREATION') {
			$confirm = GETPOST('confirm');
			// After order validation Invoice is created
			if ($action == 'confirm_validate' && $confirm == 'yes' && !empty($object->mode_reglement_id)) {

				$out = '';

				dol_include_once('/slimpay/class/slimpay.class.php');
				$slimpay = new Slimpay($this->db);

				// Check invoice is created aka first part of process went well
				$object->fetchObjectLinked(null, '', null, 'facture');
				if (is_array($object->linkedObjects) && count($object->linkedObjects) > 0) {
					foreach ( $object->linkedObjects as $object_type => $object_linked ) {
						$invoicelinked = reset($object_linked);
						$paiemnturl = $invoicelinked->array_options['options_slimpay_urlval'];
						$invoice_id = $invoicelinked->id;
					}
				}

				// Call URL before
				dol_include_once('/slimpay/class/slimpay.class.php');
				$slimpay = new Slimpay($this->db);
				$result = $slimpay->callUrl('SLIMPAY_URLBEFORE');
				if ($result < 0) {
					setEventMessage($slimpay->error, 'errors');
				}

				//if ($object->mode_reglement_id==6) {
				// Display iFrame with Paiement
				$out = '<div id="paimentvalidation"><iframe src="#" width="100%" height="100%" allowfullscreen webkitallowfullscreen frameborder="0"></iframe></div>' . "\n";

				$out .= '<script type="text/javascript">' . "\n";
				$out .= '	function PaimentValidation_pop() {' . "\n";
				$out .= '		$(\'#paimentvalidation\').dialog({' . "\n";
				$out .= '			title: "Validation"' . "\n";
				$out .= '			,width:\'80%\'' . "\n";
				$out .= '			,height:700' . "\n";
				$out .= '			,modal:true' . "\n";
				$out .= '			,resizable: false' . "\n";
				$out .= '			,close:function() {' . "\n";
				$out .= '				$(\'#paimentvalidation iframe\').attr(\'src\', \'#\');' . "\n";
				$out .= '				var url = \'' . dol_buildpath('/slimpay/slimpay/ajax/valid_payment.php', 2) . '\';' . "\n";
				$out .= '				$.get( url,' . "\n";
				$out .= '					{' . "\n";
				$out .= '						invoice_id: \'' . $invoice_id . '\'' . "\n";
				$out .= '					})' . "\n";
				$out .= '					.done(function( data ) {' . "\n";
				$out .= '					if (data!=1) {' . "\n";
				$out .= '						alert("Error "+data);' . "\n";
				$out .= '					}else {document.location.href=\''.$_SERVER['_SELF'].'?id='.$object->id.'\';}' . "\n";
				$out .= '					})' . "\n";
				$out .= '					.fail(function( data ) {' . "\n";
				$out .= '					  alert( "Error ");' . "\n";
				$out .= '					});' . "\n";
				$out .= '				}' . "\n";
				$out .= '			});' . "\n";
				$out .= '		$(\'#paimentvalidation iframe\').attr(\'src\',\'' . $paiemnturl . '\');' . "\n";
				$out .= '	}' . "\n";
				$out .= '	' . "\n";
				$out .= '	$(document).ready(function() {' . "\n";
				$out .= '		PaimentValidation_pop();' . "\n";
				$out .= '	});' . "\n";
				$out .= '</script>' . "\n";
				/*} else {

				}*/

				print $out;
			}
		}

		if ($action == 'confirm_validate' && $confirm == 'yes') {
			if (empty($object->mode_reglement_id)) {
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('PaymentMode')), 'errors');
				return - 1;
			}
		}
	}
}