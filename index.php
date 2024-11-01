<?php
/*
Plugin Name: Gateway for PayPing-VIP on WooCommerce
Version: 2.8.0
Description:  افزونه درگاه پرداخت Payping-VIP برای ووکامرس
Plugin URI: https://www.payping.ir/
Author: Mahdi Sarani
Author URI: https://mahdisarani.ir
*/

if (!defined('ABSPATH')) exit;

define( 'WOOVIPDIR', plugin_dir_path(__FILE__) );
define( 'WOOVIPDIRU', plugin_dir_url(__FILE__) );

/**
 * Detect plugin. For use on Front End only.
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if( ! is_plugin_active( 'woo-payping-gateway/index.php' ) ){
    add_action('plugins_loaded', 'load_woo_vip_payping_and_gateway', 0);
}else{
	add_action( 'admin_notices', 'ppwcvip_plugin_admin_notice' );
}

//Display admin notices 
function ppwcvip_plugin_admin_notice(){ ?>
	<div class="notice notice-warning is-dismissible">
		<p><?php _e('برای فعالسازی افزونه ووکامرس vip لطفا ابتدا افزونه پرداخت پی‌پینگ برای ووکامرس را غیرفعال کنید.', 'textdomain') ?></p>
	</div>
<?php
}

function load_woo_vip_payping_and_gateway(){
/* load custom style in wp admin page */
function load_custom_wp_admin_style(){
	wp_enqueue_style( 'woo-vip-style-admin', WOOVIPDIRU . 'assets/css/style.css', array(), null, false );
	wp_register_script( 'woo-vip-scripts', WOOVIPDIRU . 'assets/js/script-woo-vip.js', array('jquery'), null, false );
	wp_enqueue_script('woo-vip-scripts');
	wp_localize_script( 'woo-vip-scripts', 'pp_woo_vip', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
}
add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );

require_once("includes/class-woo_vip_gateway.php");
require_once("includes/class-apyping_woo_vip.php");

/* Show Debug In Console */
function WC_GPP_Debug_Log( $Debug_Mode='deactive', $object=null, $label=null ){
    if( $Debug_Mode === 'active' ){
        $object = $object; 
        $message = json_encode( $object, true);
        $label = "Debug".($label ? " ($label): " : ': '); 
        echo "<script type=\"text/javascript\">console.log(\"$label\", $message);</script>";
    }
}

/* Set Urls API */
function WC_GPP_DebugURLs( $Debug_Mode = 'deactive', $Debug_URL = null, $Method = null ){
    if( $Debug_Mode === 'deactive' ){
        $url = 'https://api.payping.ir'.$Method;
    }elseif($Debug_Mode === 'active'){
        $url = $Debug_URL.$Method;
    }else{
        $url = 'https://api.payping.ir'.$Method; 
    }
    return $url;
}

/* get path file
*@return full file path
*/
function WC_GPP_GetPathFile( $FileURL = '' ){
	$imageURL = parse_url( $FileURL );
	$filePath = get_home_path().$imageURL['path'];
	return $filePath;
}

/* used in ajax upload image */
function UploadImageItem( $filePath, $TokenCode){
	try{
	$curl = curl_init();
	curl_setopt_array( $curl, array(
	  CURLOPT_URL => "https://api.payping.ir/v1/upload/Item",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 45,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => array('file'=> new CURLFILE( $filePath )),
	  CURLOPT_HTTPHEADER => array(
		"Accept: image/*",
		"Content-Type: multipart/form-data",
		"cache-control: no-cache",
		"Authorization: Bearer ". $TokenCode
	  ),
	));

	$response = curl_exec($curl);
	$header = curl_getinfo($curl);
	$err = curl_error($curl);
	curl_close($curl);

	if($err){
		$arr = json_encode( array( 'status_code' => $header['http_code'], 'message' => 'خطایی در هنگام اتصال به پی‌پینگ رخ داده است!' ) );
	}else{
		if( $header['http_code'] == 200 ){
			 $arr = json_encode( array( 'status_code' => $header['http_code'], 'message' => 'بارگذاری با موفقیت انجام شد.', 'file_name' => $response ) );
		}else{
			 $arr = json_encode( array( 'status_code' => $header['http_code'], 'message' => 'بارگذاری ناموفق!' ) );
		}
	}
	return $arr;
}catch(Exception $e){
		$arr = json_encode( array( 'status_code' => $header['http_code'], 'message' => 'بارگذاری ناموفق، خطا سمت سایت شما! : ' . $e->getMessage() ) );
		return $arr;
	}
	return false;
}
}