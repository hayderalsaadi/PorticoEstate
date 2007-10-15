<?php
 /**********************************************************************\
 * phpGroupWare - InfoLog						*
 * http://www.phpgroupware.org						*
 * This program is part of the GNU project, see http://www.gnu.org/	*
 *									*
 * Copyright 2002, 2003 Free Software Foundation, Inc.			*
 *									*
 * Originally Written by Ralf Becker - <RalfBecker@outdoor-training.de>	*
 * Based on ToDo Written by Joseph Engo <jengo at phpgroupware.org>	*
 * --------------------------------------------				*
 * This program is Free Software; you can redistribute it and/or modify *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2 of the License, or 	*
 * at your option) any later version.					*
 \**********************************************************************/
 /* $Id: hook_home.inc.php,v 1.12 2007/02/13 15:02:07 sigurdne Exp $ */

	if ( isset($GLOBALS['phpgw_info']['user']['preferences']['infolog']['homeShowEvents'])
		&& $GLOBALS['phpgw_info']['user']['preferences']['infolog']['homeShowEvents'] )
	{
		$save_app = $GLOBALS['phpgw_info']['flags']['currentapp'];
		$GLOBALS['phpgw_info']['flags']['currentapp'] = 'infolog';

		$GLOBALS['phpgw']->translation->add_app('infolog');

		$app_id = $GLOBALS['phpgw']->applications->name2id('infolog');
		$GLOBALS['portal_order'][] = $app_id;

		$infolog = CreateObject('infolog.uiinfolog');
		$html = $infolog->index(array('nm' => array('filter' => 'own-open-today')),'','',0,False,True);
		$title = lang('InfoLog').' - '.lang($infolog->filters['own-open-today']);
		$stable = $infolog->tmpl->stable;
		unset($infolog);

		if ($stable)	// .14/6
		{
			$portalbox = CreateObject('phpgwapi.listbox',array(
				'title'     => $title,
				'primary'   => $GLOBALS['phpgw_info']['theme']['navbar_bg'],
				'secondary' => $GLOBALS['phpgw_info']['theme']['navbar_bg'],
				'tertiary'  => $GLOBALS['phpgw_info']['theme']['navbar_bg'],
				'width'     => '100%',
				'outerborderwidth' => '0',
				'header_background_image' => $GLOBALS['phpgw']->common->image('phpgwapi','bg_filler', '.png', False)
			));
			foreach(array(
				'up'       => Array('url' => '/set_box.php', 'app' => $app_id),
				'down'     => Array('url' => '/set_box.php', 'app' => $app_id),
				'close'    => Array('url' => '/set_box.php', 'app' => $app_id),
				'question' => Array('url' => '/set_box.php', 'app' => $app_id),
				'edit'     => Array('url' => '/set_box.php', 'app' => $app_id)
			) as $key => $value)
			{
				$portalbox->set_controls($key,$value);
			}
			$portalbox->data = $data;

			echo "\n<!-- BEGIN InfoLog info -->\n".$portalbox->draw($html)."\n<!-- END InfoLog info -->\n";
			unset($portalbox);
		}
		else	// HEAD / XSLT
		{
			$GLOBALS['phpgw']->portalbox->set_params(array(
				'app_id' => $app_id,
				'title'  => $title
			));
			$GLOBALS['phpgw']->portalbox->draw($html);
		}
		unset($html);
		$GLOBALS['phpgw_info']['flags']['currentapp'] = $save_app;
	}
?>