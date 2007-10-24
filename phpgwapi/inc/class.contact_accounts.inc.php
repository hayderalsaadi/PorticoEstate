<?php
	/**
	* Query statements for "account" table
	* @author Edgar Antonio Luna <eald@co.com.mx>
	* @copyright Copyright (C) 2003,2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
	* @package phpgwapi
	* @subpackage contacts
	* @version $Id: class.contact_accounts.inc.php,v 1.2 2004/12/30 06:47:30 skwashd Exp $
	*/

	/**
	* Use SQL criteria
	*/
	include_once(PHPGW_API_INC . '/class.sql_criteria.inc.php');
	/**
	* Use SQL entity
	*/
	include_once(PHPGW_API_INC . '/class.sql_entity.inc.php');

	/**
	* Query statements for "account" table
	*
	* @package phpgwapi
	* @subpackage contacts
	*/
	class contact_accounts extends sql_entity
	{
		var $map = array('account_id'		=> array('select'	=> '',
								 'criteria' 	=> '',
								 'insert'   	=> '',
								 'update'	=> '',
								 'delete'	=> '',
								 'sort'		=> '',
								 'field'	=> '',
								 'type'		=> 'integer'),
				 'account_person_id'	=> array('select'	=> '',
								 'criteria' 	=> '',
								 'insert'   	=> '',
								 'update'	=> '',
								 'delete'	=> '',
								 'sort'		=> '',
								 'field'	=> 'person_id',
								 'type'		=> 'integer'),
				 'person_only'		=> array('criteria'	=> ''),
				 'is_user'		=> array('select'	=> ''));
		
		function contact_accounts ($ali = '', $field = '', $criteria = 	'')
		{
			$this->_constructor('phpgw_accounts', 'contact_accounts');
			if($field)
			{
				$this->add_select($field);
			}
			if($criteria)
			{
				$this->add_criteria($criteria);
			}
			// $this->set_elinks('account_id', 'phpgwapi.contact_central','owner');
			$this->set_ilinks('account_person_id', 'phpgwapi.contact_person','person_id', PHPGW_SQL_LAZY_KEY);
		}

		function criteria_account_id($element)
		{
			$this->_add_criteria($this->index_criteria($element));
		}

		function criteria_person_only($element)
		{
			$this->_add_criteria($this->put_alias(sql_criteria::not_null($this->real_field('account_person_id'))));
		}

		function criteria_account_person_id($element)
		{
			$this->_add_criteria($this->index_criteria($element));
		}

		function select_is_user($element)
		{
			$this->add_field('is_user', 'count('.$this->real_field('account_id').')');
		}
	}
?>