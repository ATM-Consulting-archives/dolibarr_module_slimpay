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
 * \file    class/actions_slimpay.class.php
 * \ingroup slimpay
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsSlimpay
 */
class ActionsSlimpay
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter
		//$myvalue = ''; // A result value

		if (in_array('invoicecard', explode(':', $parameters['context'])) && $conf->global->SLIMPAY_ONEVENT=='SLIMPAY_ADDBUTTONONINVOICE')
		{
		 	//TODO invoice card replace button


		}

		/*if (in_array('ordercard', explode(':', $parameters['context'])) && $conf->global->SLIMPAY_ONEVENT=='SLIMPAY_REPLACEBUTTONORDERVAL')
		{
			$out_js = '<script type="text/javascript">'."\n";
			$out_js .= '	$(document).ready(function() {'."\n";
			$out_js .= '		// Collect options'."\n";
			$out_js .= '		var bt = $(\'a.butAction[href*="action=validate"]\');'."\n";
			$out_js .= '		if (bt.length == 0)'."\n";
			$out_js .= '		{'."\n";
			$out_js .= '			bt.remove();'."\n";
			$out_js .= '			var btOrder = $(\'<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/slimpay/slimpay.php',2).'action=validateorder='.$object->id.'">'.$langs->transnoentitiesnoconv("SlimPayPayWith").'</a></div>\');'."\n";
			$out_js .= '			$(\'div.tabsAction\').append(btOrder);'."\n";
			$out_js .= '		}'."\n";
			$out_js .= '	});'."\n";
			$out_js .= '</script>';

		}*/

		if (! $error)
		{
			print $out_js;
			// or return 1 to replace standard code
			return 0;
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}