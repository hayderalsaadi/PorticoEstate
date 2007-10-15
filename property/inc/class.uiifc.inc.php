<?php
	/**
	* phpGroupWare - HRM: a  human resource competence management system.
	*
	* @author Sigurd Nes <sigurdne@online.no>
	* @copyright Copyright (C) 2003,2004,2005,2006,2007 Free Software Foundation, Inc. http://www.fsf.org/
	* This file is part of phpGroupWare.
	*
	* phpGroupWare is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* phpGroupWare is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with phpGroupWare; if not, write to the Free Software
	* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
	* @package property
	* @subpackage ifc
 	* @version $Id: class.uiifc.inc.php,v 1.2 2007/01/26 14:53:47 sigurdne Exp $
	*/

	/**
	 * Description
	 * @package hrm
	 */

	class uiifc
	{
		var $grants;
		var $start;
		var $query;
		var $sort;
		var $order;
		var $sub;
		var $currentapp;

		var $public_functions = array
		(
			'index'		=> True,
			'view'		=> True,
			'edit'		=> True,
			'import'	=> True,
			'delete'	=> True,
			'no_access'	=> true
		);

		function uiifc()
		{
			$GLOBALS['phpgw_info']['flags']['xslt_app'] = True;
			$this->currentapp		= $GLOBALS['phpgw_info']['flags']['currentapp'];
			$this->cats				= CreateObject('phpgwapi.categories');
			$this->nextmatchs		= CreateObject('phpgwapi.nextmatchs');
			$this->account			= $GLOBALS['phpgw_info']['user']['account_id'];
			$this->bo				= CreateObject($this->currentapp.'.boifc',true);
			$this->menu				= CreateObject($this->currentapp.'.menu');
			$this->menu->sub		='ifc';
			$this->acl 				= & $GLOBALS['phpgw']->acl;
			$this->acl_location 	= '.ifc';
			$this->acl_read 			= $this->acl->check($this->acl_location,PHPGW_ACL_READ);
			$this->acl_add 				= $this->acl->check($this->acl_location,PHPGW_ACL_ADD);
			$this->acl_edit 			= $this->acl->check($this->acl_location,PHPGW_ACL_EDIT);
			$this->acl_delete 			= $this->acl->check($this->acl_location,PHPGW_ACL_DELETE);

			$this->start			= $this->bo->start;
			$this->query			= $this->bo->query;
			$this->sort				= $this->bo->sort;
			$this->order			= $this->bo->order;
			$this->allrows			= $this->bo->allrows;
			$this->cat_id			= $this->bo->cat_id;
			$this->filter			= $this->bo->filter;
		}

		function save_sessiondata()
		{
			$data = array
			(
				'start'		=> $this->start,
				'query'		=> $this->query,
				'sort'		=> $this->sort,
				'order'		=> $this->order,
			);
			$this->bo->save_sessiondata($data);
		}


		function index()
		{
			$output	= get_var('output',array('POST','GET'));
			
			if(!$output)
			{
				$output = 'html';
			}
			
			$this->menu->sub = 'alternative';
			$links = $this->menu->links();
			if(!$this->acl_read)
			{
				$this->no_access($links);
				return;
			}

			$GLOBALS['phpgw']->xslttpl->add_file(array('ifc','nextmatchs','menu',
										'search_field'));

			$ifc_info = $this->bo->read();

			$uicols	= $this->bo->uicols;
//_debug_array($uicols);
			$j=0;

			if (isset($ifc_info) AND is_array($ifc_info))
			{
				foreach($ifc_info as $entry)
				{
					for ($i=0;$i<count($uicols['name']);$i++)
					{
						if($uicols['input_type'][$i]!='hidden')
						{
							$content[$j]['row'][$i]['value'] 		= $entry[$uicols['name'][$i]];
							$content[$j]['row'][$i]['name'] 		= $uicols['name'][$i];
							if($uicols['input_type'][$i]=='link')
							{
								$content[$j]['row'][$i]['text']		= lang('link');
								$content[$j]['row'][$i]['link']		= $entry[$uicols['name'][$i]];
								$content[$j]['row'][$i]['target']	= '_blank';
							}
						}
					}

					if($this->acl_read)
					{
						$content[$j]['row'][$i]['statustext']			= lang('view the record');
						$content[$j]['row'][$i]['text']					= lang('view');
						$content[$j]['row'][$i++]['link']				= $GLOBALS['phpgw']->link('/index.php', array('menuaction'=> $this->currentapp.'.uiifc.view','ifc_id'=> $entry['id']));
					}
					if($this->acl_edit)
					{
						$content[$j]['row'][$i]['statustext']			= lang('edit the record');
						$content[$j]['row'][$i]['text']					= lang('edit');
						$content[$j]['row'][$i++]['link']				= $GLOBALS['phpgw']->link('/index.php', array('menuaction'=> $this->currentapp.'.uiifc.edit', 'ifc_id'=> $entry['id']));
					}
					if($this->acl_delete)
					{
						$content[$j]['row'][$i]['statustext']			= lang('delete the record');
						$content[$j]['row'][$i]['text']					= lang('delete');
						$content[$j]['row'][$i++]['link']				= $GLOBALS['phpgw']->link('/index.php', array('menuaction'=> $this->currentapp.'.uiifc.delete', 'ifc_id'=> $entry['id']));
					}

					$j++;
				}
			}

			for ($i=0;$i<count($uicols['descr']);$i++)
			{
				if($uicols['input_type'][$i]!='hidden')
				{
					$table_header[$i]['header'] 		= $uicols['descr'][$i];
					$table_header[$i]['width'] 		= '5%';
					$table_header[$i]['align'] 		= 'center';
					if($uicols['datatype'][$i]!='T' && $uicols['datatype'][$i]!='CH')
					{
						$table_header[$i]['sort_link']	=true;
						$table_header[$i]['sort'] 		= $this->nextmatchs->show_sort_order(array
							(
								'sort'	=> $this->sort,
								'var'	=> $uicols['name'][$i],
								'order'	=> $this->order,
								'extra'	=> array('menuaction'	=> $this->currentapp.'.uiifc.index',
												'query'			=> $this->query,
												'cat_id'		=> $this->cat_id,
												'filter'		=> $this->filter,
												'output'		=>$output,
												'allrows'		=> $this->allrows
												)
							));
					}
				}
			}

			if($this->acl_read)
			{
				$table_header[$i]['width'] 			= '5%';
				$table_header[$i]['align'] 			= 'center';
				$table_header[$i]['header']			= lang('view');
				$i++;
			}
			if($this->acl_edit)
			{
				$table_header[$i]['width'] 			= '5%';
				$table_header[$i]['align'] 			= 'center';
				$table_header[$i]['header']			= lang('edit');
				$i++;
			}
			if($this->acl_delete)
			{
				$table_header[$i]['width'] 			= '5%';
				$table_header[$i]['align'] 			= 'center';
				$table_header[$i]['header']			= lang('delete');
				$i++;
			}


			if($this->acl_add)
			{
				$table_add[] = array
				(
					'lang_add'				=> lang('add'),
					'lang_add_statustext'	=> lang('add a ifc'),
					'add_action'			=> $GLOBALS['phpgw']->link('/index.php', array('menuaction'=> $this->currentapp.'.uiifc.edit','output'=>$output)),
				);
			}

			if(!$this->allrows)
			{
				$record_limit	= $GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'];
			}
			else
			{
				$record_limit	= $this->bo->total_records;
			}

			$link_data = array
			(
				'menuaction'	=> $this->currentapp.'.uiifc.index',
				'sort'			=> $this->sort,
				'order'			=> $this->order,
				'cat_id'		=> $this->cat_id,
				'filter'		=> $this->filter,
				'query'			=> $this->query,
				'output'		=> $output
			);

			$msgbox_data = (isset($receipt)?$GLOBALS['phpgw']->common->msgbox_data($receipt):'');

			$data = array
			(
				'msgbox_data'							=> $GLOBALS['phpgw']->common->msgbox($msgbox_data),
				'links'									=> $links,
				'cat_filter'							=> $this->cats->formatted_xslt_list(array('select_name' => 'cat_id','selected' => $this->cat_id,'globals' => True,'link_data' => $link_data)),
				'filter_data'							=> $this->nextmatchs->xslt_filter(array('filter' => $this->filter,'link_data' => $link_data)),
				'allow_allrows'							=> True,
				'allrows'								=> $this->allrows,
				'start_record'							=> $this->start,
				'record_limit'							=> $record_limit,
				'num_records'							=> ($ifc_info?count($ifc_info):0),
				'all_records'							=> $this->bo->total_records,
				'link_url'								=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'img_path'								=> $GLOBALS['phpgw']->common->get_image_path('phpgwapi','default'),
				'lang_searchfield_statustext'			=> lang('Enter the search string. To show all entries, empty this field and press the SUBMIT button again'),
				'lang_searchbutton_statustext'			=> lang('Submit the search string'),
				'query'									=> $this->query,
				'lang_search'							=> lang('search'),
				'table_header'							=> $table_header,
				'table_add'								=> $table_add,
				'values'								=> (isset($content)?$content:'')
			);

//_debug_array($data);
			$function_msg= lang('list ifc values');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp). ': ' . $function_msg;
			
			if($output == 'wml')
			{
				$GLOBALS['phpgw']->xslttpl->wml_out = true;
			}
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array("list2_{$output}" => $data));
			$this->save_sessiondata();
		}

		function import()
		{
			$acl_location = '.ifc.import';
			if(!$this->acl->check($acl_location,PHPGW_ACL_ADD))
			{
//				$this->no_access();
//				return;
			}

			$bolocation		= CreateObject($this->currentapp.'.bolocation');
			
			$GLOBALS['phpgw']->xslttpl->add_file(array('ifc'));
			$values		= get_var('values',array('POST'));
			$insert_record = $GLOBALS['phpgw']->session->appsession('insert_record',$this->currentapp);
			if (isset($values) && is_array($values))
			{
				if ((isset($values['save']) && $values['save']) || (isset($values['apply']) && $values['apply']))
				{

					$ifcfile = $_FILES['ifcfile']['tmp_name'];

					if(!$ifcfile)
					{
						$ifcfile = get_var('tsvfile',array('POST','GET'));
					}
				
					for ($i=0; $i<count($insert_record['location']); $i++)
					{
						if($_POST[$insert_record['location'][$i]])
						{
							$values['location'][$insert_record['location'][$i]]= $_POST[$insert_record['location'][$i]];
						}
					}

					while (is_array($insert_record['extra']) && list($key,$column) = each($insert_record['extra']))
					{
						if($_POST[$key])
						{
							$values['extra'][$column]	= $_POST[$key];
						}
					}

					$values['street_name'] 		= $_POST['street_name'];
					$values['street_number']	= $_POST['street_number'];
					if(isset($values['location']) && $values['location'])
					{
						$values['location_name']	= $_POST['loc' . (count($values['location'])).'_name']; // if not address - get the parent name as address
					}

					if(!isset($receipt['error']) || !$receipt['error'])
					{
					//	$values['ifc_id']	= $ifc_id;
						$receipt = $this->bo->import($values,$ifcfile);
						$ifc_id = $receipt['ifc_id'];
					//	$this->cat_id = ($values['cat_id']?$values['cat_id']:$this->cat_id);

						if (isset($values['save']) && $values['save'])
						{
							$GLOBALS['phpgw']->session->appsession('session_data','ifc_receipt',$receipt);
							$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uiifc.index'));
						}
					}
				}
				else
				{
					if ($ifcfile)
					{
						unlink ($ifcfile);
					}
					$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uiifc.index'));
				}
			}				

			$location_data=$bolocation->initiate_ui_location(array(
						'values'	=> (isset($values['location_data'])?$values['location_data']:''),
						'type_id'	=> -1, // calculated from location_types
						'no_link'	=> False, // disable lookup links for location type less than type_id
						'tenant'	=> False,
						'lookup_type'	=> 'form',
						'lookup_entity'	=> false,
						'entity_data'	=> (isset($values['p'])?$values['p']:'')
						));


			$link_data = array
			(
				'menuaction'	=> $this->currentapp.'.uiifc.import'
			);

			$msgbox_data = isset($msgbox_data) ? $GLOBALS['phpgw']->common->msgbox_data($receipt) : '';

			$data = array
			(
				'msgbox_data'					=> $GLOBALS['phpgw']->common->msgbox($msgbox_data),
				'import_url'					=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'location_data'					=> $location_data,
				'lang_file'						=> lang('file'),
				'lang_file_statustext'			=> lang('choose file to import'),				

				'lang_save'						=> lang('save'),
				'lang_cancel'					=> lang('cancel'),
				'lang_apply'					=> lang('apply'),
				'lang_id_statustext'			=> lang('Choose an ID'),
				'lang_apply_statustext'			=> lang('Apply the values'),
				'lang_cancel_statustext'		=> lang('Leave the actor untouched and return back to the list'),
				'lang_save_statustext'			=> lang('Save the actor and return back to the list'),
				'lang_member_of'				=> lang('member of'),

				'lang_edit'						=> lang('edit'),
				'lang_add'						=> lang('add'),
			);
			$GLOBALS['phpgw_info']['flags']['app_header'] = lang('ifc') . ': ' . lang('import');
//_debug_array($data);
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('import' => $data));
		}

		function edit()
		{
			$acl_location = '.ifc';
			if(!$this->acl_add)
			{
				$this->no_access();
				return;
			}

			$output	= get_var('output',array('POST','GET'));
			
			if(!$output)
			{
				$output = 'html';
			}

			$ifc_id	= get_var('ifc_id',array('POST','GET'));
			$values		= get_var('values',array('POST'));
			$values_attribute  = get_var('values_attribute',array('POST'));

			$insert_record_values = $GLOBALS['phpgw']->session->appsession('insert_record_values'. $acl_location,'ifc');

			if(isset($insert_record_values) && is_array($insert_record_values))
			{
				for ($j=0;$j<count($insert_record_values);$j++)
				{
					$insert_record['extra'][$insert_record_values[$j]]	= $insert_record_values[$j];
				}
			}

			$GLOBALS['phpgw']->xslttpl->add_file(array('ifc','attributes_form'));

			if (isset($values) && is_array($values))
			{
				if(!$this->acl_edit)
				{
					$this->no_access($links);
					return;
				}

				if(isset($insert_record['extra']) && is_array($insert_record['extra']))
				{
					while (is_array($insert_record['extra']) && list($key,$column) = each($insert_record['extra']))
					{
						if($_POST[$key])
						{
							$values['extra'][$column]	= $_POST[$key];
						}
					}
				}

				if ((isset($values['save']) && $values['save']) || (isset($values['apply']) && $values['apply']))
				{
					if(!$values['cat_id'] || $values['cat_id'] == 'none')
					{
						$receipt['error'][]=array('msg'=>lang('Please select a category!'));
					}
					if(!$values['name'])
					{
						$receipt['error'][]=array('msg'=>lang('Please enter a name !'));
					}
					if(!$values['address'])
					{
						$receipt['error'][]=array('msg'=>lang('Please enter an address !'));
					}
					if(!$values['zip'])
					{
						$receipt['error'][]=array('msg'=>lang('Please enter a zip code !'));
					}
					if(!$values['town'])
					{
						$receipt['error'][]=array('msg'=>lang('Please enter a town !'));
					}

					if(isset($values_attribute) && is_array($values_attribute))
					{
						foreach ($values_attribute as $attribute )
						{
							if($attribute['allow_null'] != 'True' && !$attribute['value'])
							{
								$receipt['error'][]=array('msg'=>lang('Please enter value for attribute %1', $attribute['input_text']));
							}
						}
					}

					if($ifc_id)
					{
						$values['ifc_id']=$ifc_id;
					}

					if(!isset($receipt['error']) || !$receipt['error'])
					{
						$receipt = $this->bo->save($values,$values_attribute);
						$ifc_id = $receipt['ifc_id'];

						if (isset($values['save']) && $values['save'])
						{
							$GLOBALS['phpgw']->session->appsession('session_data','hrm_training_receipt',$receipt);
							$GLOBALS['phpgw']->redirect_link('/index.php', array('menuaction'=> $this->currentapp.'.uiifc.index', 'output'=> $output));
						}
					}
				}
				else
				{
					$GLOBALS['phpgw']->redirect_link('/index.php', array('menuaction'=> $this->currentapp.'.uiifc.index', 'output'=> $output));
				}
			}

			$values = $this->bo->read_single($ifc_id);

			/* Preserve attribute values from post */
			if(isset($receipt['error']) && (isset( $values_attribute) && is_array( $values_attribute)))
			{
				$values = $this->bo->preserve_attribute_values($values,$values_attribute);
			}

			if ($ifc_id)
			{
				$function_msg = lang('edit ifc');
			}
			else
			{
				$function_msg = lang('add ifc');
			}

			$link_data = array
			(
				'menuaction'	=> $this->currentapp.'.uiifc.edit',
				'ifc_id'		=> $ifc_id,
				'output'		=> $output
			);

			$msgbox_data = (isset($receipt)?$GLOBALS['phpgw']->common->msgbox_data($receipt):'');

			$data = array
			(
				'value_entry_date'				=> (isset($values['entry_date'])?$values['entry_date']:''),
				'value_name'					=> (isset($values['name'])?$values['name']:''),
				'value_address'					=> (isset($values['address'])?$values['address']:''),
				'value_zip'						=> (isset($values['zip'])?$values['zip']:''),
				'value_town'					=> (isset($values['town'])?$values['town']:''),
				'value_remark'					=> (isset($values['remark'])?$values['remark']:''),

				'lang_entry_date'				=> lang('Entry date'),
				'lang_name'						=> lang('name'),
				'lang_address'					=> lang('address'),
				'lang_zip'						=> lang('zip'),
				'lang_town'						=> lang('town'),
				'lang_remark'					=> lang('remark'),

				'msgbox_data'					=> $GLOBALS['phpgw']->common->msgbox($msgbox_data),
				'form_action'					=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'lang_id'						=> lang('training ID'),
				'lang_save'						=> lang('save'),
				'lang_cancel'					=> lang('cancel'),
				'value_id'						=> $ifc_id,
				'lang_done_status_text'			=> lang('Back to the list'),
				'lang_save_status_text'			=> lang('Save the training'),
				'lang_apply'					=> lang('apply'),
				'lang_apply_status_text'		=> lang('Apply the values'),

				'lang_category'					=> lang('category'),
				'lang_no_cat'					=> lang('no category'),
				'cat_select'					=> $this->cats->formatted_xslt_list(array('select_name' => 'values[cat_id]','selected' => (isset($values['cat_id'])?$values['cat_id']:''))),
				'attributes_values'				=> $values['attributes'],

				'lang_access'					=> lang('private'),
				'value_access'					=> (isset($values['access'])?$values['access']:''),
				'lang_access_off_statustext'	=> lang('The note is public. If the note should be private, check this box'),
				'lang_access_on_statustext'		=> lang('The note is private. If the note should be public, uncheck this box')
			);

			$appname		= lang('ifc');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;
			if($output == 'wml')
			{
				$GLOBALS['phpgw']->xslttpl->wml_out = true;
			}
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('edit' => $data));
		}

		function view()
		{
			if(!$this->acl_delete)
			{
				$this->no_access();
				return;
			}

			$output	= get_var('output',array('POST','GET'));

			if(!$output)
			{
				$output = 'html';
			}

			$ifc_id	= get_var('ifc_id',array('POST','GET'));
			$values		= get_var('values',array('POST'));

			$GLOBALS['phpgw']->xslttpl->add_file(array('ifc','attributes_view'));

			if ($ifc_id)
			{
				$values = $this->bo->read_single($ifc_id);
				$function_msg = lang('view ifc');
			}
			else
			{
				return;
			}

			$data = array
			(
				'value_entry_date'			=> (isset($values['entry_date'])?$values['entry_date']:''),
				'value_name'				=> (isset($values['name'])?$values['name']:''),
				'value_address'				=> (isset($values['address'])?$values['address']:''),
				'value_zip'					=> (isset($values['zip'])?$values['zip']:''),
				'value_town'				=> (isset($values['town'])?$values['town']:''),
				'value_remark'				=> (isset($values['remark'])?$values['remark']:''),

				'lang_id'					=> lang('ifc ID'),
				'lang_entry_date'			=> lang('Entry date'),
				'lang_name'					=> lang('name'),
				'lang_address'				=> lang('address'),
				'lang_zip'					=> lang('zip'),
				'lang_town'					=> lang('town'),
				'lang_remark'				=> lang('remark'),

				'form_action'				=> $GLOBALS['phpgw']->link('/index.php', array('menuaction'=> $this->currentapp.'.uiifc.index','output'=>$output)),
				'lang_cancel'				=> lang('cancel'),
				'value_id'					=> $ifc_id,
				'lang_category'				=> lang('category'),
				'value_cat'					=> $this->cats->id2name($values['cat_id']),
				'attributes_values'			=> $values['attributes'],
				'lang_access'				=> lang('private'),
				'value_access'				=> (isset($values['access'])?lang($values['access']):'')
			);

			$appname	= lang('ifc');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;
			if($output == 'wml')
			{
				$GLOBALS['phpgw']->xslttpl->wml_out = true;
			}

			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('view' => $data));
		}

		function delete()
		{
			if(!$this->acl_delete)
			{
				$this->no_access();
				return;
			}

			$output	= get_var('output',array('POST','GET'));

			if(!$output)
			{
				$output = 'html';
			}

			$ifc_id	= get_var('ifc_id',array('POST','GET'));
			$confirm	= get_var('confirm',array('POST'));

			$link_data = array
			(
				'menuaction' => $this->currentapp.'.uiifc.index'
			);

			if (get_var('confirm',array('POST')))
			{
				$this->bo->delete($ifc_id);
				$GLOBALS['phpgw']->redirect_link('/index.php',$link_data);
			}

			$GLOBALS['phpgw']->xslttpl->add_file(array('app_delete'));

			$data = array
			(
				'done_action'			=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'delete_action'			=> $GLOBALS['phpgw']->link('/index.php', array('menuaction'=> $this->currentapp.'.uiifc.delete', 'ifc_id'=> $ifc_id)),
				'lang_confirm_msg'		=> lang('do you really want to delete this entry'),
				'lang_yes'				=> lang('yes'),
				'lang_yes_statustext'	=> lang('Delete the entry'),
				'lang_no_statustext'	=> lang('Back to the list'),
				'lang_no'				=> lang('no')
			);

			$appname		= lang('ifc');
			$function_msg	= lang('delete');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;

			if($output == 'wml')
			{
				$GLOBALS['phpgw']->xslttpl->wml_out = true;
			}

			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('delete' => $data));
		}

		function no_access($links = '')
		{
			$GLOBALS['phpgw']->xslttpl->add_file(array('no_access','menu'));

			$receipt['error'][]=array('msg'=>lang('NO ACCESS'));

			$msgbox_data = $GLOBALS['phpgw']->common->msgbox_data($receipt);

			$data = array
			(
				'msgbox_data'	=> $GLOBALS['phpgw']->common->msgbox($msgbox_data),
				'links'			=> $links,
			);

			$appname	= lang('No access');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname;
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('no_access' => $data));
		}
	}