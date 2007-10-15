<?php
	/**************************************************************************\
	* phpGroupWare - Inventory                                                 *
	* http://www.phpgroupware.org                                              *
	* Written by Bettina Gille [ceb@phpgroupware.org]                          *
	* -----------------------------------------------                          *
	* This program is free software; you can redistribute it and/or modify it  *
	* under the terms of the GNU General Public License as published by the    *
	* Free Software Foundation; either version 2 of the License, or (at your   *
	* option) any later version.                                               *
	\**************************************************************************/
	/* $Id: order_archiv.php,v 1.8 2001/07/03 14:44:51 bettina Exp $ */
  
	$phpgw_info['flags'] = array('currentapp' => 'inv',
					'enable_nextmatchs_class' => True);

	include('../header.inc.php');

	$inventory = CreateObject('inv.inventory');  
	$grants = $phpgw->acl->get_grants('inv');
	$grants[$phpgw_info['user']['account_id']] = PHPGW_ACL_READ + PHPGW_ACL_ADD + PHPGW_ACL_EDIT + PHPGW_ACL_DELETE;

	$t = CreateObject('phpgwapi.Template',PHPGW_APP_TPL);  
	$t->set_file(array('order_list_t' => 'listorders.tpl',                                                                                                                
						'order_list' => 'listorders.tpl'));
	$t->set_block('order_list_t','order_list','list'); 

	if (! $start) { $start = 0; }

	if (!$filter) { $filter = 'none'; }

	$orders = $inventory->read_orders($start,True,$query,$filter,$sort,$order,'archive');

//--------------------------------- nextmatch --------------------------------------------

	$left = $phpgw->nextmatchs->left('/inv/order_archiv.php',$start,$inventory->total_records,'&subarchive=True');
	$right = $phpgw->nextmatchs->right('/inv/order_archiv.php',$start,$inventory->total_records,'&subarchive=True');
	$t->set_var('left',$left);
	$t->set_var('right',$right);

    $t->set_var('lang_showing',$phpgw->nextmatchs->show_hits($inventory->total_records,$start));

// ------------------------------ end nextmatch ------------------------------------------

	$t->set_var('title_action',lang('Order archive'));
	$t->set_var('action','');
	$t->set_var('lang_search',lang('Search'));
	$t->set_var('search_action',$phpgw->link('/inv/order_archiv.php','&subarchive=True'));

// -------------------------- header declaration ------------------------------------------

	$t->set_var('th_bg',$phpgw_info['theme']['th_bg']);
	$t->set_var('sort_num',$phpgw->nextmatchs->show_sort_order($sort,'num',$order,'/inv/order_archiv.php',lang('Order ID'),'&subarchive=True'));
	$t->set_var('sort_descr',$phpgw->nextmatchs->show_sort_order($sort,'descr',$order,'/inv/order_archiv.php',lang('Description'),'&subarchive=True'));
	$t->set_var('sort_date',$phpgw->nextmatchs->show_sort_order($sort,'date',$order,'/inv/order_archiv.php',lang('Date'),'&subarchive=True'));
	$t->set_var('sort_status',$phpgw->nextmatchs->show_sort_order($sort,'status',$order,'/inv/order_archiv.php',lang('Status'),'&subarchive=True'));
	$t->set_var('sort_customer',$phpgw->nextmatchs->show_sort_order($sort,'customer',$order,'/inv/order_archiv.php',lang('Customer'),'&subarchive=True'));
	$t->set_var('lang_products',lang('Products'));
	$t->set_var('lang_delivery',lang('Delivery'));
	$t->set_var('lang_invoice',lang('Invoice'));
	$t->set_var('lang_edit',lang('Edit'));
	$t->set_var('lang_delete',lang('Delete'));

// ---------------------------- end header declaration ------------------------------------

	$d = CreateObject('phpgwapi.contacts');
	for ($i=0;$i<count($orders);$i++)
	{
		$id = $orders[$i]['id'];
		$owner = $orders[$i]['owner'];
		$tr_color = $phpgw->nextmatchs->alternate_row_color($tr_color);
		$t->set_var('tr_color',$tr_color);
		$num = $phpgw->strip_html($orders[$i]['num']);

		$date = $orders[$i]['date'];

		$month = $phpgw->common->show_date(time(),'n');
		$day = $phpgw->common->show_date(time(),'d');
		$year = $phpgw->common->show_date(time(),'Y');

		$date = $date + (60*60) * $phpgw_info['user']['preferences']['common']['tz_offset'];
		$dateout = $phpgw->common->show_date($date,$phpgw_info['user']['preferences']['common']['dateformat']);

		$descr = $phpgw->strip_html($orders[$i]['descr']);
		if (!$descr) { $descr = '&nbsp;'; }

		$ab_id = $orders[$i]['customer'];
		if (!$ab_id)
		{
			$customerout = '&nbsp;';
		}
		else
		{
			$cols = array('n_given' => 'n_given',
						'n_family' => 'n_family',
						'org_name' => 'org_name');
			$entry = $d->read_single_entry($ab_id,$cols);
        	if ($customer[0]['org_name'] == '')
			{
				$customerout = $customer[0]['n_given'] . ' ' . $customer[0]['n_family'];
			}
			else
			{
				$customerout = $entry[0]['org_name'] . ' [ ' . $entry[0]['n_given'] . ' ' . $entry[0]['n_family'] . ' ]';
			}
		}

		$status = $orders[$i]['status'];
		$statusout = lang($status);

//--------- template declaration for list records--------------------

		$t->set_var(array('num' => $num,
						'date' => $dateout,
						'descr' => $descr,
						'customer' => $customerout,
						'status' => $statusout));

		$t->set_var('products',$phpgw->link('/inv/orderproducts.php','order_id=' . $id . '&subarchive=True'));
		$t->set_var('delivery',$phpgw->link('/inv/delivery.php','order_id=' . $id . '&subarchive=True'));
		$t->set_var('invoice',$phpgw->link('/inv/invoice.php','order_id=' . $id . '&subarchive=True'));

		if ($inventory->check_perms($grants[$owner],PHPGW_ACL_EDIT) || $owner == $phpgw_info['user']['account_id'])
		{
			$t->set_var('edit',$phpgw->link('/inv/editorder.php','id=' . $id . '&subarchive=True'));
			$t->set_var('lang_edit_entry',lang('Edit'));
		}
		else
		{
			$t->set_var('edit','');
			$t->set_var('lang_edit_entry','&nbsp;');
		}

		$t->parse('list','order_list',True);
	}

//-------- -------------- end record declaration ------------------------

	$t->parse('out','order_list_t',True);
	$t->p('out');

	$phpgw->common->phpgw_footer();
?>