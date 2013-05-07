<?php

// pgo.php
// Run through URLs to produce profiling data for PHP PGO builds
// Usage: pgo.php [<SERVER>|vhost|printnum] [PORT] [App_Name]

$apps = array( 'drupal', 'wordpress', 'mediawiki', 'joomla', 'phpbb', 'symfony', 'other' );
$p1count = 8;
$p2count = 2;
$debug = 1;

$SERVER = 'localhost';
$PORT = '80';
$APPNAME = '';
if ( isset($argv[2]) )  {
	$PORT = $argv[2];
}
if ( isset($argv[1]) )  {
	if ( strcmp($argv[1], 'vhost') == 0 )  {
		$SERVER = '';
	}
	else  {
		$SERVER = $argv[1] . ':' . $PORT . '/';
	}
}
if ( isset($argv[3]) )  {
	// Run only for a particular application.
	foreach ( $apps as $app )  {
		$argv[3] = strtolower($argv[3]);
		if ( strcmp($argv[3], $app) == 0 )  {
			$APPNAME = $argv[3];
			break;
		}
	}
	if ( $APPNAME == '' )  {
		echo "Bad parameter or unknown application name.\n";
		echo "Valid application identifiers: " . implode(", ", $apps) . "\n";
		exit;
	}
}

// Apps  ##################################################################################################### //
$drupalP1 = array(
	'http://'.$SERVER.'drupal/index.php',
	'http://'.$SERVER.'drupal/?q=node/1',
	'http://'.$SERVER.'drupal/?q=blog/1',
	'http://'.$SERVER.'drupal/?q=node/2',
	'http://'.$SERVER.'drupal/?q=forum',
	'http://'.$SERVER.'drupal/?q=forum/1',
	'http://'.$SERVER.'drupal/?q=node/3'
);
$drupalP2 = array(
	'http://'.$SERVER.'drupal/?q=user/login&destination=node/1%23comment-form',
	'http://'.$SERVER.'drupal/?q=user/register&destination=node/1%23comment-form',
	'http://'.$SERVER.'drupal/?q=user/password',
	'http://'.$SERVER.'drupal/?q=rss.xml'
);

$wordpressP1 = array(
	'http://'.$SERVER.'wordpress/',
	'http://'.$SERVER.'wordpress/?p=4',
	'http://'.$SERVER.'wordpress/?p=1',
	'http://'.$SERVER.'wordpress/?page_id=2',
	'http://'.$SERVER.'wordpress/?cat=1'
);
$wordpressP2 = array(
	'http://'.$SERVER.'wordpress/wp-login.php',
	'http://'.$SERVER.'wordpress/?m=201112'
);

$mediawikiP1 = array(
	'http://'.$SERVER.'mediawiki/index.php?title=Main_Page',
	'http://'.$SERVER.'mediawiki/index.php?title=Talk%3AMain_Page',
	'http://'.$SERVER.'mediawiki/index.php?title=Test_Page',
	'http://'.$SERVER.'mediawiki/index.php?title=Talk%3ATest_Page&action=edit&redlink=1',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3ANewPages'
);
$mediawikiP2 = array(
	'http://'.$SERVER.'mediawiki/index.php?title=Main_Page&printable=yes',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3AWhatLinksHere/Main_Page',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3ARecentChangesLinked/Main_Page',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3ARecentChanges',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3ASearch/Current_events',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3AUserLogin',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3ARecentChangesLinked',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3ABrokenRedirects',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3ADeadendPages',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3ADoubleRedirects',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3AProtectedPages',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3AWantedPages',
	'http://'.$SERVER.'mediawiki/index.php?title=Special%3ASpecialPages'
);

$joomlaP1 = array(
	'http://'.$SERVER.'joomla/',
	'http://'.$SERVER.'joomla/index.php/using-joomla/extensions/components/content-component/article-category-list/24-joomla',
	'http://'.$SERVER.'joomla/index.php/using-joomla/extensions/components/content-component/article-category-list/8-beginners',
	'http://'.$SERVER.'joomla/index.php/sample-sites',
	'http://'.$SERVER.'joomla/index.php/parks-home',
	'http://'.$SERVER.'joomla/index.php/park-blog',
	'http://'.$SERVER.'joomla/index.php/park-blog/17-first-blog-post',
	'http://'.$SERVER.'joomla/index.php/image-gallery',
	'http://'.$SERVER.'joomla/index.php/park-links',
	'http://'.$SERVER.'joomla/index.php/image-gallery/animals',
	'http://'.$SERVER.'joomla/index.php/image-gallery/scenery',
	'http://'.$SERVER.'joomla/index.php/site-map',
	'http://'.$SERVER.'joomla/index.php/site-map/articles',
	'http://'.$SERVER.'joomla/index.php/site-map/contacts',
	'http://'.$SERVER.'joomla/index.php/site-map/weblinks',
	'http://'.$SERVER.'joomla/index.php/using-joomla/extensions/components',
	'http://'.$SERVER.'joomla/index.php/getting-started',
	'http://'.$SERVER.'joomla/index.php/using-joomla',
	'http://'.$SERVER.'joomla/index.php/the-joomla-project',
	'http://'.$SERVER.'joomla/index.php/the-joomla-community',
	'http://'.$SERVER.'joomla/index.php?format=feed&type=rss',
	'http://'.$SERVER.'joomla/index.php?format=feed&type=atom'
);
$joomlaP2 = array(
	'http://'.$SERVER.'joomla/index.php/login',
	'http://'.$SERVER.'joomla/administrator/',
	'http://'.$SERVER.'joomla/index.php/using-joomla/extensions/components/users-component/password-reset',
	'http://'.$SERVER.'joomla/index.php/using-joomla/extensions/components/users-component/username-reminder',
	'http://'.$SERVER.'joomla/index.php/using-joomla/extensions/components/users-component/registration-form'
);

$phpbbP1 = array (
	'http://'.$SERVER.'phpbb/index.php',
	'http://'.$SERVER.'phpbb/viewforum.php?f=2',
	'http://'.$SERVER.'phpbb/viewtopic.php?f=2&t=1',
	'http://'.$SERVER.'phpbb/search.php?search_id=unanswered',
	'http://'.$SERVER.'phpbb/search.php?search_id=active_topics'
);
$phpbbP2 = array (
	'http://'.$SERVER.'phpbb/ucp.php?mode=login',
	'http://'.$SERVER.'phpbb/ucp.php?mode=register'
);
$symfonyP1 = array (
	'http://'.$SERVER.'symfony/web/app_dev.php/acme-pizza/pizza/list',
	'http://'.$SERVER.'symfony/web/app_dev.php/acme-pizza/pizza/create',
	'http://'.$SERVER.'symfony/web/app_dev.php/acme-pizza/order/index',
	'http://'.$SERVER.'symfony/web/app_dev.php/acme-pizza/order/list',
	'http://'.$SERVER.'symfony/web/app_dev.php/acme-pizza/order/show/6',
	'http://'.$SERVER.'symfony/web/app_dev.php/acme-pizza/pizza/list',
	'http://'.$SERVER.'symfony/web/app_dev.php/acme-pizza/customer/list',
	'http://'.$SERVER.'symfony/web/app_dev.php/acme-pizza/pizza/update/31'
);
$symfonyP2 = array();

$otherP1 = array();
$otherP2 = array();
// ########################################################################################################### //

$total = 0;
foreach ( $apps as $app )  {
	${$app} = count( ${"${app}P1"} ) * $p1count;
	${$app} += count( ${"${app}P2"} ) * $p2count;
	$total += ${$app};
	echo "$app: ${$app}\n";
}
echo "\nTotal Transactions: $total\n\n";
if ( isset($argv[1]) && ($argv[1] == 'printnum') )  {
	exit;
}

$stat = 0;
$total = 0;
$error = 0;
foreach ( $apps as $app )  {
	if ( !empty($APPNAME) )  {
		if ( $APPNAME != $app )  {
			continue;
		}
	}

	echo "Testing P1 URIs in $app\n";
	for ( $i=0; $i < $p1count; $i++ )  {
		if ( empty(${"${app}P1"}) )  {  break;  }
		foreach ( ${"${app}P1"} as $url )  {
			($debug == 1) ? print "$url\n" : '';
			$stat = file_get_contents("$url");
			if ( $stat === FALSE )  {
				echo "Error retrieving $url\n";
				$error++;
			}
			$total++;
		}
	}

	echo "Testing P2 URIs in $app\n";
	for ( $i=0; $i < $p2count; $i++ )  {
		if ( empty(${"${app}P2"}) )  {  break;  }
		foreach ( ${"${app}P2"} as $url )  {
			($debug == 1) ? print "$url\n" : '';
			$stat = file_get_contents("$url");
			if ( $stat === FALSE )  {
				echo "Error retrieving $url\n";
				$error++;
			}
			$total++;
		}
	}
}

echo "Transactions: $total, Errors: $error\n";

?>