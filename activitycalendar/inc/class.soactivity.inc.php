<?php
phpgw::import_class('activitycalendar.socommon');
phpgw::import_class('activitycalendar.soorganization');
phpgw::import_class('activitycalendar.sogroup');
//phpgw::import_class('activitycalendar.socontactperson');

include_class('activitycalendar', 'activity', 'inc/model/');
include_class('activitycalendar', 'target', 'inc/model/');
include_class('activitycalendar', 'category', 'inc/model/');

class activitycalendar_soactivity extends activitycalendar_socommon
{
	protected static $so;

	public $xmlrpc_methods = array
	(
		array
		(
			'name'       => 'get_activities',
			'decription' => 'Get list of activities'
		),
		array
		(
			'name'       => 'get_targetgroups',
			'decription' => 'Get list of targetgroups'
		),
		array
		(
			'name'       => 'get_statuscodes',
			'decription' => 'Get list of statuscodes'
		),
		array
		(
			'name'       => 'get_category_list',
			'decription' => 'Get list of categories'
		)
	);

	var $public_functions = array
		(
			'get_activities'  		=> true,
		);
	
	/**
	 * Get a static reference to the storage object associated with this model object
	 *
	 * @return rental_soparty the storage object
	 */
	public static function get_instance()
	{
		if (self::$so == null) {
			self::$so = CreateObject('activitycalendar.soactivity');
		}
		return self::$so;
	}
	
	/**
	 * Generate SQL query
	 *
	 * @todo Add support for filter "party_type", meaning what type of contracts
	 * the party is involved in.
	 *
	 * @param string $sort_field
	 * @param boolean $ascending
	 * @param string $search_for
	 * @param string $search_type
	 * @param array $filters
	 * @param boolean $return_count
	 * @return string SQL
	 */
	protected function get_query(string $sort_field, boolean $ascending, string $search_for, string $search_type, array $filters, boolean $return_count)
	{
		$clauses = array('1=1');

		//Add columns to this array to include them in the query
		$columns = array();

		if($sort_field != null) {
			$dir = $ascending ? 'ASC' : 'DESC';
			//$order = "ORDER BY id $dir";
			$order = "ORDER BY $sort_field $dir";
		}
		/*else
		{
			$dir = $ascending ? 'ASC' : 'DESC';
			$order = "ORDER BY id $dir";
		}*/
		//var_dump($search_type);
		//var_dump($search_for);
		if($search_for)
		{
			$query = $this->marshal($search_for,'string');
			$like_pattern = "'%".$search_for."%'";
			$like_clauses = array();
			switch($search_type){
				case "name":
					$like_clauses[] = "party.first_name $this->like $like_pattern";
					$like_clauses[] = "party.last_name $this->like $like_pattern";
					$like_clauses[] = "party.company_name $this->like $like_pattern";
					break;
				case "address":
					$like_clauses[] = "party.address_1 $this->like $like_pattern";
					$like_clauses[] = "party.address_2 $this->like $like_pattern";
					$like_clauses[] = "party.postal_code $this->like $like_pattern";
					$like_clauses[] = "party.place $this->like $like_pattern";
					break;
				case "identifier":
					$like_clauses[] = "party.identifier $this->like $like_pattern";
					break;
				case "reskontro":
					$like_clauses[] = "party.reskontro $this->like $like_pattern";
					break;
				case "result_unit_number":
					$like_clauses[] = "party.result_unit_number $this->like $like_pattern";
					break;
				case "all":
				default:
					$like_clauses[] = "activity.title $this->like $like_pattern";
					break;
			}


			if(count($like_clauses))
			{
				$clauses[] = '(' . join(' OR ', $like_clauses) . ')';
			}
		}

		$filter_clauses = array();
		
		if(isset($filters[$this->get_id_field_name()])){
			$id = $this->marshal($filters[$this->get_id_field_name()],'int');
			$filter_clauses[] = "activity.id = {$id}";
		}
		if(isset($filters['activity_state']) && $filters['activity_state'] != 'all'){
			$activity_state = $this->marshal($filters['activity_state'],'int');
			$filter_clauses[] = "activity.state = {$activity_state}";
		}
		if(isset($filters['activity_district']) && $filters['activity_district'] != 'all'){
			$activity_district = $this->marshal($filters['activity_district'],'int');
			$filter_clauses[] = "activity.district = '{$activity_district}'";
		}
/*
		// All parties with contracts of type X
		if(isset($filters['party_type']))
		{
			$party_type = $this->marshal($filters['party_type'],'int');
			if(isset($party_type) && $party_type > 0)
			{
				$filter_clauses[] = "contract.location_id = {$party_type}";
			}
		}
*/		
		
		if(count($filter_clauses))
		{
			$clauses[] = join(' AND ', $filter_clauses);
		}

		$condition =  join(' AND ', $clauses);

		if($return_count) // We should only return a count
		{
			$cols = 'COUNT(DISTINCT(activity.id)) AS count';
		}
		else
		{
			$columns[] = 'activity.id';
			$columns[] = 'activity.title';
			$columns[] = 'activity.organization_id';
			$columns[] = 'activity.group_id';
			$columns[] = 'activity.district';
			$columns[] = 'activity.office';
			$columns[] = 'activity.state';
			$columns[] = 'activity.category';
			$columns[] = 'activity.target';
			$columns[] = 'activity.description';
			$columns[] = 'activity.arena';
			$columns[] = 'activity.time';
			$columns[] = 'activity.create_date';
			$columns[] = 'activity.last_change_date';
			$columns[] = 'activity.contact_person_1';
			$columns[] = 'activity.contact_person_2';
			$columns[] = 'activity.special_adaptation';
			
			$cols = implode(',',$columns);
		}

		$tables = "activity_activity activity";

		//$join_contracts = "	{$this->left_join} rental_contract_party c_p ON (c_p.party_id = party.id)
		//{$this->left_join} rental_contract contract ON (contract.id = c_p.contract_id)";
		
		//var_dump("SELECT {$cols} FROM {$tables} WHERE {$condition} {$order}");
		return "SELECT {$cols} FROM {$tables} WHERE {$condition} {$order}";
	}



	/**
	 * Function for adding a new activity to the database. Updates the activity object.
	 *
	 * @param activitycalendar_activity $activity the party to be added
	 * @return bool true if successful, false otherwise
	 */
	function add(&$activity)
	{
		// Insert a new activity
		$ts_now = strtotime('now');
		$q ="INSERT INTO activity_activity (title, create_date) VALUES ('tmptitle', $ts_now)";
		$result = $this->db->query($q, __LINE__,__FILE__);

		if(isset($result)) {
			// Set the new party ID
			$activity->set_id($this->db->get_last_insert_id('activity_activity', 'id'));
			// Forward this request to the update method
			return $this->update($activity);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Update the database values for an existing activity object.
	 *
	 * @param $activity the activity to be updated
	 * @return boolean true if successful, false otherwise
	 */
	function update($activity)
	{
		$id = intval($activity->get_id());
		$ts_now = strtotime('now');
			
		$values = array(
			'title = '     . $this->marshal($activity->get_title(), 'string'),
			'organization_id = '. $this->marshal($activity->get_organization_id(), 'int'),
			'group_id = '     . $this->marshal($activity->get_group_id(), 'int'),
			'district =  '     . $this->marshal($activity->get_district(), 'string'),
			'office =  '     . $this->marshal($activity->get_office(), 'int'),
			'category = '          . $this->marshal($activity->get_category(), 'int'),
			'state = '          . $this->marshal($activity->get_state(), 'int'),
			'target = '   . $this->marshal($activity->get_target(), 'string'),
			'description = '     . $this->marshal($activity->get_description(), 'string'),
			'arena = '      . $this->marshal($activity->get_arena(), 'int'),
			'time = '      . $this->marshal($activity->get_time(), 'string'),
			'last_change_date = '    . $this->marshal($ts_now, 'int'),
			'contact_person_1 = '          . $this->marshal($activity->get_contact_person_1(), 'int'),
			'contact_person_2 = '          . $this->marshal($activity->get_contact_person_2(), 'int'),
			'special_adaptation = '			.($activity->get_special_adaptation() ? "true" : "false")
		);
		
		$result = $this->db->query('UPDATE activity_activity SET ' . join(',', $values) . " WHERE id=$id", __LINE__,__FILE__);

		return isset($result);
	}
	
	function import_activity($activity)
	{
		$id = intval($activity->get_id());
		$ts_now = strtotime('now');
		
		$columns = array(
			'title',
			'organization_id',
			'group_id',
			'district',
			'office',
			'category',
			'state',
			'target',
			'description',
			'arena',
			'time',
			'last_change_date',
			'create_date',
			'contact_person_1',
			'contact_person_2',
			'special_adaptation'
		);
			
		$values = array(
			$this->marshal($activity->get_title(), 'string'),
			$this->marshal($activity->get_organization_id(), 'int'),
			$this->marshal($activity->get_group_id(), 'int'),
			$this->marshal($activity->get_district(), 'string'),
			$this->marshal($activity->get_office(), 'int'),
			$this->marshal($activity->get_category(), 'int'),
			$this->marshal($activity->get_state(), 'int'),
			$this->marshal($activity->get_target(), 'string'),
			$this->marshal($activity->get_description(), 'string'),
			$this->marshal($activity->get_arena(), 'int'),
			$this->marshal($activity->get_time(), 'string'),
			$this->marshal($activity->get_last_change_date(), 'int'),
			$this->marshal($ts_now, 'int'),
			$this->marshal($activity->get_contact_person_1(), 'int'),
			$this->marshal($activity->get_contact_person_2(), 'int'),
			($activity->get_special_adaptation() ? "true" : "false")
		);
		
		$result = $this->db->query('INSERT INTO activity_activity (' . join(',', $columns) . ') VALUES (' . join(',', $values) . ')', __LINE__,__FILE__);

		return isset($result);
	}

	public function get_id_field_name($extended_info = false)
	{
		if(!$extended_info)
		{
			$ret = 'id';
		}
		else
		{
			$ret = array
			(
				'table'			=> 'activity', // alias
				'field'			=> 'id',
				'translated'	=> 'id'
			);
		}
		return $ret;
	}

	protected function populate(int $activity_id, &$activity)
	{

		if($activity == null) {
			$activity = new activitycalendar_activity((int) $activity_id);

			$activity->set_title($this->unmarshal($this->db->f('title'), 'string'));
			$activity->set_organization_id($this->unmarshal($this->db->f('organization_id'), 'int'));
			$activity->set_group_id($this->unmarshal($this->db->f('group_id'), 'int'));
			$activity->set_district($this->unmarshal($this->db->f('district'), 'int'));
			$activity->set_office($this->unmarshal($this->db->f('office'), 'int'));
			$activity->set_category($this->unmarshal($this->db->f('category'), 'int'));
			$activity->set_state($this->unmarshal($this->db->f('state'), 'int'));
			$activity->set_target($this->unmarshal($this->db->f('target'), 'string'));
			$activity->set_description($this->unmarshal($this->db->f('description'), 'string'));
			$activity->set_arena($this->unmarshal($this->db->f('arena'), 'string'));
			$activity->set_time($this->unmarshal($this->db->f('time'), 'string'));
			$activity->set_contact_person_1($this->unmarshal($this->db->f('contact_person_1'), 'int'));
			$activity->set_contact_person_2($this->unmarshal($this->db->f('contact_person_2'), 'int'));
			$activity->set_last_change_date($this->unmarshal($this->db->f('last_change_date'), 'int'));
			$activity->set_special_adaptation($this->unmarshal($this->db->f('special_adaptation', 'bool')));
		}
		return $activity;
	}
	
	function get_category_name($category_id)
	{
		$result = "Ingen";
		if($category_id != null)
		{
			$sql = "SELECT name FROM bb_activity where id=$category_id";
			$this->db->query($sql, __LINE__, __FILE__);
			while($this->db->next_record()){
				$result = $this->db->f('name');
			}
    	}
		return $result;
	}
	
	function get_categories()
	{
		$categories = array();
		$sql = "SELECT * FROM bb_activity where active=1 and parent_id=1";
		$this->db->query($sql, __LINE__, __FILE__);
		while($this->db->next_record()){
			$category = new activitycalendar_category($this->db->f('id'));
			$category->set_parent_id($this->db->f('parent_id'));
			$category->set_name($this->db->f('name'));
			$categories[] = $category;
		}
		return $categories;
	}
	
	function select_district_list()
	{
		$this->db->query("SELECT id, descr FROM fm_district where id >'0' ORDER BY id ", __LINE__, __FILE__);

		$i = 0;
		while ($this->db->next_record())
		{
			$district[$i]['id'] = $this->db->f('id');
			$district[$i]['name'] = stripslashes($this->db->f('descr'));
			$i++;
		}

		return $district;
	}
	
	function get_district_from_name($name)
	{
		$this->db->query("SELECT part_of_town_id FROM fm_part_of_town where name like UPPER('%{$name}%') ", __LINE__, __FILE__);
		while($this->db->next_record()){
			$result = $this->db->f('part_of_town_id');
		}	
		return $result;
	}
	
	function get_district_name($district_id)
	{
		//$result = "Ingen";
		$values = array();
		if($district_id != null)
		{
			$sql = "SELECT name FROM fm_part_of_town where part_of_town_id in ($district_id)";
			$this->db->query($sql, __LINE__, __FILE__);
			while($this->db->next_record()){
				$values[] = $this->db->f('name');
			}
    	}
    	$result = implode(",",$values);
		return $result;
	}
	
	function get_districts()
	{
		$this->db->query("SELECT part_of_town_id, name FROM fm_part_of_town district_id ", __LINE__, __FILE__);

		$i = 0;
		while ($this->db->next_record())
		{
			$district[$i]['part_of_town_id'] = $this->db->f('part_of_town_id');
			$district[$i]['name'] = stripslashes($this->db->f('name'));
			$i++;
		}

		return $district;
	}
	
	function get_office_name($district_id)
	{
		$result = "Ingen";
		if($district_id != null)
		{
			$sql = "SELECT descr FROM fm_district where id=$district_id";
			$this->db->query($sql, __LINE__, __FILE__);
			while($this->db->next_record()){
				$result = $this->db->f('descr');
			}
    	}
		return $result;
	}
	
	
	function get_target_name($target_id)
	{
		$result = "Ingen";
		if($target_id != null)
		{
			$sql = "SELECT name FROM bb_agegroup where id=$target_id";
			$this->db->query($sql, __LINE__, __FILE__);
			while($this->db->next_record()){
				$result = $this->db->f('name');
			}
    	}
		return $result;
	}
	
	function get_targets()
	{
		$targets = array();
		$sql = "SELECT * FROM bb_agegroup where active=1 ORDER BY sort";
		$this->db->query($sql, __LINE__, __FILE__);
		while($this->db->next_record()){
			$target = new activitycalendar_target($this->db->f('id'));
			$target->set_description($this->db->f('description'));
			$target->set_name($this->db->f('name'));
			$targets[] = $target;
		}
		return $targets;
	}
	
	function get_category_from_name($name)
	{
    	if($name != null)
    	{
			$sql = "select id from bb_activity where name like '%{$name}%'";
    		$this->db->query($sql, __LINE__, __FILE__);
			while($this->db->next_record()){
				$result = $this->db->f('id');
			}
    	}
		return $result;
	}
	
	function get_target_from_sort_id($id)
	{
    	if($id != null && is_numeric($id))
    	{
			$sql = "select id from bb_agegroup where sort={$id} and active=1";
    		$this->db->query($sql, __LINE__, __FILE__);
			while($this->db->next_record()){
				$result = $this->db->f('id');
			}
    	}
		return $result;
	}
	
	function get_orgid_from_orgno($orgno)
	{
    	if($orgno != null)
    	{
			$sql = "select id from bb_organization where organization_number='{$orgno}'";
    		$this->db->query($sql, __LINE__, __FILE__);
			while($this->db->next_record()){
				$result = $this->db->f('id');
			}
    	}
		return $result;
	}
	
	function update_org_description($org_id, $description)
	{
    	if($org_id != null)
    	{
			$sql = "update bb_organization set description='{$description}' where id={$org_id}";
    		$result = $this->db->query($sql, __LINE__, __FILE__);
    	}
		return isset($result);
	}
	
	function set_org_active($org_id)
	{
		if($org_id != null)
		{
			$sql = "update bb_organization set show_in_portal=1 where id={$org_id}";
    		$result = $this->db->query($sql, __LINE__, __FILE__);
		}
		return isset($result);
	}
	
	function get_activities()
	{
		$activities = array();
		$sql = "SELECT * FROM activity_activity";
		$this->db->query($sql, __LINE__, __FILE__);
		while ($this->db->next_record())
		{			
			$activities[]= array
			(
				'id'				=> (int) $this->db->f('id'),
				'title'				=> utf8_decode($this->db->f('title',true)),
				'organization_id'	=> $this->db->f('organization_id',true),
				'group_id'			=> $this->db->f('group_id'),
				'district'			=> $this->db->f('district',true),
				'category'			=> $this->db->f('category'),
				'state'				=> $this->db->f('state',true),
				'target'			=> $this->db->f('target'),
				'description'		=> utf8_decode($this->db->f('description')),
				'arena'				=> $this->db->f('arena'),
				'time'				=> utf8_decode($this->db->f('time')),
				'contact_person_1'	=> $this->db->f('contact_person_1'),
				'contact_person_2'	=> $this->db->f('contact_person_2'),
				'special_adaptation'=> $this->db->f('special_adaptation'),
			);
		}

		foreach ($activities as &$activity)
		{
				$activity['organization_info']	= $this->get_org_info($activity['organization_id']);
				$activity['group_info']			= $this->get_group_info($activity['group_id']);
				$activity['district_name']		= utf8_decode($this->get_district_name($activity['district']));
				$activity['category_name']		= utf8_decode($this->get_category_name($activity['category']));
				$activity['arena_info']			= $this->get_arena_info($activity['arena']);
				$activity['contact_person']		= $this->get_contact_person($activity['organization_id'],$activity['group_id'],$activity['contact_person_1']);
		}
//	_debug_array($activities);
		return $activities;
	}
	
	function get_contact_person($org_id, $group_id, $cont_pers)
	{
		if($group_id && $cont_pers)
		{
			$cont_pers = (int)$cont_pers;
	//		$this->db->query("SELECT * FROM bb_group_contact WHERE id={$cont_pers}", __LINE__, __FILE__);
			$this->db->query("SELECT * FROM bb_group_contact WHERE id={$cont_pers}", __LINE__, __FILE__);
			while($this->db->next_record()){
				$result = array('name' => utf8_decode($this->db->f('name')),'phone' => $this->db->f('phone'),'email' => $this->db->f('email'));
			}
		}
		else if($org_id && $cont_pers)
		{
			$cont_pers = (int)$cont_pers;
			$this->db->query("SELECT * FROM bb_organization_contact WHERE id={$cont_pers}", __LINE__, __FILE__);
			while($this->db->next_record()){
				$result = array('name' => utf8_decode($this->db->f('name')),'phone' => $this->db->f('phone'),'email' => $this->db->f('email'));
			}
		}
		return $result;
	}
	
	function get_org_info($org_id)
	{
		$result = array();
		if($org_id)
		{
			$org_id = (int)$org_id;
			$this->db->query("SELECT * FROM bb_organization WHERE id={$org_id}", __LINE__, __FILE__);
			$this->db->next_record();
			$result = array
			(
				'name'			=> utf8_decode($this->db->f('name')),
				'shortname'		=> utf8_decode($this->db->f('shortname')),
				'description'	=> utf8_decode($this->db->f('description')),
				'homepage'		=> $this->db->f('homepage'),
				'phone'			=> $this->db->f('phone'),
				'email'			=> $this->db->f('email')
			);
		}
		return $result;
	}
	
	function get_group_info($group_id)
	{
		$result = array();
		if($group_id)
		{
			$group_id = (int)$group_id;
			$this->db->query("SELECT * FROM bb_group WHERE id={$group_id}", __LINE__, __FILE__);
			$this->db->next_record();
			$result = array
			(
				'name'				=> utf8_decode($this->db->f('name')),
				'shortname'			=> utf8_decode($this->db->f('shortname')),
				'description'		=> utf8_decode($this->db->f('description')),
				'organization_id'	=> $this->db->f('organization_id')
			);

		}
		return $result;
	}
	
	function get_arena_info($arena_id)
	{
		$result = array();
		if($arena_id)
		{
			$arena_id = (int)$arena_id;
			$this->db->query("SELECT * FROM activity_arena WHERE id={$arena_id}", __LINE__, __FILE__);
			$this->db->next_record();
			$result = array
			(
				'arena_name' => utf8_decode($this->db->f('arena_name')),
				'address' => utf8_decode($this->db->f('address'))
			);
		}
		return $result;
	}
	
	function get_statuscodes()
	{
		$statuscodes[] = array('id' => '0', 'name' => 'Ingen');
		$statuscodes[] = array('id' => '1', 'name' => 'Ny');
		$statuscodes[] = array('id' => '2', 'name' => 'Endring');
		$statuscodes[] = array('id' => '3', 'name' => 'Akseptert');
		$statuscodes[] = array('id' => '4', 'name' => 'Behandlet');
		$statuscodes[] = array('id' => '5', 'name' => 'Avvist');

		return $statuscodes;
	}
	
	function get_targetgroups()
	{
		$sql = "SELECT * FROM bb_agegroup where active=1 ORDER BY sort";
		$this->db->query($sql, __LINE__, __FILE__);
		while($this->db->next_record()){
			$targets[] = array(
					'id'				=> (int) $this->db->f('id'),
					'name'				=> $this->db->f('name',true),
			);
		}
		return $targets;
	}
	
	function get_category_list()
	{
		$sql = "SELECT * FROM bb_activity where active=1 and parent_id=1";
		$this->db->query($sql, __LINE__, __FILE__);
		while($this->db->next_record()){
			$categories[] = array(
					'id'				=> (int) $this->db->f('id'),
					'name'				=> $this->db->f('name',true),
			);
		}
		return $categories;
	}
}
