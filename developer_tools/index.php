<?php
	/**************************************************************************\
	* phpGroupWare - Developer Tools                                           *
	* http://www.phpgroupware.org                                              *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: index.php,v 1.2 2003/02/22 13:48:08 ralfbecker Exp $ */

	$phpgw_info['flags'] = array(
		'currentapp'              => 'developer_tools'
	);
	include('../header.inc.php');
	include(PHPGW_APP_INC.'/header.inc.php');

	$phpgw->common->phpgw_footer();