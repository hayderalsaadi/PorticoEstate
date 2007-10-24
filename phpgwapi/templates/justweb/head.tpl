<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- BEGIN head -->
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="AUTHOR" content="phpGroupWare http://www.phpgroupware.org" />
		<meta name="description" CONTENT="phpGroupWare" />
		<meta name="keywords" CONTENT="phpGroupWare" />
		<meta name="robots" content="none" />
		<link rel="ICON" href="{img_icon}" type="image/x-ico" />
		<link rel="SHORTCUT ICON" href="{img_shortcut}" />
		<style type="text/css">
			{css}
		</style>	
		<title>{website_title}</title>
		{java_script}
		<script type="text/javascript" src="{webserver_url}/phpgwapi/templates/justweb/navcond.js"></script>
		<script type="text/javascript">
		//<![CDATA[		
			var myNavBar1 = new NavBar(0);
			var dhtmlMenu;
			
			//define menu items (first parameter of NavBarMenu specifies main category width, second specifies sub category width in pixels)
			//add more menus simply by adding more "blocks" of same code below
			
			dhtmlMenu = new NavBarMenu(60, 120);
			dhtmlMenu.addItem(new NavBarMenuItem("Home", "{home}"));
			myNavBar1.addMenu(dhtmlMenu);
			
			dhtmlMenu = new NavBarMenu(60, 140);
			dhtmlMenu.addItem(new NavBarMenuItem("Edit", ""));
			dhtmlMenu.addItem(new NavBarMenuItem("Add new Appointment", "{appt}"));
			dhtmlMenu.addItem(new NavBarMenuItem("Add new Todo", "{todo}"));
			myNavBar1.addMenu(dhtmlMenu);
			
			dhtmlMenu = new NavBarMenu(125, 140);
			dhtmlMenu.addItem(new NavBarMenuItem("Preferences", ""));
			dhtmlMenu.addItem(new NavBarMenuItem("General", "{prefs}"));
			dhtmlMenu.addItem(new NavBarMenuItem("Email", "{email}"));
			dhtmlMenu.addItem(new NavBarMenuItem("Calendar", "{calendar}"));
			dhtmlMenu.addItem(new NavBarMenuItem("Addressbook", "{addressbook}"));
			myNavBar1.addMenu(dhtmlMenu);
			
			dhtmlMenu = new NavBarMenu(62, 120);
			dhtmlMenu.addItem(new NavBarMenuItem("Help", ""));
			dhtmlMenu.addItem(new NavBarMenuItem("General", ""));
			myNavBar1.addMenu(dhtmlMenu);
			
			//set menu colors
			myNavBar1.setColors("#343434", "#eeeeee", "#60707C", "#ffffff", "#888888", "#eeeeee", "#60707C", "#ffffff", "#777777")
			myNavBar1.setFonts("Verdana", "Normal", "Normal", "10pt", "Verdana", "Normal", "Normal", "10pt");
			
			//uncomment below line to center the menu (valid values are "left", "center", and "right"
			//myNavBar1.setAlign("center")
			
			var fullWidth;
			
			function init()
			{
				// Get width of window, need to account for scrollbar width in Netscape.
				fullWidth = getWindowWidth() 
				- (isMinNS4 && getWindowHeight() < getPageHeight() ? 16 : 0);
				myNavBar1.moveTo(10,36);
				myNavBar1.resize(500 /*fullWidth*/);
				myNavBar1.setSizes(0,1,1);
				myNavBar1.create();
				myNavBar1.setzIndex(2);
			}

			function checkphpgw()
			{
				//window.alert("test");
			}
			
			function pageInit()
			{
				window.setInterval("checkphpgw()",60000);
			}

			var strBaseURL = '{str_base_url}';
	
			{win_on_events}
		//]]>
</script>

</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">