<!-- $Id: no_access.xsl,v 1.2 2006/05/23 13:02:12 sigurdne Exp $ -->

	<xsl:template match="no_access">
		
		<xsl:choose>
			<xsl:when test="links !=''">
				<xsl:call-template name="menu"/> 
			</xsl:when>
		</xsl:choose>
		<table width="100%" cellpadding="2" cellspacing="2" align="center">
			<xsl:choose>
				<xsl:when test="msgbox_data != ''">
					<tr>
						<td align="left" colspan="3">
							<xsl:call-template name="msgbox"/>
						</td>
					</tr>
				</xsl:when>
			</xsl:choose>
		</table>
	</xsl:template>