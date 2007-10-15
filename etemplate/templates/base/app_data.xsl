<!-- $Id: app_data.xsl,v 1.1 2007/02/04 13:53:48 sigurdne Exp $ -->

	<xsl:template match="phpgw">
	<xsl:variable name="webserver_url"><xsl:value-of select="webserver_url"/></xsl:variable>
		<script type="text/javascript" language="javascript" src="{$webserver_url}/phpgwapi/templates/default/default_scripts.js"></script>
		<xsl:choose>
			<xsl:when test="app_java_script != ''">
				<script type="text/javascript" language="javascript">
					<xsl:value-of disable-output-escaping="yes" select="app_java_script"/>
				</script>
			</xsl:when>
		</xsl:choose>
		<xsl:choose>
			<xsl:when test="app_java_script_url != ''">
				<xsl:variable name="app_java_script_url" select="app_java_script_url"/>
				<script type="text/javascript" language="javascript" src="{$webserver_url}/{$current_app}/templates/{$app_java_script_url}"></script>
			</xsl:when>
		</xsl:choose>
		<table width="100%" height="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td width="100%" height="100%" valign="top" align="center" class="app_body">
					<xsl:choose>
						<xsl:when test="msgbox_data">
							<xsl:call-template name="msgbox"/>
						</xsl:when>
					</xsl:choose>
					<xsl:value-of disable-output-escaping="yes" select="body_data"/>
				</td>
			</tr>
		</table>
	</xsl:template>


