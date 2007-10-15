<?php
/**
* eTemplate - basic application development environment
* @copyright Copyright (C) 2002-2006 Free Software Foundation, Inc. http://www.fsf.org/
* @author Ralf Becker <ralf.becker@outdoortraining.de>
* @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
* @package etemplate
* @version $Id: class.boetemplate.inc.php,v 1.45 2007/02/13 19:52:04 sigurdne Exp $
*/
	include_once(PHPGW_INCLUDE_ROOT . '/etemplate/inc/class.soetemplate.inc.php');

	/**
	 * Buiseness Objects for eTemplates
	*
	 * Not so much so far, as the most logic is still in the UI-class
	 * @param $types,$alings converts internal names/values to (more) human readible ones
	 */
	class boetemplate extends soetemplate
	{
		var $extensions = array();

		var $types = array(
			'label'	=> 'Label',			// Label $cell['label'] is (to be translated) textual content
			'text'	=> 'Text',			// Textfield 1 Line (size = [length][,maxlength])
			'int'		=> 'Integer',		// like text, but only numbers (size = [min][,max])
			'float'	=> 'Floating Point', // --------------- " --------------------------
			'textarea'=> 'Textarea',	// Multiline Text Input (size = [rows][,cols])
			'checkbox'=> 'Checkbox',
			'radio'	=> 'Radiobutton',	// Radiobutton (size = value if checked)
			'button'	=> 'Submitbutton',
			'hrule'	=> 'Horizontal Rule',
			'template'=> 'Template',	// $cell['name'] contains template-name, $cell['size'] index into $content,$cname,$readonlys
			'image'	=> 'Image',			// label = url, name=link or method, help=alt or title
			'date'	=> '', 				// Datefield, size='' timestamp or size=format like 'm/d/Y'
			'select'	=>	'Selectbox',	// Selectbox ($sel_options[$name] or $content[options-$name] is array with options)
												// if size > 1 then multiple selections, size lines showed
			'html'	=> 'Html',			// Raw html in $content[$cell['name']]
			'file'	=> 'FileUpload',	// show an input type='file', set the local name as ${name}_path
			'vbox'	=> 'VBox',			// a (vertical) box to contain widgets in rows, size = # of rows
			'hbox'	=> 'HBox',			// a (horizontal) box to contain widgets in cols, size = # of cols 
			'deck'	=> 'Deck'			// a container of elements where only one is visible, size = # of elem.
		);
		/**
		 * constructor of class
		*
		 * @param $name     name of etemplate or array with name and other keys
		 * @param $load_via name/array with keys of other etemplate to load in order to get $name
		 * Calls the constructor of soetemplate
		 */
		function boetemplate($name='',$load_via='')
		{
			$this->public_functions += array(
				'set_row_attribute' => True,
				'disable_row' => True,
				'set_column_attribute' => True,
				'disable_column' => True,
				'disable_cells' => True,
				'set_cell_attribute' => True,
				'get_cell_attribute' => True,
				'get_array' => True,
				'set_array' => True,
				'unset_array' => True
			);
			$this->soetemplate();

			$tname = &$name;
			if (is_array($name))
			{
				$tname = &$name['name'];
			}
			$tname = (strstr($tname,'.') === False && !empty($tname) ?
				(is_array($load_via) ? $load_via['name'] : $load_via).'.':'').$tname;

			if (empty($tname) || !$this->read($name,'','',0,'',$load_via))
			{
				$this->init($name);
			}
		}

		/**
		 * allows a few variables (eg. row-number) to be used in field-names
		 *
		 * This is mainly used for autorepeat, but other use is possible.
		 * You need to be aware of the rules PHP uses to expand vars in strings, a name
		 * of "Row$row[length]" will expand to 'Row' as $row is scalar, you need to use
		 * "Row${row}[length]" instead. Only one indirection is allowd in a string by php !!!
		 * Out of that reason we have now the variable $row_cont, which is $cont[$row] too.
		 * Attention !!!
		 * Using only number as index in field-names causes a lot trouble, as depending
		 * on the variable type (which php determines itself) you used filling and later
		 * accessing the array it can by the index or the key of an array element.
		 * To make it short and clear, use "Row$row" or "$col$row" not "$row" or "$row$col" !!!
		 * @param $name the name to expand
		 * @param $c is the column index starting with 0 (if you have row-headers, data-cells start at 1)
		 * @param $row is the row number starting with 0 (if you have col-headers, data-cells start at 1)
		 * @param $c_, $row_ are the respective values of the previous template-inclusion,
		 * @param            eg. the column-headers in the eTemplate-editor are templates itself,
		 * @param            to show the column-name in the header you can not use $col as it will
		 * @param            be constant as it is always the same col in the header-template,
		 * @param            what you want is the value of the previous template-inclusion.
		 * @param $cont content array of the template, you might use it to generate button-names with
		 * @param       id values in it: "del[$cont[id]]" expands to "del[123]" if $cont = array('id' => 123)
		 */
		function expand_name($name,$c,$row,$c_='',$row_='',$cont='')
		{
			if(empty($name))
			{
				return '';
			}
			if (strstr($name,'$') !== False)
			{
				if (!$cont)
				{
					$cont = array();
				}
				$col = $this->num2chrs($c-1);	// $c-1 to get: 0:'@', 1:'A', ...
				$col_ = $this->num2chrs($c_-1);
				$row_cont = isset($cont[$row]) ? $cont[$row] : 0;
				$col_row_cont = isset($cont[$col.$row]) ? $cont[$col.$row] : 0;

				eval('$name = "'.$name.'";');
			}
			if ($name[0] == '@')
			{
				$name = $this->get_array($cont,substr($name,1));
			}
			return $name;
		}

		/**
		 * Checks if we have an row- or column autorepeat and sets the indexes for $content, etc.
		*
		 * Autorepeat is important to allow a variable numer of rows or cols, eg. for a list.
		 * The eTemplate has only one (have to be the last) row or column, which gets
		 * automaticaly repeated as long as content is availible. To check this the content
		 * has to be in an sub-array of content. The index / subscript into content is
		 * determined by the content of size for templates or name for regular fields.
		 * An autorepeat is defined by an index which contains variables to expand.
		 * (vor variable expansion in names see expand_names). Usually I use the keys
		 * $row: 0, 1, 2, 3, ... for only rows, $col: '@', 'A', 'B', 'C', ... for only cols or
		 * $col$row: '@0','A0',... '@1','A1','B1',... '@2','A2','B2',... for both rows and cells.
		 * In general everything expand_names can generate is ok - see there.
		 * As you usually have col- and row-headers, data-cells start with '1' or 'A' !!!
		 * @param $cell array with data of cell: name, type, size, ...
		 * @param $c,$r col/row index starting from 0
		 * @param &$idx returns the index in $content and $readonlys (NOT $sel_options !!!)
		 * @param &$idx_cname returns the basename for the form-name: is $idx if only one value
		 * @param       (no ',') is given in size (name (not template-fields) are always only one value)
		 * @param $check_col boolean to check for col- or row-autorepeat
		 * @return true if cell is autorepeat (has index with vars / '$') or false otherwise
		 */
		function autorepeat_idx($cell,$c,$r,&$idx,&$idx_cname,$check_col=False)
		{
			$org_idx = $idx = '';
			if(isset($cell['type']) && $cell['type'] == 'template')
			{
				if(isset($cell['size']))
				{
					$org_idx = $idx = $cell['size'];
				}
			}
			else
			{
				if(isset($cell['name']))
				{
					$org_idx = $idx = $cell['name'];
				}
			}

			$idx = $this->expand_name($idx,$c,$r);
			if (!($komma = strpos($idx,',')))
			{
				$idx_cname = $idx;
			}
			else
			{
				$idx_cname = substr($idx,1+$komma);
				$idx = substr($idx,0,$komma);
			}
			$Ok = False;
			$pat = $org_idx;
			while (!$Ok && ($pat = strstr($pat,'$')))
			{
				$pat = substr($pat,$pat[1] == '{' ? 2 : 1);

				if ($check_col)
				{
					$Ok = $pat[0] == 'c' && !(substr($pat,0,4) == 'cont' ||
							substr($pat,0,2) == 'c_' || substr($pat,0,4) == 'col_');
				}
				else
				{
					$Ok = $pat[0] == 'r' && !(substr($pat,0,2) == 'r_' || 
						substr($pat,0,4) == 'row_' && substr($pat,0,8) != 'row_cont');
				}
			}
			if ($this->name && $this->name == $this->debug)
			{
				echo "$this->name ".($check_col ? 'col' : 'row')."-check: c=$c, r=$r, idx='$org_idx' ==> ".($Ok?'True':'False')."<p>\n";
			}
			return $Ok;
		}

		/**
		 * creates a new appsession-id via microtime()
		 */
		function appsession_id()
		{
			list($msec,$sec) = explode(' ',microtime());
			$id = $GLOBALS['phpgw_info']['flags']['currentapp'] . (intval(1000000 * $msec) + 1000000 * ($sec % 100000));
			//echo "<p>microtime()=".microtime().", sec=$sec, msec=$msec, id=$id</p>\n";
			return $id;
		}

		/**
		 * appsession
		 * db-sessions appsession function
		*
		 * It is used to overcome the problem with overflowing php4-sessions
		 */
		function appsession($location = 'default', $appname = '', $data = '##NOTHING##')
		{
			// use the version from the sessions-class if we use db-sessions
			//
			if ($GLOBALS['phpgw_info']['server']['sessions_type'] == 'db')
			{
				return $GLOBALS['phpgw']->session->appsession($location,$appname,$data);
			}
			// if not, we use or own copy of the appsessions function
			// setting these class vars to be compatible with the session-class
			//
			$this->sessionid  = $GLOBALS['phpgw']->session->sessionid;
			$this->account_id = $GLOBALS['phpgw']->session->account_id;

			if (! $appname)
			{
				$appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}

			if ($data == '##NOTHING##')
			{
				$query = "SELECT content FROM phpgw_app_sessions WHERE"
					." sessionid='".$this->sessionid."' AND loginid='".$this->account_id."'"
					." AND app = '".$appname."' AND location='".$location."'";

				$GLOBALS['phpgw']->db->query($query,__LINE__,__FILE__);
				$GLOBALS['phpgw']->db->next_record();

				// I added these into seperate steps for easier debugging
				$data = $GLOBALS['phpgw']->db->f('content');
				// Changed by Skeeter 2001 Mar 04 0400Z
				// This was not properly decoding structures saved into session data properly
//				$data = $GLOBALS['phpgw']->common->decrypt($data);
//				return stripslashes($data);
				// Changed by milosch 2001 Dec 20
				// do not stripslashes here unless this proves to be a problem.
				// Changed by milosch 2001 Dec 25
				/* do not decrypt and return if no data (decrypt returning garbage) */
				if($data)
				{
					$data = $GLOBALS['phpgw']->crypto->decrypt($data);
//					echo 'appsession returning: '; _debug_array($data);
				}
			}
			else
			{
				$GLOBALS['phpgw']->db->query("SELECT content FROM phpgw_app_sessions WHERE "
					. "sessionid = '".$this->sessionid."' AND loginid = '".$this->account_id."'"
					. " AND app = '".$appname."' AND location = '".$location."'",__LINE__,__FILE__);

				$encrypteddata = $GLOBALS['phpgw']->crypto->encrypt($data);
				$encrypteddata = $GLOBALS['phpgw']->db->db_addslashes($encrypteddata);

				if ($GLOBALS['phpgw']->db->num_rows()==0)
				{
					$GLOBALS['phpgw']->db->query("INSERT INTO phpgw_app_sessions (sessionid,loginid,app,location,content,session_dla) "
						. "VALUES ('".$this->sessionid."','".$this->account_id."','".$appname
						. "','".$location."','".$encrypteddata."','" . time() . "')",__LINE__,__FILE__);
				}
				else
				{
					$GLOBALS['phpgw']->db->query("UPDATE phpgw_app_sessions SET content='".$encrypteddata."'"
						. "WHERE sessionid = '".$this->sessionid."'"
						. "AND loginid = '".$this->account_id."' AND app = '".$appname."'"
						. "AND location = '".$location."'",__LINE__,__FILE__);
				}
			}
			// we need to clean up not longer used records, else the db gets bigger and bigger
			//
			$GLOBALS['phpgw']->db->query("DELETE FROM phpgw_app_sessions WHERE session_dla <= '" . (time() - $GLOBALS['phpgw_info']['server']['sessions_timeout'])
				. "'",__LINE__,__FILE__);

			return $data;
		}

		/**
		 * saves content,readonlys,template-keys, ... via the appsession function
		 *
		 * As a user may open several windows with the same content/template wie generate a location-id from microtime
		 * which is used as location for appsession to descriminate between the different windows. This location-id
		 * is then saved as a hidden-var in the form. The above mentions session-id has nothing to do / is different
		 * from the session-id which is constant for all windows opened in one session.
		 * @param $data the data to save
		 * @param $id the id to use or '' to generate a new id
		 * @return the location-id
		 */
		function save_appsession($data,$id='')
		{
			if (!$id)
			{
				$id = $this->appsession_id;
			}
			$this/*GLOBALS['phpgw']->session*/->appsession($id,'etemplate',$data);

			return $id;
		}

		/**
		 * gets content,readonlys,template-keys, ... back from the appsession function
		 *
		 * @param $id the location-id
		 * @return the session-data
		 */
		function get_appsession($id)
		{
			$data = $this/*GLOBALS['phpgw']->session*/->appsession($id,'etemplate');

			//echo "<p>get_appsession('$id') data="; _debug_array($data);

			// if we delete the returned value here, we cant get back (back-button),
			// not even to a non-submitted page
			//$GLOBALS['phpgw']->session->appsession_delete($id,'etemplate');

			return $data;
		}

		/**
		 * gets an attribute in a named cell
		 *
		 * @return the attribute or False if named cell not found
		 */
		function get_cell_attribute($name,$attr)
		{
			reset($this->data);
		 while(list($row,$cols) = each($this->data))
			{
				while(list($col,$cell) = each($cols))
				{
					if ($cell['name'] == $name)
					{
						reset($this->data);
						return $this->data[$row][$col][$attr];
					}
				}
			}
			reset($this->data);

			return False;
		}

		/**
		 * set an attribute in a named cell
		 *
		 * @return the number of changed cells
		 */
		function set_cell_attribute($name,$attr,$val)
		{
			//echo "<p>set_cell_attribute(tpl->name=$this->name, name='$name', attr='$attr',val='$val')</p>\n";

			$n = 0;
			foreach($this->data as $row => $cols)
			{
				foreach($cols as $col => $cell)
				{
					if (isset($cell['name']) && $cell['name'] == $name)
					{
						$this->data[$row][$col][$attr] = $val;
						++$n;
					}
					if ($cell['type'] == 'template' && (isset($cell['obj']) && is_object($cell['obj']) || $cell['name'][0] != '@'))
					{
						if (!isset($cell['obj']) || !is_object($cell['obj']))
						{
							$this->data[$row][$col]['obj'] = CreateObject('etemplate.etemplate',$cell['name']);
						}
						$n += $this->data[$row][$col]['obj']->set_cell_attribute($name,$attr,$val);
					}
				}
			}
			return $n;
		}

		/**
		 * disables all cells with name == $name
		*
		 */
		function disable_cells($name)
		{
			return $this->set_cell_attribute($name,'disabled',True);
		}
		
		/**
		 * set one or more attibutes for row $n
		 *
		 * @param $n is numerical row-number starting with 1 (!)
		 * @param $height in percent or pixel or '' for no height
		 * @param $class name of css class (without the leading '.') or '' for no class
		 * @param $valign alignment (top,middle,bottom) or '' for none
		 * @param $disabled True or expression or False to disable or enable the row
		 * @param Only the number 0 means dont change the attribute !!!
		 */
		function set_row_attributes($n,$height=0,$class=0,$valign=0,$disabled=0)
		{
			list($old_height,$old_disabled) = explode(',',$this->data[0]["h$n"]);
			$disabled = $disabled !== 0 ? $disabled : $old_disabled;
			$this->data[0]["h$n"] = ($height !== 0 ? $height : $old_height).
				($disabled ? ','.$disabled : '');
			list($old_class,$old_valign) = explode(',',$this->data[0]["c$n"]);
			$valign = $valign !== 0 ? $valign : $old_valign;
			$this->data[0]["c$n"] = ($class !== 0 ? $class : $old_class).
				($valign ? ','.$valign : '');
		}

		/**
		 * disables row $n
		 *
		 * @param $n is numerical row-number starting with 1 (!)
		 * @param $enable can be used to re-enable a row if set to True
		 */
		function disable_row($n,$enable=False)
		{
			$this->set_row_attributes($n,0,0,0,!$enable);
		}

		/**
		 * set one or more attibutes for column $c
		 *
		 * @param $c is numerical column-number starting with 0 (!), or the char-code starting with 'A'
		 * @param $width in percent or pixel or '' for no height
		 * @param $disabled True or expression or False to disable or enable the column
		 * @param Only the number 0 means dont change the attribute !!!
		 */
		function set_column_attributes($c,$width=0,$disabled=0)
		{
			if (is_numeric($c))
			{
				$c = $this->num2chrs($c);
			}
			list($old_width,$old_disabled) = explode(',',$this->data[0][$c]);
			$disabled = $disabled !== 0 ? $disabled : $old_disabled;
			$this->data[0][$c] = ($width !== 0 ? $width : $old_width).
				($disabled ? ','.$disabled : '');
		}

		/**
		 * disables column $c
		 *
		 * @param $c is numerical column-number starting with 0 (!), or the char-code starting with 'A'
		 * @param $enable can be used to re-enable a column if set to True
		 */
		function disable_column($c,$enable=False)
		{
			$this->set_column_attributes($c,0,!$enable);
		}

		/**
		 * trys to load the Extension / Widget-class from the app or etemplate
		 * @param $name name of the extension, the classname should be class.${name}_widget.inc.php
		 * the $name might be "$name.$app" to give a app-name (default is the current app,or template-name)
		 */
		function loadExtension($type)
		{
			@list($class,$app) = explode('.',$type);
			$class .= '_widget';

			if ($app == '')
			{
				$app = isset($GLOBALS['phpgw_info']['flags']['current_app']) ? $GLOBALS['phpgw_info']['flags']['current_app'] : '';
			}
			if (!file_exists(PHPGW_SERVER_ROOT."/$app/inc/class.$class.inc.php"))
			{
				list($app) = explode('.',$this->name);
			}
			if (!file_exists(PHPGW_SERVER_ROOT."/$app/inc/class.$class.inc.php"))
			{
				$app = 'etemplate';
			}
			if (!file_exists(PHPGW_SERVER_ROOT."/$app/inc/class.$class.inc.php"))
			{
				return $GLOBALS['phpgw_info']['etemplate']['extension'][$type] = False;
			}
			$GLOBALS['phpgw_info']['etemplate']['extension'][$type] = createObject($app.'.'.$class,$ui='html');

			return $GLOBALS['phpgw_info']['etemplate']['extension'][$type]->human_name;
		}

		/*
		@function haveExtension
		@syntax haveExtension($type)
		@abstract checks if extension is loaded and load it if it isnt
		*/
		function haveExtension($type,$function='')
		{
			return ( (isset($GLOBALS['phpgw_info']['etemplate']['extension'][$type]) && $GLOBALS['phpgw_info']['etemplate']['extension'][$type]) || $this->loadExtension($type)) &&
					($function == '' || (isset($GLOBALS['phpgw_info']['etemplate']['extension'][$type]->public_functions[$function]) && $GLOBALS['phpgw_info']['etemplate']['extension'][$type]->public_functions[$function]));
		}

		/*
		@function extensionPreProcess
		@syntax extensionPreProcess(&$cell,&$value,&$readonlys)
		@param $type of the extension
		@param $name form-name of this widget/field (used as a unique index into extension_data)
		@param &$cell table-cell on which the extension operates
		@param &$value value of the extensions content(-array)
		@param &$readonlys value of the extensions readonly-setting(-array)
		@abstract executes the pre_process-function of the extension $cell[]type]
		*/
		function extensionPreProcess($type,$name,&$value,&$cell,&$readonlys)
		{
			if (!$this->haveExtension($type))
			{
				return False;
			}
			return $GLOBALS['phpgw_info']['etemplate']['extension'][$type]->pre_process($name,$value,$cell,$readonlys,
				$GLOBALS['phpgw_info']['etemplate']['extension_data'][$name],$this);
		}

		/*
		@function extensionPostProcess
		@syntax extensionPostProcess(&$cell,&$value)
		@param $type of the extension
		@param $name form-name of this widget/field (used as a unique index into extension_data)
		@param &$value value of the extensions content(-array)
		@abstract executes the post_process-function of the extension $cell[type]
		*/
		function extensionPostProcess($type,$name,&$value,$value_in)
		{
			if (!$this->haveExtension($type,'post_process'))
			{
				return False;
			}
			return $GLOBALS['phpgw_info']['etemplate']['extension'][$type]->post_process($name,$value,
				$GLOBALS['phpgw_info']['etemplate']['extension_data'][$name],
				$GLOBALS['phpgw_info']['etemplate']['loop'],$this,$value_in);
		}

		/*
		@syntax extensionRender(&$cell,$form_name,&$value,$readonly)
		@abstract executes the render-function of the extension $cell[type]
		*/
		function extensionRender($type,$name,&$value,&$cell,$readonly)
		{
			if (!$this->haveExtension($type,'render'))
			{
				return False;
			}
			return $GLOBALS['phpgw_info']['etemplate']['extension'][$type]->render($cell,$name,$value,$readonly,
				$GLOBALS['phpgw_info']['etemplate']['extension_data'][$name],$this);
		}

		/**
		 * checks if idx, which may contain ONE subindex is set in array
		 */
		function isset_array($arr,$idx)
		{
			if (ereg('^([^[]*)\\[(.*)\\]$',$idx,$regs))
			{
				return $regs[2] && isset($arr[$regs[1]][$regs[2]]);
			}
			return isset($arr[$idx]);
		}

		/**
		 * sets $arr[$idx] = $val
		 * This works for non-trival indexes like 'a[b][c]' too: $arr['a']['b']['c'] = $val;
		 */
		function set_array(&$arr,$idx,$val)
		{
			if (!is_array($arr))
			{
				die('set_array() $arr is no array');
			}
			$idxs = explode('[',str_replace(']','',$idx));
			$pos = &$arr;
			while (list($n,$idx) = each($idxs))
			{
				$pos = &$pos[$idx];
			}
			$pos = $val;
		}

		/**
		 * return a var-param to $arr[$idx]
		 *
		 * $sub = get_array($arr,'a[b]'); $sub = 'c'; is equivalent to $arr['a']['b'] = 'c';
		 * This works for non-trival indexes like 'a[b][c]' too: it returns &$arr[a][b][c]
		 */
		function &get_array(&$arr,$idx)
		{
			if (!is_array($arr))
			{
				die('set_array() $arr is no array');
			}
			$idxs = explode('[',str_replace(']','',$idx));
			$pos = &$arr;
			while (list($n,$idx) = each($idxs))
			{
				if (!is_array($pos))
				{
					return $pos = '';
				}
				$pos = &$pos[$idx];
			}
			return $pos;
		}

		/**
		 * unsets $arr[$idx]
		 *
		 * unset_array($arr,'a[b]'); is equivalent to unset($arr['a']['b']);
		 * This works for non-trival indexes like 'a[b][c]' too
		 */
		function unset_array(&$arr,$idx)
		{
			if (!is_array($arr))
			{
				die('set_array() $arr is no array');
			}
			$idxs = explode('[',str_replace(']','',$idx));
			$pos = &$arr;
			while ((list($n,$idx) = each($idxs)) && $n < count($idxs)-1)
			{
				$pos = &$pos[$idx];
			}
			unset($pos[$idx]);
		}

		/**
		 * merges $old and $new, content of $new has precedence over $old
		 *
		 * THIS IS NOT THE SAME AS PHP4: array_merge (as it calls itself recursive for values which are arrays.
		 */
		function complete_array_merge($old,$new)
		{
			@reset($new);
			while (list($k,$v) = @each($new))
			{
				if (!is_array($v) || !isset($old[$k]))
				{
					$old[$k] = $v;
				}
				else
				{
					$old[$k] = $this->complete_array_merge($old[$k],$v);
				}
			}
			return $old;
		}


		function cache_name($name='',$template='default',$lang='default')
		{
			if (empty($name))
			{
				$name     = $this->name;
				$template = $this->template;
				$lang     = $this->lang;
			}
			elseif (is_array($name))
			{
				$template = $name['template'];
				$lang     = $name['lang'];
				$name     = $name['name'];
			}
			if (empty($template))
			{
				$template = 'default';
			}
			$cname = $template . '/' . $name . (!empty($lang) && $lang != 'default' ? '.' . $lang : '');
			//echo "cache_name('$name','$template','$lang') = '$cname'";

			return $cname;
		}

		/**
		 * stores the etemplate in the cache in phpgw_info
		*
		 */
		function store_in_cache()
		{
			//echo "<p>store_in_cache('$this->name','$this->template','$this->lang','$this->version')</p>\n";
			$GLOBALS['phpgw_info']['etemplate']['cache'][$this->cache_name()] = $this->as_array(1);
		}

		function in_cache($name,$template='default',$lang='default',$group=0,$version='')
		{
			$cname = $this->cache_name($name,$template,$lang);
			if (is_array($name))
			{
				$version = $name['version'];
				$name    = $name['name'];
			}
			if (!isset($GLOBALS['phpgw_info']['etemplate']['cache'][$cname]) ||
				!empty($version) && $GLOBALS['phpgw_info']['etemplate']['cache'][$cname]['version'] != $version)
			{
				//echo " NOT found in cache</p>\n";
				return False;
			}
			//echo " found in cache</p>\n";
			return $cname;
		}

		function read_from_cache($name,$template='default',$lang='default',$group=0,$version='')
		{
			//if (is_array($name)) $version = $name['version']; echo "<p>read_from_cache(,,,version='$version'): ";
			if ($cname = $this->in_cache($name,$template,$lang,$group))
			{
				$this->init($GLOBALS['phpgw_info']['etemplate']['cache'][$cname]);

				return True;
			}
			return False;
		}

		/**
		 * Reads an eTemplate from the cache or database / filesystem (and updates the cache)
		*
		 * @param as discripted in soetemplate::read
		 * @param $load_via name/array of keys of etemplate to load in order to get $name (only as second try!)
		 * @return True if a fitting template is found, else False
		 */
		function read($name,$template='default',$lang='default',$group=0,$version='',$load_via='')
		{
			if (is_array($name)) {
				$pname = &$name['name'];
			}
			else
			{
				$pname = &$name;
			}	
			if (empty($pname))
			{
				return False;
			}
			$parent = is_array($load_via) ? $load_via['name'] : $load_via;
		 if (strstr($pname,'.') === False && !empty($parent))
			{
				$pname = $parent . '.' . $pname;
			}
			if (!$this->read_from_cache($name,$template,$lang,$group,$version))
			{
				if (!soetemplate::read($name,$template,$lang,$group,$version))
				{
					if ($load_via && (is_string($load_via) ||
						!isset($load_via['tpls_in_file']) || $load_via['tpls_in_file'] > 1))
					{
						soetemplate::read($load_via);
						return $this->read_from_cache($name,$template,$lang,$group,$version);
					}
					return False;
				}
				$this->store_in_cache();
			}
			return True;
		}

		/**
		 * saves eTemplate-object to db and update the cache
		*
		 * keys see soetemplate::save
		 * @return the number of affected rows, 1 should be ok, 0 somethings wrong
		 */
		function save($name='',$template='.',$lang='.',$group='',$version='.')
		{
			if ($result = soetemplate::save($name,$template,$lang,$group,$version))
			{
				$this->store_in_cache();
			}
			return $result;
		}
	}