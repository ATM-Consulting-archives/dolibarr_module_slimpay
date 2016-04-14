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
 * \file admin/slimpay.php
 * \ingroup slimpay
 * \brief This file is an example module setup page
 * Put some comments here
 */
// Dolibarr environment
$res = @include ("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include ("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";
require_once '../lib/slimpay.lib.php';
require_once '../class/slimpay.class.php';

// Translations
$langs->load("slimpay@slimpay");
$langs->load("admin");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if ($action == 'setvar') {
	foreach ( $_POST as $key => $val ) {

		if (substr($key, 0, 8) == 'SLIMPAY_') {
			$res = dolibarr_set_const($db, $key, $val, 'chaine', 0, '', 0);
			if (! $res > 0)
				$error ++;

			if ($error) {
				setEventMessage($langs->trans("Error"), 'errors');
			}
		}
	}

	if (empty($error)) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	}
} elseif ($action == "testcredentials") {
	$api = new Slimpay($db);
	$result = $api->testSlimApyConnection();
	if ($result < 0) {
		setEventMessages(null, $api->errors, 'errors');
	} else {
		setEventMessages(null, $langs->trans('SlimPayConnectOK'), 'mesgs');
	}
}

if (!empty($conf->global->SLIMPAY_URLBEFORE) || !empty($conf->global->SLIMPAY_URLAFTER)) {
	if (!function_exists('curl_init')) {
		setEventMessage($langs->transnoentities('SlimPayInstallCURLphp'),'errors');
	}
}

/*
 * View
 */
$page_name = "SlimpaySetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = slimpayAdminPrepareHead();
dol_fiche_head($head, 'settings', $langs->trans("Module104011Name"), 0, "slimpay@slimpay");

// Setup page goes here
$form = new Form($db);
$var = false;
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvar">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="300px">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Value") . '</td>' . "\n";

// Example with a yes / no select
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td>' . $langs->trans("SLIMPAY_USER") . '</td>';
print '<td>';
print '<input type="text" name="SLIMPAY_USER" value="' . $conf->global->SLIMPAY_USER . '" class="flat"/>';
print '</td>';
print '</tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td width="300px">' . $langs->trans("SLIMPAY_PASSWORD") . '</td>';
print '<td>';
print '<input type="text" size="30" name="SLIMPAY_PASSWORD" value="' . $conf->global->SLIMPAY_PASSWORD . '" class="flat"/>';
print '</td>';
print '</tr>';

if (! empty($conf->global->SLIMPAY_PASSWORD) && ! empty($conf->global->SLIMPAY_USER)) {
	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td colspan="2" align="center">';
	print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?action=testcredentials">' . $langs->trans("SlimPayTestConnection") . '</a>';
	print '</td>';
	print '</tr>';
}

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td width="300px">' . $langs->trans("SLIMPAY_CREDITORREF") . '</td>';
print '<td>';
print '<input type="text" name="SLIMPAY_CREDITORREF" value="' . $conf->global->SLIMPAY_CREDITORREF . '" class="flat"/>';
print '</td>';
print '</tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td width="300px">' . $langs->trans("SLIMPAY_URLAPI") . '</td>';
print '<td>';
print '<input type="text" size="30" name="SLIMPAY_URLAPI" value="' . $conf->global->SLIMPAY_URLAPI . '" class="flat"/>';
print '</td>';
print '</tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td width="300px">' . $langs->trans("SLIMPAY_INVOICEPAYEDONSUCCES") . '</td>';
print '<td>';
print ajax_constantonoff('SLIMPAY_INVOICEPAYEDONSUCCES');
print '</td>';
print '</tr>';

if (! empty($conf->banque->enabled)) {
	$var = ! $var;
	print '<tr ' . $bc[$var] . '>';
	print '<td width="300px">' . $langs->trans("SLIMPAY_DEFAULTBANK") . '</td>';
	print '<td>';
	$form->select_comptes($conf->global->SLIMPAY_DEFAULTBANK, 'SLIMPAY_DEFAULTBANK', 0, '', 2);
	print '</td>';
	print '</tr>';
}

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td width="300px">' . $langs->trans("SLIMPAY_EVENTCREATE") . '</td>';
print '<td>';

$arrayval[''] = '';
if ($conf->grapefruit->enabled && ! empty($conf->global->GRAPEFRUIT_ORDER_CREATE_BILL_ON_VALIDATE)) {
	$arrayval['SLIMPAY_ONINVOICECREATION'] = $langs->trans('SLIMPAY_ONINVOICECREATION');
}
$arrayval['SLIMPAY_ADDBUTTONONINVOICE'] = $langs->trans('SLIMPAY_ADDBUTTONONINVOICE');

print $form->selectarray('SLIMPAY_ONEVENT', $arrayval, $conf->global->SLIMPAY_ONEVENT);

print '</select>';
print '</td>';
print '</tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td width="300px">' . $langs->trans("SLIMPAY_URLBEFORE") . '</td>';
print '<td>';
print '<input type="text" size="30" name="SLIMPAY_URLBEFORE" value="' . $conf->global->SLIMPAY_URLBEFORE . '" class="flat"/>';
print '</td>';
print '</tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td width="300px">' . $langs->trans("SLIMPAY_URLAFTER") . '</td>';
print '<td>';
print '<input type="text" size="30" name="SLIMPAY_URLAFTER" value="' . $conf->global->SLIMPAY_URLAFTER . '" class="flat"/>';
print '</td>';
print '</tr>';

$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td colspan="2" align="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</td>';
print '</tr>';

print '</table>';

print '</form>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="300px">' . $langs->trans("Parameters") . '</td>' . "\n";
print '<td>' . $langs->trans("Value") . '</td>' . "\n";
$var = ! $var;
print '<tr ' . $bc[$var] . '>';
print '<td width="300px">' . $langs->trans("SLIMPAY_DELETEINVONFAILURE") . '</td>';
print '<td>';
print ajax_constantonoff('SLIMPAY_DELETEINVONFAILURE');
print '</td>';
print '</tr>';

print '</table>';

llxFooter();

$db->close();