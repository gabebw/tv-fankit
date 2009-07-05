<head>
<?php
// $VMARS_SITEINFO['PAGE'] is set in setup.php
if( $VMARS_SITEINFO['page']['subname'] == null ) {
    // blank subname -> no raquo
    printf("<title>%s | %s</title>\n",
	$VMARS_SITEINFO['site_name'],
	$VMARS_SITEINFO['page']['name']);
} else {
    printf("<title>%s | %s &raquo; %s</title>\n",
	$VMARS_SITEINFO['site_name'],
	$VMARS_SITEINFO['page']['name'],
	$VMARS_SITEINFO['page']['subname']);
}
?>
<link rel="stylesheet" type="text/css" href="/vmars/css/reset.css" />
<link rel="stylesheet" type="text/css" href="/vmars/css/vmars.css" />
<!--[if lt IE 7]><link rel="stylesheet" type="text/css" href="/vmars/css/vmars.ie.css" /><![endif]-->
</head>
