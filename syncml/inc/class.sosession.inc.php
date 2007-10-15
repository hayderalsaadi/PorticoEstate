<?php
	/**
	 * phpGroupWare (http://phpgroupware.org/)
	 * SyncML interface
	 *
	 * @author    Johan Gunnarsson <johang@phpgroupware.org>
	 * @copyright Copyright (c) 2007 Free Software Foundation, Inc.
	 * @license   GNU General Public License 3 or later
	 * @package   syncml
	 * @version   $Id: class.sosession.inc.php,v 1.4 2007/08/27 07:49:58 johang Exp $
	 */

	/**
	 * Manage input/output to/from the mappings table.
	 */
	class syncml_sosession
	{
		/**
		 * Cleans out old and abandoned session mappings.
		 */
		function clean_session_mappings()
		{
			$GLOBALS['phpgw']->db->query(sprintf('
				DELETE FROM phpgw_syncml_sessions
				WHERE
					session_dla < %d',
				time() - 10),
				__LINE__, __FILE__);
		}

		/**
		 * Hashes session specific data in header.
		 *
		 * @param  $header Header xml.
		 * @return Session hash.
		 */
		function generate_session_hash($header)
		{
			if(is_array($header))
			{
				return md5(var_export($header, true));
			}
			else
			{
				return (string)$header;
			}
		}

		/**
		 * Get phpgw session id from SyncML header data.
		 *
		 * @param $locuri
		 * @param $locname
		 * @param $sessionid
		 * @return int Session ID
		 * @access private
		 */
		function get_session_mapping($header)
		{
			$syncml_hash = $this->generate_session_hash($header);

			$this->clean_session_mappings();

			$GLOBALS['phpgw']->db->query(sprintf('
				UPDATE phpgw_syncml_sessions
				SET session_dla = %d
				WHERE
					syncml_hash = \'%s\'',
				time(), $syncml_hash),
				__LINE__, __FILE__);

			$GLOBALS['phpgw']->db->query(sprintf("
				SELECT
					phpgw_sid,
					next_nonce
				FROM phpgw_syncml_sessions
				WHERE
					syncml_hash = '%s'",
				$GLOBALS["phpgw"]->db->db_addslashes($syncml_hash)),
				__LINE__, __FILE__);

			if(!$GLOBALS['phpgw']->db->next_record())
			{
				$this->set_session_mapping($syncml_hash, '');

				return array('', '');
			}

			return array(
				$GLOBALS['phpgw']->db->f('phpgw_sid'),
				$GLOBALS['phpgw']->db->f('next_nonce')
			);
		}

		function remove_session_mapping($header)
		{
			$syncml_hash = $this->generate_session_hash($header);

			$GLOBALS['phpgw']->db->query(sprintf("
				DELETE FROM phpgw_syncml_sessions
				WHERE
					syncml_hash = '%s'",
				$GLOBALS["phpgw"]->db->db_addslashes($syncml_hash)),
				__LINE__, __FILE__);
		}

		/**
		 * Creates a new session mapping. If old exists, it will get
		 * replaced.
		 *
		 * @param $syncml_header Either the synchdr xml arrasy or a string.
		 * @param $phpgw_sid     phpgw session id.
		 */
		function set_session_mapping($header, $phpgw_sid)
		{
			$syncml_hash = $this->generate_session_hash($header);

			$GLOBALS['phpgw']->db->query(sprintf("
				DELETE FROM phpgw_syncml_sessions
				WHERE
					syncml_hash = '%s'",
				$GLOBALS["phpgw"]->db->db_addslashes($syncml_hash)),
				__LINE__, __FILE__);

			$GLOBALS['phpgw']->db->query(sprintf("
				INSERT INTO phpgw_syncml_sessions(
					syncml_hash, phpgw_sid, session_dla)
				VALUES('%s', '%s', '%d')",
				$GLOBALS["phpgw"]->db->db_addslashes($syncml_hash),
				$GLOBALS["phpgw"]->db->db_addslashes($phpgw_sid),
				time()),
				__LINE__, __FILE__);
		}

		/**
		 *
		 */
		function set_next_nonce($header, $next_nonce)
		{
			$syncml_hash = $this->generate_session_hash($header);

			$GLOBALS['phpgw']->db->query(sprintf('
				UPDATE phpgw_syncml_sessions
				SET next_nonce = \'%s\'
				WHERE
					syncml_hash = \'%s\'',
				$GLOBALS["phpgw"]->db->db_addslashes($next_nonce),
				$syncml_hash),
				__LINE__, __FILE__);
		}
	}