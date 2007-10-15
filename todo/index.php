<?php
	/**
	* Todo
	*
	* @author Joseph Engo <jengo@phpgroupware.org>
	* @copyright Copyright (C) 2000-2005 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @package todo
	* @version $Id: index.php,v 1.77 2006/07/23 10:24:38 skwashd Exp $
	*/

	$GLOBALS['phpgw_info']['flags'] = array
	(
		'currentapp' => 'todo',
		'noheader'   => True,
		'nonavbar'   => True
	);
	
	/**
	 * Include phpgroupware header
	 */
	include('../header.inc.php');

	$GLOBALS['phpgw']->redirect_link('/index.php', array('menuaction' => 'todo.uitodo.show_list'));
?>