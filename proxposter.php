<?php
error_reporting(0);
/*
Plugin Name: Proxy Poster
Plugin URI: http://www.fusecurity.com/proxyposter/
Description: This plugin will allow the administrator to post lists of proxies from various sources.
Version: 1.1.8
Author: Lerie Taylor
Author URI: http://fusecurity.com/
License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

function pp_get_web_page($url)
{
	$options = array(
		CURLOPT_RETURNTRANSFER => true,     // return web page
		CURLOPT_HEADER         => false,    // don't return headers
		CURLOPT_FOLLOWLOCATION => true,     // follow redirects
		CURLOPT_ENCODING       => "",       // handle all encodings
		CURLOPT_USERAGENT      => "spider", // who am i
		CURLOPT_AUTOREFERER    => true,     // set referer on redirect
		CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
		CURLOPT_TIMEOUT        => 120,      // timeout on response
		CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	);

	$ch      = curl_init( $url );
	curl_setopt_array( $ch, $options );
	$content = curl_exec( $ch );
	$err     = curl_errno( $ch );
	$errmsg  = curl_error( $ch );
	$header  = curl_getinfo( $ch );
	curl_close( $ch );

	$header['errno']   = $err;
	$header['errmsg']  = $errmsg;
	$header['content'] = $content;
	return $header;
}

add_action('admin_menu', 'pp_plugin_menu');

function pp_plugin_menu()
{
	add_posts_page('Proxy Poster Options', 'Proxy Poster', 'manage_options', 'proxy-poster', 'pp_plugin_options');
}

function pp_plugin_options()
{
	if (!current_user_can('manage_options'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	if($_GET['makepost'])
	{
		$my_post = array();
		$my_post['post_title'] = filter_var($_POST['txtSubj'],FILTER_SANITIZE_STRING);
		$my_post['post_content'] = $_POST['txtProxyList'];
		$my_post['post_status'] = 'publish';
		$my_post['post_author'] = 1;
		$my_post['post_category'] = array("Proxies","Proxy List");

		//Insert the post into the database
		wp_insert_post($my_post);
	}

	if($_POST['btnSave'])
	{
		$list = @explode("\n",$_POST['txtProxyList']);

		$fh = fopen("proxies.txt","w");
		foreach($list as $proxy)
		{
			fwrite($fh,$proxy."\n");
		}
		fclose($fh);
		echo "<script>window.open('proxies.txt','Download');</script>";
	}

	if($_GET['getproxies'])
	{
		$list = array();
		$sites = array();

		if($_POST['aliveproxy']) array_push($sites,"http://aliveproxy.com/socks5-list/");
		if($_POST['socks24']) array_push($sites,"http://socks24.blogspot.com/");
		if($_POST['proxylistnet']) array_push($sites,"http://www.proxylist.net/");
		if($_POST['proxylists']) array_push($sites,"http://www.proxylists.net/");
		if($_POST['cyber']) array_push($sites,"http://www.digitalcybersoft.com/ProxyList/fresh-proxy-list.shtml");
		if($_POST['proxyblind']) array_push($sites,"http://www.proxyblind.org/proxy-list.shtml");
		if($_POST['proxypriv']) array_push($sites,"http://www.proxyserverprivacy.com/free-proxy-list.shtml");
		if($_POST['fresh']) array_push($sites,"http://www.freshproxy.org/");
		if($_POST['proxz']) array_push($sites,"http://www.proxz.com/");
		if($_POST['my-proxy']) array_push($sites,"http://proxies.my-proxy.com/");
		if($_POST['freeproxy']) array_push($sites,"http://www.freeproxy.ru/download/lists/goodproxy.txt");

		if($_POST['atomis'])
		{
			array_push($sites,"http://atomintersoft.com/products/alive-proxy/socks5-list");
			array_push($sites,"http://atomintersoft.com/proxy_list_port_8080");
			array_push($sites,"http://atomintersoft.com/proxy_list_port_8000");
			array_push($sites,"http://atomintersoft.com/proxy_list_port_81");
			array_push($sites,"http://atomintersoft.com/proxy_list_port_80");
		}

		foreach($sites as $site)
		{
			$site_info = pp_get_web_page($site);
			$pattern = '/\d+[.]\d+[.]\d+[.]\d+[:]\d+/';
			preg_match_all($pattern, $site_info['content'], $matches);

			for($i=0;$i<sizeof($matches[0]);$i++)
			{
				$list .= $matches[0][$i].",";
			}
		}

		$list = explode(',',$list);
		$prev_count = sizeof($list);

		$list = array_unique($list);
		$proxy_count = sizeof($list);
		$remov = $prev_count-$proxy_count;

		$today = date("m.d.y");
		echo '	<div class="wrap">
			<p style="float:left;padding-right:10px;"><img src="http://fusecurity.com/proxyposter/posterlogo.png"></p>
			<p>
				<h2>Proxy Poster Options</h2>
				<div id="form">
				<form method="post" action="?page=proxy-poster&makepost=true">

				<ul>
					<li><textarea cols="40" rows="10" name="txtProxyList">';

					$proxy_match = '/\d+[.]\d+[.]\d+[.]\d+[:]\d+/';

					for($i=1;$i<sizeof($list);$i++)
					{
						if(preg_match($proxy_match, $list[$i]))
						{
							if($i==10) echo "<!--more-->\n";
							echo $list[$i]."\n";
						}
					}

				echo '	</textarea></li>
					<li><b>Duplicates removed</b>: '.$remov.'</li>
					<li><input type="text" name="txtSubj" size="50" value="Proxylist for '.$today.' ('.$proxy_count.')"></li>
				</ul>';
?>
<?php
		echo '		</p>

				<p>
					<input type="submit" value="Publish These Proxies">
					<input type="submit" value="Save these proxies" name="btnSave">
				</p>

				</form>
				</div>
			</p>
			<p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_donations">
				<input type="hidden" name="business" value="charliewinslow@gmail.com">
				<input type="hidden" name="lc" value="US">
				<input type="hidden" name="item_name" value="Fuse Development">
				<input type="hidden" name="no_note" value="0">
				<input type="hidden" name="currency_code" value="USD">
				<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
				<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>
			</p>
			</div>';
	} else {
		echo '	<div class="wrap">
			<p style="float:left;padding-right:10px;"><img src="http://fusecurity.com/proxyposter/posterlogo.png"></p>
			<p>
				<h2>Proxy Poster Options</h2>
					<form method="post" action="?page=proxy-poster&getproxies=true">

					<div style="float:left;padding-left:10px;">
						<p><input type="checkbox" name="socks24"> Socks24</p>
						<p><input type="checkbox" name="aliveproxy"> aliveproxy</p>
						<p><input type="checkbox" name="proxylistnet"> proxylist</p>
						<p><input type="checkbox" name="atomis"> atomintersoft</p>
						<p><input type="checkbox" name="cyber"> digital cybersoft</p>
					</div>

					<div style="float:left;padding-left:10px;">
						<p><input type="checkbox" name="proxylists"> proxylists</p>
						<p><input type="checkbox" name="proxyblind"> proxyblind</p>
						<p><input type="checkbox" name="proxypriv"> proxyprivacy</p>
						<p><input type="checkbox" name="fresh"> freshproxy</p>
						<p><input type="checkbox" name="proxz"> proxz</p>
					</div>

					<div style="float:left;padding-left:10px;">
						<p><input type="checkbox" name="my-proxy"> my-proxy</p>
						<p><input type="checkbox" name="freeproxy"> freeproxy.ru</p>
					</div>

					<div style="float:left;padding-left:10px;">
						<p><input type="submit" value="Get These Proxies"></p>

						<p>
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
							<input type="hidden" name="cmd" value="_donations">
							<input type="hidden" name="business" value="charliewinslow@gmail.com">
							<input type="hidden" name="lc" value="US">
							<input type="hidden" name="item_name" value="Fuse Development">
							<input type="hidden" name="no_note" value="0">
							<input type="hidden" name="currency_code" value="USD">
							<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
							<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
							<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
							</form>
						</p>
					</div>

					</form>
			</div>';
	}
}

?>
