<?php
	/**
	* phpGroupWare - property: a Facilities Management System.
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
	* @subpackage eco
 	* @version $Id: class.uiinvoice.inc.php,v 1.49 2007/01/26 14:53:47 sigurdne Exp $
	*/

	/**
	 * Description
	 * @package property
	 */

	class property_uiinvoice
	{
		var $grants;
		var $cat_id;
		var $start;
		var $query;
		var $sort;
		var $order;
		var $filter;
		var $user_lid;
		var $sub;
		var $currentapp;

		var $public_functions = array
		(
			'index'  	=> True,
			'edit_period'	=> True,
			'list_sub'	=> True,
			'consume'	=> True,
			'remark'	=> True,
			'delete'	=> True,
			'add'		=> True,
			'debug'		=> True,
			'view_order'	=> True,
			'excel'		=> True,
			'excel_sub'	=> True,
			'receipt'	=> True
		);

		function property_uiinvoice()
		{
			$GLOBALS['phpgw_info']['flags']['xslt_app'] = True;
			$this->currentapp		= $GLOBALS['phpgw_info']['flags']['currentapp'];
			$this->nextmatchs		= CreateObject('phpgwapi.nextmatchs');
			$this->account			= $GLOBALS['phpgw_info']['user']['account_id'];

			$this->bo			= CreateObject($this->currentapp.'.boinvoice',True);
			$this->bocommon			= CreateObject($this->currentapp.'.bocommon');
			$this->menu			= CreateObject($this->currentapp.'.menu');

			$this->start			= $this->bo->start;
			$this->query			= $this->bo->query;
			$this->sort			= $this->bo->sort;
			$this->order			= $this->bo->order;
			$this->filter			= $this->bo->filter;
			$this->cat_id			= $this->bo->cat_id;
			$this->user_lid			= $this->bo->user_lid;
			$this->allrows			= $this->bo->allrows;
			$this->district_id		= $this->bo->district_id;
			
			$this->acl 			= CreateObject('phpgwapi.acl');

			$this->acl_location		= '.invoice';
			$this->acl_read 		= $this->acl->check('.invoice',1);
			$this->acl_add 			= $this->acl->check('.invoice',2);
			$this->acl_edit 		= $this->acl->check('.invoice',4);
			$this->acl_delete 		= $this->acl->check('.invoice',8);

			$this->menu->sub		='invoice';
		}

		function save_sessiondata()
		{
			$data = array
			(
				'start'			=> $this->start,
				'query'			=> $this->query,
				'sort'			=> $this->sort,
				'order'			=> $this->order,
				'filter'		=> $this->filter,
				'cat_id'		=> $this->cat_id,
				'user_lid'		=> $this->user_lid,
				'allrows'		=> $this->allrows,
				'district_id'		=> $this->district_id
			);
			$this->bo->save_sessiondata($data);
		}


		function excel()
		{
			$paid 			= get_var('paid',array('POST','GET'));
			$start_date 	= get_var('start_date',array('POST','GET'));
			$end_date 		= get_var('end_date',array('POST','GET'));
			$submit_search 	= get_var('submit_search',array('POST','GET'));
			$vendor_id 		= get_var('vendor_id',array('POST','GET'));
			$workorder_id 	= get_var('workorder_id',array('POST','GET'));
			$loc1 			= get_var('loc1',array('POST','GET'));
			$voucher_id 	= get_var('voucher_id',array('POST','GET'));

			$start_date=urldecode($start_date);
			$end_date=urldecode($end_date);

			if(!$end_date)
			{
				$end_date = $GLOBALS['phpgw']->common->show_date(mktime(0,0,0,date("m"),date("d"),date("Y")),$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
				$start_date = $end_date;
			}

			$list = $this->bo->read_invoice($paid,$start_date,$end_date,$vendor_id,$loc1,$workorder_id,$voucher_id);

			while (is_array($list[0]) && list($name_entry,) = each($list[0]))
			{
				$name[]=$name_entry;
			}

			$descr	= $name; 

			$this->bocommon->excel($list,$name,$descr);
		}


		function excel_sub()
		{
			$voucher_id 	= get_var('voucher_id',array('POST','GET'));
			$paid 		= get_var('paid',array('POST','GET'));
			
			if ($voucher_id)
			{
				$list = $this->bo->read_invoice_sub($voucher_id,$paid);

				$name = array(
					'workorder_id',
					'status',
					'voucher_id',
					'invoice_id',
					'budget_account',
					'dima',
					'dimb',
					'dimd',
					'tax_code',
					'amount',
					'charge_tenant',
					'claim_issued',
					'vendor'
				    );

				$descr = array(
					lang('Workorder'),
					lang('status'),
					lang('voucher'),
					lang('Invoice Id'),
					lang('Budget account'),
					lang('Dim A'),
					lang('Dim B'),
					lang('Dim D'),
					lang('Tax code'),
					lang('Sum'),
					lang('Charge tenant'),
					lang('claim issued'),
					lang('vendor')
				    );

				$this->bocommon->excel($list,$name,$descr);
			}
		}

		function index()
		{
//			$start_time = explode(' ',microtime());
//			$start_time = $start_time[1]+$start_time[0];

			if(!$this->acl_read)
			{
				$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uilocation.stop', 'perm'=>1, 'acl_location'=> $this->acl_location));
			}

			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice','menu',
										'nextmatchs',
										'search_field'));

			$paid 			= get_var('paid',array('POST','GET'));
			$start_date 	= get_var('start_date',array('POST','GET'));
			$end_date 		= get_var('end_date',array('POST','GET'));
			$submit_search 	= get_var('submit_search',array('POST','GET'));
			$vendor_id 		= get_var('vendor_id',array('POST','GET'));
			$workorder_id 	= get_var('workorder_id',array('POST','GET'));
			$loc1 			= get_var('loc1',array('POST','GET'));
			$voucher_id 	= get_var('voucher_id',array('POST','GET'));
			$b_account_class= get_var('b_account_class',array('POST','GET'));
			
			$start_date=urldecode($start_date);
			$end_date=urldecode($end_date);

			if(!$end_date)
			{
				$end_date = $GLOBALS['phpgw']->common->show_date(mktime(0,0,0,date("m"),date("d"),date("Y")),$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
				$start_date = $end_date;
			}

			$links = $this->menu->links('invoice_'.!!$paid);

			$values 	= get_var('values',array('POST','GET'));

			if($values['save'] && $values['counter'])
			{
				$receipt=$this->bo->update_invoice($values);
			}

			$content = $this->bo->read_invoice($paid,$start_date,$end_date,$vendor_id,$loc1,$workorder_id,$voucher_id);

			$i=0;
			if(is_array($content))
			{
				foreach($content as $extra)
				{

					$sum								= $sum + $extra['amount'];
					$content[$i]['amount'] 				= number_format($extra['amount'], 2, ',', ' ');
					$content[$i]['lang_payment_date'] 	= lang('Payment Date');
					$content[$i]['link_sub'] 			= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.list_sub', 'user_lid'=> $this->user_lid, 'query'=> $this->query));
					$content[$i]['lang_sub'] 			= lang('Voucher ID');
					$content[$i]['lang_sub_help'] 		= lang('Klick this link to enter the list of sub-invoices');
					$content[$i]['link_period'] 		= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.edit_period'));
					$content[$i]['lang_period'] 		= lang('Period');
					$content[$i]['lang_period_help'] 	= lang('Klick this link to edit the period');

					if($this->acl_delete && !$paid)
					{
						$content[$i]['link_delete']				= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.delete', 'voucher_id'=> $extra['voucher_id']));
						$content[$i]['lang_delete_statustext']	= lang('delete the voucher');
						$content[$i]['text_delete']				= lang('delete');
					}

					$content[$i]['link_front']				= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.receipt', 'voucher_id'=> $extra['voucher_id']));
					$content[$i]['lang_front_statustext']	= lang('A printout version of the frontpage');
					$content[$i]['text_front']				= 'F';

					$i++;
				}
			}

			$table_header[] = array
			(
				'sort_voucher'		=> $this->nextmatchs->show_sort_order(array
												(
													'sort'	=> $this->sort,
													'var'	=> 'bilagsnr',
													'order'	=> $this->order,
													'extra'	=> array(
															'menuaction' 		=> $this->currentapp.'.uiinvoice.index',
															'cat_id'		=> $this->cat_id,
															'sub'			=> $this->sub,
															'paid'			=> $paid,
															'vendor_id'		=> $vendor_id,
															'user_lid'		=> $this->user_lid,
															'b_account_class'	=> $b_account_class,
															'district_id'		=> $this->district_id,
															'start_date'		=> $start_date,
															'end_date'		=> $end_date
															)
												)),
				'lang_voucher'		=> lang('voucher'),
				'sort_voucher_date'	=> $this->nextmatchs->show_sort_order(array
												(
													'sort'	=> $this->sort,
													'var'	=> 'fakturadato',
													'order'	=> $this->order,
													'extra'	=> array('menuaction' => $this->currentapp.'.uiinvoice.index',
																	'cat_id'		=> $this->cat_id,
																	'sub'			=> $this->sub,
																	'paid'			=> $paid,
																	'vendor_id'		=> $vendor_id,
																	'user_lid'		=> $this->user_lid,
																	'b_account_class'	=> $b_account_class,
																	'district_id'		=> $this->district_id,
																	'start_date'		=> $start_date,
																	'end_date'		=> $end_date
																	)
												)),
				'lang_voucher_date'	=> lang('Voucher Date'),
				'lang_days'		=> lang('Days'),
				'lang_sum'		=> lang('Sum'),
				'sort_vendor_id'	=> $this->nextmatchs->show_sort_order(array
												(
													'sort'	=> $this->sort,
													'var'	=> 'spvend_code',
													'order'	=> $this->order,
													'extra'	=> array('menuaction' => $this->currentapp.'.uiinvoice.index',
																	'cat_id'		=> $this->cat_id,
																	'sub'			=> $this->sub,
																	'paid'			=> $paid,
																	'vendor_id'		=> $vendor_id,
																	'user_lid'		=> $this->user_lid,
																	'b_account_class'	=> $b_account_class,
																	'district_id'		=> $this->district_id,
																	'start_date'		=> $start_date,
																	'end_date'		=> $end_date
																	)
												)),
				'lang_vendor_id'			=> lang('Vendor'),
				'lang_num_sub_invoice'		=> lang('Count'),
				'lang_type'					=> lang('Type'),
				'lang_period'				=> lang('Period'),
				'lang_kredit'				=> lang('KreditNota'),
				'lang_none'					=> lang('None'),
				'lang_janitor'				=> lang('Janitor'),
				'lang_supervisor'			=> lang('Supervisor'),
				'lang_budget_responsible'	=> lang('Budget Responsible'),
				'lang_transfer'				=> lang('Transfer'),
				'lang_delete'				=> lang('delete'),
				'lang_front'				=> 'F'
			);

			$table_done[] = array
			(
				'lang_done'					=> lang('Done'),
				'lang_done_statustext'		=> lang('Close this window')
			);

			$link_data = array
			(
				'menuaction'		=> $this->currentapp.'.uiinvoice.index',
				'order'				=> $this->order,
				'sort'				=> $this->sort,
				'cat_id'			=> $this->cat_id,
				'user_lid'			=> $this->user_lid,
				'sub'				=> $this->sub,
				'query'				=> $this->query,
				'start'				=> $this->start,
				'paid'				=> $paid,
				'vendor_id'			=> $vendor_id,
				'workorder_id'		=> $workorder_id,
				'start_date'		=> $start_date,
				'end_date'			=> $end_date,
				'filter'			=> $this->filter,
				'b_account_class'	=> $b_account_class,
				'district_id'		=> $this->district_id
			);

			if($this->acl_add)
			{
				$table_add[] = array
				(
					'lang_add'				=> lang('add'),
					'lang_add_statustext'	=> lang('add an invoice'),
					'add_action'			=> $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.add'))
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

			$msgbox_data = $this->bocommon->msgbox_data($receipt);

			$link_excel = array
			(
				'menuaction'		=> $this->currentapp.'.uiinvoice.excel',
				'order'				=> $this->order,
				'sort'				=> $this->sort,
				'cat_id'			=> $this->cat_id,
				'user_lid'			=> $this->user_lid,
				'sub'				=> $this->sub,
				'query'				=> $this->query,
				'start'				=> $this->start,
				'paid'				=> $paid,
				'vendor_id'			=> $vendor_id,
				'workorder_id'		=> $workorder_id,
				'start_date'		=> $start_date,
				'end_date'			=> $end_date,
				'filter'			=> $this->filter,
				'allrows'			=> $this->allrows,
				'b_account_class'	=> $b_account_class,
				'district_id'		=> $this->district_id
			);

			$GLOBALS['phpgw']->js->validate_file('overlib','overlib',$this->currentapp);
			$GLOBALS['phpgw']->js->validate_file('core','check',$this->currentapp);

			$data['lang_excel']						= 'excel';
			$data['link_excel']						= $GLOBALS['phpgw']->link('/index.php',$link_excel);
			$data['lang_excel_help']				= lang('Download table to MS Excel');

			$data['msgbox_data']					= $GLOBALS['phpgw']->common->msgbox($msgbox_data);
			$data['sum']							= number_format($sum, 2, ',', '');
			$data['links']							= $links;
			$data['allow_allrows']					= true;
			$data['allrows']						= $this->allrows;
			$data['start_record']					= $this->start;
			$data['record_limit']					= $record_limit;
			$data['num_records']					= count($content);
			$data['all_records']					= $this->bo->total_records;
			$data['link_url']						= $GLOBALS['phpgw']->link('/index.php',$link_data);
			$data['img_path']						= $GLOBALS['phpgw']->common->get_image_path('phpgwapi','default');
			$data['lang_no_cat']					= lang('no category');
			$data['lang_cat_statustext']			= lang('Select the category the building belongs to. To do not use a category select NO CATEGORY');
			$data['select_name']					= 'cat_id';
			$data['select_action']					= $GLOBALS['phpgw']->link('/index.php',$link_data);
			$data['lang_no_user']					= lang('None');
			$data['lang_user_statustext']			= lang('Select the user the selection belongs to. To do not use a user select NO USER');
			$data['select_user_name']				= 'user_lid';
			$data['lang_searchfield_statustext']	= lang('Enter the search string. To show all entries, empty this field and press the SUBMIT button again');
			$data['lang_searchbutton_statustext']	= lang('Submit the search string');
			$data['lang_search']					= lang('search');
			$data['query']							= $this->query;
			$data['form_action']					= $GLOBALS['phpgw']->link('/index.php',$link_data);

//_debug_array($content);
			$appname	= lang('invoice');
			$function_msg	= lang('list voucher');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;

			if (isset($paid) && $paid)
			{
				$jscal = CreateObject('phpgwapi.jscalendar');
				$jscal->add_listener('start_date');
				$jscal->add_listener('end_date');

				$bolocation								= CreateObject($this->currentapp.'.bolocation');
				$data['user_list']						= $this->bo->get_invoice_user_list('select',$this->user_lid,array('all'),$default='all');
				$location_data							= $bolocation->initiate_ui_location(array('type_id'=> 1));

				$data['cat_list']						= $this->bo->select_category('select',$this->cat_id);
				$data['start_date']						= $start_date;
				$data['end_date']						= $end_date;
				$data['vendor_id']						= $vendor_id;

				$data['img_cal']						= $GLOBALS['phpgw']->common->image('phpgwapi','cal');
				$data['lang_datetitle']					= lang('Select date');

				$data['lang_workorder']					= lang('Workorder ID');
				$data['lang_workorder_statustext']		= lang('enter the Workorder ID to search by workorder - at any date');
				$data['workorder_id']					= $workorder_id;

				$data['addressbook_link']				= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uilookup.vendor'));
				$data['lang_select_vendor_statustext']	= lang('Select the vendor by klicking this link');
				$data['lang_vendor']					= lang('Vendor');
				$data['property_link']					= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uilocation.index', 'lookup'=> 1, 'type_id'=> 1, 'lookup_name'=> 0));
				$data['lang_select_property_statustext']= lang('Select the property by klicking this link');
				$data['lang_property_statustext']		= lang('Search by property');
				$data['lang_property']					= lang('property');
				$data['loc1']							= $loc1;
				$data['lang_search']					= lang('Search');
				$data['lang_search_statustext']			= lang('Search for paid invoices');

				$data['table_header_list_voucher_paid']	= $table_header;
				$data['values_list_voucher_paid']		= $content;

				$data['lang_voucher_id_statustext']		= lang('Search for voucher id');
				$data['lang_voucher_id']				= lang('Voucher ID');
				$data['voucher_id']						= $voucher_id;

				$data['account_class_list']				= $this->bo->select_account_class($b_account_class);
				$data['lang_no_account_class']			= lang('No account');
				$data['lang_account_class_statustext']	= lang('Select the account class the selection belongs to');
				$data['select_account_class_name']		= 'b_account_class';

			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('list_voucher_paid' => $data));
			}
			else
			{
				$data['user_list']						= $this->bo->get_invoice_user_list('filter',$this->user_lid,array('all'),$default='none');
				$data['cat_list']						= $this->bo->select_category('filter',$this->cat_id);
				$data['table_done']						= $table_done;
				$data['img_check']						= $GLOBALS['phpgw']->common->get_image_path($this->currentapp).'/check.png';
				$data['lang_save']						= lang('save');
				$data['done_action']					= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.index'));
				$data['lang_done_statustext']			= lang('Back to the list');
				$data['lang_save_statustext']			= lang('Save the voucher');
				$data['lang_select_all']				= lang('Select All');
				$data['message']						= $receipt['message'];
				$data['error']							= $receipt['error'];
				$data['table_header_list_voucher']		= $table_header;
				$data['values_list_voucher']			= $content;
				$data['acl_delete']						= $this->acl_delete;
				$data['table_add_invoice']				= $table_add;

				$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('list_voucher' => $data));
			}
			$this->save_sessiondata();

			$end_time = $this->bo->end_time;
		}

		function list_sub()
		{

			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice','menu',
										'nextmatchs'));

			$paid = get_var('paid',array('POST','GET'));

			$links = $this->menu->links('invoice_');

			$values  = get_var('values',array('POST','GET'));
			$voucher_id = get_var('voucher_id',array('POST','GET'));

			if($values['save'] && $values['counter'])
			{
				$receipt=$this->bo->update_invoice_sub($values);
			}

//_debug_array($values);

//echo $voucher_id;


			if ($voucher_id)
			{
				$content = $this->bo->read_invoice_sub($voucher_id,$paid);
			}

			$i=0;
			if(is_array($content))
			{
				while(each($content))
				{
					$sum									= $sum + $content[$i]['amount'];
					$content[$i]['amount'] 					= number_format($content[$i]['amount'], 2, ',', '');
					$content[$i]['paid']					= $paid;
					$content[$i]['lang_tax_code_statustext']= lang('select the appropriate tax code');
					$content[$i]['lang_dimb_statustext']= lang('select the appropriate dim code');
					$content[$i]['dimb_list']				= $this->bo->select_dimb_list($content[$i]['dimb']);
					$content[$i]['tax_code_list']			= $this->bo->tax_code_list($content[$i]['tax_code']);
					$content[$i]['lang_remark'] 			= lang('Remark');
					$content[$i]['link_remark'] 			= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.remark'));
					$content[$i]['lang_remark_help'] 		= lang('Klick this link to view the remark');
					$content[$i]['link_order'] 				= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.view_order'));
					$content[$i]['link_claim'] 				= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uitenant_claim.check'));
					$i++;
				}
			}

//_debug_array($content);
			$table_header[] = array
			(
				'sort_workorder'		=> $this->nextmatchs->show_sort_order(array
												(
													'sort'	=> $this->sort,
													'var'	=> 'pmwrkord_code',
													'order'	=> $this->order,
													'extra'	=> array('menuaction'	=> $this->currentapp.'.uiinvoice.list_sub',
															'cat_id'	=> $this->cat_id,
															'sub'		=> $this->sub,
															'paid'		=> $paid,
															'voucher_id'	=> $voucher_id,
															'query'		=> $this->query)
												)),
				'lang_workorder'			=> lang('Workorder'),
				'sort_budget_account'		=> $this->nextmatchs->show_sort_order(array
												(
													'sort'	=> $this->sort,
													'var'	=> 'spbudact_code',
													'order'	=> $this->order,
													'extra'	=> array('menuaction'	=> $this->currentapp.'.uiinvoice.list_sub',
															'cat_id'	=> $this->cat_id,
															'sub'		=> $this->sub,
															'paid'		=> $paid,
															'voucher_id'	=> $voucher_id,
															'query'		=> $this->query)
												)),
				'lang_budget_account'		=> lang('Budget account'),

				'sort_sum'					=> $this->nextmatchs->show_sort_order(array
												(
													'sort'	=> $this->sort,
													'var'	=> 'belop',
													'order'	=> $this->order,
													'extra'	=> array('menuaction'	=> $this->currentapp.'.uiinvoice.list_sub',
															'cat_id'	=> $this->cat_id,
															'sub'		=> $this->sub,
															'paid'		=> $paid,
															'voucher_id'	=> $voucher_id,
															'query'		=> $this->query)
												)),

				'lang_sum'			=> lang('Sum'),
				'lang_type'			=> lang('Type'),
				'lang_close_order'		=> lang('Close order'),
				'lang_charge_tenant'		=> lang('Charge tenant'),
				'lang_invoice_id'		=> lang('Invoice Id'),
				'sort_dima'			=> $this->nextmatchs->show_sort_order(array
												(
													'sort'	=> $this->sort,
													'var'	=>	'dima',
													'order'	=>	$this->order,
													'extra'		=> array('menuaction'	=> $this->currentapp.'.uiinvoice.list_sub',
																'cat_id'	=> $this->cat_id,
																'sub'		=> $this->sub,
																'paid'		=> $paid,
																'voucher_id'	=> $voucher_id,
																'query'		=> $this->query)
												)),
				'lang_dima'			=> lang('Dim A'),
				'lang_dimb'			=> lang('Dim B'),
				'lang_dimd'			=> lang('Dim D'),
				'lang_tax_code'			=> lang('Tax code'),
				'lang_remark'			=> lang('Remark'),
			);

			$table_done[] = array
			(
				'lang_done'		=> lang('Done'),
				'lang_done_statustext'	=> lang('Close this window')
			);

			$link_data = array
			(
				'menuaction'		=> $this->currentapp.'.uiinvoice.list_sub',
				'order'			=> $this->order,
				'sort'			=> $this->sort,
				'cat_id'		=> $this->cat_id,
				'user_lid'		=> $this->user_lid,
				'sub'			=> $this->sub,
				'query'			=> $this->query,
				'start'			=> $this->start,
				'paid'			=> $paid,
				'voucher_id'		=> $voucher_id,
				'user_lid'		=> $this->user_lid,
				'query'			=> $this->query
			);

			if ($paid)
			{
				$function_msg	= lang('list paid invoice');

			}
			else
			{
				$function_msg	= lang('list invoice');
			}

			$msgbox_data = $this->bocommon->msgbox_data($receipt);


			$link_excel = array
			(
				'menuaction'	=> $this->currentapp.'.uiinvoice.excel_sub',
				'voucher_id'	=> $voucher_id,
				'paid'		=> $paid
			);

			$GLOBALS['phpgw']->js->validate_file('overlib','overlib',$this->currentapp);
			$GLOBALS['phpgw']->js->validate_file('core','check',$this->currentapp);

			$data = array
			(

				'lang_excel'					=> 'excel',
				'link_excel'					=> $GLOBALS['phpgw']->link('/index.php',$link_excel),
				'lang_excel_help'				=> lang('Download table to MS Excel'),

				'img_check'					=> $GLOBALS['phpgw']->common->get_image_path($this->currentapp).'/check.png',
				'msgbox_data'					=> $GLOBALS['phpgw']->common->msgbox($msgbox_data),
				'sum'						=> number_format($sum, 2, ',', ''),
				'form_action'					=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'lang_save'					=> lang('save'),
				'lang_done'					=> lang('Done'),
				'done_action'					=> $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.index', 'user_lid'=> $this->user_lid, 'query'=> $this->query)),
				'lang_done_statustext'				=> lang('Back to the list'),
				'lang_save_statustext'				=> lang('Save the voucher'),
				'links'						=> $links,
				'allow_allrows'					=> false,
				'start_record'					=> $this->start,
				'record_limit'					=> count($content),//$GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'],
				'num_records'					=> count($content),
				'all_records'					=> $this->bo->total_records,
				'link_url'					=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'img_path'					=> $GLOBALS['phpgw']->common->get_image_path('phpgwapi','default'),
				'lang_submit'					=> lang('submit'),
				'table_header_list_invoice_sub'			=> $table_header,
				'values_list_invoice_sub'			=> $content,
				'paid'						=> $paid,
				'vendor'					=> $content[0]['vendor'],
				'lang_vendor'					=> lang('Vendor'),
				'voucher_id'					=> $voucher_id,
				'lang_voucher_id'				=> lang('Voucher Id'),
				'lang_claim'					=> lang('Claim'),
				'table_done'					=> $table_done
			);

//_debug_array($data);

			$appname = lang('invoice');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('list_invoice_sub' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
			$this->save_sessiondata();
		}


		function edit_period()
		{
			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice'));

			$GLOBALS['phpgw_info']['flags']['noframework'] = True;

			$voucher_id 	= get_var('voucher_id',array('POST','GET'));
			$period 	= get_var('period',array('POST','GET'));
			$submit 	= get_var('submit',array('POST','GET'));

			if($submit)
			{
				$receipt	= $this->bo->update_period($voucher_id,$period);
			}

			$function_msg	= lang('Edit period');

			$link_data = array
			(
				'menuaction'	=> $this->currentapp.'.uiinvoice.edit_period',
				'voucher_id'	=> $voucher_id);


			$msgbox_data = $this->bocommon->msgbox_data($receipt);

			$data = array
			(
				'msgbox_data'		=> $GLOBALS['phpgw']->common->msgbox($msgbox_data),
				'period_list'		=> $this->bo->period_list($period),
				'function_msg'		=> $function_msg,
				'form_action'		=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'lang_save'		=> lang('save'),
				'select_name'		=> 'period'
			);

//_debug_array($data);

			$GLOBALS['phpgw_info']['flags']['app_header'] = $function_msg;
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('edit_period' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
		}

		function remark()
		{
			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice'));
			$GLOBALS['phpgw_info']['flags'][nofooter] = True;
			$GLOBALS['phpgw_info']['flags']['noframework'] = True;

			$id 	= get_var('id',array('POST','GET'));
			$paid 	= get_var('paid',array('POST','GET'));

			$data = array
			(
				'remark' => $this->bo->read_remark($id,$paid)
			);

//_debug_array($data);

			$appname	= lang('invoice');
			$function_msg	= lang('remark');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('remark' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
		}

		function consume()
		{
			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice','menu',
										'nextmatchs',
										'search_field'));

			$start_date 		= get_var('start_date',array('POST','GET'));
			$end_date 		= get_var('end_date',array('POST','GET'));
			$submit_search 		= get_var('submit_search',array('POST','GET'));
			$vendor_id 		= get_var('vendor_id',array('POST','GET'));

			$workorder_id 		= get_var('workorder_id',array('POST','GET'));
			$loc1 			= get_var('loc1',array('POST','GET'));
			$district_id 		= get_var('district_id',array('POST','GET'));
			$b_account_class 	= get_var('b_account_class',array('POST','GET'));

			if($vendor_id)
			{
				$contacts		= CreateObject($this->currentapp.'.soactor');
				$contacts->role		= 'vendor';
				$vendor			= $contacts->read_single(array('actor_id'=>(int)$vendor_id));
				if(is_array($vendor))
				{
					foreach($vendor['attributes'] as $attribute)
					{
						if($attribute['name']=='org_name')
						{
							$vendor_name=$attribute['value'];
							break;
						}
					}
				}
			}

			$links = $this->menu->links('consume');

			$dateformat = $GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'];
//_debug_array($values);
			if(!$submit_search)
			{
				$start_date = $GLOBALS['phpgw']->common->show_date(mktime(0,0,0,date("m"),date("d"),date("Y")),$dateformat);
				$end_date	= $start_date;
			}
			else
			{
				$content = $this->bo->read_consume($start_date,$end_date,$vendor_id,$loc1,$workorder_id,$b_account_class,$district_id);
			}

			if(is_array($content))
			{
				$p_year = date("Y",strtotime($start_date));
			$p_month = date("m",strtotime($start_date));
			$i=0;
				while(each($content))
				{
					$p_start_date = $GLOBALS['phpgw']->common->show_date(mktime(0,0,0,$content[$i]['period'],1,$p_year),$dateformat);
					$p_end_date = $GLOBALS['phpgw']->common->show_date(mktime(0,0,0,($content[$i]['period']+1),0,$p_year),$dateformat);
					$sum				= $sum+$content[$i]['consume'];
					$content[$i]['link_voucher'] 	= $GLOBALS['phpgw']->link('/index.php',array(
														'menuaction'	=> $this->currentapp.'.uiinvoice.index',
														'paid'		=> true,
														'user_lid'	=> 'all',
														'district_id'	=> $district_id,
														'b_account_class'=> $b_account_class,
														'start_date'	=> $p_start_date,
														'end_date'	=> $p_end_date
														)
													);
					$content[$i]['consume'] 	= number_format($content[$i]['consume'], 0, ',', ' ');
					$i++;
				}
			}


			$table_header[] = array
			(
				'lang_district'			=> lang('District'),
				'lang_period'			=> lang('Period'),
				'lang_budget_account'		=> lang('Budget account'),
				'lang_consume'			=> lang('Consume'),
			);

			$table_done[] = array
			(
				'lang_done'		=> lang('Done'),
				'lang_done_statustext'	=> lang('Close this window')
			);

			$link_data = array
			(
				'menuaction'		=> $this->currentapp.'.uiinvoice.consume',
				'order'			=> $this->order,
				'sort'			=> $this->sort,
				'cat_id'		=> $this->cat_id,
				'district_id'		=> $district_id,
				'sub'			=> $this->sub,
				'query'			=> $this->query,
				'start'			=> $this->start,
				'filter'		=> $this->filter
			);

			$GLOBALS['phpgw']->js->validate_file('overlib','overlib',$this->currentapp);

			$data['lang_sum']				= lang('Sum');
			$data['sum']					= number_format($sum, 0, ',', ' ');
			$data['links']					= $links;
			$data['allow_allrows']				= false;
			$data['start_record']				= $this->start;
			$data['record_limit']				= $GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'];
			$data['num_records']				= count($content);
			$data['all_records']				= $this->bo->total_records;
			$data['link_url']				= $GLOBALS['phpgw']->link('/index.php',$link_data);
			$data['img_path']				= $GLOBALS['phpgw']->common->get_image_path('phpgwapi','default');
			$data['lang_no_cat']				= lang('no category');
			$data['lang_cat_statustext']			= lang('Select the category the building belongs to. To do not use a category select NO CATEGORY');
			$data['select_name']				= 'cat_id';
			$data['select_action']				= $GLOBALS['phpgw']->link('/index.php',$link_data);
			$data['lang_no_district']			= lang('No district');
			$data['lang_district_statustext']		= lang('Select the district the selection belongs to. To do not use a district select NO DISTRICT');
			$data['select_district_name']			= 'district_id';
			$data['lang_searchfield_statustext']		= lang('Enter the search string. To show all entries, empty this field and press the SUBMIT button again');
			$data['lang_searchbutton_statustext']		= lang('Submit the search string');
			$data['lang_search']				= lang('search');
			$data['query']					= $this->query;
			$data['form_action']				= $GLOBALS['phpgw']->link('/index.php',$link_data);

			$data['district_list']				= $this->bocommon->select_district_list('select',$district_id);
			$data['cat_list']				= $this->bo->select_category('select',$this->cat_id);
			$data['start_date']				= $start_date;
			$data['end_date']				= $end_date;
			$data['vendor_id']				= $vendor_id;
			$data['vendor_name']				= $vendor_name;

			$data['account_class_list']			= $this->bo->select_account_class($b_account_class);
			$data['lang_no_account_class']			= lang('No account');
			$data['lang_account_class_statustext']		= lang('Select the account class the selection belongs to');
			$data['select_account_class_name']		= 'b_account_class';

			$jscal = CreateObject('phpgwapi.jscalendar');
			$jscal->add_listener('start_date');
			$jscal->add_listener('end_date');

			$data['img_cal']						= $GLOBALS['phpgw']->common->image('phpgwapi','cal');
			$data['lang_datetitle']				= lang('Select date');

			$data['lang_workorder']				= lang('Workorder ID');
			$data['lang_workorder_statustext']		= lang('enter the Workorder ID to search by workorder - at any date');
			$data['workorder_id']				= $workorder_id;

			$data['addressbook_link']			= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uilookup.vendor'));
			$data['lang_select_vendor_statustext']		= lang('Select the vendor by klicking this link');
			$data['lang_vendor']				= lang('Vendor');

			$bolocation					= CreateObject($this->currentapp.'.bolocation');
			$location_data					= $bolocation->initiate_ui_location(array('type_id'=> 1));

			$data['property_link']				= $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uilocation.index', 'lookup'=> 1, 'type_id'=> 1, 'lookup_name'=> 0));

			$data['lang_select_property_statustext']	= lang('Select the property by klicking this link');
			$data['lang_property_statustext']		= lang('Search by property');

			$data['lang_property']				= lang('property');
			$data['loc1']					= $loc1;
			$data['lang_search']				= lang('Search');
			$data['lang_search_statustext']			= lang('Search for paid invoices');

			$data['table_header_consume']			= $table_header;
			$data['values_consume']				= $content;

			$appname					= lang('consume');
			$function_msg					= lang('list consume');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('consume' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();

			$this->save_sessiondata();
		}

		function delete()
		{
			if(!$this->acl_delete)
			{
				$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uilocation.stop', 'perm'=>8, 'acl_location'=> $this->acl_location));
			}

			$voucher_id = get_var('voucher_id',array('POST','GET'));
			$confirm	= get_var('confirm',array('POST'));

			$link_data = array
			(
				'menuaction' => $this->currentapp.'.uiinvoice.index'
			);

			if (get_var('confirm',array('POST')))
			{
				$this->bo->delete($voucher_id);
				$GLOBALS['phpgw']->redirect_link('/index.php',$link_data);
			}

			$GLOBALS['phpgw']->xslttpl->add_file(array('app_delete'));

			$data = array
			(
				'done_action'		=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'delete_action'		=> $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.delete', 'voucher_id'=> $voucher_id)),
				'lang_confirm_msg'	=> lang('do you really want to delete this entry'),
				'lang_yes'		=> lang('yes'),
				'lang_yes_statustext'	=> lang('Delete the entry'),
				'lang_no_statustext'	=> lang('Back to the list'),
				'lang_no'		=> lang('no')
			);

			$appname	= lang('invoice');
			$function_msg	= lang('delete voucher');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('delete' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
		}

		function add()
		{
			if(!$this->acl_add)
			{
				$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uilocation.stop', 'perm'=>2, 'acl_location'=> $this->acl_location));
			}

			$receipt = $GLOBALS['phpgw']->session->appsession('session_data','add_receipt');

			if($receipt['voucher_id'])
			{
				$link_receipt = $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.receipt', 'voucher_id'=> $receipt['voucher_id']));
			}

			$GLOBALS['phpgw']->session->appsession('session_data','add_receipt','');

			$bolocation	= CreateObject($this->currentapp.'.bolocation');

			$referer = parse_url($_SERVER['HTTP_REFERER']);
			parse_str($referer['query']); // produce $menuaction
			if(get_var('cancel',array('POST','GET')) || $menuaction != $this->currentapp.'.uiinvoice.add')
			{
				$GLOBALS['phpgw']->session->appsession('session_data','add_values','');
			}
			
			if(!$GLOBALS['phpgw']->session->appsession('session_data','add_values') && get_var('add_invoice',array('POST','GET')))
			{
				$values['art']			= get_var('art',array('POST','GET'));
				$values['type']			= get_var('type',array('POST','GET'));
				$values['dim_b']		= get_var('dim_b',array('POST','GET'));
				$values['invoice_num']		= get_var('invoice_num',array('POST','GET'));
				$values['kid_nr']		= get_var('kid_nr',array('POST','GET'));
				$values['vendor_id']		= get_var('vendor_id',array('POST','GET'));
				$values['vendor_name']		= get_var('vendor_name',array('POST','GET'));
				$values['janitor']		= get_var('janitor',array('POST','GET'));
				$values['supervisor']		= get_var('supervisor',array('POST','GET'));
				$values['budget_responsible']	= get_var('budget_responsible',array('POST','GET'));
				$values['invoice_date'] 	= urldecode(get_var('invoice_date',array('POST','GET')));
				$values['num_days']		= get_var('num_days',array('POST','GET'));
				$values['payment_date'] 	= urldecode(get_var('payment_date',array('POST','GET')));
				$values['sday'] 		= get_var('sday',array('POST','GET'));
				$values['smonth'] 		= get_var('smonth',array('POST','GET'));
				$values['syear']		= get_var('syear',array('POST','GET'));
				$values['eday'] 		= get_var('eday',array('POST','GET'));
				$values['emonth'] 		= get_var('emonth',array('POST','GET'));
				$values['eyear']		= get_var('eyear',array('POST','GET'));
				$values['auto_tax'] 		= get_var('auto_tax',array('POST','GET'));
				$values['merknad']		= get_var('merknad',array('POST','GET'));
				$values['b_account_id']		= get_var('b_account_id',array('POST','GET'));
				$values['b_account_name']	= get_var('b_account_name',array('POST','GET'));
				$values['amount']		= get_var('amount',array('POST','GET'));
				$values['order']		= get_var('order',array('POST','GET'));

				$insert_record = $GLOBALS['phpgw']->session->appsession('insert_record',$this->currentapp);

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
				$values['location_name']	= $_POST['loc' . (count($values['location'])).'_name']; // if no address - get the parent name as address

				$GLOBALS['phpgw']->session->appsession('session_data','add_values',$values);
			}
			else
			{
				$values	= $GLOBALS['phpgw']->session->appsession('session_data','add_values');
				$GLOBALS['phpgw']->session->appsession('session_data','add_values','');
			}

			$location_code 			= get_var('location_code',array('POST','GET'));
			$debug 				= get_var('debug',array('POST','GET'));
			$add_invoice 			= get_var('add_invoice',array('POST','GET'));


			$links = $this->menu->links('add_inv');

			if($location_code)
			{
				$values['location_data'] = $bolocation->read_single($location_code,array('tenant_id'=>$tenant_id,'p_num'=>$p_num));
			}



			if($add_invoice && is_array($values))
			{

				if($values['order'] && !ctype_digit($values['order'])):
				{
					$receipt['error'][]=array('msg'=>lang('Please enter an integer for order!'));
					unset($values['order']);
				}
				elseif($values['order']):
				{
					$order=True;
				}
				endif;

				if (!$values['amount'])
				{
					$receipt['error'][] = array('msg'=>lang('Please - enter an amount!'));
				}
				if (!$values['art'])
				{
					$receipt['error'][] = array('msg'=>lang('Please - select type invoice!'));
				}
				if (!$values['vendor_id'] && !$order)
				{
					$receipt['error'][] = array('msg'=>lang('Please - select Vendor!'));
				}

				if (!$values['type'])
				{
					$receipt['error'][] = array('msg'=>lang('Please - select type order!'));
				}

				if (!$values['budget_responsible']  && !$order)
				{
					$receipt['error'][] = array('msg'=>lang('Please - select budget responsible!'));
				}

				if(!$order && $values['vendor_id'])
				{
					if (!$this->bo->check_vendor($values['vendor_id']))
					{
						$receipt['error'][] = array('msg'=>lang('That Vendor ID is not valid !'). ' : ' . $values['vendor_id']);
					}
				}
				
				if (!$values['payment_date'] && !$values['num_days'])
				{
					$receipt['error'][] = array('msg'=>lang('Please - select either payment date or number of days from invoice date !'));
				}

//_debug_array($values);
				if (!is_array($receipt['error']))
				{
					$dateformat = strtolower($GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
					$dateformat = str_replace(".","",$dateformat);
					$dateformat = str_replace("-","",$dateformat);
					$dateformat = str_replace("/","",$dateformat);
					$y=strpos($dateformat,'y');
					$d=strpos($dateformat,'d');
					$m=strpos($dateformat,'m');

					if($values['invoice_date'])
					{
			 			$dateparts = explode('/', $values['invoice_date']);
			 			$values['sday'] = $dateparts[$d];
			 			$values['smonth'] = $dateparts[$m];
			 			$values['syear'] = $dateparts[$y];

			 			$dateparts = explode('/', $values['payment_date']);
			 			$values['eday'] = $dateparts[$d];
			 			$values['emonth'] = $dateparts[$m];
			 			$values['eyear'] = $dateparts[$y];
					}

					$values['regtid'] 		= date($this->bocommon->datetimeformat);

					$receipt = $this->bo->add($values,$debug);

					if($debug)
					{
						$this->debug($receipt);
						return;
					}
					unset($values);
					$GLOBALS['phpgw']->session->appsession('session_data','add_receipt',$receipt);
					$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.add'));

				}
				else
				{
					if($values['location'])
					{
						$location_code=implode("-", $values['location']);
						$values['location_data'] = $bolocation->read_single($location_code,$values['extra']);
					}
					$GLOBALS['phpgw']->session->appsession('session_data','add_values','');
				}
			}

			$location_data=$bolocation->initiate_ui_location(array(
   						'values'	=> $values['location_data'],
   						'type_id'	=> -1, // calculated from location_types
   						'no_link'	=> False, // disable lookup links for location type less than type_id
   						'tenant'	=> False,
   						'lookup_type'	=> 'form',
   						'lookup_entity'	=> False, //$this->bocommon->get_lookup_entity('project'),
   						'entity_data'	=> False //$values['p']
   						));

			$b_account_data=$this->bocommon->initiate_ui_budget_account_lookup(array(
						'b_account_id'		=> $values['b_account_id'],
						'b_account_name'	=> $values['b_account_name']));

			$link_data = array
			(
				'menuaction'	=> $this->currentapp.'.uiinvoice.add',
				'debug'		=> True
			);

			$dateformat = strtolower($GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
			$sep = '/';
			$dlarr[strpos($dateformat,'y')] = 'yyyy';
			$dlarr[strpos($dateformat,'m')] = 'MM';
			$dlarr[strpos($dateformat,'d')] = 'DD';
			ksort($dlarr);

			$dateformat= (implode($sep,$dlarr));

			$msgbox_data = $this->bocommon->msgbox_data($receipt);

			$jscal = CreateObject('phpgwapi.jscalendar');
			$jscal->add_listener('invoice_date');
			$jscal->add_listener('payment_date');

			$data = array
			(
				'msgbox_data'					=> $GLOBALS['phpgw']->common->msgbox($msgbox_data),
				'links'						=> $links,

				'img_cal'					=> $GLOBALS['phpgw']->common->image('phpgwapi','cal'),
				'lang_datetitle'				=> lang('Select date'),

				'form_action'					=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'cancel_action'					=> $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.index')),
				'lang_cancel'					=> lang('Cancel'),
				'lang_cancel_statustext'			=> lang('cancel'),
				'action_url'					=> $GLOBALS['phpgw']->link('/index.php',array('menuaction'=>  $this->currentapp .'.uiinvoice.add')),
				'tsvfilename'					=> '',

				'lang_add'					=> lang('add'),
				'lang_add_statustext'				=> lang('Klick this button to add a invoice'),

				'lang_invoice_date'				=> lang('invoice date'),
				'lang_payment_date'				=> lang('Payment date'),
				'lang_no_of_days'				=> lang('Days'),
				'lang_invoice_number'				=> lang('Invoice Number'),
				'lang_invoice_num_statustext'			=> lang('Enter Invoice Number'),

				'lang_select'					=> lang('Select per button !'),
				'lang_kidnr'					=> lang('KID nr'),
				'lang_kid_nr_statustext'			=> lang('Enter Kid nr'),

				'lang_vendor'					=> lang('Vendor'),
				'addressbook_link'				=> $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uilookup.vendor')),

				'lang_invoice_date_statustext'			=> lang('Enter the invoice date'),
				'lang_num_days_statustext'			=> lang('Enter the payment date or the payment delay'),
				'lang_payment_date_statustext'			=> lang('Enter the payment date or the payment delay'),
				'lang_vendor_statustext'			=> lang('Select the vendor by klicking the button'),
				'lang_vendor_name_statustext'			=> lang('Select the vendor by klicking the button'),
				'lang_select_vendor_statustext'			=> lang('Select the vendor by klicking this button'),

				'value_invoice_date'				=> $values['invoice_date'],
				'value_payment_date'				=> $values['payment_date'],
				'value_belop'					=> $values['belop'],
				'value_vendor_id'				=> $values['vendor_id'],
				'value_vendor_name'				=> $values['vendor_name'],
				'value_kid_nr'					=> $values['kid_nr'],
				'value_dim_b'					=> $values['dim_b'],
				'value_invoice_num'				=> $values['invoice_num'],
				'value_merknad'					=> $values['merknad'],
				'value_num_days'				=> $values['num_days'],
				'value_amount'					=> $values['amount'],
				'value_order'					=> $values['order'],

				'lang_auto_tax'					=> lang('Auto TAX'),
				'lang_auto_tax_statustext'			=> lang('Set tax'),

				'lang_amount'					=> lang('Amount'),
				'lang_amount_statustext'			=> lang('Amount of the invoice'),

				'lang_order'					=> lang('Order ID'),
				'lang_order_statustext'				=> lang('Order # that initiated the invoice'),

				'lang_art'					=> lang('Art'),
				'art_list'					=> $this->bo->get_lisfm_ecoart($values['art']),
				'select_art'					=> 'art',
				'lang_select_art' 				=> lang('Select Invoice Type'),
				'lang_art_statustext'				=> lang('You have to select type of invoice'),

				'lang_type'					=> lang('Type invoice II'),
				'type_list'					=> $this->bo->get_type_list($values['type']),
				'select_type'					=> 'type',
				'lang_no_type'					=> lang('No type'),
				'lang_type_statustext'				=> lang('Select the type  invoice. To do not use type -  select NO TYPE'),

				'lang_dimb'					=> lang('Dim B'),
				'dimb_list'					=> $this->bo->select_dimb_list($values['dim_b']),
				'select_dimb'					=> 'dim_b',
				'lang_no_dimb'					=> lang('No Dim B'),
				'lang_dimb_statustext'				=> lang('Select the Dim B for this invoice. To do not use Dim B -  select NO DIM B'),

				'lang_janitor'					=> lang('Janitor'),
				'janitor_list'					=> $this->bocommon->get_user_list_right(32,$values['janitor'],'.invoice'),
				'select_janitor'				=> 'janitor',
				'lang_no_janitor'				=> lang('No janitor'),
				'lang_janitor_statustext'			=> lang('Select the janitor responsible for this invoice. To do not use janitor -  select NO JANITOR'),

				'lang_supervisor'				=> lang('Supervisor'),
				'supervisor_list'				=> $this->bocommon->get_user_list_right(64,$values['supervisor'],'.invoice'),
				'select_supervisor'				=> 'supervisor',
				'lang_no_supervisor'				=> lang('No supervisor'),
				'lang_supervisor_statustext'			=> lang('Select the supervisor responsible for this invoice. To do not use supervisor -  select NO SUPERVISOR'),

				'lang_budget_responsible'			=> lang('B - responsible'),
				'budget_responsible_list'			=> $this->bocommon->get_user_list_right(128,$values['budget_responsible'],'.invoice'),
				'select_budget_responsible'			=> 'budget_responsible',
				'lang_select_budget_responsible'		=> lang('Select B-Responsible'),
				'lang_budget_responsible_statustext'		=> lang('You have to select a budget responsible for this invoice in order to add the invoice'),
				'lang_merknad'					=> lang('Descr'),
				'lang_merknad_statustext'			=> lang('Descr'),
				'location_data'					=> $location_data,
				'b_account_data'				=> $b_account_data,
				'link_receipt'					=> $link_receipt,
				'lang_receipt'					=> lang('receipt')
				);

//_debug_array($data);

			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice','menu'));

			$appname						= lang('Invoice');
			$function_msg					= lang('Add invoice');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('add' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
		}

		function receipt()
		{

			if(!$this->acl_read)
			{
				$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uilocation.stop', 'perm'=>1, 'acl_location'=> $this->acl_location));
			}

			$GLOBALS['phpgw_info']['flags'][noheader] = True;
			$GLOBALS['phpgw_info']['flags'][nofooter] = True;
			$GLOBALS['phpgw_info']['flags']['xslt_app'] = False;

			$voucher_id = get_var('voucher_id',array('POST','GET'));

			if($voucher_id)
			{
				$values = $this->bo->read_single_voucher($voucher_id);
			}
//	_debug_array($values);
			$pdf	= CreateObject('phpgwapi.pdf');

			if (isSet($values) AND is_array($values))
			{

				$contacts	= CreateObject($this->currentapp.'.soactor');
				$contacts->role='vendor';
				if($values[0]['vendor_id'])
				{
					$vendor_data	= $contacts->read_single(array('actor_id'=>$values[0]['vendor_id']));
					if(is_array($vendor_data))
					{
						foreach($vendor_data['attributes'] as $attribute)
						{
							if($attribute['name']=='org_name')
							{
								$value_vendor_name = $attribute['value'];
								break;
							}
						}
					}
				}
			
				$sum = 0;
				foreach($values as $entry)
				{
					$content[] = array
					(
						lang('order')		=> $entry['order'],
						lang('invoice id')	=> $entry['invoice_id'],
						lang('budget account')	=> $entry['b_account_id'],
						lang('object')		=> $entry['dim_a'],
						lang('dim_d')		=> $entry['dim_d'],
						lang('Tax code')	=> $entry['tax'],
						'Tjeneste'		=> $entry['kostra_id'],
						lang('amount')		=> number_format($entry['amount'], 2, ',', ' ')

					);
					$sum = $sum + $entry['amount'];
				}
			}

			$dateformat = $GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat'];
			$date = $GLOBALS['phpgw']->common->show_date('',$dateformat);

			// don't want any warnings turning up in the pdf code if the server is set to 'anal' mode.
			//error_reporting(7);
			//error_reporting(E_ALL);
			set_time_limit(1800);
			$pdf -> ezSetMargins(50,70,50,50);
			$pdf->selectFont(PHPGW_APP_INC . '/pdf/fonts/Helvetica.afm');

			// put a line top and bottom on all the pages
			$all = $pdf->openObject();
			$pdf->saveState();
			$pdf->setStrokeColor(0,0,0,1);
			$pdf->line(20,40,578,40);
			$pdf->line(20,822,578,822);
			$pdf->addText(50,823,6,lang('voucher'));
			$pdf->addText(50,34,6,'BBB');
			$pdf->addText(300,34,6,$date);

			$pdf->setColor(1,0,0);
			$pdf->addText(500,750,40,'E',-10);
			$pdf->ellipse(512,768,30);
			$pdf->setColor(1,0,0);


			$pdf->restoreState();
			$pdf->closeObject();
			// note that object can be told to appear on just odd or even pages by changing 'all' to 'odd'
			// or 'even'.
			$pdf->addObject($all,'all');
			$pdf->ezStartPageNumbers(500,28,10,'right','{PAGENUM} ' . lang('of') . ' {TOTALPAGENUM}',1);
	/*
			$pdf->ezText(lang('voucher id') . ': ' . $voucher_id,14);
			$pdf->ezText(lang('Type') . ' ' .$values[0]['art'] ,14);
			$pdf->ezText(lang('vendor') . ' ' .$values[0]['vendor_id'] . ' ' . $value_vendor_name ,14);
			$pdf->ezText(lang('invoice date') . ' ' . $GLOBALS['phpgw']->common->show_date(strtotime($values[0]['invoice_date']),$dateformat) ,14);
			$pdf->ezText(lang('Payment date') . ' ' . $GLOBALS['phpgw']->common->show_date(strtotime($values[0]['payment_date']),$dateformat) ,14);
			$pdf->ezText(lang('Janitor') . ' ' .$values[0]['janitor'] ,14);
			$pdf->ezText(lang('Supervisor') . ' ' .$values[0]['supervisor'] ,14);
			$pdf->ezText(lang('Budget Responsible') . ' ' .$values[0]['budget_responsible'] ,14);
			$pdf->ezText(lang('Project id') . ' ' .$values[0]['project_id'] ,14);
			$pdf->ezText(lang('Sum') . ' ' .number_format($sum, 2, ',', ' ') ,14);
	*/

			$content_heading[] = array
					(
						'text'		=> lang('voucher id'),
						'value'		=> $voucher_id
					);
			$content_heading[] = array
					(
						'text'		=> lang('Type'),
						'value'		=> $values[0]['art']
					);
			$content_heading[] = array
					(
						'text'		=> lang('vendor'),
						'value'		=> $values[0]['vendor_id'] . ' ' . $value_vendor_name
					);
			$content_heading[] = array
					(
						'text'		=> lang('invoice date'),
						'value'		=> $GLOBALS['phpgw']->common->show_date(strtotime($values[0]['invoice_date']),$dateformat)
					);
			$content_heading[] = array
					(
						'text'		=> lang('Payment date'),
						'value'		=> $GLOBALS['phpgw']->common->show_date(strtotime($values[0]['payment_date']),$dateformat)
					);
			$content_heading[] = array
					(
						'text'		=> lang('Janitor'),
						'value'		=> $values[0]['janitor']
					);
			$content_heading[] = array
					(
						'text'		=> lang('Supervisor'),
						'value'		=> $values[0]['supervisor']
					);
			$content_heading[] = array
					(
						'text'		=> lang('Budget Responsible'),
						'value'		=> $values[0]['budget_responsible']
					);
			
			if($values[0]['project_id'])
			{
				$content_heading[] = array
						(
							'text'		=> lang('Project id'),
							'value'		=> $values[0]['project_id']
						);
			}
			
			$content_heading[] = array
					(
						'text'		=> lang('Sum'),
						'value'		=> number_format($sum, 2, ',', ' ')
					);

			$pdf->ezTable($content_heading,'','',
							array('xPos'=>70,'xOrientation'=>'right','width'=>400,0,'shaded'=>0,'fontSize' => 8,'showLines'=> 0,'titleFontSize' => 12,'outerLineThickness'=>0,'showHeadings'=>0
							,'cols'=>array('text'=>array('justification'=>'left','width'=>100),
									'value'=>array('justification'=>'left','width'=>200))
							)
						);
		
			$pdf->ezSetDy(-20);
			
			$table_header = array(
				lang('order')=>array('justification'=>'right','width'=>60),
				lang('invoice id')=>array('justification'=>'right','width'=>60),
				lang('budget account')=>array('justification'=>'right','width'=>80),
				lang('object')=>array('justification'=>'right','width'=>70),
				lang('dim_d')=>array('justification'=>'right','width'=>50),
				lang('Tax code')=>array('justification'=>'right','width'=>50),
				'Tjeneste'=>array('justification'=>'right','width'=>50),
				lang('amount')=>array('justification'=>'right','width'=>80),
				);
			

			if(is_array($values))
			{
				$pdf->ezTable($content,'','',
							array('xPos'=>70,'xOrientation'=>'right','width'=>500,0,'shaded'=>0,'fontSize' => 8,'showLines'=> 2,'titleFontSize' => 12,'outerLineThickness'=>2
							,'cols'=>$table_header
							)
						);
			}

			$document= $pdf->ezOutput();
			$pdf->print_pdf($document,'receipt_'.$voucher_id);
		}


		function debug($values)
		{
//			_debug_array($values);
			$GLOBALS['phpgw_info']['flags'][noheader] = True;
			$GLOBALS['phpgw_info']['flags'][nofooter] = True;
			$GLOBALS['phpgw_info']['flags']['noframework'] = True;

			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice','menu','table_header'));
			
			$link_data_add = array
			(
				'menuaction'	=> $this->currentapp.'.uiinvoice.add',
				'add_invoice'	=> True
			);

			$link_data_cancel = array
			(
				'menuaction'	=> $this->currentapp.'.uiinvoice.add'
			);

			$post_data = array
			(
				'location_code'		=> $values[0]['location_code'],
				'art'			=> $values[0]['art'],
				'type'			=> $values[0]['type'],
				'dim_b'			=> $values[0]['dim_b'],
				'invoice_num'		=> $values[0]['invoice_num'],
				'kid_nr'		=> $values[0]['kid_nr'],
				'vendor_id'		=> $values[0]['spvend_code'],
				'vendor_name'		=> $values[0]['vendor_name'],
				'janitor'		=> $values[0]['janitor'],
				'supervisor'		=> $values[0]['supervisor'],
				'budget_responsible'	=> $values[0]['budget_responsible'],
				'invoice_date' 		=> urlencode($values[0]['invoice_date']),
				'num_days'		=> $values[0]['num_days'],
				'payment_date' 		=> urlencode($values[0]['payment_date']),
				'sday' 			=> $values[0]['sday'],
				'smonth' 		=> $values[0]['smonth'],
				'syear'			=> $values[0]['syear'],
				'eday' 			=> $values[0]['eday'],
				'emonth' 		=> $values[0]['emonth'],
				'eyear'			=> $values[0]['eyear'],
				'auto_tax' 		=> $values[0]['auto_tax'],
				'merknad'		=> $values[0]['merknad'],
				'b_account_id'		=> $values[0]['spbudact_code'],
				'b_account_name'	=> $values[0]['b_account_name'],
				'amount'		=> $values[0]['amount'],
				'order'			=> $values[0]['order'],
			);

			$link_data_add		= $link_data_add + $post_data;
			$link_data_cancel	= $link_data_cancel + $post_data;

			$table_add[] = array
			(
				'lang_add'		=> lang('Add'),
				'lang_add_statustext'	=> lang('Add this invoice'),
				'add_action'		=> $GLOBALS['phpgw']->link('/index.php',$link_data_add),
				'lang_cancel'		=> lang('cancel'),
				'lang_cancel_statustext'=> lang('Do not add this invoice'),
				'cancel_action'		=> $GLOBALS['phpgw']->link('/index.php',$link_data_cancel)
			);


			$import = array(
				'Bestilling'		=> 'pmwrkord_code',
				'Fakt. Nr' 		=> 'fakturanr',
				'Konto'			=> 'spbudact_code',
				'Objekt'		=> 'dima',
				'Fag/Timer/Matr' 	=> 'dimd',
				'MVA'			=> 'mvakode',
				'Tjeneste'		=> 'kostra_id',
				'Bel�p [kr]'		=> 'belop'
			);

			$header = array('Bestilling','Fakt. Nr','Konto','Objekt','Fag/Timer/Matr','MVA','Tjeneste','Bel�p [kr]');

			for ($i=0;$i<count($header);$i++)
			{
				$table_header[$i]['header'] 	= $header[$i];
				$table_header[$i]['width'] 		= '5%';
				$table_header[$i]['align'] 		= 'center';
			}
		//	$sum=0;

			$import_count = count($import);
			$values_count = count($values);
			for ($i=0; $i<$values_count; $i++)
			{
				for ($k=0; $k<$import_count; $k++)
				{
					$content[$i]['row'][$k]['value'] 	= $values[$i][$import[$header[$k]]];
					if ($import[$header[$k]]=='belop')
					{
						$content[$i]['row'][$k]['align'] 	= 'right';
				//		$sum=$sum+$values[$i][$import[$header[$k]]];
						$content[$i]['row'][$k]['value'] 	= number_format($values[$i][$import[$header[$k]]], 2, ',', '');
					}
				}
			}



			$data = array
			(
				'artid'					=> $values[0]['artid'],
				'lang_type'				=> lang('Type'),
				'project_id'				=> $values[0]['project_id'],
				'lang_project_id'			=> lang('Project id'),
				'lang_vendor'				=> lang('Vendor'),
				'vendor_name'				=> $values[0]['vendor_name'],
				'spvend_code'				=> $values[0]['spvend_code'],
				'lang_fakturadato'			=> lang('invoice date'),
				'fakturadato'				=> $values[0]['fakturadato'],
				'lang_forfallsdato'			=> lang('Payment date'),
				'forfallsdato'				=> $values[0]['forfallsdato'],
				'lang_janitor'				=> lang('Janitor'),
				'oppsynsmannid'				=> $values[0]['oppsynsmannid'],
				'lang_supervisor'			=> lang('Supervisor'),
				'saksbehandlerid'			=> $values[0]['saksbehandlerid'],
				'lang_budget_responsible'		=> lang('Budget Responsible'),
				'budsjettansvarligid'			=> $values[0]['budsjettansvarligid'],
				'lang_sum'				=> lang('Sum'),
				'sum'					=> number_format($values[0]['amount'], 2, ',', ''),
				'table_header'				=> $table_header,
				'values'				=> $content,
				'table_add'				=> $table_add
			);

//_debug_array($data);
			$appname						= lang('Invoice');
			$function_msg					= lang('Add invoice: Debug');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('debug' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
		}

		function view_order()
		{
			$order_id	= get_var('order_id',array('POST','GET'));
			$soXport    = CreateObject($this->currentapp.'.soXport');

			$order_type = $soXport->check_order(intval($order_id));
			switch($order_type)
			{
				case 'workorder':
					$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uiwo_hour.view', 'no_email'=> true, 'show_cost'=> true, 'workorder_id'=> $order_id));
					break;
				case 's_agreement':
					$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uis_agreement.view', 'id'=> $order_id));
					break;
			}
		}
	}
?>