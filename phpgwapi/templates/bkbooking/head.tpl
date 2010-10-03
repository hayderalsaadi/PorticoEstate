<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<!-- BEGIN head -->
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="author" content="phpGroupWare http://www.phpgroupware.org">
		<meta name="description" content="phpGroupWare">
		<meta name="keywords" content="phpGroupWare">
		<meta name="robots" content="none">
		<title>{site_title}</title>
		<link rel="icon" href="{img_icon}" type="image/x-ico">
		<link rel="shortcut icon" href="{img_icon}">
		<!-- BEGIN stylesheet -->
        	<link href="{stylesheet_uri}" type="text/css" rel="StyleSheet">
        <!-- END stylesheet -->

		{css}

		<script type="text/javascript">
		<!--
			var strBaseURL = '{str_base_url}';
			{win_on_events}
		//-->
		</script>
		{javascript}
		<!-- BEGIN javascript -->
       		<script type="text/javascript" src="{javascript_uri}"></script>
    	<!-- END javascript -->

	</head>
<body>
    <div id="wrapper">
    <div id="header">
	<div id="login-bar">
		<a href="{login_url}">{login_text}</a>
	</div>
        <a href="index.php?menuaction=bookingfrontend.uisearch.index"><div id="logo" style="background: url({logofile}) no-repeat"></div></a>
		<div id="centerimage"></div>
		<form action="index.php" method="get" id="header-search" class="{header_search_class}">
			<input type="hidden" name="menuaction" value="bookingfrontend.uisearch.index" />
          	<input class="query
" type="text" name="searchterm"/>
      		<xsl:text> </xsl:text><input type="submit" value="{lbl_search}"/>
		</form>
    </div>
<div class="yui-skin-sam" id='frontend'>
  <div style='margin:0; padding: 0; line-height: 0'>&nbsp;</div>