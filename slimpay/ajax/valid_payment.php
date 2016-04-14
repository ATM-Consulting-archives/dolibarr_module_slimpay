<?php
/* <slimpay>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file slimpay/slimpay/ajax/valid_payment.php
 * \brief File to load user combobox
 */
if (! defined('NOTOKENRENEWAL'))
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))
	define('NOREQUIREMENU', '1');
	// if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))
	define('NOREQUIREAJAX', '1');
	// if (! defined('NOREQUIRESOC')) define('NOREQUIRESOC','1');
	// if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');

$res = @include ("../../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");


dol_include_once('/slimpay/class/slimpay.class.php');
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

$langs->load('slimpay@slimpay');

$invoice_id = GETPOST('invoice_id', 'int');

/*
 * View
 */

top_httphead();

// print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

if (empty($invoice_id)) {
	print $langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("InvoiceId"));
	exit();
}

$error = 0;
$error_str = '';

//$db->begin();

$slimpay = new Slimpay($db);
$invoice = new Facture($db);

$result = $invoice->fetch($invoice_id);
if ($result < 0) {
	$error_str .= $invoice->error;
	$error ++;
}

if (empty($error)) {
	$result = $slimpay->checkPaymentState($invoice->array_options['options_slimpay_refext']);
	if ($result < 0) {
		$error_str .= explode("/n", $slimpay->errors);
		$error ++;
	}
}

if (empty($error)) {
	if ($slimpay->state_invoice = 'closed.completed') {
		$result = $slimpay->setAsPaidInvoice($invoice,$user);
		if ($result < 0) {
			$error_str .= explode("/n", $slimpay->errors);
			$error ++;
		}
	}
}

// If error during payment process
// Delete invoice
if (! empty($error) && ! empty($conf->global->SLIMPAY_DELETEINVONFAILURE)) {
	$result = $invoice->delete($invoice->id);
	if ($result < 0) {
		$error_str .= $invoice->errors;
		$error ++;
	}
}

// Commit or rollback
if ($error) {
	//$db->rollback();
	print $error_str;
} else {
	//$db->commit();

	// Call URL before
	dol_include_once('/slimpay/class/slimpay.class.php');
	$result = $slimpay->callUrl('SLIMPAY_URLAFTER');
	if ($result < 0) {
		setEventMessage($slimpay->error, 'errors');
	}

	print 1;
}

