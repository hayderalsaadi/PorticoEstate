<?php
	/**
	* phpGroupWare - DEMO: A best practice demonstration module.
	*
	* @author Sigurd Nes <sigurdne@online.no>
	* @author Dave Hall <skwashd@phpgroupware.org>
	* @copyright Copyright (C) 2003-2007 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
	* @package demo
	* @subpackage demo
 	* @version $Id: class.bodemo.inc.php,v 1.8 2007/01/24 12:53:01 sigurdne Exp $
	*/

	/*
	   This program is free software: you can redistribute it and/or modify
	   it under the terms of the GNU General Public License as published by
	   the Free Software Foundation, either version 3 of the License, or
	   (at your option) any later version.

	   This program is distributed in the hope that it will be useful,
	   but WITHOUT ANY WARRANTY; without even the implied warranty of
	   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	   GNU General Public License for more details.

	   You should have received a copy of the GNU General Public License
	   along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

	/**
	 * application logic
	 * @package demo
	 */

	class demo_bodemo
	{
		public $start;
		public $query;
		public $filter;
		public $sort;
		public $order;
		public $cat_id;
		public $allrows;
		private $acl_location;
		private $currentapp;
		private $so;
		private $custom;
		private $use_session;
		private $dateformat;
		private $datetimeformat;

		public function __construct($session = false)
		{
			$this->acl_location 	= '.demo_location';
			$this->currentapp	= $GLOBALS['phpgw_info']['flags']['currentapp'];
			$this->so 			= CreateObject('demo.sodemo', $this->acl_location);
			$this->custom 		= createObject('phpgwapi.custom_fields');

			if ($session)
			{
				$this->read_sessiondata();
				$this->use_session = true;
			}

			$this->start	= phpgw::get_var('start');
			$this->query	= phpgw::get_var('query');
			$this->sort		= phpgw::get_var('sort');
			$this->order	= phpgw::get_var('order');
			$this->filter	= phpgw::get_var('filter');
			$this->cat_id	= phpgw::get_var('cat_id', 'int');
			$this->allrows	= phpgw::get_var('allrows', 'bool');

			$this->dateformat 		= phpgwapi_db::date_format();
			$this->datetimeformat 	= phpgwapi_db::datetime_format();
		}

		public function save_sessiondata($data)
		{
			if ($this->use_session)
			{
				$GLOBALS['phpgw']->session->appsession('session_data','demo_app',$data);
			}
		}

		private function read_sessiondata()
		{
			$data = $GLOBALS['phpgw']->session->appsession('session_data','demo_app');

			$this->start	= isset($data['start']) ? $data['start'] : '';
			$this->query	= isset($data['query']) ? $data['query'] : '';
			$this->filter	= isset($data['filter']) ? $data['filter'] : '';
			$this->sort		= isset($data['sort']) ? $data['sort'] : '';
			$this->order	= isset($data['order']) ? $data['order'] : '';
			$this->cat_id	= isset($data['cat_id']) ? $data['cat_id'] : '';
		}

		public static function check_perms($rights, $required)
		{
			return ($rights & $required);
		}

		public function read()
		{
			$lookup = array
			(
				'start'		=> $this->start,
				'query'		=> $this->query,
				'sort'		=> $this->sort,
				'order'		=> $this->order,
				'cat_id'	=> $this->cat_id,
				'allrows'	=> $this->allrows,
				'filter'	=> $this->filter,
			);

			$this->total_records = $this->so->total_records;
			return $this->so->read($lookup);
		}

		/**
		* Get list of records with dynamically allocated coulmns
		*
		* @return array Array with records.
		*/
		public function read2()
		{
			$custom_attributes = $this->custom->get_attribs($this->currentapp, $this->acl_location, 0, '', 'ASC', 'attrib_sort', true, true);
			$lookup = array
			(
				'start'		=> $this->start,
				'query'		=> $this->query,
				'sort'		=> $this->sort,
				'order'		=> $this->order,
				'cat_id'	=> $this->cat_id,
				'allrows'	=> $this->allrows,
				'filter'	=> $this->filter,
				'custom_attributes' => $custom_attributes
			);
			
			$demo_info = $this->so->read2($lookup);
			$this->total_records = $this->so->total_records;
			$this->uicols	= $this->so->uicols;
			return $demo_info;
		}

		public function read_single($id = 0)
		{
			$values['attributes'] = $this->custom->get_attribs($this->currentapp, $this->acl_location, 0, '', 'ASC', 'attrib_sort', true, true);
			
			if($id)
			{
				$values = $this->so->read_single($id, $values);
			}
			
			$values = $this->custom->prepare_attributes($values, 'demo', $this->acl_location);
			
			$dateformat = $GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'];
			if(isset($values['entry_date']) && $values['entry_date'])
			{
				$values['entry_date']	= $GLOBALS['phpgw']->common->show_date($values['entry_date'],$dateformat);
			}

			return $values;
		}

		public function save($values, $values_attribute = array())
		{
			if(is_array($values_attribute))
			{
				foreach ( $values_attribute as &$attrib )
				{
					if ( $attrib['datatype'] == 'CH' && $attrib['value'] )
					{
						$attrib['value'] = serialize($attrib[$i]['value'] );
					}
					if ( $attrib['datatype'] == 'R' && $attrib['value'] )
					{
						$attrib['value'] = $attrib['value'][0];
					}

					if ( $attrib['datatype'] == 'N' && $attrib['value'] )
					{
						$attrib['value'] = str_replace(',', '.', $attrib['value']);
					}
	
					if ( $attrib['datatype'] == 'D' && $attrib['value'] )
					{
						$values_attribute[$i]['value'] = date($this->dateformat, $this->date_to_timestamp($attrib['value']));
					}
				}
			}


			if (isset($values['demo_id']) && $values['demo_id'])
			{
				$receipt = $this->so->edit($values,$values_attribute);
			}
			else
			{
				$receipt = $this->so->add($values,$values_attribute);
			}

			$custom_functions = $this->custom->read_custom_function(
				array
				(
					'appname'	=> $this->currentapp,
					'location'	=> $this->acl_location,
					'allrows'	=> true
				));

			if ( isset($custom_functions) && is_array($custom_functions) )
			{
				foreach($custom_functions as $entry)
				{
					if ( is_file(PHPGW_APP_INC . "/custom/{$entry['file_name']}") 
						&& $entry['active'] )
					{
						include_once(PHPGW_APP_INC . "/custom/{$entry['file_name']}");
					}
				}
			}

			return $receipt;
		}

		public function delete($id)
		{
			$this->so->delete($id);
		}

		private function select_category_list($format='',$selected='')
		{
			switch($format)
			{
				case 'select':
					$GLOBALS['phpgw']->xslttpl->add_file(array('cat_select'));
					break;
				case 'filter':
					$GLOBALS['phpgw']->xslttpl->add_file(array('cat_filter'));
					break;
			}

			$categories = $this->so->select_category_list();

			$category_list = array();
			if ( is_array($categories) )
			{
				foreach ( $categories as $category )
				{
					if ( $category['id'] == $selected )
					{
						$category_list[] = array
						(
							'cat_id'	=> $category['id'],
							'name'		=> $category['name'],
							'selected'	=> 'selected'
						);
					}
					else
					{
						$category_list[] = array
						(
							'cat_id'	=> $category['id'],
							'name'		=> $category['name'],
						);
					}
				}
			}
			return $category_list;
		}

		/**
		* Preserve attribute values from post in case of an error
		*
		* @param array $values_attribute attribute definition and values from posting
		* @param array $values value set with 
		* @return array Array with attribute definition and values
		*/
		private function preserve_attribute_values($values='',$values_attribute='')
		{
			return $this->custom->preserve_attribute_values($values,$values_attribute);
		}

		public function get_acl_location()
		{
			return $this->acl_location;
		}



	}
