<?php
	/**
	* phpGroupWare - SMS: A SMS Gateway.
	*
	* @author Sigurd Nes <sigurdne@online.no>
	* @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
	* @package sms
	* @subpackage config
 	* @version $Id: class.soconfig.inc.php,v 1.6 2007/03/10 15:21:46 sigurdne Exp $
	*/

	/**
	 * Description
	 * @package sms
	 */

	class sms_soconfig
	{
		var $grants;
		var $db;
		var $db2;
		var $account;
		var $config_data;

		function sms_soconfig()
		{
			$this->currentapp	= 'sms';
			$this->account		= $GLOBALS['phpgw_info']['user']['account_id'];
			$this->bocommon		= CreateObject($this->currentapp.'.bocommon');
			$this->db 		= clone($GLOBALS['phpgw']->db);
			$this->db2 		= clone($this->db);

			$this->grants		= $GLOBALS['phpgw']->acl->get_grants('sms');
			$this->join		= $this->db->join;
			$this->like		= $this->db->like;

		}


		function read_repository()
		{
			$sql = "SELECT phpgw_sms_config_type.name as type, value as config_value, phpgw_sms_config_attrib.name as config_name "
				. " FROM phpgw_sms_config_value $this->join phpgw_sms_config_attrib ON "
				. " phpgw_sms_config_value.attrib_id = phpgw_sms_config_attrib.id AND "
				. " phpgw_sms_config_value.type_id = phpgw_sms_config_attrib.type_id"
				. " $this->join phpgw_sms_config_type ON  phpgw_sms_config_value.type_id = phpgw_sms_config_type.id";
				
			$this->db->query($sql,__LINE__,__FILE__);
			while ($this->db->next_record())
			{
				$test = @unserialize($this->db->f('config_value'));
				if($test)
				{
					$this->config_data[$this->db->f('type')][$this->db->f('config_name')] = $test;
				}
				else
				{
					$this->config_data[$this->db->f('type')][$this->db->f('config_name')] = $this->db->f('config_value');
				}
			}
		}


		function read_type($data)
		{
			if(is_array($data))
			{
				if ($data['start'])
				{
					$start=$data['start'];
				}
				else
				{
					$start=0;
				}
				$query		= (isset($data['query'])?$data['query']:'');
				$sort		= (isset($data['sort'])?$data['sort']:'DESC');
				$order		= (isset($data['order'])?$data['order']:'');
				$allrows	= (isset($data['allrows'])?$data['allrows']:'');
			}

			if ($order)
			{
				$ordermethod = " order by $order $sort";

			}
			else
			{
				$ordermethod = ' order by name asc';
			}

			$table = 'phpgw_sms_config_type';

			if($query)
			{
				$query = ereg_replace("'",'',$query);
				$query = ereg_replace('"','',$query);

				$querymethod = " WHERE name $this->like '%$query%'";
			}

			$sql = "SELECT * FROM $table $querymethod";

			$this->db2->query($sql,__LINE__,__FILE__);
			$this->total_records = $this->db2->num_rows();

			if(!$allrows)
			{
				$this->db->limit_query($sql . $ordermethod,$start,__LINE__,__FILE__);
			}
			else
			{
				$this->db->query($sql . $ordermethod,__LINE__,__FILE__);
			}

			while ($this->db->next_record())
			{
				$config_info[] = array
				(
					'id'	=> $this->db->f('id'),
					'name'	=> stripslashes($this->db->f('name')),
					'descr'	=> stripslashes($this->db->f('descr'))
				);
			}

			return $config_info;
		}


		function read_single_type($id)
		{
			$sql = 'SELECT * FROM phpgw_sms_config_type where id=' . intval($id);

			$this->db->query($sql,__LINE__,__FILE__);

			if ($this->db->next_record())
			{
				$values['id']		= $id;
				$values['name']		= stripslashes($this->db->f('name'));
				$values['descr']	= stripslashes($this->db->f('descr'));
			}
			return $values;
		}


		function add_type($values)
		{
			$this->db->transaction_begin();

			$values['name'] = $this->db->db_addslashes($values['name']);
			$values['descr'] = $this->db->db_addslashes($values['descr']);
			$values['type_id'] = $this->bocommon->next_id('phpgw_sms_config_type');

			$insert_values=array(
				$values['type_id'],
				$values['name'],
				$values['descr'],
				);

			$insert_values	= $this->bocommon->validate_db_insert($insert_values);
			$this->db->query("INSERT INTO phpgw_sms_config_type (id,name,descr) "
				. "VALUES ($insert_values)",__LINE__,__FILE__);

			$receipt['message'][]=array('msg'=>lang('config type has been saved'));
			$receipt['type_id']= $values['type_id'];

			$this->db->transaction_commit();

			return $receipt;
		}

		function edit_type($values)
		{
			$this->db->transaction_begin();

			$value_set['name']		= $this->db->db_addslashes($values['name']);
			$value_set['descr']		= $this->db->db_addslashes($values['descr']);

			$value_set	= $this->bocommon->validate_db_update($value_set);

			$this->db->query("UPDATE phpgw_sms_config_type set $value_set WHERE id=" . $values['type_id'],__LINE__,__FILE__);

			$this->db->transaction_commit();

			$receipt['message'][]=array('msg'=>lang('config type has been edited'));

			$receipt['type_id']= $values['type_id'];
			return $receipt;
		}

		function delete_type($id)
		{
			$this->db->transaction_begin();
			$this->db->query('DELETE FROM phpgw_sms_config_value WHERE type_id =' . intval($type_id),__LINE__,__FILE__);
			$this->db->query('DELETE FROM phpgw_sms_config_choice WHERE type_id =' . intval($type_id),__LINE__,__FILE__);
			$this->db->query('DELETE FROM phpgw_sms_config_attrib WHERE type_id =' . intval($type_id),__LINE__,__FILE__);
			$this->db->query('DELETE FROM phpgw_sms_config_type WHERE id='  . intval($id),__LINE__,__FILE__);
			$this->db->transaction_commit();
		}

		function read_attrib($data)
		{
			if(is_array($data))
			{
				if ($data['start'])
				{
					$start=$data['start'];
				}
				else
				{
					$start=0;
				}
				$query		= (isset($data['query'])?$data['query']:'');
				$sort		= (isset($data['sort'])?$data['sort']:'DESC');
				$order		= (isset($data['order'])?$data['order']:'');
				$allrows	= (isset($data['allrows'])?$data['allrows']:'');
				$type_id	= (isset($data['type_id'])?$data['type_id']:0);
			}

			if ($order)
			{
				$ordermethod = " order by $order $sort";

			}
			else
			{
				$ordermethod = ' order by name asc';
			}

			$attrib_table = 'phpgw_sms_config_attrib';
			$value_table = 'phpgw_sms_config_value';
			
			if($query)
			{
				$query = ereg_replace("'",'',$query);
				$query = ereg_replace('"','',$query);

				$querymethod = " AND name $this->like '%$query%'";
			}

			$sql = "SELECT * , $value_table.id as value_id FROM $attrib_table LEFT JOIN $value_table ON ($attrib_table.type_id = $value_table.type_id AND $attrib_table.id = $value_table.attrib_id )WHERE $attrib_table.type_id = '$type_id' $querymethod";

			$this->db2->query($sql,__LINE__,__FILE__);
			$this->total_records = $this->db2->num_rows();

			if(!$allrows)
			{
				$this->db->limit_query($sql . $ordermethod,$start,__LINE__,__FILE__);
			}
			else
			{
				$this->db->query($sql . $ordermethod,__LINE__,__FILE__);
			}

			while ($this->db->next_record())
			{
				$config_info[] = array
				(
					'id'		=> $this->db->f(1),
					'type_id'	=> $this->db->f('type_id'),
					'value_id'	=> $this->db->f('value_id'),					
					'name'		=> stripslashes($this->db->f('name')),
					'value'		=> stripslashes($this->db->f('value'))
				);
			}

			return $config_info;
		}


		function read_single_attrib($type_id,$id)
		{
			$sql = 'SELECT * FROM phpgw_sms_config_attrib WHERE type_id =' . intval($type_id) . ' AND id=' . intval($id);

			$this->db->query($sql,__LINE__,__FILE__);

			if ($this->db->next_record())
			{
				$values['id']		= $id;
				$values['input_type']	= $this->db->f('input_type');
				$values['name']		= stripslashes($this->db->f('name'));
				$values['descr']	= stripslashes($this->db->f('descr'));
				if($this->db->f('input_type')=='listbox')
				{
					$values['choice'] = $this->read_attrib_choice($type_id,$id);
				}
			}

			return $values;
		}


		function read_attrib_choice($type_id,$attrib_id)
		{
			$choice_table = 'phpgw_sms_config_choice';
			$sql = "SELECT * FROM $choice_table WHERE type_id=$type_id AND attrib_id=$attrib_id ";
			$this->db->query($sql,__LINE__,__FILE__);

			while ($this->db->next_record())
			{
				$choice[] = array
				(
					'id'	=> $this->db->f('id'),
					'value'	=> $this->db->f('value')
				);
			}
			return $choice;
		}


		function add_attrib($values)
		{
			$this->db->transaction_begin();

			$values['name'] = $this->db->db_addslashes($values['name']);
			$values['descr'] = $this->db->db_addslashes($values['descr']);
			$values['attrib_id'] = $this->bocommon->next_id('phpgw_sms_config_attrib',array('type_id'=>$values['type_id']));

			$insert_values=array(
				$values['type_id'],
				$values['attrib_id'],
				$values['input_type'],
				$values['name'],
				$values['descr'],
				);

			$insert_values	= $this->bocommon->validate_db_insert($insert_values);
			$this->db->query("INSERT INTO phpgw_sms_config_attrib (type_id,id,input_type,name,descr) "
				. "VALUES ($insert_values)",__LINE__,__FILE__);

			$receipt['message'][]=array('msg'=>lang('config attrib has been saved'));
			$receipt['attrib_id']= $values['attrib_id'];

			$this->db->transaction_commit();

			return $receipt;
		}

		function edit_attrib($values)
		{
			$this->db->transaction_begin();

			$value_set['name']	= $this->db->db_addslashes($values['name']);
			$value_set['descr']	= $this->db->db_addslashes($values['descr']);
			$value_set['input_type']	= $values['input_type'];

			$value_set	= $this->bocommon->validate_db_update($value_set);

			$this->db->query("UPDATE phpgw_sms_config_attrib set $value_set WHERE type_id =" . $values['type_id'] . " AND id=" . $values['attrib_id'],__LINE__,__FILE__);


			if($values['new_choice'])
			{
				$choice_id = $this->bocommon->next_id('phpgw_sms_config_choice' ,array('type_id'=>$values['type_id'],'attrib_id'=>$values['attrib_id']));
	
				$values_insert= array(
					$values['type_id'],
					$values['attrib_id'],
					$choice_id,
					$values['new_choice']
					);

				$values_insert	= $this->bocommon->validate_db_insert($values_insert);

				$this->db->query("INSERT INTO phpgw_sms_config_choice (type_id,attrib_id,id,value) "
				. "VALUES ($values_insert)",__LINE__,__FILE__);
			}

			if($values['delete_choice'])
			{
				for ($i=0;$i<count($values['delete_choice']);$i++)
				{
					$this->db->query("DELETE FROM phpgw_sms_config_choice WHERE type_id=" . $values['type_id']. " AND attrib_id=" . $values['attrib_id']  ." AND id=" . $values['delete_choice'][$i],__LINE__,__FILE__);
				}
			}


			$this->db->transaction_commit();

			$receipt['message'][]=array('msg'=>lang('config attrib has been edited'));

			$receipt['attrib_id']= $values['attrib_id'];
			return $receipt;
		}

		function delete_attrib($type_id,$id)
		{
			$this->db->transaction_begin();
			$this->db->query('DELETE FROM phpgw_sms_config_value WHERE type_id =' . intval($type_id) . ' AND attrib_id=' . intval($id),__LINE__,__FILE__);
			$this->db->query('DELETE FROM phpgw_sms_config_choice WHERE type_id =' . intval($type_id) . ' AND attrib_id=' . intval($id),__LINE__,__FILE__);
			$this->db->query('DELETE FROM phpgw_sms_config_attrib WHERE type_id =' . intval($type_id) . ' AND id=' . intval($id),__LINE__,__FILE__);
			$this->db->transaction_commit();
		}

		function read_value($data)
		{
			if(is_array($data))
			{
				if ($data['start'])
				{
					$start=$data['start'];
				}
				else
				{
					$start=0;
				}
				$query		= (isset($data['query'])?$data['query']:'');
				$sort		= (isset($data['sort'])?$data['sort']:'DESC');
				$order		= (isset($data['order'])?$data['order']:'');
				$allrows	= (isset($data['allrows'])?$data['allrows']:'');
				$type_id	= (isset($data['type_id'])?$data['type_id']:0);
				$attrib_id	= (isset($data['attrib_id'])?$data['attrib_id']:0);
			}

			if ($order)
			{
				$ordermethod = " order by $order $sort";

			}
			else
			{
				$ordermethod = ' order by value asc';
			}

			$table = 'phpgw_sms_config_value';

			if($query)
			{
				$query = ereg_replace("'",'',$query);
				$query = ereg_replace('"','',$query);

				$querymethod = " AND name $this->like '%$query%'";
			}

			$sql = "SELECT * FROM $table WHERE type_id = '$type_id' AND attrib_id = '$attrib_id' $querymethod";

			$this->db2->query($sql,__LINE__,__FILE__);
			$this->total_records = $this->db2->num_rows();

			if(!$allrows)
			{
				$this->db->limit_query($sql . $ordermethod,$start,__LINE__,__FILE__);
			}
			else
			{
				$this->db->query($sql . $ordermethod,__LINE__,__FILE__);
			}

			while ($this->db->next_record())
			{
				$config_info[] = array
				(
					'id'		=> $this->db->f('id'),
					'type_id'	=> $type_id,
					'attrib_id'	=> $attrib_id,
					'value'		=> stripslashes($this->db->f('value')),

				);
			}

			return $config_info;
		}


		function read_single_value($type_id,$attrib_id,$id)
		{
			$sql = 'SELECT * FROM phpgw_sms_config_value WHERE type_id =' . intval($type_id) . ' AND attrib_id=' . intval($attrib_id) . ' AND id=' . intval($id);

			$this->db->query($sql,__LINE__,__FILE__);

			if ($this->db->next_record())
			{
				$values['id']		= $id;
				$values['value']	= stripslashes($this->db->f('value'));
			}

			return $values;
		}

		function add_value($values)
		{
			$this->db->transaction_begin();

			$values['value'] = $this->db->db_addslashes($values['value']);
			$id = $this->bocommon->next_id('phpgw_sms_config_value',array('type_id'=>$values['type_id'],'attrib_id'=>$values['attrib_id']));

			$insert_values=array(
				$values['type_id'],
				$values['attrib_id'],
				$id,
				$values['value']
				);

			$insert_values	= $this->bocommon->validate_db_insert($insert_values);
			$this->db->query("INSERT INTO phpgw_sms_config_value (type_id,attrib_id,id,value) "
				. "VALUES ($insert_values)",__LINE__,__FILE__);

			$receipt['message'][]=array('msg'=>lang('config value has been saved'));
			$receipt['attrib_id']= $values['attrib_id'];

			$this->db->transaction_commit();

			return $receipt;
		}

		function edit_value($values)
		{
			if(!$values['value'])
			{
				$this->delete_value($values['type_id'],$values['attrib_id'],$values['id']);
			}
			else
			{
				$this->db->transaction_begin();
				$value_set['value']	= $this->db->db_addslashes($values['value']);
				$value_set	= $this->bocommon->validate_db_update($value_set);
				$this->db->query("UPDATE phpgw_sms_config_value set $value_set WHERE type_id =" . $values['type_id'] . " AND attrib_id=" . $values['attrib_id'] . " AND id=" . $values['id'],__LINE__,__FILE__);
				$this->db->transaction_commit();
			}

			$receipt['message'][]=array('msg'=>lang('config attrib has been edited'));

			$receipt['attrib_id']= $values['attrib_id'];
			return $receipt;
		}

		function delete_value($type_id,$attrib_id,$id)
		{
			$this->db->transaction_begin();
			$this->db->query('DELETE FROM phpgw_sms_config_value WHERE type_id =' . intval($type_id) . ' AND attrib_id=' . intval($attrib_id) . ' AND id=' . intval($id),__LINE__,__FILE__);
			$this->db->transaction_commit();
		}



		function select_choice_list($type_id,$attrib_id)
		{
			$this->db->query('SELECT * FROM phpgw_sms_config_choice WHERE type_id =' . intval($type_id) . ' AND attrib_id=' . intval($attrib_id) . ' ORDER BY value');

			while ($this->db->next_record())
			{
				$choice[] = array(
					'id'	=> stripslashes($this->db->f('value')),
					'name'	=> stripslashes($this->db->f('value'))
					);
			}
			return $choice;
		}
		function select_conf_list()
		{
			$this->db->query("SELECT * FROM phpgw_sms_config_type  ORDER BY name ");

			$i = 0;
			while ($this->db->next_record())
			{
				$type[$i]['id']			= $this->db->f('id');
				$type[$i]['name']		= stripslashes($this->db->f('name'));
				$i++;
			}
			return $type;
		}
	}