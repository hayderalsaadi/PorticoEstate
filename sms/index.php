<?php
	/**
	/**
	$currentapp='sms';
	include('../header.inc.php');
	$start_page = 'sms';

	if ( isset($GLOBALS['phpgw_info']['user']['preferences']['property']['default_start_page'])
		&& $GLOBALS['phpgw_info']['user']['preferences']['property']['default_start_page'] )
	{
		$start_page = $GLOBALS['phpgw_info']['user']['preferences']['property']['default_start_page'];
	}

	$GLOBALS['phpgw']->redirect_link('/index.php',array('menuaction' => "sms.ui{$start_page}.index"));