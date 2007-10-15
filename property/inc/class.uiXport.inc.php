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
	* @subpackage admin
 	* @version $Id: class.uiXport.inc.php,v 1.23 2007/01/26 14:53:47 sigurdne Exp $
	*/

	/**
	 * Description
	 * @package property
	 */

	class property_uiXport
	{
		var $public_functions = array(
			'import' 	=> True,
			'export' 	=> True,
			'rollback'	=> True
		);

		var $start;
		var $limit;
		var $query;
		var $sort;
		var $order;
		var $filter;
		var $cat_id;

		function property_uiXport()
		{

			$GLOBALS['phpgw_info']['flags']['xslt_app'] = True;
			$this->currentapp		= $GLOBALS['phpgw_info']['flags']['currentapp'];
			$this->bo       		= CreateObject($this->currentapp.'.boXport',True);
			$this->invoice  		= CreateObject($this->currentapp.'.boinvoice');
			$this->bocommon  		= CreateObject($this->currentapp.'.bocommon');
			$this->menu			= CreateObject($this->currentapp.'.menu');
			$this->contacts			= CreateObject($this->currentapp.'.soactor');
			$this->contacts->role		= 'vendor';

			$this->acl 			= CreateObject('phpgwapi.acl');
			$this->acl_location		= '.invoice';
			$this->acl_read 		= $this->acl->check('.invoice',1);
			$this->acl_add 			= $this->acl->check('.invoice',2);
			$this->acl_edit 		= $this->acl->check('.invoice',4);
			$this->acl_delete 		= $this->acl->check('.invoice',8);
			$this->acl_manage 		= $this->acl->check('.invoice',16);

			$this->start    		= $this->bo->start;
			$this->limit    		= $this->bo->limit;
			$this->query    		= $this->bo->query;
			$this->sort     		= $this->bo->sort;
			$this->order    		= $this->bo->order;
			$this->filter   		= $this->bo->filter;
			$this->cat_id   		= $this->bo->cat_id;
			$this->menu->sub		='invoice';
		}

		function import()
		{
			if(!$this->acl_add)
			{
				$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uilocation.stop', 'perm'=>2, 'acl_location'=> $this->acl_location));
			}

			$receipt = $GLOBALS['phpgw']->session->appsession('session_data','import_receipt');
			$GLOBALS['phpgw']->session->appsession('session_data','import_receipt','');

			$art				= get_var('art',array('POST','GET'));
			$type				= get_var('type',array('POST','GET'));
			$dim_b				= get_var('dim_b',array('POST','GET'));
			$invoice_num			= get_var('invoice_num',array('POST','GET'));
			$kid_nr				= get_var('kid_nr',array('POST','GET'));
			$vendor_id			= get_var('vendor_id',array('POST','GET'));
			$vendor_name			= get_var('vendor_name',array('POST','GET'));
			$janitor			= get_var('janitor',array('POST','GET'));
			$supervisor			= get_var('supervisor',array('POST','GET'));
			$budget_responsible		= get_var('budget_responsible',array('POST','GET'));
			$invoice_date 			= urldecode(get_var('invoice_date',array('POST','GET')));
			$num_days			= get_var('num_days',array('POST','GET'));
			$payment_date 			= urldecode(get_var('payment_date',array('POST','GET')));
			$cancel 			= get_var('cancel',array('POST','GET'));
			$convert 			= get_var('convert',array('POST','GET'));
			$conv_type 			= get_var('conv_type',array('POST','GET'));
			$sday 				= get_var('sday',array('POST','GET'));
			$smonth 			= get_var('smonth',array('POST','GET'));
			$syear 				= get_var('syear',array('POST','GET'));
			$eday 				= get_var('eday',array('POST','GET'));
			$emonth 			= get_var('emonth',array('POST','GET'));
			$eyear 				= get_var('eyear',array('POST','GET'));
			$download 			= get_var('download',array('POST','GET'));
			$auto_tax 			= get_var('auto_tax',array('POST','GET'));

			$tsvfile = $_FILES['tsvfile']['tmp_name'];

			if(!$tsvfile)
			{
				$tsvfile = get_var('tsvfile',array('POST','GET'));
			}

			$links = $this->menu->links('import_inv');

			if ($cancel && $tsvfile)
			{
				unlink ($tsvfile);
			}

			if ($convert)
			{
				unset($receipt);

				if ($conv_type=='')
				{
					$receipt['error'][] = array('msg'=>lang('Please - select a import format !'));
				}

				if (!$tsvfile)
				{
					$receipt['error'][] = array('msg'=>lang('Please - select a file to import from !'));
				}

				if (!$art)
				{
					$receipt['error'][] = array('msg'=>lang('Please - select type invoice!'));
				}
				if (!$vendor_id)
				{
					$receipt['error'][] = array('msg'=>lang('Please - select Vendor!'));
				}

				if (!$type)
				{
					$receipt['error'][] = array('msg'=>lang('Please - select type order!'));
				}

				if (!$budget_responsible)
				{
					$receipt['error'][] = array('msg'=>lang('Please - select budget responsible!'));
				}

				if (!$this->invoice->check_vendor($vendor_id))
				{
					$receipt['error'][] = array('msg'=>lang('That Vendor ID is not valid !'). ' : ' . $vendor_id);
				}

				if (!$payment_date && !$num_days)
				{
					$receipt['error'][] = array('msg'=>lang('Please - select either payment date or number of days from invoice date !'));
				}

				if (!file_exists($tsvfile))
				{
					$receipt['error'][] = array('msg'=>lang('The file is empty or removed!'));
				}
				if (!is_array($receipt['error']))
				{

					$dateformat = strtolower($GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
					$dateformat = str_replace(".","",$dateformat);
					$dateformat = str_replace("-","",$dateformat);
					$dateformat = str_replace("/","",$dateformat);
					$y=strpos($dateformat,'y');
					$d=strpos($dateformat,'d');
					$m=strpos($dateformat,'m');

					if($invoice_date)
					{
			 			$dateparts = explode('/', $invoice_date);
			 			$sday = $dateparts[$d];
			 			$smonth = $dateparts[$m];
			 			$syear = $dateparts[$y];

			 			$dateparts = explode('/', $payment_date);
			 			$eday = $dateparts[$d];
			 			$emonth = $dateparts[$m];
			 			$eyear = $dateparts[$y];
					}

					$old = $tsvfile;
					$tsvfile = $GLOBALS['phpgw_info']['server']['temp_dir'].'/invoice_import_'.basename($tsvfile);
					rename($old,$tsvfile);

					$invoice_common=array(
								'bilagsnr'			=> $this->invoice->next_bilagsnr(),
								'art'				=> $art,
								'type'				=> $type,
								'dim_b'				=> $dim_b,
								'invoice_num'			=> $invoice_num,
								'kid_nr'			=> $kid_nr,
								'vendor_id'			=> $vendor_id,
								'vendor_name'			=> $vendor_name,
								'janitor'			=> $janitor,
								'supervisor'			=> $supervisor,
								'budget_responsible'		=> $budget_responsible,
								'num_days'			=> $num_days,
								'sday'				=> $sday,
								'smonth'			=> $smonth,
								'syear'				=> $syear,
								'eday'				=> $eday,
								'emonth'			=> $emonth,
								'eyear'				=> $eyear,
								'tsvfile'			=> $tsvfile,
								'conv_type'			=> $conv_type,
								'invoice_date'			=> $invoice_date,
								'payment_date'			=> $payment_date,
								'auto_tax'			=> $auto_tax
							);

					$buffer = $this->bo->import($invoice_common,$download);

					if(!$download)
					{
						$receipt = $buffer;
						$GLOBALS['phpgw']->session->appsession('session_data','import_receipt',$receipt);
						unlink ($tsvfile);
						unset($invoice_common);
						unset($art);
						unset($type);
						unset($dim_b);
						unset($invoice_num);
						unset($kid_nr);
						unset($vendor_id);
						unset($vendor_name);
						unset($janitor);
						unset($supervisor);
						unset($budget_responsible);
						unset($invoice_date);
						unset($num_days);
						unset($payment_date);
						unset($conv_type);
						unset($auto_tax);
//						$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uiXport.import'));
					}
					else
					{
						$this->debug_import($buffer,$invoice_common);
						return;
					}
				}
			}


			set_time_limit(0);

			$link_data = array
			(
				'menuaction'	=> $this->currentapp.'.uiXport.import',
				'sub'		=> $sub
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

				'img_cal'						=> $GLOBALS['phpgw']->common->image('phpgwapi','cal'),
				'lang_datetitle'				=> lang('Select date'),

				'form_action'					=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'cancel_action'					=> $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiinvoice.index', 'sub'=> $sub)),
				'lang_cancel'					=> lang('Cancel'),
				'lang_cancel_statustext'			=> lang('cancel the import'),
				'action_url'					=> $GLOBALS['phpgw']->link('/index.php',array('menuaction'=>  $this->currentapp .'.uiXport.import')),
				'tsvfilename'					=> '',

				'lang_debug'					=> lang('Debug output in browser'),
				'lang_debug_statustext'				=> lang('Check this to have the output to screen before import (recommended)'),
				'value_debug'					=> $download,

				'lang_import'					=> lang('Import'),
				'lang_import_statustext'			=> lang('Klick this button to start the import'),

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
				'lang_file_statustext'				=> lang('Select the file to import from'),
				'lang_vendor_statustext'			=> lang('Select the vendor by klicking the button'),
				'lang_vendor_name_statustext'			=> lang('Select the vendor by klicking the button'),
				'lang_select_vendor_statustext'			=> lang('Select the vendor by klicking this button'),


				'value_invoice_date'				=> $invoice_date,
				'value_payment_date'				=> $payment_date,
				'value_belop'					=> $belop,
				'value_vendor_id'				=> $vendor_id,
				'value_vendor_name'				=> $vendor_name,
				'value_kid_nr'					=> $kid_nr,
				'value_dim_b'					=> $dim_b,
				'value_invoice_num'				=> $invoice_num,
				'value_merknad'					=> $merknad,
				'value_num_days'				=> $num_days,
//				'value_tsvfile'					=> $tsvfile,

				'lang_file'					=> lang('File'),
				'lang_conv'					=> lang('Conversion'),
				'conv_list'					=> $this->bo->select_import_conv($conv_type),
				'select_conv'					=> 'conv_type',
				'lang_select_conversion'			=> lang('Select the type of conversion:'),
				'lang_conv_statustext'				=> lang('You have to select the Conversion for this import'),

				'lang_auto_tax'					=> lang('Auto TAX'),
				'lang_auto_tax_statustext'			=> lang('Set tax during import'),

				'lang_art'					=> lang('Art'),
				'art_list'					=> $this->invoice->get_lisfm_ecoart($art),
				'select_art'					=> 'art',
				'lang_select_art' 				=> lang('Select Invoice Type'),
				'lang_art_statustext'				=> lang('You have to select type of invoice'),

				'lang_type'					=> lang('Type invoice II'),
				'type_list'					=> $this->invoice->get_type_list($type),
				'select_type'					=> 'type',
				'lang_no_type'					=> lang('No type'),
				'lang_type_statustext'				=> lang('Select the type  invoice. To do not use type -  select NO TYPE'),

				'lang_dimb'					=> lang('Dim B'),
				'dimb_list'					=> $this->invoice->select_dimb_list($dim_b),
				'select_dimb'					=> 'dim_b',
				'lang_no_dimb'					=> lang('No Dim B'),
				'lang_dimb_statustext'				=> lang('Select the Dim B for this invoice. To do not use Dim B -  select NO DIM B'),

				'lang_janitor'					=> lang('Janitor'),
				'janitor_list'					=> $this->bocommon->get_user_list_right(32,$janitor,'.invoice'),
				'select_janitor'				=> 'janitor',
				'lang_no_janitor'				=> lang('No janitor'),
				'lang_janitor_statustext'			=> lang('Select the janitor responsible for this invoice. To do not use janitor -  select NO JANITOR'),

				'lang_supervisor'				=> lang('Supervisor'),
				'supervisor_list'				=> $this->bocommon->get_user_list_right(64,$supervisor,'.invoice'),
				'select_supervisor'				=> 'supervisor',
				'lang_no_supervisor'				=> lang('No supervisor'),
				'lang_supervisor_statustext'			=> lang('Select the supervisor responsible for this invoice. To do not use supervisor -  select NO SUPERVISOR'),

				'lang_budget_responsible'			=> lang('B - responsible'),
				'budget_responsible_list'			=> $this->bocommon->get_user_list_right(128,$budget_responsible,'.invoice'),
				'select_budget_responsible'			=> 'budget_responsible',
				'lang_select_budget_responsible'		=> lang('Select B-Responsible'),
				'lang_budget_responsible_statustext'		=> lang('You have to select a budget responsible for this invoice in order to make the import')
			);

			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice','menu'));

			$appname	= lang('Invoice');
			$function_msg	= lang('Import from CSV');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;
			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('import' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
		}

		function debug_import($buffer='',$invoice_common='')
		{
			$table	= $buffer['table'];
			$header	= $buffer['header'];
			$import	= $buffer['import'];

			$sum=0;

			$import_count = count($import);
			$table_count = count($table);
			for ($i=0; $i<$table_count; $i++)
			{
				for ($k=0; $k<$import_count; $k++)
				{
					$content[$i]['row'][$k]['value'] 	= $table[$i][$import[$header[$k]]];
					if ($import[$header[$k]]=='belop')
					{
						$content[$i]['row'][$k]['align'] 	= 'right';
						$sum=$sum+$table[$i][$import[$header[$k]]];
						$content[$i]['row'][$k]['value'] 	= number_format($table[$i][$import[$header[$k]]], 2, ',', '');
					}
				}
			}

			for ($k=0; $k<count($header); $k++)
			{
				$table_header[$k]['header'] 	= $header[$k];
				$table_header[$k]['width'] 		= '5%';
				$table_header[$k]['align'] 		= 'center';
			}


			$link_data_add = array
			(
				'menuaction'	=> $this->currentapp.'.uiXport.import',
				'convert'	=> 'true'
			);

			$link_data_cancel = array
			(
				'menuaction'	=> $this->currentapp.'.uiXport.import',
				'cancel'	=> True

			);

			$link_data_add		= $link_data_add + $invoice_common;
			$link_data_cancel	= $link_data_cancel + $invoice_common;


			$table_add[] = array
			(
				'lang_add'		=> lang('Import'),
				'lang_add_statustext'	=> lang('Import this invoice'),
				'add_action'		=> $GLOBALS['phpgw']->link('/index.php',$link_data_add),
				'lang_cancel'		=> lang('cancel'),
				'lang_cancel_statustext'=> lang('Do not import this invoice'),
				'cancel_action'		=> $GLOBALS['phpgw']->link('/index.php',$link_data_cancel)

			);

			$vendor	= $this->contacts->read_single(array('actor_id'=>$table[0]['spvend_code']));
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

			$data = array
			(
				'artid'							=> $table[0]['artid'],
				'lang_type'						=> lang('Type'),
				'artid'							=> $table[0]['artid'],
				'lang_bilagsnr'					=> lang('bilagsnr'),
				'bilagsnr'						=> $table[0]['bilagsnr'],
				'lang_vendor'					=> lang('Vendor'),
				'vendor_name'					=> $vendor_name,
				'spvend_code'					=> $table[0]['spvend_code'],
				'lang_fakturadato'				=> lang('invoice date'),
				'fakturadato'					=> $table[0]['fakturadato'],
				'lang_forfallsdato'				=> lang('Payment date'),
				'forfallsdato'					=> $table[0]['forfallsdato'],
				'lang_janitor'					=> lang('Janitor'),
				'oppsynsmannid'					=> $table[0]['oppsynsmannid'],
				'lang_supervisor'				=> lang('Supervisor'),
				'saksbehandlerid'				=> $table[0]['saksbehandlerid'],
				'lang_budget_responsible'		=> lang('Budget Responsible'),
				'budsjettansvarligid'			=> $table[0]['budsjettansvarligid'],
				'lang_sum'						=> lang('Sum'),
				'sum'							=> number_format($sum, 2, ',', ''),
				'table_header'					=> $table_header,
				'values'						=> $content,
				'table_add'						=> $table_add
			);

			unset($content);

			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice','table_header'));
			$appname						= lang('Invoice');
			$function_msg	= lang('Debug');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;

			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('debug' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
		}

		function export()
		{
			if(!$this->acl_manage)
			{
				$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uilocation.stop', 'perm'=>16, 'acl_location'=> $this->acl_location));
			}

			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice','menu',
										'search_field'));

			$values 	= get_var('values',array('POST','GET'));
			$date 	= get_var('date',array('POST','GET'));

			$links = $this->menu->links('export_inv');

			if($values['submit'])
			{
				if (!$values['conv_type'] && !$values['file'] ):
				{

					$receipt['error'][] =  array('msg'=> lang('No conversion type could be located.') . ' - ' . lang('Please choose a conversion type from the list'));
				}
				elseif($values['conv_type'] && !$values['file']):
				{
					$receipt = $this->bo->export(array('conv_type'=>$values['conv_type'],'download'=>$values['download'],'force_period_year'=>$values['force_period_year']));
					if(!$values['download'])
					{
						$GLOBALS['phpgw_info']['flags'][noheader] = True;
						$GLOBALS['phpgw_info']['flags'][nofooter] = True;
						$GLOBALS['phpgw_info']['flags']['xslt_app'] = False;
						$GLOBALS['phpgw_info']['flags']['noframework'] = True;
						echo '<pre>' . $receipt['message'][0]['msg'] . '</pre>';
						echo '&nbsp<a href="'.$GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiXport.export')) . '">' . lang('Back') . '</a>';
					}
				}
				endif;
			}
			else
			{
				$date = $GLOBALS['phpgw']->common->show_date(mktime(0,0,0,date("m"),date("d"),date("Y")),$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
			}
//_debug_array($receipt);

			$link_data = array
			(
				'menuaction'		=> $this->currentapp.'.uiXport.export',
				'invoice_id'		=> $invoice_id,
				'sub'			=> $sub);

			$msgbox_data = $this->bocommon->msgbox_data($receipt);

			$force_period_year[0]['id'] = date(Y);
			$force_period_year[1]['id'] = date(Y) -1;


			$data = array
			(
				'msgbox_data'				=> $GLOBALS['phpgw']->common->msgbox($msgbox_data),
				'force_period_year'			=> $force_period_year,
				'lang_force_period_year'		=> lang('Force year for period'),
				'lang_force_period_year_statustext'	=> lang('Force year for period'),
				'lang_select_year'			=> lang('select year'),
				'links'					=> $links,
				'lang_select_conv'			=> lang('Select conversion'),
				'conv_list'				=> $this->bo->select_export_conv($values['conv_type']),
				'select_conv'				=> 'values[conv_type]',
				'lang_conv_statustext'			=> lang('Select conversion'),

				'lang_rollback_file'			=> lang('Roll back'),
				'link_rollback_file'			=> $GLOBALS['phpgw']->link('/index.php',array('menuaction'=> $this->currentapp.'.uiXport.rollback')),

				'lang_export_to_file'			=> lang('Export to file'),
				'value_debug'				=> $values['debug'],
				'lang_debug_statustext'			=> lang('Uncheck to debug the result'),

				'lang_submit'				=> lang('Submit'),
				'lang_cancel'				=> lang('Cancel'),

				'message'				=> $message,
				'form_action'				=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'lang_save'				=> lang('save')
			);

//_debug_array($data);
			$appname	= lang('Invoice');
			$function_msg	= lang('Export invoice');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;

			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('export' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
		}

		function rollback()
		{
			if(!$this->acl_manage)
			{
				$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction'=> $this->currentapp.'.uilocation.stop', 'perm'=>16, 'acl_location'=> $this->acl_location));
			}

			$GLOBALS['phpgw']->xslttpl->add_file(array('invoice','menu',
										'search_field'));

			$values 	= get_var('values',array('POST','GET'));
			$date 	= get_var('date',array('POST','GET'));
//_debug_array($values);

			$links = $this->menu->links('export_inv');

			if($values['submit'])
			{
				if (!$values['conv_type'])
				{
					$receipt['error'][] = array('msg'=> lang('No conversion type could be located.') .' - ' . lang('Please choose a conversion type from the list'));
				}

				if(!$values['file'])
				{
					$receipt['error'][] = array('msg'=>lang('Please choose a file'));
				}

				if(!$receipt['error'])
				{
					$receipt = $this->bo->rollback($values['conv_type'],$values['file'],$date);
				}
			}
			else
			{
				$date = $GLOBALS['phpgw']->common->show_date(mktime(0,0,0,date("m"),date("d"),date("Y")),$GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
			}

			$link_data = array('menuaction'	=> $this->currentapp.'.uiXport.rollback');

			$dateformat = strtolower($GLOBALS['phpgw_info']['user']['preferences']['common']['dateformat']);
			$sep = '/';
			$dlarr[strpos($dateformat,'y')] = 'yyyy';
			$dlarr[strpos($dateformat,'m')] = 'MM';
			$dlarr[strpos($dateformat,'d')] = 'DD';
			ksort($dlarr);

			$dateformat= (implode($sep,$dlarr));

//_debug_array($receipt);
			$jscal = CreateObject('phpgwapi.jscalendar');
			$jscal->add_listener('date');

			$msgbox_data = $this->bocommon->msgbox_data($receipt);

			$data = array
			(
				'msgbox_data'				=> $GLOBALS['phpgw']->common->msgbox($msgbox_data),
				'links'					=> $links,

				'img_cal'					=> $GLOBALS['phpgw']->common->image('phpgwapi','cal'),
				'lang_datetitle'			=> lang('Select date'),
				'calendar_setup'			=> "Calendar.setup({inputField  : 'date',ifFormat  : '" . $jsDateFormat . "',button : 'date-trigger'});",

				'lang_select_conv'			=> lang('Select conversion'),
				'conv_list'				=> $this->bo->select_export_conv($values['conv_type']),
				'select_conv'				=> 'values[conv_type]',
				'lang_conv_statustext'			=> lang('Select conversion'),

				'lang_select_file'			=> lang('Select file to roll back'),
				'lang_no_file'				=> lang('No file selected'),
				'lang_file_statustext'			=> lang('Select file to roll back'),
				'select_file'				=> 'values[file]',

				'rollback_file_list'			=> $this->bo->select_rollback_file($values['file']),
				'lang_export_to_file'			=> lang('Export to file'),
				'value_debug'				=> $values['debug'],

				'value_date'				=> $date,
				'lang_date'				=> lang('Export date'),
				'lang_date_statustext'			=> lang('Select date for the file to roll back'),

				'lang_submit'				=> lang('Submit'),
				'lang_cancel'				=> lang('Cancel'),

				'form_action'				=> $GLOBALS['phpgw']->link('/index.php',$link_data),
				'lang_save'				=> lang('save')
			);

//_debug_array($data);

			$appname		= lang('Invoice');
			$function_msg		= lang('Rollback invoice');

			$GLOBALS['phpgw_info']['flags']['app_header'] = lang($this->currentapp) . ' - ' . $appname . ': ' . $function_msg;

			$GLOBALS['phpgw']->xslttpl->set_var('phpgw',array('rollback' => $data));
		//	$GLOBALS['phpgw']->xslttpl->pp();
		}
	}
?>