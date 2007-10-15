<!-- $Id: branch_select.xsl,v 1.1 2005/01/17 10:03:18 sigurdne Exp $ -->

	<xsl:template name="branch_select">
	<xsl:variable name="lang_branch_statustext"><xsl:value-of select="lang_branch_statustext"/></xsl:variable>
	<xsl:variable name="select_name"><xsl:value-of select="select_branch"/></xsl:variable>
		<select name="{$select_name}" class="forms" onMouseover="window.status='{$lang_branch_statustext}'; return true;" onMouseout="window.status='';return true;">
			<option value=""><xsl:value-of select="lang_no_branch"/></option>
				<xsl:apply-templates select="branch_list"/>
		</select>
	</xsl:template>

	<xsl:template match="branch_list">
	<xsl:variable name="id"><xsl:value-of select="id"/></xsl:variable>
		<xsl:choose>
			<xsl:when test="selected">
				<option value="{$id}" selected="selected"><xsl:value-of disable-output-escaping="yes" select="name"/></option>
			</xsl:when>
			<xsl:otherwise>
				<option value="{$id}"><xsl:value-of disable-output-escaping="yes" select="name"/></option>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>