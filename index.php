<?php
//	set up all our variables.
include("config.php");
include("lib/cache.php");
include("lib/generate.php");

//	find out what versions of the docs we have; if the given version isn't available, switch to the most recent.
$d = dir($dataDir);
$versions = array();
$has_version = false;
while(($entry = $d->read()) !== false){
	if(strpos($entry, ".")!==0){
		$versions[] = $entry;
	}
}
$d->close();
sort($versions);

// Get the version and page from a URL like dojotoolkit.org/api?qs=dijit.Dialog&v=1.7.
// This URL was likely generated by .htaccess's rewrite rule:
//
// 		RewriteRule ^(.*)$ index.php?qs=$1 [L,QSA]
//
$parts = array();
$is_page = false;
$page = $defPage;
$version = $defVersion;
if(array_key_exists("qs", $_GET) && strlen($_GET["qs"])){
	$r = $_GET["qs"];
	$r = str_replace("jsdoc/", "", $r);
	$parts = explode("/", $r);

	//	check if the version exists
	$version = $parts[0];
	if(in_array($version, $versions)){
		array_shift($parts);
	} else {
		$version = $defVersion;
	}

	if(count($parts)){
		$page = implode("/", $parts);
		$is_page = true;
	}
}

//	check if the version passed is available.
foreach($versions as $entry){
	if($entry == $version){
		$has_version = true;
		break;
	}
}
if(!$has_version){
	$version = $versions[count($versions)-1];
}

//	get the theme from the config file.
if(!isset($default_theme)){
	$default_theme = "dtk";
}
$th = isset($theme) ? $theme : $default_theme;

//	check to clear the cache or not
if(isset($_GET["clearcache"]) && $use_cache){
	cache_clear($version);
}

?><!DOCTYPE html>
<html>
	<head>
	<title><?php echo ($is_page ? $page : "API Documentation") ?> - The Dojo Toolkit</title>
		<meta http-equiv="X-UA-Compatible" content="chrome=1"/>
		<link rel="stylesheet" href="<?php echo $dojoroot ?>/dojo/resources/dojo.css" />
		<link rel="stylesheet" href="<?php echo $dojoroot ?>/dijit/themes/claro/claro.css" />
		<link rel="stylesheet" href="<?php echo $basePath ?>/css/jsdoc.css" type="text/css" media="all" />
		<link rel="stylesheet" href="<?php echo $basePath ?>/css/jsdoc-print.css" type="text/css" media="print" />
<?php if(file_exists("themes/" . $th . "/theme.css")){ ?>
<link rel="stylesheet" href="<?php echo $basePath ?>/themes/<?php echo $th ?>/theme.css" type="text/css" media="all" />
<?php } ?>
		<script type="text/javascript">dojoConfig={
			isDebug:false,
			async: true
		};</script>
		<script type="text/javascript" src="<?php echo $dojoroot ?>/dojo/dojo.js"></script>

		<!-- SyntaxHighlighter -->
		<script type="text/javascript" src="<?php echo $basePath ?>/js/syntaxhighlighter/scripts/shCore.js"><</script>
		<script type="text/javascript" src="<?php echo $basePath ?>/js/syntaxhighlighter/scripts/shBrushJScript.js"><</script>
		<script type="text/javascript" src="<?php echo $basePath ?>/js/syntaxhighlighter/scripts/shBrushXml.js"><</script>
		<link rel="stylesheet" href="<?php echo $basePath ?>/js/syntaxhighlighter/styles/shCore.css" type="text/css" />
		<link rel="stylesheet" href="<?php echo $basePath ?>/js/syntaxhighlighter/styles/shThemeDefault.css" type="text/css" />

		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="keywords" content="The Dojo Toolkit, dojo, JavaScript Framework" />
		<meta name="description" content="The Dojo Toolkit" />
		<meta name="author" content="Dojo Foundation" />
		<meta name="copyright" content="Copyright 2006-2012 by the Dojo Foundation" />
		<meta name="company" content="Dojo Foundation" />

		<script type="text/javascript">
			var baseUrl = "<?php echo $_base_url; ?>";
			var theme = "<?php echo $th; ?>";
			var siteName = 'The Dojo Toolkit';
			require({
				packages: [{
					name: "api",
					location: "<?php echo $_base_url; ?>js"
				}]},
			[
				"dojo/dom",
				"dojo/_base/fx",
				"dojo/ready",
				"dijit/registry",
				"api/api"		// main work is done in here
			], function(dom, fx, ready, registry){
				ready(function(){
					setTimeout(function(){
						var loader = dom.byId("loader");
						fx.fadeOut({ node: loader, duration: 500, onEnd: function(){ loader.style.display = "none"; }}).play();
					}, 500);
				});
			});

			// Set currentVersion as a global variable, since it's accessed from api.js
			currentVersion = '<?php echo $version; ?>';

			// page specified in URL
			var page = '<?php echo ($is_page?$page:"") ?>';

			var bugdb = '<?php echo $bugdb; ?>';
		</script>
	</head>
	<body class="claro">
		<div id="loader" style="display:none"><div id="loaderInner"></div></div>
		<script>
			// Don't show loading screen unless scripts are enabled; otherwise it will hang on loading screen forever.
			document.getElementById("loader").style.display="";
		</script>
		<div id="printBlock"></div>

		<div id="main" data-dojo-type="dijit.layout.BorderContainer" data-dojo-props="liveSplitters: false">
			<div id="head" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region: 'top'">
<?php include("themes/" . $th . "/header.php"); ?>
			</div>
			<div id="navigation" data-dojo-type="dijit.layout.BorderContainer" style="width:300px;" data-dojo-props="minSize: 20, region:'leading', splitter: true, gutters: false">
				<div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="title:'Search', region:'top'">
					<div style="padding: 4px;">
						<label for="versionSelector">Version: </label>
						<select id="versionSelector" style="width:auto;"><?php
foreach($versions as $v){
	echo '<option value="' . $v . '"' . ($version==$v?' selected="true"':'') . '>' . $v . '</option>' . "\n";
}
						?></select>
					</div>
				</div>
				<div data-dojo-type="dijit.layout.AccordionContainer" data-dojo-props="region: 'center'">
					<div id="moduleTreePane" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="title: 'Modules', selected: true">
						<!-- give link to plain html tree for google, will be replaced by dijit/Tree on browsers -->
						<p id="plainTree">
							See <a href="<?php echo $_base_url; ?><?php echo $version; ?>/tree.html">plain HTML tree</a> listing modules.
						</p>
						<script>
							// Hide link to plain tree except for search engines
							document.getElementById("plainTree").style.display = "none";
						</script>
					</div>
					<div data-dojo-type="dijit.layout.ContentPane" title="Legend">
						Types:
						<ul class="jsdoc-legend">
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/array.png" align="middle" title="Array" alt="Array" border="0" /> - Array
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/boolean.png" align="middle" title="Boolean" alt="Boolean" border="0" /> - Boolean
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/constructor.png" align="middle" title="Constructor" alt="Constructor" border="0" /> - Constructor
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/date.png" align="middle" title="Date" alt="Date" border="0" /> - Date
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/domnode.png" align="middle" title="DomNode" alt="DomNode" border="0" /> - DOMNode
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/error.png" align="middle" title="Error" alt="Error" border="0" /> - Error
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/function.png" align="middle" title="Function" alt="Function" border="0" /> - Function
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/number.png" align="middle" title="Number" alt="Number" border="0" /> - Number
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/object.png" align="middle" title="Object" alt="Object" border="0" /> - Object
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/regexp.png" align="middle" title="RegExp" alt="RegExp" border="0" /> - RegExp
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/singleton.png" align="middle" title="Singleton" alt="Singleton" border="0" /> - Singleton
							<li><img src="<?php echo $basePath ?>/css/icons/16x16/string.png" align="middle" title="String" alt="String" border="0" /> - String
						</ul>
						Modifiers:
						<ul class="jsdoc-legend">
							<li><span class="jsdoc-extension"></span> - extension property/method; must manually require() another module to access this</span>
							<li><span class="jsdoc-private"=></span> - private property/method
							<li><span class="jsdoc-inherited"></span> - inherited from a superclass
						</ul>
					</div>
				</div>
			</div>
			<div id="content" data-dojo-type="dijit.layout.TabContainer" data-dojo-props="region: 'center', tabStrip: true">
				<div id="baseTab" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="title: 'Welcome'">
				</div>
			</div>
			<div id="foot" data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region: 'bottom'">
<?php include("themes/" . $th . "/footer.php"); ?>
			</div>
		</div>
		<?php if($is_page){ ?>
			<!-- inline documentation into plain HTML output for benefit of search engines -->
			<div id="plainHtmlContent">
				<?php echo generate_object_html($page, $version, $_base_url, "", true, array(), ""); ?>
			</div>
			<script>
				// Hide HTML content except for search engines
				document.getElementById("plainHtmlContent").style.display = "none";
			</script>
		<?php } ?>
	</body>
</html>
