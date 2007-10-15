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
	* @subpackage custom
 	* @version $Id: lag_lang_filer.php,v 1.2 2007/03/16 08:57:05 sigurdne Exp $
	*/

	/**
	 * Description
	 * @package property
	 */

	class lag_lang_filer
	{
		var	$function_name = 'lag_lang_filer';

		function lag_lang_filer()
		{
			$this->currentapp	= $GLOBALS['phpgw_info']['flags']['currentapp'];
			$this->bocommon		= CreateObject($this->currentapp.'.bocommon');
			$this->db 		= $this->bocommon->new_db();
		}
		
		function pre_run($data='')
		{
			if($data['enabled']==1)
			{
				$confirm	= True;
				$cron		= True;
			}
			else
			{
				$confirm	= get_var('confirm',array('POST'));
				$execute	= get_var('execute',array('GET'));
			}
			if ($confirm)
			{
				$this->execute($cron);
			}
			else
			{
			$lang_yes			= lang('yes');
			$GLOBALS['phpgw']->xslttpl->add_file(array('confirm_custom'));
			$msgbox_data = $this->bocommon->msgbox_data($this->receipt);
			$data = array