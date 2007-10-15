<?php
	/**
	* phpGroupWare - property: a Facilities Management System.
	*
	* @author Sigurd Nes <sigurdne@online.no>
	* @copyright Copyright (C) 2003,2004,2005,2006,2007 Free Software Foundation, Inc. http://www.fsf.org/
	* This file is part of phpGroupWare.
	*
	* phpGroupWare is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* phpGroupWare is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with phpGroupWare; if not, write to the Free Software
	* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
	* @package property
	* @subpackage admin
 	* @version $Id: class.sostandard_2.inc.php,v 1.11 2007/01/26 14:53:47 sigurdne Exp $
	*/

	/**
	 * Description
	 * @package property
	 */

	class property_sostandard_2
	{

		function property_sostandard_2()
		{
			$this->currentapp	= $GLOBALS['phpgw_info']['flags']['currentapp'];
			$this->account		= $GLOBALS['phpgw_info']['user']['account_id'];
			$this->bocommon		= CreateObject($this->currentapp.'.bocommon');
			$this->db           	= $this->bocommon->new_db();
			$this->db2           	= $this->bocommon->new_db();

			$this->join		= $this->bocommon->join;
			$this->like		= $this->bocommon->like;
		}

		function read($data)
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
				$type		= (isset($data['type'])?$data['type']:0);
				$allrows	= (isset($data['allrows'])?$data['allrows']:'');
			}

			if ($order)
			{
				$ordermethod = " order by $order $sort";

			}
			else
			{
				$ordermethod = ' order by id asc';
			}

			$table = $this->select_table($type);

			if($query)
			{
				$query = ereg_replace("'",'',$query);
				$query = ereg_replace('"','',$query);

				$querymethod = " where id $this->like '%$query%' or descr $this->like '%$query%'";
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
				$standard[] = array
				(
					'id'	=> $this->db->f('id'),
					'descr'	=> $this->db->f('descr')
				);
			}
			return $standard;
		}


		function select_table($type)
		{

			switch($type)
			{
				case 'workorder_status':
					$table='fm_workorder_status';
					break;
				case 'request_status':
					$table='fm_request_status';
					break;
				case 'agreement_status':
					$table='fm_agreement_status';
					break;
				case 'building_part':
					$table='fm_building_part';
					break;
				case 'document_status':
					$table='fm_document_status';
					break;
				case 'unit':
					$table='fm_standard_unit';
					break;
			}

			return $table;
		}


		function read_single($id,$type)
		{

			$table = $this->select_table($type);

			$sql = "SELECT * FROM $table  where id='$id'";

			$this->db->query($sql,__LINE__,__FILE__);

			if ($this->db->next_record())
			{
				$standard['id']			= $this->db->f('id');
				$standard['descr']		= $this->db->f('descr');

				return $standard;
			}
		}

		function add($standard,$type)
		{
			$table = $this->select_table($type);

			$standard['descr'] = $this->db->db_addslashes($standard['descr']);
			
			$this->db->transaction_begin();

			$this->db->query("INSERT INTO $table (id, descr) "
				. "VALUES ('" . $standard['id'] . "','" . $standard['descr']. "')",__LINE__,__FILE__);

			$this->db->transaction_commit();
			$receipt['message'][] = array('msg' => lang('standard has been saved'));
			return $receipt;
		}

		function edit($standard,$type)
		{

			$table = $this->select_table($type);

			$standard['descr'] = $this->db->db_addslashes($standard['descr']);

			$this->db->transaction_begin();

			$this->db->query("UPDATE $table set descr='" . $standard['descr']
							. "' WHERE id='" . $standard['id']. "'",__LINE__,__FILE__);

			$this->db->transaction_commit();
			$receipt['message'][] = array('msg' => lang('standard has been edited'));
			return $receipt;
		}

		function delete($id,$type)
		{
			$table = $this->select_table($type);
			$this->db->transaction_begin();
			$this->db->query("DELETE FROM $table WHERE id=" . (int)$id,__LINE__,__FILE__);
			$this->db->transaction_commit();
		}
	}
?>