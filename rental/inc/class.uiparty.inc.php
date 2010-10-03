<?php
phpgw::import_class('rental.uicommon');
phpgw::import_class('rental.soparty');
phpgw::import_class('rental.socontract');
phpgw::import_class('rental.sodocument');
phpgw::import_class('rental.bofellesdata');
include_class('rental', 'party', 'inc/model/');
include_class('rental', 'unit', 'inc/model/');
include_class('rental', 'location_hierarchy', 'inc/locations/');

class rental_uiparty extends rental_uicommon
{
	public $public_functions = array
	(
			'add'				=> true,
			'edit'				=> true,
			'index'				=> true,
			'query'				=> true,
			'view'				=> true,
			'download'			=> true,
			'download_agresso'	=> true,
			'sync'				=> true,
			'syncronize_party'	=> true
	);

	public function __construct()
	{
		parent::__construct();
		self::set_active_menu('rental::parties');
		$GLOBALS['phpgw_info']['flags']['app_header'] .= '::'.lang('parties');
	}

	/**
	 * (non-PHPdoc)
	 * @see rental/inc/rental_uicommon#query()
	 */
	public function query()
	{
		if($GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'] > 0)
		{
			$user_rows_per_page = $GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'];
		}
		else {
			$user_rows_per_page = 10;
		}
		// YUI variables for paging and sorting
		$start_index	= phpgw::get_var('startIndex', 'int');
		$num_of_objects	= phpgw::get_var('results', 'int', 'GET', $user_rows_per_page);
		$sort_field		= phpgw::get_var('sort', 'string', 'GET', 'identifier');
		$sort_ascending	= phpgw::get_var('dir') == 'desc' ? false : true;
		// Form variables
		$search_for 	= phpgw::get_var('query');
		$search_type	= phpgw::get_var('search_option');
		// Create an empty result set
		$result_objects = array();
		$result_count = 0;
		
		//Create an empty result set
		$parties = array();
		
		$exp_param 	= phpgw::get_var('export');
		$export = false;
		if(isset($exp_param)){
			$export=true;
			$num_of_objects = null;
		}
		
		//Retrieve a contract identifier and load corresponding contract
		$contract_id = phpgw::get_var('contract_id');
		if(isset($contract_id))
		{
			$contract = rental_socontract::get_instance()->get_single($contract_id);
		}
		
		//Retrieve the type of query and perform type specific logic
		$type = phpgw::get_var('type');
		switch($type)
		{
			case 'included_parties': // ... get all parties incolved in the contract
				$filters = array('contract_id' => $contract_id);
				break;
			case 'not_included_parties': // ... get all parties not included in the contract
				$filters = array('not_contract_id' => $contract_id, 'party_type' => phpgw::get_var('party_type'));
				break;
			case 'sync_parties':
			case 'sync_parties_res_unit':
			case 'sync_parties_identifier':
			case 'sync_parties_org_unit':
				$filters = array('sync' => $type, 'party_type' => phpgw::get_var('party_type'), 'active' => phpgw::get_var('active'));
				$bofelles = rental_bofellesdata::get_instance();
				break;
			default: // ... get all parties of a given type
				phpgwapi_cache::session_set('rental', 'party_query', $search_for);
				phpgwapi_cache::session_set('rental', 'party_search_type', $search_type);
				phpgwapi_cache::session_set('rental', 'party_type', phpgw::get_var('party_type'));
				phpgwapi_cache::session_set('rental', 'party_status', phpgw::get_var('active'));
				$filters = array('party_type' => phpgw::get_var('party_type'), 'active' => phpgw::get_var('active'));
				break;
		}
		
		$result_objects = rental_soparty::get_instance()->get($start_index, $num_of_objects, $sort_field, $sort_ascending, $search_for, $search_type, $filters);
		$result_count = rental_soparty::get_instance()->get_count($search_for, $search_type, $filters);
		
		// Create an empty row set
		$rows = array();
		foreach ($result_objects as $party) {
			if(isset($party))
			{
				$serialized = $party->serialize($contract);
				$sync_data = $party->get_sync_data();
				if($type == 'sync_parties')
				{
					$unit_name_and_id = $bofelles->responsibility_id_exist($sync_data['responsibility_id']);
				}
				else if($type == 'sync_parties_res_unit')
				{
					$unit_name_and_id = $bofelles->result_unit_exist($sync_data['result_unit_number']);
				}
				else if($type == 'sync_parties_identifier')
				{
					$unit_name_and_id = $bofelles->result_unit_exist($party->get_identifier());
				}
				else if($type == 'sync_parties_org_unit')
				{
					$unit_name_and_id = $bofelles->org_unit_exist($sync_data['org_enhet_id']);
				}
				
				if(isset($unit_name_and_id))
				{
					$unit_id = $unit_name_and_id['UNIT_ID'];
					$unit_name = $unit_name_and_id['UNIT_NAME'];
					if(isset($unit_id) && is_numeric($unit_id))
					{
						$serialized['org_unit_name'] =  isset($unit_name) ? $unit_name : lang('no_name');
						$serialized['org_unit_id'] = $unit_id;
					}
				}
				$rows[] = $serialized;
			}
		}
		// ... add result data
		$party_data = array('results' => $rows, 'total_records' => $result_count);

		$editable = phpgw::get_var('editable') == 'true' ? true : false;

		if(!$export){
			array_walk(
				$party_data['results'], 
				array($this, 'add_actions'), 
				array(													// Parameters (non-object pointers)
					$contract_id,										// [1] The contract id
					$type,												// [2] The type of query
					isset($contract) ? $contract->serialize() : null, 	// [3] Serialized contract
					$editable,											// [4] Editable flag
					$this->type_of_user									// [5] User role			
				)
			);
		}
		
		
		return $this->yui_results($party_data, 'total_records', 'results');
	}

	/**
	 * Add action links for the context menu of the list item
	 *
	 * @param $value pointer to
	 * @param $key ?
	 * @param $params [composite_id, type of query, contract editable]
	 */
	public function add_actions(&$value, $key, $params)
	{
		$value['ajax'] = array();
		$value['actions'] = array();
		$value['labels'] = array();

		// Get parameters
		$contract_id = $params[0];
		$type = $params[1];
		$serialized_contract= $params[2];
		$editable = $params[3];
		$user_is = $params[4];
		
		// Depending on the type of query: set an ajax flag and define the action and label for each row
		switch($type)
		{
			case 'included_parties':
				$value['ajax'][] = false;
				$value['actions'][] = html_entity_decode(self::link(array('menuaction' => 'rental.uiparty.view', 'id' => $value['id'])));
				$value['labels'][] = lang('show');

				if($editable == true)
				{
					$value['ajax'][] = true;
					$value['actions'][] = html_entity_decode(self::link(array('menuaction' => 'rental.uicontract.remove_party', 'party_id' => $value['id'], 'contract_id' => $params[0])));
					$value['labels'][] = lang('remove');

					if($value['id'] != $serialized_contract['payer_id']){
						$value['ajax'][] = true;
						$value['actions'][] = html_entity_decode(self::link(array('menuaction' => 'rental.uicontract.set_payer', 'party_id' => $value['id'], 'contract_id' => $params[0])));
						$value['labels'][] = lang('set_payer');
					}
				}
				break;
			case 'not_included_parties':
				$value['ajax'][] = false;
				$value['actions'][] = html_entity_decode(self::link(array('menuaction' => 'rental.uiparty.view', 'id' => $value['id'])));
				$value['labels'][] = lang('show');
				if($editable == true)
				{
					$value['ajax'][] = true;
					$value['actions'][] = html_entity_decode(self::link(array('menuaction' => 'rental.uicontract.add_party', 'party_id' => $value['id'], 'contract_id' => $params[0])));
					$value['labels'][] = lang('add');
				}
				break;
			default:
				$value['ajax'][] = false;
				$value['actions'][] = html_entity_decode(self::link(array('menuaction' => 'rental.uiparty.view', 'id' => $value['id'])));
				$value['labels'][] = lang('show');
					
				if($user_is[ADMINISTRATOR] || $user_is[EXECUTIVE_OFFICER])
				{
					$value['ajax'][] = false;
					$value['actions'][] = html_entity_decode(self::link(array('menuaction' => 'rental.uiparty.edit', 'id' => $value['id'])));
					$value['labels'][] = lang('edit');
					
					if(isset($value['org_enhet_id']) && $value['org_enhet_id'] != '')
					{
						$value['ajax'][] = false;
						$value['actions'][] = html_entity_decode(self::link(array('menuaction' => 'frontend.uihelpdesk.index', 'org_enhet_id' => $value['org_enhet_id'])));
						$value['labels'][] = lang('frontend_access');
					}
					
					if(isset($value['org_unit_id']))
					{
						$value['ajax'][] = true;
						$value['actions'][] = html_entity_decode(self::link(array('menuaction' => 'rental.uiparty.syncronize_party', 'org_unit_id' => $value['org_unit_id'], 'party_id' => $value['id'])));
						$value['labels'][] = lang('syncronize_party');
					}
				}
				break;
		}
	}


	/**
	 * Public method. View all contracts.
	 */
	public function index()
	{
		$this->render('party_list.php');
	}

	/**
	 * Public method. Forwards the user to edit mode.
	 */
	public function add()
	{
		$GLOBALS['phpgw']->redirect_link('/index.php', array('menuaction' => 'rental.uiparty.edit'));
	}

	/**
	 * Public method. Called when a user wants to view information about a party.
	 * @param HTTP::id	the party ID
	 */
	public function view()
	{
		$GLOBALS['phpgw_info']['flags']['app_header'] .= '::'.lang('view');
		// Get the contract part id
		$party_id = (int)phpgw::get_var('id');
		if(isset($party_id) && $party_id > 0)
		{
			$party = rental_soparty::get_instance()->get_single($party_id); 
		}
		else
		{
			$this->render('permission_denied.php',array('error' => lang('invalid_request')));
			return;
		}
		
		if(isset($party) && $party->has_permission(PHPGW_ACL_READ))
		{
			return $this->render(
				'party.php', 
				array (
					'party' 	=> $party,
					'editable' => false,
					'cancel_link' => self::link(array('menuaction' => 'rental.uiparty.index', 'populate_form' => 'yes')),
				)
			);
		}
		else
		{
			$this->render('permission_denied.php',array('error' => lang('permission_denied_view_party')));
		}
	}

	/**
	 * Public method. Called when user wants to edit a contract party.
	 * @param HTTP::id	the party ID
	 */
	public function edit()
	{
		$GLOBALS['phpgw_info']['flags']['app_header'] .= '::'.lang('edit');
		// Get the contract part id
		$party_id = (int)phpgw::get_var('id');
		
		
		// Retrieve the party object or create a new one if correct permissions
		if(($this->isExecutiveOfficer() || $this->isAdministrator()))
		{
			if(isset($party_id) && $party_id > 0)
			{	
				$party = rental_soparty::get_instance()->get_single($party_id); 
			}
			else
			{
				$party = new rental_party();
			}
		}
		else
		{
			$this->render('permission_denied.php',array('error' => lang('permission_denied_edit')));
		}

		if(isset($_POST['save_party'])) // The user has pressed the save button
		{
			if(isset($party)) // If a party object is created
			{
				// ... set all parameters
				$party->set_identifier(phpgw::get_var('identifier'));
				$party->set_first_name(phpgw::get_var('firstname'));
				$party->set_last_name(phpgw::get_var('lastname'));
				$party->set_title(phpgw::get_var('title'));
				$party->set_company_name(phpgw::get_var('company_name'));
				$party->set_department(phpgw::get_var('department'));
				$party->set_address_1(phpgw::get_var('address1'));
				$party->set_address_2(phpgw::get_var('address2'));
				$party->set_postal_code(phpgw::get_var('postal_code'));
				$party->set_place(phpgw::get_var('place'));
				$party->set_phone(phpgw::get_var('phone'));
				$party->set_mobile_phone(phpgw::get_var('mobile_phone'));
				$party->set_fax(phpgw::get_var('fax'));
				$party->set_email(phpgw::get_var('email'));
				$party->set_url(phpgw::get_var('url'));
				$party->set_account_number(phpgw::get_var('account_number'));
				$party->set_reskontro(phpgw::get_var('reskontro'));
				$party->set_is_inactive(phpgw::get_var('is_inactive') == 'on' ? true : false);
				$party->set_comment(phpgw::get_var('comment'));
				//$party->set_location_id(phpgw::get_var('location_id'));
				$party->set_org_enhet_id(phpgw::get_var('org_enhet_id'));
				
				if(rental_soparty::get_instance()->store($party)) // ... and then try to store the object
				{
					$message = lang('messages_saved_form');	
				}
				else
				{
					$error = lang('messages_form_error');
				}
			}
		}
		return $this->render('party.php', array
			(
				'party' 	=> $party,
				'editable' => true,
				'message' => isset($message) ? $message : phpgw::get_var('message'),
				'error' => isset($error) ? $error : phpgw::get_var('error'),
				'cancel_link' => self::link(array('menuaction' => 'rental.uiparty.index', 'populate_form' => 'yes')),
			)	
		);
	}
	
	public function download_agresso(){
		$browser = CreateObject('phpgwapi.browser');
		$browser->content_header('export.txt','text/plain');
		print rental_soparty::get_instance()->get_export_data();
	}
	
	public function sync()
	{
		$sync_job	= phpgw::get_var('sync', 'string', 'GET');
		switch($sync_job)
		{
			case 'resp_and_service':
				self::set_active_menu('rental::parties::sync::sync_resp_and_service');
				$this->render('sync_party_list.php');
				break;
			case 'res_unit_number':
				self::set_active_menu('rental::parties::sync::sync_res_units');
				$this->render('sync_party_list_res_unit.php');
				break;
			case 'identifier':
				self::set_active_menu('rental::parties::sync::sync_identifier');
				$this->render('sync_party_list_identifier.php');
				break;
			case 'org_unit':
				self::set_active_menu('rental::parties::sync::sync_org_unit');
				$this->render('sync_party_list_org_id.php');
				break;
		}
	}
	
	public function syncronize_party()
	{
		if(($this->isExecutiveOfficer() || $this->isAdministrator()))
		{
			$party_id = phpgw::get_var('party_id');
			$org_unit_id = phpgw::get_var('org_unit_id');
			if(isset($party_id) && $party_id > 0 && isset($org_unit_id) && $org_unit_id > 0)
			{	
				$party = rental_soparty::get_instance()->get_single($party_id);
				$party->set_org_enhet_id($org_unit_id);
				rental_soparty::get_instance()->store($party);
			}
		}
	}
}
?>