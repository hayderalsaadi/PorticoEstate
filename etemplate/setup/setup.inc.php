<?php
	/**************************************************************************\
	* phpGroupWare - Editable Templates                                        *
	* http://www.phpgroupware.org                                              *
	" Written by Ralf Becker <RalfBecker@outdoor-training.de>                  *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: setup.inc.php,v 1.18 2007/02/11 21:58:04 sigurdne Exp $ */

	$setup_info['etemplate']['name']      = 'etemplate';
	$setup_info['etemplate']['version']   = '0.9.15.002';
	$setup_info['etemplate']['app_order'] = 8;	// just behind the developers-tools
	$setup_info['etemplate']['tables']    = array('phpgw_etemplate');
	$setup_info['etemplate']['enable']    = 1;

	$setup_info['etemplate']['author'] = 
 	$setup_info['etemplate']['maintainer'] = array(
		'name'  => 'phpGroupWare Coordination Team',
		'email' => 'phpgroupware-developers@gnu.org'
	);
	$setup_info['etemplate']['license']   = 'GPL';
	$setup_info['etemplate']['description'] =
		'<b>eTemplates</b> are a new widget-based template system for phpGroupWare with an
		interactive editor and a database table-editor (creates tables_current.inc.php and
		updates automaticaly tables_update.inc.php).';
	$setup_info['etemplate']['note'] =
		'For <b>more information</b> check out the <a href="etemplate/doc/etemplate.html" target="_blank">Tutorial</a>, 
		the <a href="etemplate/doc/referenz.html" target="_blank">Referenz Documentation</a> 
		or the <a href="http://docs.phpgroupware.org/wiki/etemplate" target="_blank">eTemplate page in our Wiki</a>.';

	/* The hooks this app includes, needed for hooks registration */
	//$setup_info['etemplate']['hooks'][] = 'preferences';
	//$setup_info['etemplate']['hooks'][] = 'admin';
	//$setup_info['etemplate']['hooks'][] = 'about';

	/* Dependencies for this app to work */
	$setup_info['etemplate']['depends'][] = array(
		 'appname' => 'phpgwapi',
		 'versions' => Array('0.9.13','0.9.14','0.9.15','0.9.16','0.9.17')
	);
?>
