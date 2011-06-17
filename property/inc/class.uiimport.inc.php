<?php

	class property_uiimport
	{
		var $public_functions = array
		(
			'index'		=> true
		);

		const DELIMITER = ";";
		const ENCLOSING = "'";
		
		// List of messages, warnings and errors to be displayed to the user after the import
		protected $messages = array();
		protected $warnings = array();
		protected $errors = array();
		
		// File system path to import folder on server
		protected $file;
		protected $district;
		protected $csvdata;
		protected $account;
		protected $conv_type;
		protected $import_conversion;
		
		// Label on the import button. Changes as we step through the import process.
		protected $import_button_label;
		
		protected $defalt_values;
		
		public function __construct()
		{
			set_time_limit(10000); //Set the time limit for this request oto 3000 seconds
			$this->account		= (int)$GLOBALS['phpgw_info']['user']['account_id'];
			$this->db           = & $GLOBALS['phpgw']->db;
		}
		

		/**
		 * Public method. 
		 * 
		 * @return unknown_type
		 */
		public function index()
		{
			// Set the submit button label to its initial state
			$this->import_button_label = "Start import";

			// If the parameter 'importsubmit' exist (submit button in import form), set path
			if (phpgw::get_var("importsubmit")) 
			{
				// Get the path for user input or use a default path
				
				if($this->file = $_FILES['file']['tmp_name'])
				{
					$this->csvdata = $this->getcsvdata($this->file);
				}

				$this->conv_type 	= phpgw::get_var('conv_type');
//_debug_array($this->csvdata);
				phpgwapi_cache::session_set('property', 'file', $this->file);
				phpgwapi_cache::session_set('property', 'csvdata', $this->csvdata);
				phpgwapi_cache::session_set('property', 'conv_type', $this->conv_type);
				$GLOBALS['phpgw']->redirect_link('/index.php', array('menuaction' => 'property.uiimport.index', 'importstep' => 'true'));
			} 
			else if(phpgw::get_var("importstep"))
			{
				$start_time = time(); // Start time of import
				$start = date("G:i:s",$start_time);
				echo "<h3>Import started at: {$start}</h3>";
				echo "<ul>";
				$this->file = phpgwapi_cache::session_get('property', 'file');
				$this->csvdata = phpgwapi_cache::session_get('property', 'csvdata');
				$this->conv_type = phpgwapi_cache::session_get('property', 'conv_type');

				if($this->conv_type)
				{
					if ( preg_match('/\.\./', $this->conv_type) )
					{
						break;
					}

					$file = PHPGW_SERVER_ROOT . "/property/inc/import/{$GLOBALS['phpgw_info']['user']['domain']}/{$this->conv_type}";
	
					if ( is_file($file) )
					{
//_debug_Array($file);die();
						require_once $file;
						$this->import_conversion = new import_conversion;
					}
				}

				$result = $this->import(); // Do import step, result determines if finished for this area
				echo '<li class="info">Aktiviteter: finished step ' .$result. '</li>';
				while($result != '1')
				{
					$result = $this->import();
					echo '<li class="info">Aktiviteter: finished step ' .$result. '</li>';
					flush();
				}

				echo "</ul>";
				$end_time = time();
				$difference = ($end_time - $start_time) / 60;
				$end = date("G:i:s",$end_time);
				echo "<h3>Import ended at: {$end}. Import lasted {$difference} minutes.";
				
				$this->messages = array_merge($this->messages,$this->import_conversion->messages);
				$this->warnings = array_merge($this->warnings,$this->import_conversion->warnings);
				$this->errors = array_merge($this->errors,$this->import_conversion->errors);

				if ($this->errors)
				{ 
					echo "<ul>";
					foreach ($this->errors as $error)
					{
						echo '<li class="error">Error: ' . $error . '</li>';
					}
		
					echo "</ul>";
				}
		
				if ($this->warnings)
				{ 
					echo "<ul>";
					foreach ($this->warnings as $warning)
					{
						echo '<li class="warning">Warning: ' . $warning . '</li>';
					}
					echo "</ul>";
				}
		
				if ($this->messages)
				{
					echo "<ul>";
		
					foreach ($this->messages as $message)
					{
						echo '<li class="info">' . $message . '</li>';
					}
					echo "</ul>";
				}
			}
			else
			{

				$conv_list = $this->get_import_conv($this->conv_type);
				
				$conv_option = '<option value="">' . lang('none selected') . '</option>' . "\n";
				foreach ( $conv_list as $conv)
				{
					$selected = '';
					if ( $conv['selected'])
					{
						$selected = 'selected =  "selected"';
					}

					$conv_option .=  <<<HTML
					<option value='{$conv['id']}'{$selected}>{$conv['name']}</option>
HTML;
				}			
				$html = <<<HTML
				<h1><img src="rental/templates/base/images/32x32/actions/document-save.png" /> Importer</h1>
				<div id="messageHolder"></div>
				<form action="index.php?menuaction=property.uiimport.index" method="post" enctype="multipart/form-data">
					<fieldset>
						<label for="file">Choose file:</label> <input type="file" name="file" id="file" />
						<label for="conv_type">Choose conversion:</label>
						<select name="conv_type" id="conv_type">
						{$conv_option}
						</select>
						<input type="submit" name="importsubmit" value="{$this->import_button_label}"  />
		 			</fieldset>
				</form>
HTML;
				echo $html;
			}
		}
		
		/**
		 * Import Facilit data to Portico Estate's rental module
		 * The function assumes CSV files have been uploaded to a location on the server reachable by the
		 * web server user.  The CSV files must correspond to the table names from Facilit, as exported
		 * from Access. Field should be enclosed in single quotes and separated by comma.  The CSV files
		 * must contain the column headers on the first line.
		 * 
		 * @return unknown_type
		 */
		public function import()
		{
			$steps = 1;
			
			/* Import logic:
			 * 
			 * 1. Do step logic if the session variable is not set
			 * 2. Set step result on session
			 * 3. Set label for import button
			 * 4. Log messages for this step
			 *  
			 */
			
			$this->messages = array();
			$this->warnings = array();
			$this->errors = array();
			
			// Import data if not done before and put them on the users session
			if (!phpgwapi_cache::session_get('property', 'data_import'))
			{
				phpgwapi_cache::session_set('property', 'data_import', $this->import_data()); 
                $this->log_messages(1);
				return '1';
			}

			// We're done with the import, so clear all session variables so we're ready for a new one
			phpgwapi_cache::session_clear('property', 'data_import');
			phpgwapi_cache::session_clear('property', 'conv_type');
			return '1';
		}
		
		protected function import_data()
		{
			$start_time = time();
			
			$datalines = $this->csvdata;
			
			$this->messages[] = "Read 'import_all.csv' file in " . (time() - $start_time) . " seconds";
			$this->messages[] = "'importfile.csv' contained " . count($datalines) . " lines";
			

			$ok = true;
			$_ok = false;
			$this->db->transaction_begin();

			//Do your magic...
			foreach ($datalines as $data)
			{
				if(!$_ok = $this->import_conversion->add($data))
				{
					$ok = false;
				}
			}
			
			if($ok)
			{
				$this->messages[] = "Imported data. (" . (time() - $start_time) . " seconds)";
				$this->db->transaction_commit();
				return true;
			}
			else
			{
				$this->errors[] = "Import of data failed. (" . (time() - $start_time) . " seconds)";
				$this->db->transaction_abort();
				return false;
			}
		}


		protected function getcsvdata($path, $skipfirstline = true)
		{
			// Open the csv file
			$handle = fopen($path, "r");
			
			if ($skipfirstline)
			{
				// Read the first line to get the headers out of the way
				$this->getcsv($handle);
			}
			
			$result = array();
			
			while(($data = $this->getcsv($handle)) !== false)
			{
				$result[] = $data;
			}
			
			fclose($handle);
			
			return $result;
		}
			
		
		/**
		 * Read the next line from the given file handle and parse it to CSV according to the rules set up
		 * in the class constants DELIMITER and ENCLOSING.  Returns FALSE like getcsv on EOF.
		 * 
		 * @param file-handle $handle
		 * @return array of values from the parsed csv line
		 */
		protected function getcsv($handle)
		{
			return fgetcsv($handle, 1000, self::DELIMITER, self::ENCLOSING);
		}
		

		private function log_messages($step)
        {
        	sort($this->errors);
        	sort($this->warnings);
        	sort($this->messages);
        	
            $msgs = array_merge(
            	array('----------------Errors--------------------'),
            	$this->errors,
            	array('---------------Warnings-------------------'),
            	$this->warnings,
            	array('---------------Messages-------------------'),
            	$this->messages
            );

            $path = $GLOBALS['phpgw_info']['server']['temp_dir'];
            if(is_dir($path.'/logs') || mkdir($path.'/logs'))
            {
                file_put_contents("$path/logs/$step.log", implode(PHP_EOL, $msgs));
            }
        }

		protected function get_import_conv($selected='')
		{
			$dir_handle = @opendir(PHPGW_SERVER_ROOT . "/property/inc/import/{$GLOBALS['phpgw_info']['user']['domain']}");
			$i=0; $myfilearray = array();
			while ($file = readdir($dir_handle))
			{
				if ((substr($file, 0, 1) != '.') && is_file(PHPGW_SERVER_ROOT . "/property/inc/import/{$GLOBALS['phpgw_info']['user']['domain']}/{$file}") )
				{
					$myfilearray[$i] = $file;
					$i++;
				}
			}
			closedir($dir_handle);
			sort($myfilearray);

			for ($i=0;$i<count($myfilearray);$i++)
			{
				$fname = preg_replace('/_/',' ',$myfilearray[$i]);

				$conv_list[] = array
				(
					'id'		=> $myfilearray[$i],
					'name'		=> $fname,
					'selected'	=> $myfilearray[$i]==$selected ? 1 : 0
				);
			}

			return $conv_list;
		}
	}