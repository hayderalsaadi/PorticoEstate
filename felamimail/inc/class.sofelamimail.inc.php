<?php
	/***************************************************************************\
	* phpGroupWare - FeLaMiMail                                                 *
	* http://www.linux-at-work.de                                               *
	* http://www.phpgw.de                                                       *
	* http://www.phpgroupware.org                                               *
	* Written by : Lars Kneschke [lkneschke@linux-at-work.de]                   *
	* -------------------------------------------------                         *
	* This program is free software; you can redistribute it and/or modify it   *
	* under the terms of the GNU General Public License as published by the     *
	* Free Software Foundation; either version 2 of the License, or (at your    *
	* option) any later version.                                                *
	\***************************************************************************/
	/* $Id: class.sofelamimail.inc.php,v 1.5 2006/12/18 20:03:32 sigurdne Exp $ */

	class sofelamimail
	{
		function sofelamimail()
		{
			#$this->bopreferences	= CreateObject('felamimail.bopreferences');
		}

		function fetchheader($_header)
		{
			$headerRows = explode("\n",$_header);
			for($i=0;$i<count($headerRows);$i++)
			{
				if(preg_match("/^From:(.*)/i",$headerRows[$i],$matches))
					$retValue['from'] = $matches[1];
				if(preg_match("/^to:(.*)/i",$headerRows[$i],$matches))
					$retValue['to'] = $matches[1];
			}
			
			return $retValue;
		}
	}

?>