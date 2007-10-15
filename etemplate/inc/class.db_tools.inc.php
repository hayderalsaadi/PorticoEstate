<?php
/**
* eTemplate - basic application development environment
* @copyright Copyright (C) 2002-2006 Free Software Foundation, Inc. http://www.fsf.org/
* @author Ralf Becker <ralf.becker@outdoortraining.de>
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
* @package etemplate
* @version $Id: class.db_tools.inc.php,v 1.24 2007/02/13 12:59:32 sigurdne Exp $
*/
	class db_tools
	{
		var $public_functions = array
		(
			'edit'         => True,
			'needs_save'   => True,
			//'admin'       => True,
			//'preferences' => True
		);

		var $debug = 0;
		var $editor;	// editor eTemplate
		var $data;		// Table definitions
		var $app;		// used app
		var $table;		// used table
		var $changes;
		
		var $types = array
		(
			'varchar'	=> 'varchar',
			'int'		=> 'int',
			'auto'		=> 'auto',
			'blob'		=> 'blob',
			'char'		=> 'char',
			'date'		=> 'date',
			'decimal'	=> 'decimal',
			'float'		=> 'float',
			'longtext'	=> 'longtext',
			'text'		=> 'text',
			'timestamp'	=> 'timestamp',
//			'abstime'   => 'abstime (mysql:timestamp)',
		);
		var $setup_header = '';

		/**
		 * constructor of class
		*
		 */
		function db_tools()
		{
			@set_time_limit(600); //@ to stop issues on safe_mode installs
			$this->editor = createObject('etemplate.etemplate','etemplate.db-tools.edit');
			$this->data = array();

			if (!is_array($GLOBALS['phpgw_info']['apps']) || !count($GLOBALS['phpgw_info']['apps']))
			{
				ExecMethod('phpgwapi.applications.read_installed_apps');
			}

			//I know this formatting is ugly, but it needs to be ugly for now - skwashd
			$this->setup_header = '<?php
/**
* ' . $this->app . ' Setup
*
* @copyright Copyright (C) 2006 Free Software Foundation, Inc. http://www.fsf.org/
* @author Your Name <first.last@domain.com>
* @license http://www.gnu.org/licenses/gpl.html GNU Lesser General Public License
* @package etemplate
* @version $Id: class.db_tools.inc.php,v 1.24 2007/02/13 12:59:32 sigurdne Exp $
*/
';

		}

		/**
		 * this is the table editor (and the callback/submit-method too)
		*
		 */
		function edit($content='',$msg = '')
		{
			if (isset($_GET['app']))
			{
				$this->app = $_GET['app'];
			}
			if (is_array($content))
			{
				if ($this->debug)
				{
					echo "content ="; _debug_array($content);
				}
				$this->app = $content['app'];	// this is what the user selected
				$this->table = $content['table_name'];
				$posted_app = $content['posted_app'];	// this is the old selection
				$posted_table = isset($content['posted_table']) ? $content['posted_table'] : '';
			}
			if (isset($posted_app) && $posted_app && $posted_table &&		// user changed app or table
				 ($posted_app != $this->app || $posted_table != $this->table))
			{
				if ($this->needs_save('',$posted_app,$posted_table,$this->content2table($content)))
				{
					return;
				}
				$this->renames = array();
			}
			if (!$this->app)
			{
				$this->table = '';
				$table_names = array('' => lang('none'));
			}
			else
			{
				$this->read($this->app,$this->data);

				foreach($this->data as $name => $table)
				{
					$table_names[$name] = $name;
				}
			}
			if (!$this->table || $this->app != $posted_app)
			{
				reset($this->data);
				list($this->table) = each($this->data);	// use first table
			}
			elseif ($this->app == $posted_app && $posted_table)
			{
				$this->data[$posted_table] = $this->content2table($content);
			}
			if ( isset($content['write_tables']) && $content['write_tables'] )
			{
				if ($this->needs_save('',$this->app,$this->table,$this->data[$posted_table]))
				{
					return;
				}
				$msg .= lang('Table unchanged, no write necessary !!!');
			}
			elseif ( isset($content['delete']) && $content['delete'] )
			{
				list($col) = each($content['delete']);

				@reset($this->data[$posted_table]['fd']);
				while ($col-- > 0 && list($key,$data) = @each($this->data[$posted_table]['fd'])) ;

				unset($this->data[$posted_table]['fd'][$key]);
				$this->changes[$posted_table][$key] = '**deleted**';
			}
			elseif ( isset($content['add_column']) && $content['add_column'] )
			{
				$this->data[$posted_table]['fd'][''] = array();
			}
			elseif ( (isset($content['add_table']) && $content['add_table'])
				|| (isset($content['import']) && $content['import']) )
			{
				if (!$this->app)
				{
					$msg .= lang('Select an app first !!!');
				}
				elseif (!$content['new_table_name'])
				{
					$msg .= lang('Please enter table-name first !!!');
				}
				elseif ($content['add_table'])
				{
					$this->table = $content['new_table_name'];
					$this->data[$this->table] = array('fd' => array(),'pk' =>array(),'ix' => array(),'uc' => array(),'fk' => array());
					$msg .= lang('New table created');
				}
				else // import
				{
					$oProc = createObject('phpgwapi.schema_proc',$GLOBALS['phpgw_info']['server']['db_type']);
					$oProc->m_odb =& $GLOBALS['phpgw']->db;
					$oProc->m_oTranslator->_GetColumns($oProc,$content['new_table_name'],$nul);

					while (list($key,$tbldata) = each ($oProc->m_oTranslator->sCol))
					{
						$cols .= $tbldata;
					}
					eval('$cols = array('. $cols . ');');

					$this->data[$this->table = $content['new_table_name']] = array(
						'fd' => $cols,
						'pk' => $oProc->m_oTranslator->pk,
						'fk' => $oProc->m_oTranslator->fk,
						'ix' => $oProc->m_oTranslator->ix,
						'uc' => $oProc->m_oTranslator->uc
					);
				}
			}
			elseif (isset($content['editor']) && $content['editor'])
			{
				ExecMethod('etemplate.editor.edit');
				return;
			}
			// from here on, filling new content for eTemplate
			$content = array(
				'msg' => $msg,
				'table_name' => $this->table,
				'app' => $this->app,
			);
			if (!isset($table_names[$this->table]))	// table is not jet written
			{
				$table_names[$this->table] = $this->table;
			}
			$sel_options = array(
				'table_name' => $table_names,
				'type' => $this->types
			);
			if ($this->table != '' && isset($this->data[$this->table]))
			{
				$content += $this->table2content($this->data[$this->table]);
			}
			$no_button = array( );
			if (!$this->app || !$this->table)
			{
				$no_button += array('write_tables' => True);
			}
			if ($this->debug)
			{
				echo 'editor.edit: content ='; _debug_array($content);
			}
			$this->editor->exec('etemplate.db_tools.edit',$content,$sel_options,$no_button,
				array('posted_table' => $this->table,'posted_app' => $this->app,'changes' => $this->changes));
		}

		/**
		 * checks if table was changed and if so offers user to save changes
		*
		 * @param $cont the content of the form (if called by process_exec)
		 * @param $posted_app the app the table is from
		 * @param $posted_table the table-name
		 * @param $edited_table the edited table-definitions
		 * @return only if no changes
		 */
		function needs_save($cont='',$posted_app='',$posted_table='',$edited_table='')
		{
			$msg = '';
			if (!$posted_app && is_array($cont))
			{
				if (isset($cont['yes']))
				{
					$this->app   = $cont['app'];
					$this->table = $cont['table'];
					$this->read($this->app,$this->data);
					$this->data[$this->table] = $cont['edited_table'];
					$this->changes = $cont['changes'];
					if ($cont['new_version'])
					{
						$this->update($this->app,$this->data,$cont['new_version']);
					}
					else
					{
						foreach($this->data as $tname => $tinfo)
						{
							$tables .= ($tables ? ',' : '') . "'$tname'";
						}
						$this->setup_version($this->app,'',$tables);
					}
					$msg .= $this->write($this->app,$this->data) ?
						lang('File writen') : lang('Error: writing file (no write-permission for the webserver) !!!');
				}
				$this->changes = array();
				// return to edit with everything set, so the user gets the table he asked for
				$this->edit(array(
					'app' => $cont['new_app'],
					'table_name' => $cont['app']==$cont['new_app'] ? $cont['new_table'] : '',
					'posted_app' => $cont['new_app']
				),$msg);

				return True;
			}
			$new_app   = $this->app;	// these are the ones, the users whiches to change too
			$new_table = $this->table;

			$this->app = $posted_app;
			$this->data = array();
			$this->read($posted_app,$this->data);

			if (isset($this->data[$posted_table]) &&
				 $this->tables_identical($this->data[$posted_table],$edited_table))
			{
				if ($new_app != $this->app)	// are we changeing the app, or hit the user just write
				{
					$this->app = $new_app;	// if we change init the data empty
					$this->data = array();
				}
				return False;	// continue edit
			}
			$content = array(
				'app' => $posted_app,
				'table' => $posted_table,
				'version' => $this->setup_version($posted_app)
			);
			$preserv = $content + array(
				'new_app' => $new_app,
				'new_table' => $new_table,
				'edited_table' => $edited_table,
				'changes' => $this->changes
			);
			$new_version = explode('.',$content['version']);
			$minor = count($new_version)-1;
			$new_version[$minor] = sprintf('%03d',1+$new_version[$minor]);
			$content['new_version'] = implode('.',$new_version);

			$tmpl = new etemplate('etemplate.db-tools.ask_save');

			if (!file_exists(PHPGW_SERVER_ROOT."/$posted_app/setup/tables_current.inc.php"))
			{
				$tmpl->disable_cells('version');
				$tmpl->disable_cells('new_version');
			}
			$tmpl->exec('etemplate.db_tools.needs_save',$content,array(),array(),$preserv);

			return True;	// dont continue in edit
		}

		/**
		 * creates content-array from a $table
		*
		 * @param $table table-definition, eg. $phpgw_baseline[$table_name]
		 * @return content-array
		 */
		function table2content($table)
		{
			$content = array();
			for ($n = 1; list($col_name,$col_defs) = each($table['fd']); ++$n)
			{
				$col_defs['name'] = $col_name;
				$col_defs['pk'] = in_array($col_name,$table['pk']);
				$col_defs['uc']  = in_array($col_name,$table['uc']);
				$col_defs['ix'] = in_array($col_name,$table['ix']);
				$col_defs['fk'] = isset($table['fk'][$col_name]) ? $table['fk'][$col_name] : '';
				if (isset($col_defs['default']) && $col_defs['default'] == '')
				{
					$col_defs['default'] = is_int($col_defs['default']) ? '0' : "''";	// special value for empty, but set, default
				}
				$col_defs['n'] = $n;

				$content["Row$n"] = $col_defs;
			}
			if ($this->debug >= 3)
			{
				echo "<p>table2content: content ="; _debug_array($content);
			}
			return $content;
		}

		/**
		 * creates table-definition from posted content
		*
		 * @param $content posted content-array
		 *  It sets some reasonalbe defaults for not set precisions (else setup will not install)
		 * @return table-definition
		 */
		function content2table($content)
		{
			if (!is_array($this->data))
			{
				$this->read($content['posted_app'],$this->data);
			}

			$posted_table = $content['posted_table'];
			$old_cols = isset($this->data[$posted_table]) ? $this->data[$posted_table]['fd'] : '';

			$this->changes = $content['changes'];

			$table = array();
			$table['fd'] = array();	// do it in the default order of tables_*
			$table['pk'] = array();
			$table['fk'] = array();
			$table['ix'] = array();
			$table['uc'] = array();
			for (reset($content),$n = 1; isset($content["Row$n"]); ++$n)
			{
				$col = $content["Row$n"];

				while ( isset($this->changes[$posted_table]) 
					&& (list($old_name,$old_col) = @each($old_cols))
					&& isset($this->changes[$posted_table][$old_name]) && $this->changes[$posted_table][$old_name] == '**deleted**');

				if (($name = $col['name']) != '')		// ignoring lines without column-name
				{
					if (isset($old_name) && ($col['name'] != $old_name && $n <= count($old_cols)))	// column renamed --> remeber it
					{
						$this->changes[$posted_table][$old_name] = $col['name'];
						//echo "<p>content2table: $posted_table.$old_name renamed to $col[name]</p>\n";
					}
					if ($col['precision'] <= 0)
					{
						switch ($col['type']) // set some defaults for precision, else setup fails
						{
							case 'float':   
							case 'int':     $col['precision'] = 4; break;
							case 'char':    $col['precision'] = 1; break;
							case 'varchar': $col['precision'] = 255; break;
						}
					}
					while (list($prop,$val) = each($col))
					{
						switch ($prop)
						{
							case 'default':
							case 'type':	// selectbox ensures type is not empty
							case 'precision':
							case 'scale':
							case 'nullable':
								if ($val != '' || $prop == 'nullable')
								{
									$table['fd'][$name][$prop] = $prop=='default'&& $val=="''" ? '' : $val;
								}
								break;
							case 'pk':
							case 'uc':
							case 'ix':
								if ($val)
								{
									$table[$prop][] = $name;
								}
								break;
							case 'fk':
								if ($val != '')
								{
									$table['fk'][$name] = $val;
								}
								break;
						}
					}
				}
			}
			if ($this->debug >= 2)
			{
				echo "<p>content2table: table ="; _debug_array($table);
				echo "<p>changes = "; _debug_array($this->changes);
			}
			return $table;
		}

		/**
		 * includes $app/setup/tables_current.inc.php
		*
		 * @param $app application name
		 * @param $phpgw_baseline where to put the data
		 * @return True if file found, False else
		 */
		function read($app,&$phpgw_baseline)
		{
			$file = PHPGW_SERVER_ROOT."/$app/setup/tables_current.inc.php";

			$phpgw_baseline = array();

			if ($app != '' && file_exists($file))
			{
				include($file);
			}
			else
			{
				return False;
			}
			if ($this->debug >= 5)
			{
				echo "<p>read($app): file='$file', phpgw_baseline =";
				_debug_array($phpgw_baseline);
			}
			return True;
		}

		function write_array($arr,$depth,$parent='')
		{
			$only_vals = false;
			$tabs = '';
			if (in_array($parent,array('pk','fk','ix','uc')))
			{
				$depth = 0;
				if ($parent != 'fk')
				{
					$only_vals = true;
				}
			}
			if ($depth)
			{
				$tabs = "\n";
				for ($n = 0; $n < $depth; ++$n)
				{
					$tabs .= "\t";
				}
				++$depth;
			}
			$def = "array($tabs".($tabs ? "\t" : '');

			$n = 0;
			foreach($arr as $key => $val)
			{
				if (!$only_vals)
				{
					$def .= "'$key' => ";
				}
				if (is_array($val))
				{
					$def .= $this->write_array($val,$parent == 'fd' ? 0 : $depth,$key,$only_vals);
				}
				else
				{
					if (!$only_vals && $key == 'nullable')
					{
						$def .= $val ? 'True' : 'False';
					}
					else
					{
						$def .= "'$val'";
					}
				}
				if ($n < count($arr)-1)
				{
					$def .= ",$tabs".($tabs ? "\t" : '');
				}
				++$n;
			}
			$def .= "$tabs)";

			return $def;
		}

		/**
		 * writes tabledefinitions $phpgw_baseline to file /$app/setup/tables_current.inc.php
		*
		 * @param $app app-name
		 * @param $phpgw_baseline tabledefinitions
		 * @return True if file writen else False
		 */
		function write($app,$phpgw_baseline)
		{
			$file = PHPGW_SERVER_ROOT."/$app/setup/tables_current.inc.php";

			if (file_exists($file) && ($f = fopen($file,'r')))
			{
				$header = fread($f,filesize($file));
				if ($end = strpos($header,');'))
				{
					$footer = substr($header,$end+3);	// this preservs other stuff, which should not be there
				}
				$header = substr($header,0,strpos($header,'$phpgw_baseline'));
				fclose($f);

				if (is_writable(PHPGW_SERVER_ROOT."/$app/setup"))
				{
					$old_file = PHPGW_SERVER_ROOT . "/$app/setup/tables_current.old.inc.php";
					if (file_exists($old_file))
					{
						unlink($old_file);
					}
					rename($file,$old_file);
				}
				while ($header[strlen($header)-1] == "\t")
				{
					$header = substr($header,0,strlen($header)-1);
				}
			}
			if (!$header)
			{
				$header = $this->setup_header . "\n\n";
			}
			if (!is_writeable(PHPGW_SERVER_ROOT."/$app/setup") || !($f = fopen($file,'w')))
			{
				return False;
			}
			$def = "\t\$phpgw_baseline = ";
			$def .= $this->write_array($phpgw_baseline,1);
			$def .= ";\n";

			fwrite($f,$header . $def . $footer);
			fclose($f);

			return True;
		}

		/**
		 * reads and updates the version and tables info in file $app/setup/setup.inc.php
		*
		 * @param $app the app
		 * @param $new new version number to set, if $new != ''
		 * @param $tables new tables to include, if $tables != ''
		 * @return the version or False if the file could not be read or written
		 */
		function setup_version($app,$new = '',$tables='')
		{
			//echo "<p>etemplate.db_tools.setup_version('$app','$new','$tables')</p>\n";

			$file = PHPGW_SERVER_ROOT."/$app/setup/setup.inc.php";
			if (file_exists($file))
			{
				include($file);
			}
			if (!is_array($setup_info[$app]) || !isset($setup_info[$app]['version']))
			{
				return False;
			}
			if (($new == '' || $setup_info[$app]['version'] == $new) &&	
				(!$tables || $setup_info[$app]['tables'] && "'".implode("','",$setup_info[$app]['tables'])."'" == $tables))
			{
				return $setup_info[$app]['version'];	// no change requested or not necessary 
			}
			if ($new == '') 
			{
				$new = $setup_info[$app]['version'];
			}
			if (!($f = fopen($file,'r')))
			{
				return False;
			}
			$fcontent = fread($f,filesize($file));
			fclose ($f);

			if (is_writable(PHPGW_SERVER_ROOT."/$app/setup"))
			{
				$old_file = PHPGW_SERVER_ROOT . "/$app/setup/setup.old.inc.php";
				if (file_exists($old_file))
				{
					unlink($old_file);
				}
				rename($file,$old_file);
			}
			$fnew = eregi_replace("(.*\\$"."setup_info\\['$app'\\]\\['version'\\][ \\t]*=[ \\t]*')[^']*('.*)","\\1$new"."\\2",$fcontent);
			
			if ($tables != '')
			{
				if ($setup_info[$app]['tables'])	// if there is already tables array, update it
				{
					$fnew = eregi_replace("(.*\\$"."setup_info\\['$app'\\]\\['tables'\\][ \\t]*=[ \\t]*array\()[^)]*","\\1$tables",$fwas=$fnew);

					if ($fwas == $fnew)	// nothing changed => tables are in single lines
					{
						$fwas = explode("\n",$fwas);
						$fnew = $prefix = '';
						$stage = 0;	// 0 = before, 1 = in, 2 = after tables section
						foreach($fwas as $line)
						{
							if (eregi("(.*\\$"."setup_info\\['$app'\\]\\['tables'\\]\\[[ \\t]*\\][ \\t]*=[ \\t]*)'",$line,$parts))
							{
								if ($stage == 0)	// first line of tables-section
								{
									$stage = 1;
									$prefix = $parts[1];
								}
							}
							else					// not in table-section
							{
								if ($stage == 1)	// first line after tables-section ==> add it
								{
									$tables = explode(',',$tables);
									foreach ($tables as $table)
									{
										$fnew .= $prefix . $table . ";\n";
									}
									$stage = 2; 
								}
								if (strpos($line,'?>') === False)	// dont write the closeing tag
								{
									$fnew .= $line . "\n";
								}
							}
						}
					}
				}
				else	// add the tables array
				{
					if (strstr($fnew,'?>'))	// remove a closeing tag
					{
						$fnew = str_replace('?>','',$fnew);
					}
					$fnew .= "\t\$setup_info['$app']['tables'] = array($tables);\n";
				}
			}
			if (!is_writeable(PHPGW_SERVER_ROOT."/$app/setup") || !($f = fopen($file,'w')))
			{
				return False;
			}
			fwrite($f,$fnew);
			fclose($f);

			return $new;
		}

		/**
		 * updates file /$app/setup/tables_update.inc.php to reflect changes in $current
		*
		 * @param $app app-name
		 * @param $current new tabledefinitions
		 * @param $version new version
		 * @return True if file writen else False
		 */
		function update($app,$current,$version)
		{
			//echo "<p>etemplate.db_tools.update('$app',...,'$version')</p>\n";

			if (!is_writable(PHPGW_SERVER_ROOT."/$app/setup"))
			{
				return False;
			}
			$file_baseline = PHPGW_SERVER_ROOT."/$app/setup/tables_baseline.inc.php";
			$file_current  = PHPGW_SERVER_ROOT."/$app/setup/tables_current.inc.php";
			$file_update   = PHPGW_SERVER_ROOT."/$app/setup/tables_update.inc.php";

			if (!file_exists($file_baseline) && !copy($file_current,$file_baseline))
			{
				//echo "<p>Can't copy $file_current to $file_baseline !!!</p>\n";
				return False;
			}
			$old_version = $this->setup_version($app);
			$old_version_ = str_replace('.','_',$old_version);

			if (file_exists($file_update))
			{
				$f = fopen($file_update,'r');
				$update = fread($f,filesize($file_update));
				$update = str_replace('?>','',$update);
				fclose($f);
				$old_file = PHPGW_SERVER_ROOT . "/$app/setup/tables_update.old.inc.php";
				if (file_exists($old_file))
				{
					unlink($old_file);
				}
				rename($file_update,$old_file);
			}
			else
			{
				$update = $this->setup_header;
			}
			$update .= "
	\$test[] = '$old_version';
	function $app"."_upgrade$old_version_()
	{";

			$update .= "
		\$GLOBALS['phpgw_setup']->oProc->m_odb->transaction_begin();\n";
			$update .= $this->update_schema($app,$current,$tables);
			$update .= "
		if(\$GLOBALS['phpgw_setup']->oProc->m_odb->transaction_commit())
		{
			\$GLOBALS['setup_info']['$app']['currentver'] = '$version';
		}
		return \$GLOBALS['setup_info']['$app']['currentver'];
	}
?".">\n";
		
	//		$update .= "\n
	//	\$GLOBALS['setup_info']['$app']['currentver'] = '$version';
	//	return \$GLOBALS['setup_info']['$app']['currentver'];
	//}
//?".">\n";
			
			if (!($f = fopen($file_update,'w')))
			{
				//echo "<p>Cant open '$update' for writing !!!</p>\n";
				return False;
			}
			fwrite($f,$update);
			fclose($f);

			$this->setup_version($app,$version,$tables);

			return True;
		}

		function remove_from_array(&$arr,$value)
		{
			foreach($arr as $key => $val)
			{
				if ($val == $value)
				{
					unset($arr[$key]);
				}
			}
		}

		function update_schema($app,$current,&$tables)
		{
			$this->read($app,$old);

			$tables = '';
			$update = '';
			foreach($old as $name => $table_def)
			{
				if (!isset($current[$name]))	// table $name droped
				{
					$update .= "\t\t\$GLOBALS['phpgw_setup']->oProc->DropTable('$name');\n";
				}
				else
				{
					$tables .= ($tables ? ',' : '') . "'$name'";

					$new_table_def = $table_def;
					foreach($table_def['fd'] as $col => $col_def)
					{
						if (!isset($current[$name]['fd'][$col]))	// column $col droped
						{
							if (!isset($this->changes[$name][$col]) || $this->changes[$name][$col] == '**deleted**')
							{
								unset($new_table_def['fd'][$col]);
								$this->remove_from_array($new_table_def['pk'],$col);
								$this->remove_from_array($new_table_def['fk'],$col);
								$this->remove_from_array($new_table_def['ix'],$col);
								$this->remove_from_array($new_table_def['uc'],$col);
								$update .= "\t\t\$GLOBALS['phpgw_setup']->oProc->DropColumn('$name',";
								$update .= $this->write_array($new_table_def,2).",'$col');\n";
							}
							else	// column $col renamed
							{
								$new_col = $this->changes[$name][$col];
								$update .= "\t\t\$GLOBALS['phpgw_setup']->oProc->RenameColumn('$name','$col','$new_col');\n";
							}
						}
					}
					if ( isset($this->changes[$name])
						&& is_array($this->changes[$name]) )
					{
						foreach($this->changes[$name] as $col => $new_col)
						{
							if ($new_col != '**deleted**')
							{
								$old[$name]['fd'][$new_col] = $old[$name]['fd'][$col];	// to be able to detect further changes of the definition
								unset($old[$name]['fd'][$col]);
							}
						}
					}
				}
			}
			foreach($current as $name => $table_def)
			{
				if (!isset($old[$name]))	// table $name added
				{
					$tables .= ($tables ? ',' : '') . "'$name'";

					$update .= "\t\t\$GLOBALS['phpgw_setup']->oProc->CreateTable('$name',";
					$update .= $this->write_array($table_def,2).");\n";
				}
				else
				{
					$old_norm = $this->normalize($old[$name]);
					$new_norm = $this->normalize($table_def);
					foreach($table_def['fd'] as $col => $col_def)
					{
						if (($add = !isset($old[$name]['fd'][$col])) ||	// column $col added
							 serialize($old_norm['fd'][$col]) != serialize($new_norm['fd'][$col])) // column definition altered
						{
							$update .= "\t\t$"."GLOBALS['phpgw_setup']->oProc->".($add ? 'Add' : 'Alter')."Column('$name','$col',";
							$update .= $this->write_array($col_def,2) . ");\n";
						}
					}
				}
			}
			if ($this->debug)
			{
				echo "<p>update_schema($app, ...) =<br><pre>$update</pre>)</p>\n";
			}
			return $update;
		}

		/**
		 * sets all nullable properties to True or False
		*
		 * @return the new array
		 */
		function normalize($table)
		{
			$all_props = array('type','precision','nullable','default');

			foreach($table['fd'] as $col => $props)
			{
				$table['fd'][$col] = array
				(
					'type'		=> strval($props['type']),
					'precision'	=> (int) (isset($props['precision']) ? $props['precision'] : 0),
					'scale'		=> (int) (isset($props['scale']) ? $props['scale'] : 0),
					'nullable'	=> isset($props['nullable']) ? !!$props['nullable'] : false,
					'default'	=> strval( isset($props['default']) ? $props['default'] : '' )
				);
			}
			return array
			(
				'fd' => $table['fd'],
				'pk' => $table['pk'],
				'fk' => $table['fk'],
				'ix' => $table['ix'],
				'uc' => $table['uc']
			);
		}

		/**
		 * compares two table-definitions
		*
		 * @return True if they are identical or False else
		 */
		function tables_identical($a,$b)
		{
			$a = serialize($this->normalize($a));
			$b = serialize($this->normalize($b));

			//echo "<p>checking if tables identical = ".($a == $b ? 'True' : 'False')."<br>\n";
			//echo "a: $a<br>\nb: $b</p>\n";

			return $a == $b;
		}
	}