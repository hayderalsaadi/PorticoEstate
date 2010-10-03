<?php
	phpgw::import_class('booking.socommon');

	class booking_socompleted_reservation_export_file extends booking_socommon
	{	
		protected 
			$file_storage,
			$so_completed_reservation,
			$so_completed_reservation_export;
		
		protected static $export_type_to_file_type_map = array(
			'internal' => 'csv',
			'external' => 'txt',
		);
		
		function __construct()
		{		
			$this->file_storage = CreateObject('booking.filestorage', $this);
			$this->so_completed_reservation = CreateObject('booking.socompleted_reservation');
			$this->so_completed_reservation_export = CreateObject('booking.socompleted_reservation_export');
			
			parent::__construct('bb_completed_reservation_export_file', 
				array(
					'id'				=> array('type' => 'int'),
					'type' 			=> array('type' => 'string', 'required' => true, 'query' => true),
					'filename' 		=> array('type' => 'string'),
					'total_cost'	=> array('type' => 'decimal', 'required' => true),
					'total_items'	=> array('type' => 'int', 'required' => true),
					key(booking_socommon::$AUTO_CREATED_ON) => current(booking_socommon::$AUTO_CREATED_ON),
					key(booking_socommon::$AUTO_CREATED_BY) => current(booking_socommon::$AUTO_CREATED_BY),
					'created_by_name' => booking_socommon::$REL_CREATED_BY_NAME,
				)
			);
		}
		
		protected function file_type_for_export_type($export_type) {
			return isset(self::$export_type_to_file_type_map[$export_type]) ? 
				self::$export_type_to_file_type_map[$export_type] :
				'txt';
		}
		
		protected function get_available_export_types() {
			return $this->so_completed_reservation_export->get_available_export_types();
		}
		
		protected function get_export_file_data($from_entity, $of_type) {
			return $this->so_completed_reservation_export->get_export_file_data($from_entity, $of_type);
		}
		
		public function get_file($entity_file) {
			if (isset($entity_file['filename']) && !empty($entity_file['filename'])) {
				return $this->file_storage->get($entity_file['filename']);
			}
			
			return null;
		}
		
		public function associate_reservation_with_export_file($reservation_id, $export_file_id, $invoice_order_id) {
			$this->so_completed_reservation->associate_with_export_file($reservation_id, $export_file_id, $invoice_order_id);
		}
		
		public function count_associated_reservations($for_export_file_id) {
			return $this->so_completed_reservation->count_reservations_for_export_file($for_export_file_id);
		}
		
		public function combine_export_result_data(array &$export_results) {
			return $this->so_completed_reservation_export->combine_export_data($export_results);
		}
		
		public function export_has_generated_file(&$export, $of_type) {
			return $this->so_completed_reservation_export->has_generated_file($export, $of_type);
		}
		
		public function generate_for(array $completed_reservation_exports) {
			$export_types = $this->get_available_export_types();
			
			$export_results 			= array_fill_keys($export_types, array());
			$export_data 				= array_fill_keys($export_types, '');
			$export_infos 				= array_fill_keys($export_types, array());
			$export_configurations 	= array_fill_keys($export_types, array());
			$total_items 				= array_fill_keys($export_types, 0);
			$total_cost 				= array_fill_keys($export_types, 0.0);
			
			$entity_export_files = array();
			$export_files = array();
			$export_conf_updates = array();
			
			try {				
				$this->db->transaction_begin();
				
				$do_generate_files = false;
			
				foreach($export_types as $export_type) {
					foreach ($completed_reservation_exports as $export) {
						if ($this->export_has_generated_file($export, $export_type)) {
							continue; //Don't include data for exports that already have a generated file
						}
						
						$do_generate_files = true;
						
						list($conf, $export_result) = $this->get_export_file_data($export, $export_type);
						$export_results[$export_type][] = $export_result;
						
						if (!is_null($export_result['export'])) {
							$export_infos[$export_type][] = $export_result['export']['info'];	
						}
						
						$export_configurations[$export_type][$export['id']] = $conf;
						$total_items[$export_type] += $export_result['total_items'];
						$total_cost[$export_type] += $export_result['total_cost'];
					}
					
					$export_data[$export_type] = $this->combine_export_result_data($export_results[$export_type]);
				}
				
				if ($do_generate_files === false) {
					return false;
				}
			
				foreach($export_types as $export_type) {
					$entity_export_file = array();
					$entity_export_file['type'] = $export_type;
					$entity_export_file['total_cost'] = $total_cost[$export_type];
					$entity_export_file['total_items'] = $total_items[$export_type];
				
					$receipt = $this->add($entity_export_file);
					$entity_export_file['id'] = $receipt['id'];
				
					$entity_export_file['filename'] = 'export_'.$export_type.'_'.$entity_export_file['id'].'.'.$this->file_type_for_export_type($export_type);
				
					$export_file = new booking_storage_object($entity_export_file['filename']);
					$export_files[] = $export_file;
					
					$export_file->set_data($export_data[$export_type]);
					
					$this->file_storage->attach($export_file)->persist();

					$this->update($entity_export_file); //Save the generated file name
					$entity_export_files[$entity_export_file['id']] = $entity_export_file;
				
					foreach($export_configurations[$export_type] as $export_id => $conf) {
						$export_conf_updates[] = sprintf(
							"UPDATE bb_completed_reservation_export_configuration SET export_file_id=%s WHERE id=%s",
							$entity_export_file['id'],
							$conf['id']
						);
					}
					
					$associated_reservation_count = 0;
					foreach($export_infos[$export_type] as $key => &$export_info_collection) {
						if (!is_array($export_info_collection)) continue;
	 					foreach($export_info_collection as $item_key => &$export_item_info) {
							$this->associate_reservation_with_export_file($export_item_info['id'], $entity_export_file['id'], $export_item_info['invoice_file_order_id']);
							$associated_reservation_count++;
						}
					}
				
					//double-check that the total_items match the total number of completed reservations associated with the exported file
					if ($associated_reservation_count !== $entity_export_file['total_items']) {
						throw new UnexpectedValueException(sprintf(
							"Exported item count (%s) does not match count (%s) of associated completed reservations", 
							$entity_export_file['total_items'],
							$associated_reservation_count
						));
					}
				}
			
				$this->db_query(
					join(";\n", $export_conf_updates),
					__LINE__, __FILE__
				);
			
				if ($this->db->transaction_commit()) { 
					return $entity_export_files;
				}
			
				throw new UnexpectedValueException('Transaction failed.');
			
			} catch (Exception $e) {
				$this->delete_export_system_files($export_files);
				throw $e;
			}
			
			return false;
		}
		
		public function delete_export_system_files(&$export_files) {
			try {
				foreach($export_files as $export_file) {
					if ($export_file->exists()) {
						$export_file->delete();
					}
				}
			} catch (booking_unattached_storage_object $e) { }
		}
	}