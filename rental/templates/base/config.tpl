<!-- BEGIN header -->
<form method="POST" action="{action_url}">
<table border="0" align="center" width="85%">
   <tr class="th">
    <td colspan="2"><font color="{th_text}">&nbsp;<b>{title}</b></font></td>
   </tr>
<!-- END header -->
<!-- BEGIN body -->
   <tr class="row_on">
    <td colspan="2">&nbsp;</td>
   </tr>
   <tr class="row_off">
    <td colspan="2">&nbsp;<b>{lang_rental}</b></td>
   </tr>
   <tr class="row_on">
    <td>{lang_area_suffix}:</td>
    <td><input name="newsettings[area_suffix]" value="{value_area_suffix}"></td>
   </tr>
   <tr class="row_off">
    <td>{lang_currency_prefix}:</td>
    <td><input name="newsettings[currency_prefix]" value="{value_currency_prefix}"></td>
   </tr>
   <tr class="row_on">
    <td>{lang_currency_suffix}:</td>
    <td><input name="newsettings[currency_suffix]" value="{value_currency_suffix}"></td>
   </tr>
   <tr class="row_off">
    <td>{lang_serial_start}:</td>
    <td><input name="newsettings[serial_start]" value="{value_serial_start}"></td>
   </tr>
   <tr class="row_on">
    <td>{lang_serial_stop}:</td>
    <td><input name="newsettings[serial_stop]" value="{value_serial_stop}"></td>
   </tr>
   <tr class="row_off">
    <td>{lang_billing_time_limit}:</td>
    <td><input name="newsettings[billing_time_limit]" value="{value_billing_time_limit}"></td>
   </tr>
   		<tr class="row_off">
			<td colspan="2">&nbsp;<b>{lang_external_db}</b></td>
		</tr>
		<tr class="row_off">
			<td>{lang_Debug}:</td>
			<td>
				<select name="newsettings[external_db_debug]">
					<option value="" {selected_external_db_debug_}>NO</option>
					<option value="1" {selected_external_db_debug_1}>YES</option>
				</select>
			</td>
		</tr>
		<tr class="row_off">
			<td>{lang_login_external_db_host}:</td>
			<td><input name="newsettings[external_db_host]" value="{value_external_db_host}"></td>
		</tr>
		<tr class="row_off">
			<td>{lang_type}:</td>
			<td>
				<select name="newsettings[external_db_type]">
					<option value="" {selected_external_db_type_}>None</option>
					<option value="mssql" {selected_external_db_type_mssql}>mssql</option>
					<option value="mysql" {selected_external_db_type_mysql}>mysql</option>
					<option value="oracle" {selected_external_db_type_oracle}>oracle</option>
					<option value="postgres" {selected_external_db_type_postgres}>postgres</option>
				</select>
			</td>
		</tr>
		<tr class="row_off">
			<td>{lang_login_external_db_name}:</td>
			<td><input name="newsettings[external_db_name]" value="{value_external_db_name}"></td>
		</tr>
		<tr class="row_off">
			<td>{lang_login_external_db_user}:</td>
			<td><input name="newsettings[external_db_user]" value="{value_external_db_user}"></td>
		</tr>


		<tr class="row_off">
			<td>{lang_login_external_db_password}:</td>
			<td><input type ="password" name="newsettings[external_db_password]" value="{value_external_db_password}"></td>
		</tr>

<!-- END body -->
<!-- BEGIN footer -->
  <tr class="th">
    <td colspan="2">
&nbsp;
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" name="submit" value="{lang_submit}">
      <input type="submit" name="cancel" value="{lang_cancel}">
    </td>
  </tr>
</table>
</form>
<!-- END footer -->