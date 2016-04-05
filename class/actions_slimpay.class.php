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

		if (in_array('invoicecard', explode(':', $parameters['context'])))
		{
		 	// hbmqwlrpwz5idas
		 	//  f%Oj5AvCJlMYiD~8m6#UG~c~sZ3q
		  	?>
		  	<div class="inline-block divButAction"><a href="javascript:slimpay_card();" class="butAction">Slimpay par carte</a></div>
		  	<script type="text/javascript">
		  		function slimpay_card() {
		  			
		  			var url_auth = "https://api-sandbox.slimpay.net/oauth/token";
		  			var url_;
		  			
		  			
		  			var data = {
					    "locale": null,
					    "reference": null,
					    "started": true,
					    "creditor": {
					        "reference": "democreditor"
					    },
					    "subscriber": {
					        "reference": "subscriber01"
					    },
					    "items": [
					        {
					            "type": "cardTransaction",
					            "cardTransaction": {
					                "amount": "100",
					                "executionDate": null,
					                "operation": "authorizationDebit",
					                "reference": null
					            }
					        }
					    ]
					}
		  	</script>
		  	<?php
		  
		  
		}

		if (! $error)
		{
			/*$this->results = array('myreturn' => $myvalue);
			$this->resprints = '';*/
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}