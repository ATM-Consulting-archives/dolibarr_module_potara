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
 * \file    class/actions_potara.class.php
 * \ingroup potara
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsPotara
 */
class ActionsPotara
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
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
	
		if (in_array('thirdpartycard', explode(':', $parameters['context'])) && $action === 'add')
		{
			
			if(GETPOST('createTupple')!='') return 0;
			
			global $langs, $potara_tupple_detected;
		  // do something only for the context 'somecontext'
			
			define('INC_FROM_DOLIBARR',true);
			dol_include_once('/potara/config.php');
			dol_include_once('/potara/class/potara.class.php');
			
			$error = 0;
			
			$pot = new TPotara;
			$Tab = $pot->searchTuple( $pot->getObjectKey(GETPOST('name'), GETPOST('zipcode')) );
			
			if(count($Tab) > 0) {
				$error++ ;
				$potara_tupple_detected = count($Tab);
				$errors = $langs->trans('PotentialTuppleDetected');
				
				foreach ($Tab as &$obj) {
					
					$societe = new Societe($db);
					$societe->id = $obj->rowid;
					$societe->name = $obj->nom;
					
					$errors.="<br /> ".$societe->getNomUrl(1);
				}
				
			}
			
			if (! $error)
			{
				return 0; // or return 1 to replace standard code
			}
			else
			{
				$action='create';
				
				$this->errors[] = $errors;
				return -1;
			}
			
		}

		
	}
	
	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		
		if (in_array('thirdpartycard', explode(':', $parameters['context'])) && $action === 'create')
		{
			global $potara_tupple_detected, $langs;
			
			if(!empty($potara_tupple_detected)) {
				
				echo '<input class="button" name="createTupple" value="'.$langs->trans('CreateThirdpartyBeyondTupple', $potara_tupple_detected).'" type="submit"> ';
				
				?>
				<script type="text/javascript">

				$('input[name=createTupple]').before($('input[name=createTupple]').closest('table')).wrap('<div align="center" />');
				</script>

				<?php 
				
			}
			
		}
		
	}
	
}