<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" encoding="ISO-8859-1"/>

    <xsl:template match="/">
        <html>
            <xsl:apply-templates/>
        </html>
    </xsl:template>

    <xsl:template match="team">
        <head>
        	<!-- Frame title -->
            <title>
                <xsl:value-of select="name"/>
            </title>
        </head>

        <!-- Page body -->
        <body leftmargin="10" topmargin="10" bgcolor="#FFFFFF">
        	<table width="800" align="center"><tbody>
        		<div align="center">
        			<H1><xsl:value-of select="name"/></H1>
				<H3>Race : <xsl:value-of select="race"/></H3>
        		</div>
			<br/>
	        	<!-- The main table -->
			<table width="100%" cellspacing="1" cellpadding="2" border="rules">
				<thead>
					<tr bgcolor="gray">
						<th width="5%">#</th>
						<th width="25%">Name</th>
						<th width="15%">Position</th>
						<th width="5%">MA</th>
						<th width="5%">ST</th>
						<th width="5%">AG</th>
						<th width="5%">AV</th>
						<th width="35%">Skills</th>
					</tr>
				</thead>
				<tbody align="center">
					<xsl:for-each select="players/player">
						<tr>
							<td><xsl:value-of select="@number"/></td>
							<td align="left"><xsl:value-of select="name"/></td>
							<td><xsl:value-of select="position"/></td>
							<td><xsl:value-of select="ma"/></td>
							<td><xsl:value-of select="st"/></td>
							<td><xsl:value-of select="ag"/></td>
							<td><xsl:value-of select="av"/></td>
							<td align="left"><xsl:for-each select="skills/skill">
								<xsl:choose>
									<xsl:when test="position() = 1"><xsl:value-of select="current()"/></xsl:when>
									<xsl:otherwise>, <xsl:value-of select="current()"/></xsl:otherwise>
								</xsl:choose>
							</xsl:for-each><br/></td>
						</tr>
					</xsl:for-each>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="2" align="right" bgcolor="gray">Race</td>
						<td align="center"><xsl:value-of select="race"/></td>
						<td colspan="3" align="right" bgcolor="gray">Fan Factor</td>
						<td align="center"><xsl:value-of select="fanfactor"/></td>
                        <td bgcolor="gray"/>
					</tr>
                    <tr>
                        <td colspan="2" align="right" bgcolor="gray">Coach</td>
                        <td align="center"><xsl:value-of select="coach"/></td>
                        <td colspan="3" align="right" bgcolor="gray">Cheerleaders</td>
                        <td align="center"><xsl:value-of select="cheerleaders"/></td>
                        <td bgcolor="gray"/>
                    </tr>
                    <tr>
                        <td colspan="2" align="right" bgcolor="gray">Treasury</td>
                        <td align="center"><xsl:value-of select="treasury"/></td>
                        <td colspan="3" align="right" bgcolor="gray">Assistant Coaches</td>
                        <td align="center"><xsl:value-of select="assistants"/></td>
                        <td bgcolor="gray"/>
                    </tr>
                    <tr>
                        <td colspan="2" align="right" bgcolor="gray">Rerolls</td>
                        <td align="center"><xsl:value-of select="rerolls"/></td>
                        <td colspan="3" align="right" bgcolor="gray">Apothecary</td>
                        <td align="center"><xsl:value-of select="apothecary"/></td>
                        <td bgcolor="gray"/>
                    </tr>
				</tfoot>
	        	</table>
            </tbody></table>
        </body>
        <br/>
        <br/>
    </xsl:template>

</xsl:stylesheet>
