<?php
/*
Plugin Name: Top Position Google Finance
Plugin URI: http://www.noticiasbancarias.com/top-position-google-finance/
Description: Display your favorite quotes. Chart and values. Uses Google Finance API.
Author: Noticias Bancarias
Version: 0.1.0
Author URI: http://www.noticiasbancarias.com/
*/

/*
Google Finance Quotes is a wordpress plugin that allows you to manage and display your selected quotes (indices, stocks, etc) from google finance on your wordpress blog.
Copyright (C) 2010 Taro Hideyoshi

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

//Ddefinimos la tabla
define('TPGF_TABLE', 'tp_google_finance');

// run when plugin is activated
register_activation_hook(__FILE__,'tpgf_activation');

function tpgf_activation() {//se ejecuta cuando se activa el plugin
    global $wpdb;
	$table_name = $wpdb->prefix . TPGF_TABLE;
	$tables = $wpdb->get_results("show tables;");
	$table_exists = false;
	foreach ( $tables as $table ) {
		foreach ( $table as $value ) {
			if ( $value == $table_name ) {
				$table_exists = true;
				break;
	}}}
	if ( !$	$table_exists ) {
		$wpdb->query("CREATE TABLE " . $table_name . " (tpgf_id INT(11) NOT NULL AUTO_INCREMENT, tpgf_symbol TEXT NOT NULL, tpgf_category TEXT, PRIMARY KEY ( tpgf_id ))");
		$wpdb->query("INSERT INTO " . $table_name . " (tpgf_symbol, tpgf_category) VALUES ('yes', 'credits')");
	}
}

if ( is_admin() ){	// only for administrator
	add_action('admin_menu', 'tpgf_admin_menu'); // add link to admin menu
	function tpgf_admin_menu() {
		add_options_page('Top Position Google Finance', 'Top Position Google Finance', 'administrator', 'top-position-google-finance', 'tpgf_admin_page'); }
}

function tpgf_admin_page() {
	
	global $wpdb;
	$table_name = $wpdb->prefix . TPGF_TABLE;
	$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
	$tpgf_id = !empty($_REQUEST['tpgf_id']) ? $_REQUEST['tpgf_id'] : '';
	$tpgf_symbol = !empty($_REQUEST['tpgf_symbol']) ? $_REQUEST['tpgf_symbol'] : '';
	$tpgf_category = !empty($_REQUEST['tpgf_category']) ? $_REQUEST['tpgf_category'] : '';

	switch($action) {
		case 'credits' :
			if( !empty($tpgf_symbol) ) $wpdb->query("UPDATE " . $table_name . " SET tpgf_symbol='" . $tpgf_symbol . "' WHERE tpgf_category='credits';");break;
	}

	$credits = $wpdb->get_var($wpdb->prepare("SELECT tpgf_symbol FROM " . $table_name . " WHERE tpgf_category='credits';"));

?>
<div class="wrap">
	<h2>Top Position Google Finance</h2>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=top-position-google-finance.php' ?>">
    <?php wp_nonce_field('update-options'); ?>
    <table class="widefat">
    <tbody>
    <tr>
    <th width="50" scope="row">Display Powered by link</th>
    <td width="450" align="left">
		<?php if( $credits == 'yes') { ?>
        <input size="50" name="tpgf_symbol" type="radio" id="text_powered_yes" value="yes" checked/> yes
        <input size="50" name="tpgf_symbol" type="radio" id="text_powered_no" value="no" /> no 
        <?php } else { ?>
        <input size="50" name="tpgf_symbol" type="radio" id="text_powered_yes" value="yes" /> yes
        <input size="50" name="tpgf_symbol" type="radio" id="text_powered_no" value="no" checked/> no 
        <?php } ?>
        <input type="hidden" name="action" value="credits">
        <input type="submit" value="<?php _e('Save') ?>" />
    </td>
    </tr>
    <tr>
    <td width="500" align="left" colspan="2"><p>You can display your quotes by using of the following 2 methods.<br />
      <br /><strong>1. Put <em>&lt;?php tpgf_output('yourr quotes coma separated') ?&gt;</em> in wordpress template.</strong></p>
      <p>For example: &lt;?php tpgf_output('NASDAQ:MSFT,GOOG, CSCO,.DJI') ?&gt;</p>
      <p><strong>2. Put <em>&#91;tpgf#yourr quotes coma separated&#93;</em> in your blog content.</strong>      </p>
      <p>For example: &#91;tpgf#NASDAQ:MSFT,GOOG, CSCO,.DJI&#93; </p>
      <p><br />
      </p></td>
    </tr>
    </tbody>
    </table>
    </form>
</div>

<?php } 

// adjuntamos la hoja de estilo
add_action('wp_head', 'addHeaderCode_googlef');
function  addHeaderCode_googlef() {
     echo '<link rel="stylesheet" type="text/css" media="all" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/top-position-google-finance/styles.css" />';
	 }

// funcion que detecta los tags especiales en el contenido
add_filter( 'the_content', 'modifyContent_google' );

function  modifyContent_google($content) {

	global $wpdb;
    $table_name = $wpdb->prefix . TPGF_TABLE;
	$pos = 0;
	// esta es una forma un poco cutre pendiente sustituir por pleg_replace
	$pos = strpos($content, '[tpgf#', $pos); //posicion de comienzo
	if($pos){
		$closed_pos = strpos($content, ']', $pos) - 1; //posicion de final
		
		
		
		if($closed_pos) {
		
			$diferencia = $closed_pos - $pos -5;
			$diferencia2 = $closed_pos - $pos + 2;
		
			$quotes = substr($content, ($pos + 6), $diferencia);
			$tpgf_tag = substr($content, $pos, $diferencia2);
			
			$output =  tpgf_recuperadatos($quotes);
			$content = str_replace($tpgf_tag, $output, $content);
		}
	} 
	return ($content);
}

function tpgf_recuperadatos($quotes_) {

	global $wpdb;
	$table_name = $wpdb->prefix . TPGF_TABLE;
	$credits = $wpdb->get_var($wpdb->prepare("SELECT tpgf_symbol FROM " . $table_name . " WHERE tpgf_category='credits';"));

	if(!$quotes_) $quotes_ = 'NASDAQ:MSFT,GOOG, CSCO,.DJI';

	$quotes = explode(",", $quotes_);
	$charts = explode(",", $quotes_);
	$n = count($quotes);

	
	
	for($i=0;$i<count($quotes);$i++) {
		$charts[$i] = "http://www.google.com/finance/chart?cht=o&q=".$quotes[$i]."&amp;nocache=".time();
	}
	$chart_rsc = $charts[0];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, "http://www.google.com/finance/info?client=ig&q=".urlencode($quotes_));
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	
	$output = curl_exec($ch);
	$output = substr($output, 4, -1);
	$httpinfo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($httpinfo == 400) return false;
	$output = json_decode($output, true);
	
	
	$contenido = '
	
	<div class="top_gcontent_total">
	<div class="top_gcontent"><div class="h3top">Google Financial Quotes</div>
	
	<div id="topposition">
    	<div class="topposition_chart">
    		<div class="toppositionimage">
			<h4><div id="tpgtxt">'.$output[0]['t'].'  '.$output[0]['ltt'].'</div></h4>
			<img src="'.$chart_rsc.'" alt="Google Finance Chart"  id="tpgimg" class="top-chartg" /></div>
        <div class="top-clear"></div>
    </div>
    <div class="topposition-quotes-fg">';

	$b=0;
	foreach($output as $registro) { 
		$bc = '';
		if($b==0) $bc = "<div class='c0a94e1'></div>";
		if($b==1) $bc = "<div class='cf5110c'></div>";
		if($b==2) $bc = "<div class='cf5bb0c'></div>";
		if($b==3) $bc = "<div class='c48ae37'></div>";
		
		
		$contenido .= '<div class="topposition-quotes-registro-f"><a href="javascript:void();" onclick="document.getElementById('."'tpgimg'".').src='."'".$charts[$b]."'".'; document.getElementById('."'tpgtxt'".').innerHTML='."'".$registro['t'].'  '.$registro['ltt']."'".';" title="View chart" class="charttp"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/top-position-yahoo-finance/icon.gif"  alt="View chart" /></a><a href="javascript:void();" onclick="document.getElementById('."'tpgimg'".').src='."'".$charts[$b]."'".'; document.getElementById('."'tpgtxt'".').innerHTML='."'".$registro['t'].'  '.$registro['ltt']."'".';" title="View chart" class="charttp"><strong>'.$registro['t'].'</strong></a> '.$registro['l'].' ('.$registro['cp'].'%) <a href="http://www.google.com/finance?q='.urlencode($registro['t']).'" title="More on Google Finance">++</a>
        	</div>';
		$b++;
	} 
	$contenido .= '</div><div class="top-clear"></div>';
	if($credits=='yes') $contenido .= '<div class="top-firmag">By <a href="http://www.noticiasbancarias.com" title="Noticias bancarias">noticias bancarias</a></div>';
	$contenido .= '</div></div></div>';
	return $contenido;
}

function tpgf_output($quotes='') {
    if($quotes) echo tpgf_recuperadatos($quotes);
}
?>