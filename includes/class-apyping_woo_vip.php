<?php
if (!defined('ABSPATH'))
	exit;

/* Start class Control_Vip_Payping */
class Payping_woo_vip{
    public $TokenCode;
    public $Debug_Mode;
    public $Debug_URL;
    
	public function __construct(){
		$this->TokenCode  = get_option('PayPing_TokenCode');
		$this->Debug_Mode = get_option('PayPing_DebugMode');
		$this->Debug_URL  = get_option('PayPing_DebugUrl');

		add_action( 'admin_menu', array( $this, 'RegisterCustomMenuPage' ) );
		add_action( 'admin_init', array( $this, 'PayPing_WP_Plugin_Settings' ) );
		add_action( 'admin_head', array( $this, 'PayPingStyleEnqueue' ) );

		/* products */
//		add_filter( 'manage_product_posts_columns', array( $this, 'PayPing_set_product_colmns' ) );
//		add_action( 'manage_product_posts_custom_column' , array( $this, 'PayPing_show_sync_product' ), 10, 3 );

//		add_filter( 'bulk_actions-edit-product', array( $this, 'PayPing_register_bulk_actions_product' ) );
//		add_filter( 'handle_bulk_actions-edit-product', array( $this, 'PayPingBulkSyncProducts' ), 10, 3 );
//		add_action( 'admin_notices', array( $this, 'PayPingBulkSyncProductsNotice' ) );
		/* products */

		/* add or update product hook */
		add_action( 'woocommerce_update_product', array( $this, 'PP_Add_OR_Update_Product_WOO' ), 10, 1 );
		add_action( 'woocommerce_update_coupon', array( $this, 'PP_Add_OR_Update_Coupon_WOO' ), 10, 1 );
		/* add or update product hook */
		add_action( 'woocommerce_cart_contents', array( $this, 'PayPing_Resync_In_Cart' ) );
		
		/* ajax actions */
		add_action( 'wp_ajax_payping_img_sync_img',  array( $this, 'payping_img_sync_img' ) );

	}

	/* Start Admin Style */
	public function PayPingStyleEnqueue(){
		echo '<style type="text/css">
			table.wp-list-table thead tr th#PayPingVIP{
				text-align: center;
			}
			table.wp-list-table tbody tr td.PayPingVIP{
				text-align: center;
			}
		} 
	  </style>';
	}
	/* End Admin Style */

	/* Start Register Admin Menu */
	function RegisterCustomMenuPage(){
		add_menu_page( __('پی‌پینگ تجاری', 'woocommerce'), __('PayPing VIP', 'woocommerce'), 'manage_options', 'payping-vip', array( $this, 'All_Syncs_Items_PayPing' ), 'dashicons-chart-line' );
		add_submenu_page( 'payping-vip', __('محصولات', 'woocommerce'), __('محصولات', 'woocommerce'), 'manage_options', 'payping-vip-products', array( $this, 'PayPing_Woo_VIP_Products_List' ) );
		add_submenu_page( 'payping-vip', __('تخفیف‌ها', 'woocommerce'), __('تخفیف‌ها', 'woocommerce'), 'manage_options', 'payping-vip-coupons', array( $this, 'PayPing_Woo_VIP_Coupons_List' ) );
		add_submenu_page( 'payping-vip', __('تنظیمات', 'woocommerce'), __('تنظیمات', 'woocommerce'), 'manage_options', 'payping-vip-setting', array( $this, 'PayPing_WP_Plugin_Settings_Page' ) );
	}
	/* End Register Admin Menu */

	/*  */
	function All_Syncs_Items_PayPing(){
		echo '<div class="wrap">';
//		echo '<h1 class="wp-heading-inline">همسان‌سای‌ها</h1>';
		echo '<hr class="wp-header-end">';

		if( isset( $_POST['add_product'] ) ){
			$this->add_product(  $_POST['NonceCatToPayPing'], 1);
		}
		if( isset( $_POST['submit_cat'] ) ){
			$this->add_product_cat(  $_POST['NonceCatToPayPing'], 1);
		}
		if(isset($_POST['submit_coupons'])){
			$this->add_coupon( $_POST['st_token'], 1);
		}
		if(isset($_POST['submit_all'])){
			$this->add_product_cat( $_POST['st_token'], 1);
			$this->add_coupon( $_POST['st_token'], 1);
		}

		if(isset($_POST['SubmitCouponToPayPing'])){
			$this->Coupon_From_Woo_Too_PayPing(  1 );
		}
		if(isset($_POST['SubmitCatsToPayPing'])){
			$this->Cats_From_Woo_Too_PayPing(  1 );
		}

		if(isset($_POST['DeleteProducts'])){
			$this->PayPing_Delete_Methods();
		}

		echo '<div id="dashboard-widgets" class="metabox-holder">'; ?>
		<!-- start postbox-container -->
		<div id="postbox-container-1" class="postbox-container">
	       <h2>همسان سازی پی‌پینگ با ووکامرس</h2>
		   <div id="column4-sortables" class="meta-box-sortables ui-sortable">
		   	   <!-- Sync Products -->
			   <div id="side-sortables" class="meta-box-sortables ui-sortable">
				  <div id="dashboard_quick_press" class="postbox">
					  <h2 class="hndle ui-sortable-handle"><span>یکسان سازی محصولات</span></h2>
					  <div class="inside" style="padding: 10px;">
						  <p>برای یکسان سازی تمامی محصولات فروشگاه خود با سرویس پی پینگ بر روی دکمه محصولات کلیک کنید.</p>
					  </div>
					  <form method="post" action="">
						  <?php wp_nonce_field( 'CatsFromPayPingToWoo', 'NonceCatToPayPing' ); ?>
						  <?php submit_button( __( 'محصولات', 'woocommerce' ), 'primary', 'add_product', true, null ); ?>
					  </form>
				  </div>
			   </div>
			   <!-- Sync Products -->
			   <!-- Sync Coupons -->
		   		<!--<div id="side-sortables" class="meta-box-sortables ui-sortable">
				  <div id="dashboard_quick_press" class="postbox">
					  <h2 class="hndle ui-sortable-handle"><span>یکسان سازی کدهای تخفیف</span></h2>
					  <div class="inside" style="padding: 10px;">
						  <p>برای یکسان سازی تمامی کدهای تخفیف فروشگاه خود با سرویس پی پینگ بر روی دکمه کد تخفیف کلیک کنید.</p>
					  </div>
					  <form method="post" action="">
						  <?php wp_nonce_field( 'wvpp_secure_form', 'st_token' ); ?>
						  <?php submit_button( __( 'کد تخفیف', 'woocommerce' ), 'primary', 'submit_coupons', true, null ); ?>
					  </form>
				  </div>
			  	</div>-->
			  	<!-- Sync Coupons -->
			  	<!-- Sync Cats -->
			  	<!--<div id="side-sortables" class="meta-box-sortables ui-sortable">
				  <div id="dashboard_quick_press" class="postbox">
					  <h2 class="hndle ui-sortable-handle"><span>یکسان سازی دسته های محصولات</span></h2>
					  <div class="inside" style="padding: 10px;">
						  <p>برای یکسان سازی تمامی دسته های فروشگاه خود با سرویس پی پینگ بر روی دکمه دسته محصولات کلیک کنید.</p>
					  </div>
					  <form method="post" action="">
						  <?php wp_nonce_field( 'CatsFromPayPingToWoo', 'NonceCatToPayPing' ); ?>
						  <?php submit_button( __( 'دسته محصولات', 'woocommerce' ), 'primary', 'submit_cat', true, null ); ?>
					  </form>
				  </div>
			  	</div>-->
			  	<!-- Sync Cats -->
		   </div>
		</div>
		<!-- end postbox-container -->
		<!--<br  style="clear:both">-->
		
		 <!-- start postbox-container -->
		<div id="postbox-container-2" class="postbox-container">
	   	<h2>همسان سازی ووکامرس با پی پینگ</h2>
		   <div id="column4-sortables" class="meta-box-sortables ui-sortable">
			  <div id="side-sortables" class="meta-box-sortables ui-sortable">
				  <div id="dashboard_quick_press" class="postbox">
					  <h2 class="hndle ui-sortable-handle"><span>یکسان سازی دسته های محصولات</span></h2>
					  <div class="inside" style="padding: 10px;">
						  <p>برای یکسان سازی تمامی دسته های فروشگاه خود با سرویس پی پینگ بر روی دکمه دسته محصولات کلیک کنید.</p>
					  </div>
					  <form method="post" action="">
						  <?php wp_nonce_field( 'wvpp_secure_form', 'st_token' ); ?>
						  <?php submit_button( __( 'دسته محصولات', 'woocommerce' ), 'primary', 'SubmitCatsToPayPing', true, null ); ?>
					  </form>
				  </div>
			  </div>
			  <!-- Coupon Code -->
			  <div id="side-sortables" class="meta-box-sortables ui-sortable">
				  <div id="dashboard_quick_press" class="postbox">
					  <h2 class="hndle ui-sortable-handle"><span>یکسان سازی کدهای تخفیف</span></h2>
					  <div class="inside" style="padding: 10px;">
						  <p>برای یکسان سازی تمامی کدهای تخفیف فروشگاه خود با سرویس پی پینگ بر روی دکمه کد تخفیف کلیک کنید.</p>
					  </div>
					  <form method="post" action="">
						  <?php wp_nonce_field( 'wcvpp_secure_form', 'st_token' ); ?>
						  <?php submit_button( __( 'کد تخفیف', 'woocommerce' ), 'primary', 'SubmitCouponToPayPing', true, null ); ?>
					  </form>
				  </div>
			  </div>
			  <!-- Coupon Code -->
			  <!-- Delete None Sync -->
			  <div id="side-sortables" class="meta-box-sortables ui-sortable">
				  <div id="dashboard_quick_press" class="postbox">
					  <h2 class="hndle ui-sortable-handle"><span>حذف محصولات غیرهمسان در پی‌پینگ</span></h2>
					  <div class="inside" style="padding: 10px;">
						  <p style="color:blue;">
							  حذف تمامی محصولات پی‌پینگ که در فروشگاه شما وجود ندارند.
						  </p>
					  </div>
					  <form method="post" action="">
						  <?php wp_nonce_field( 'wvpp_secure_form', 'st_token' ); ?>
						  <?php submit_button( __( 'حذف کنید', 'woocommerce' ), 'primary', 'DeleteProducts', true, null ); ?>
					  </form>
				  </div>
			  </div>
			  <!-- Delete None Sync -->
		   </div>
		</div>
		<!-- end postbox-container -->
	<?php

		echo '</div>';
		echo '</div>';
		}

	/* Start Settings Page */
	public function PayPing_WP_Plugin_Settings_Page(){
			echo '<div class="wrap">';
			_e( '<h1>پی‌پینگ</h1>', '' );
			?>
			<form id="donate_payping" method="post" action="options.php">
			<?php settings_fields( 'PayPing_WP_Plugin_Settings' ); ?>
			<?php do_settings_sections( 'PayPing_WP_Plugin_Settings' ); ?>
			   <table class="form-table" role="presentation">
				   <tbody>
					   <tr>
						   <th scope="row"><label for="blogname">توکن پی‌پینگ</label></th>
						   <td>
							   <input type="text" class="regular-text" name="PayPing_TokenCode" placeholder="<?php _e('توکن پی‌پینگ', 'PayPing'); ?>" value="<?php echo get_option('PayPing_TokenCode'); ?>" style="text-align:left;">
						   </td>
					   </tr>
					   <tr>
						   <th scope="row"><label for="blogname">حالت اشکال‌زدایی</label></th>
						   <td>
							  <select name="PayPing_DebugMode" id="PayPing_DebugMode">
								 <option value="deactive" <?php if( get_option('PayPing_DebugMode') === 'deactive' ) echo 'selected'; ?>><?php _e('غیرفعال', 'PayPing'); ?></option>
								 <option value="active" <?php if( get_option('PayPing_DebugMode') === 'active' ) echo 'selected'; ?>><?php _e('فعال', 'PayPing'); ?></option>
							  </select>
							  <p class="description" id="home-description">
								   این مورد فقط برای زمانی انتخاب شود که می‌خواهید اشکال‌زدایی کنید، در حالت عادی <strong>غیرفعال</strong> باشد.     
							   </p>
						   </td>
					   </tr>
					   <tr>
						   <th scope="row"><label for="blogname">آدرس جایگزین</label></th>
						   <td>
							   <input type="text" class="regular-text" name="PayPing_DebugUrl" placeholder="https://api.payping.ir" value="<?php echo get_option('PayPing_DebugUrl'); ?>" style="text-align:left;">
							   <p class="description" id="home-description">
								   درحالت اشکال‌زدایی این آدرس جایگزین آدرس درخواست به سرویس‌های پی‌پینگ می‌شود.     
							   </p>
						   </td>
					   </tr>
				   </tbody>
			   </table>
			<?php submit_button(); ?>
			</form>
	  <?php echo '</div>';
		}
	/* End Settings Page */

	/* Start Products List Page */
	public function PayPing_Woo_VIP_Products_List(){
		$ItemListTable = new Item_List_Table();
        $ItemListTable->prepare_items(); ?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<h2>محصولات</h2>
			<?php $ItemListTable->display(); ?>
			<div id="pro-up-img" class="pro-up-img"><p></p></div>
		</div>
		<?php
		}
	/* End Products List Page */

	/*  Start Coupon List Page */
	public function PayPing_Woo_VIP_Coupons_List(){
		$CouponID = $_GET['couponid'];
		if( isset( $_GET['action'] ) && ! empty( $CouponID ) ){
			self::add_Coupon_From_Woo_Too_PayPing( $CouponID, 1);
		}
		$CouponListTable = new Coupon_List_Table();
        $CouponListTable->prepare_items(); ?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<h2>کدهای تخفیف</h2>
			<?php $CouponListTable->display(); ?>
			<div id="pro-up-img" class="pro-up-img"><p></p></div>
		</div>
		<?php
	}
	/* End Coupon List Page */
	
	/* Start Settings In Wordpress */
	public function PayPing_WP_Plugin_Settings(){
			$text_args = array(
				'type' => 'string', 
				'sanitize_callback' => 'sanitize_text_field',
				'default' => NULL,
			);
			register_setting( 'PayPing_WP_Plugin_Settings', 'PayPing_TokenCode', $text_args );
			register_setting( 'PayPing_WP_Plugin_Settings', 'PayPing_DebugMode', $text_args );
			register_setting( 'PayPing_WP_Plugin_Settings', 'PayPing_DebugUrl', $text_args );
		}
	/* End Settings In Wordpress */

	/* Start Add Custom Comlums In Products List */
	public function PayPing_set_product_colmns( $columns ){
		$columns = array_merge( $columns, array(
			'PayPingVIP'     => __( 'پی‌پینگ', 'PayPing' )
			) 
		);
		return $columns;
	}

	public function PayPing_show_sync_product( $column, $post_id ){
		switch( $column ){
			case 'PayPingVIP' :
				$product = wc_get_product( $post_id );
				$items_codes = $this->PayPing_Get_Methods(  'product', 'all' );
				foreach( $items_codes as $items_code ){
					$item_codes[] = $items_code['code'];
				}
				if( $product->is_type( 'simple' ) ){
					$PaypingSync = get_post_meta( $post_id , '_payping_sync' , true );
					if( isset( $PaypingSync ) && in_array( $PaypingSync, $item_codes ) === true ){
						echo '<img src="'. WOOVIPDIRU .'/assets/images/active.svg" width="30px" height="auto" alt="سینک شده" title="این محصول با پی‌پینگ همسان شده است."/>';
					}else{
						echo '<img src="'. WOOVIPDIRU .'/assets/images/deactive.svg" width="30px" height="auto" alt="سینک نشده" title="این محصول با پی‌پینگ همسان نیست."/>';
					}
				}elseif( $product->is_type( 'variable' ) ){
					$args = array(
						'post_type'     => 'product_variation',
						'post_status'   => array( 'private', 'publish' ),
						'numberposts'   => -1,
						'orderby'       => 'menu_order',
						'order'         => 'asc',
						'post_parent'   => $post_id
						);
					$variations = get_posts( $args );
					$countDeActive = 0;
					$countActive = 0;
					foreach( $variations as $variation ){
						$PaypingSync = get_post_meta( $variation->ID , '_payping_sync' , true );
						if( isset( $PaypingSync ) && in_array( $PaypingSync, $item_codes ) === true ){
							$countActive++;
						}else{
							$countDeActive++;
						}
					}
					if( $countActive >= 0 ){
						echo '<img src="'. WOOVIPDIRU .'/assets/images/active.svg" width="30px" height="auto" alt="سینک شده" title=" '. $countActive .' متغیر با پی‌پینگ همسان شده است."/>';
					}
					if( $countDeActive >= 0 ){
						echo '<img src="'. WOOVIPDIRU .'/assets/images/deactive.svg" width="30px" height="auto" alt="سینک نشده" title="' . $countDeActive . ' متغیر با پی‌پینگ همسان نیست."/>';
					}
				}
				break;
		}
	}

	public function PayPing_register_bulk_actions_product( $bulk_actions ){
	  $bulk_actions['SyncToPayPing'] = __( 'همسان‌سازی پی‌پینگ', 'PayPing' );
	  return $bulk_actions;
	}

	public function PayPingBulkSyncProducts( $redirect_to, $doaction, $post_ids ){
	  if( $doaction !== 'SyncToPayPing' ){
		return $redirect_to;
	  }

	  $this->add_product_to_payping( $post_ids );

	  $redirect_to = add_query_arg( 'bulk-syncTo-payping-product', count( $post_ids ), $redirect_to );
	  return $redirect_to;
	}

	public function PayPingBulkSyncProductsNotice(){
		if( !empty( $_REQUEST['bulk-syncTo-payping-product'] ) ){
			$emailed_count = intval( $_REQUEST['bulk-syncTo-payping-product'] );
			printf( '<div id="message" class="updated notice is-dismissible"><p>' .
			_n( '%s محصول ساخته و بروز شد.', '%s محصول ساخته و بروز شد.', $emailed_count, 'PayPing' ) . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">بستن این اطلاع.</span></button></div>', $emailed_count );
		}
	}
	/* End Add Custom Comlums In Products List */

	/* Start ReSync Item In Cart */
	public function PayPing_Resync_In_Cart(){
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();
		foreach( $items as $item => $values ){
			$product = wc_get_product( $values['product_id'] );
			if( $product->is_type( 'variable' ) ){
				$ProductID = $values['variation_id'];
				$product = wc_get_product( $values['variation_id'] );
			}else{
				$ProductID = $values['product_id'];
			}
			$ProductCode = get_post_meta( $ProductID, '_payping_sync', true );
			if( isset( $ProductCode ) && !empty( $ProductCode ) ){
				$Item = $this->PayPing_Get_Methods( 'product/BulkQuantityWP', $ProductCode );
				$ReQuantity = $Item['quantity'];
				if( isset( $ReQuantity ) && !empty( $ReQuantity ) ){

					if( $product->get_manage_stock() === 'parent' ){  
						update_post_meta( $values['product_id'], '_stock', $ReQuantity );
					}else{
						update_post_meta( $ProductID, '_stock', $ReQuantity );
					}
				}
			}
		}
	}
	/* End ReSync Item In Cart */

	/* function add product_cat from payping to woocommerce */
	public function add_product_cat(  $nonce_form = null, $notif = 0 ){

		if( current_user_can('editor') || current_user_can('administrator') ){

			$product_cat = $this->PayPing_Get_Methods(  'category', 'List' );
			$new = 0;
			$update = 0;
			foreach( $product_cat as $category ){
				$cat_name = $category['name'];
				$check_term = get_term_by( 'name', $cat_name, 'product_cat' );

				$CatWooID = $check_term->term_id;
				$CatCode = get_term_meta( $CatWooID, 'PayPingCatCode', true );
				if( empty( $CatCode ) ){
					$CatCreate = wp_insert_term( $cat_name, 'product_cat' );
					update_term_meta( $CatCreate['term_id'], 'PayPingCatCode', $category['code'] );
					$new++;
				}else{
					wp_update_term( $CatWooID, 'product_cat', array( 'name' => $cat_name ) );
					$update++;
				}
			}
			$this->PayPing_WP_Dashboard_Notice( $new, $update, 'دسته' );
		}

	}
	/* end function add product_cat from payping to woocommerce */

	/* function add products from payping to woocommerce */
	public function add_product(  $nonce_form = null, $notif = 0 ){

		if( current_user_can('editor') || current_user_can('administrator') ){

			$products = $this->PayPing_Get_Methods(  'product', 'List' );
			$new = 0;
			$update = 0;
			foreach( $products as $product ):

				$product_title = $product['title'];
				$product_content = 'توضیحات محصول';
				$insert_author = get_current_user_id();

				/* get image link as payping */
				$img_link = $product['imageLink'];
				if( ! isset( $img_link ) || $img_link == null ){
					$img_code = plugin_dir_url( __FILE__ ) . '/assets/images/item_empty.png';
				}else{
					$img_code = $product['imageLink'];
				}
				$url_img = basename( $img_code );

				/* check visible product */
				if( $product['isActive'] === true ){
					$visibility = 'visible';
				}else{
					$visibility = '_visibility_hidden';
				}

				/* check unlimited */
				if( $product['unlimited'] === true ){
					$manage_stock = 'no';
					$quantity = '';
				}else{
					$manage_stock = 'yes';
					$quantity = $product['quantity'];
				}
				/* set product data */
				$datas = array(
					'_visibility'            => $visibility,
					'_stock_status'          => 'instock',
					'total_sales'            => '0',
					'_downloadable'          => 'no',
					'_virtual'               => 'no',
					'_regular_price'         => '',
					'_sale_price'            => "1",
					'_purchase_note'         => "",
					'_featured'              => "no",
					'_weight'                => "",
					'_length'                => "",
					'_width'                 => "",
					'_height'                => "",
					'_sku'                   => "",
					'_payping_sync'          => $product['code'],
					'_product_attributes'    => array(),
					'_sale_price_dates_from' => "",
					'_sale_price_dates_to'   => "",
					'_price'                 => $product['amount'],
					'_sold_individually'     => "",
					'_manage_stock'          => $manage_stock,
					'_backorders'            => "no",
					'_stock'                 => $quantity, 
				);

				/* others product meta */
				$defineAmountByUser = $product['defineAmountByUser'];
				$isActive = $product['isActive'];
				$haveTax = $product['haveTax'];
				$categoryCode = $product['categoryCode'];

				/* get product_id */
				$parent_post_id = $this->payping_post_id_by_meta_key_and_value( '_payping_sync', $product['code'] );

				if( !$parent_post_id ){
					// Create post object
					$new_product = array(
						'post_title'    => $product_title,
						'post_content'  => $product_content,
						'post_status'   => 'publish',
						'post_author'   => $insert_author,
						'post_type'     => 'product'
					);

					// Insert the post into the database
					$parent_post_id = wp_insert_post( $new_product );

					$attach_id = $this->insert_attachment_from_url( $img_code, $parent_post_id );
					set_post_thumbnail( $parent_post_id, $attach_id );

					/* set other values */ 
					wp_set_object_terms( $parent_post_id, $categoryCode, 'product_cat' );
					$this->update_meta_product( $parent_post_id, $datas );
					$new++;
				}else{
					/* Update product_post_id */
					$update_product = array(
						'ID'           => $parent_post_id,
						'post_title'    => $product_title,
						'post_content'  => $product_content,
						'post_status'   => 'publish',
						'post_author'   => $insert_author,
						'post_type'     => 'product'
					);

					/* Update the post into the database */
					wp_update_post( $update_product );

					/* check has product thumbnail */
					if( !has_post_thumbnail( $parent_post_id ) ){
						$attach_id = $this->insert_attachment_from_url( $img_code, $parent_post_id );
						set_post_thumbnail( $parent_post_id, $attach_id );
					}else{
						$attachment_file = basename( get_the_post_thumbnail_url( $parent_post_id ) );
						/* check exist attachment file */
						if( $attachment_file !== $url_img ){
							$attach_id = $this->insert_attachment_from_url( $img_code, $parent_post_id );
							set_post_thumbnail( $parent_post_id, $attach_id );
						}
					}

					/* set other values */ 
					wp_set_object_terms($parent_post_id, $categoryCode, 'product_cat');
					$this->update_meta_product( $parent_post_id, $datas );
					$update++;
				}

			endforeach;
		}
		$this->PayPing_WP_Dashboard_Notice( $new, $update, 'محصول' );
	}
	/* end function insert and update coupon code */

	/* function insert and update coupon code */
	public function add_coupon(  $nonce_form = null, $notif = 0 ){

		if( current_user_can('editor') || current_user_can('administrator') ){

			$body = $this->PayPing_Get_Methods( 'coupon' );
			$new = 0;
			$update = 0;
			foreach( $body as $coupon ){
				/* coupon data */
				$coupon_array = get_page_by_title( $coupon['userCouponCode'], 'ARRAY_A', 'shop_coupon' );
				$CouponID = $coupon_array['ID'];

				/* check active coupon */
				if( $coupon['isActive'] === true ){
					$coupon_status = 'publish';
				}else{
					$coupon_status = 'draft';
				}

				/* check type coupon */
				if( $coupon['type'] === 0 ){
					$discount_type = 'percent';
				}elseif( $coupon['type'] === 1 ){
					$discount_type = 'fixed_cart';
				}

				/* check active product coupon */
				if( $coupon['activeProductCode'] === null ){
					$product_ids = '';
				}else{ 
					$active_product = explode(',', $coupon['activeProductCode']);
					foreach( $active_product as $PayPingCode ){
						$product_ids[] = $this->payping_post_id_by_meta_key_and_value( '_payping_sync', $PayPingCode ); 
					}
					$product_ids = implode( ', ', $product_ids );
				}

				/* get data coupon */
				$datas = array(
					'code'                        => $coupon['userCouponCode'],
					'amount'                      => $coupon['amount'],
					'date_created'                => null,
					'date_modified'               => null,
					'date_expires'                => $coupon['redeemDate'],
					'discount_type'               => $discount_type,
					'description'                 => '',
					'usage_count'                 => 0,
					'individual_use'              => false,
					'product_ids'                 => $product_ids,
					'excluded_product_ids'        => array(),
					'usage_limit'                 => $coupon['maxRedemption'],
					'usage_limit_per_user'        => 0,
					'limit_usage_to_x_items'      => null,
					'free_shipping'               => false,
					'product_categories'          => array(),
					'excluded_product_categories' => array(),
					'exclude_sale_items'          => false,
					'minimum_amount'              => '',
					'maximum_amount'              => '',
					'email_restrictions'          => array(),
					'virtual'                     => false,
					'used_by'                     => array(),
					'PayPingCouponCode'           => $coupon['code']
			   );

				/* check exist coupon in woocommerce */
				if( $CouponID === NULL ){
					/* data insert coupon code */
					$insert_coupon = array(
						'post_title'                  => $coupon['userCouponCode'],
						'post_content'                => '',
						'post_status'                 => $coupon_status,
						'post_excerpt'                => $coupon['name'],
						'post_author'                 => get_current_user_id(),
						'post_type'                   => 'shop_coupon',
					);

					/* insert coupon code */
					$new_coupon_id = wp_insert_post( $insert_coupon, true );
					/* update coupon meta */
					$this->update_coupon_meta( $new_coupon_id, $datas );
					$new++;
				}elseif( $CouponID !== NULL ){

					/* data update coupon code */
					$update_coupon = array(
						'ID'                          => $CouponID, 
						'post_title'                  => $coupon['userCouponCode'],
						'post_content'                => '',
						'post_status'                 => $coupon_status,
						'post_excerpt'                => $coupon['name'],
						'post_author'                 => get_current_user_id(),
						'post_type'                   => 'shop_coupon',
					);

					/* update coupon code */
					wp_update_post( $update_coupon, true );
					/* update coupon meta */
					$this->update_coupon_meta( $CouponID, $datas );
					$update++;
				}else{
					_e('خطای غیرمنتظره!<br/>', 'woocommerce');
				}
			}
		}
		$this->PayPing_WP_Dashboard_Notice( $new, $update, 'کد تخفیف' );
	}
	/* end function add products from payping to woocommerce */

	/* function update meta_product */
	private function update_meta_product( $id, $data ){
				update_post_meta( $id, '_visibility', $data['_visibility'] );
				update_post_meta( $id, '_stock_status', $data['_stock_status']);
				update_post_meta( $id, 'total_sales', $data['total_sales']);
				update_post_meta( $id, '_downloadable', $data['_downloadable']);
				update_post_meta( $id, '_virtual', $data['_virtual']);
				update_post_meta( $id, '_regular_price', $data['_regular_price'] );
				update_post_meta( $id, '_sale_price', $data['_sale_price'] );
				update_post_meta( $id, '_purchase_note', $data['_purchase_note'] );
				update_post_meta( $id, '_featured', $data['_featured'] );
				update_post_meta( $id, '_weight', $data['_weight'] );
				update_post_meta( $id, '_length', $data['_length'] );
				update_post_meta( $id, '_width', $data['_width'] );
				update_post_meta( $id, '_height', $data['_height'] );
				update_post_meta( $id, '_payping_sync', $data['_payping_sync']);
				update_post_meta( $id, '_product_attributes', $data['_product_attributes']);
				update_post_meta( $id, '_sale_price_dates_from', $data['_sale_price_dates_from'] );
				update_post_meta( $id, '_sale_price_dates_to', $data['_sale_price_dates_to'] );
				update_post_meta( $id, '_price', $data['_price'] );
				update_post_meta( $id, '_sold_individually', $data['_sold_individually'] );
				update_post_meta( $id, '_manage_stock', $data['_manage_stock'] );
				update_post_meta( $id, '_backorders', $data['_backorders'] );
				update_post_meta( $id, '_stock', $data['_stock'] ); 
	}

	/* function update coupon meta */
	private function update_coupon_meta( $id, $data ){

		update_post_meta( $id, 'code', $data['code'] );
		update_post_meta( $id, 'coupon_amount', $data['amount'] );
		update_post_meta( $id, 'date_created', $data['date_created'] );
		update_post_meta( $id, 'date_modified', $data['date_modified'] );
		update_post_meta( $id, 'date_expires', $data['date_expires'] );
		update_post_meta( $id, 'discount_type', $data['discount_type'] );
		update_post_meta( $id, 'description', $data['description'] );
		update_post_meta( $id, 'usage_count', $data['usage_count'] );
		update_post_meta( $id, 'individual_use', $data['individual_use'] );
		update_post_meta( $id, 'product_ids', $data['product_ids'] );
		update_post_meta( $id, 'excluded_product_ids', $data['excluded_product_ids'] );
		update_post_meta( $id, 'usage_limit', $data['usage_limit'] );
		update_post_meta( $id, 'usage_limit_per_user', $data['usage_limit_per_user'] );
		update_post_meta( $id, 'limit_usage_to_x_items', $data['limit_usage_to_x_items'] );
		update_post_meta( $id, 'free_shipping', $data['free_shipping'] );
		update_post_meta( $id, 'product_categories', $data['product_categories'] );
		update_post_meta( $id, 'excluded_product_categories', $data['excluded_product_categories'] );
		update_post_meta( $id, 'exclude_sale_items', $data['exclude_sale_items'] );
		update_post_meta( $id, 'minimum_amount', $data['minimum_amount'] );
		update_post_meta( $id, 'maximum_amount', $data['maximum_amount'] );
		update_post_meta( $id, 'email_restrictions', $data['email_restrictions'] );
		update_post_meta( $id, 'virtual', $data['virtual'] );
		update_post_meta( $id, 'used_by', $data['used_by'] );
		update_post_meta( $id, 'PayPingCouponCode', $data['PayPingCouponCode'] );

	}

	/* function add attachment from payping to woocommerce */
	private function insert_attachment_from_url( $url, $parent_post_id = null ){
		if( !class_exists( 'WP_Http' ) )
			include_once( ABSPATH . WPINC . '/class-http.php' );
		$http = new WP_Http();
		$response = $http->request( $url );
		if( $response['response']['code'] != 200 ){
			return false;
		}
		$upload = wp_upload_bits( basename( $url ), null, $response['body'] );
		if( !empty( $upload['error'] ) ){
			return false;
		}
		$file_path = $upload['file'];
		$file_name = basename( $file_path );
		$file_type = wp_check_filetype( $file_name, null );
		$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
		$wp_upload_dir = wp_upload_dir();
		$post_info = array(
			'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
			'post_mime_type' => $file_type['type'],
			'post_title'     => $attachment_title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		// Create the attachment
		$attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );
		// Include image.php
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id,  $attach_data );
		return $attach_id;
	}

	private function payping_post_id_by_meta_key_and_value( $meta_key, $meta_value ){
			   global $wpdb;

			   $parent_post_id = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) );

			   if( count( $parent_post_id ) > 1 ) 
				  return $parent_post_id; // return array
			   else
				  return $parent_post_id[0]; // return int
	}

	public function add_product_to_payping( $Products_ids, $notif = 0 ){
		/* WP_Query arguments */
		$args = array (
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'post__in'               => $Products_ids
		);
		/* The Query*/
		$posts = new WP_Query( $args );
		$posts = $posts->get_posts();
		$currency = get_woocommerce_currency();

		$new = 0;
		$update = 0;
		foreach( $posts as $post ):
			$product = wc_get_product( $post->ID );
			
			if( $product->product_type === 'variable' ){
				$available_variations = $product->get_available_variations();
				$count_variation = 0;
				foreach( $available_variations as $variation ){
					$variation_id = $available_variations[$count_variation]['variation_id'];
					$product = new WC_Product_Variation( $variation_id );
					$notis = $this->PayPing_From_Woo_To_payping( $product, $currency,  $variation_id );
					$count_variation++;
				}
			}
			if( $product->product_type == 'simple' ){
				$notis = $this->PayPing_From_Woo_To_payping( $product, $currency,  $post->ID );
			}
		endforeach;   
	}

	/* Post Coupon_From_Woo_Too_PayPing */
	public function Coupon_From_Woo_Too_PayPing(  $notif = 0 ){

		$args = array(
			'posts_per_page'   => -1,
			'post_type'        => 'shop_coupon',
			'post_status'      => 'publish',
		);
		$coupons = get_posts( $args );

		$update = 0;
		$new = 0;
		
		foreach( $coupons as $coupon ){
			$CouponID = $coupon->ID;

			$isActive = $coupon->post_status;
			$OffCode = $coupon->post_name;
			if( empty( $coupon->post_excerpt ) ){
				$CouponName = $coupon->post_name;
			}else{
				$CouponName = $coupon->post_excerpt;
			}
			
			$amount = get_post_meta( $CouponID, 'coupon_amount', true );
			$endDate = get_post_meta( $CouponID, 'date_expires', true );
			if( $endDate == null ||  $endDate == '' ){
				$endDate = strtotime( '+5 years', current_time( 'timestamp' ) );
			}
			$type = get_post_meta( $CouponID, 'discount_type', true );
			$maxRedemption = get_post_meta( $CouponID, 'usage_limit', true );
			if( !isset( $maxRedemption ) || empty( $maxRedemption ) ){
				$maxRedemption = 1000000;
			}

			$activeProductCode = get_post_meta( $CouponID, 'product_ids', true );
			
			if( isset( $activeProductCode ) && !empty( $activeProductCode ) ){
				$Product_ids = explode(',', $activeProductCode );
				foreach( $Product_ids as $Product_id ){
					$ProductsCode[] = get_post_meta( $Product_id, '_payping_sync', true );
				}
			}else{
				$ProductsCode = ''; 
			}

			if( $type === 'percent' ){
				$type = 0;
			}elseif( $type === 'fixed_cart' ){
				$type = 1;
			}
			if( $isActive === 'publish' ){
				$isActive = true;
			}else{
				$isActive = false;
			}

			$Body = [];
			$Body['name'] = $CouponName;
			$Body['userCouponCode'] = $OffCode;
			$Body['type'] = $type;
			$Body['amount'] = intval( $amount );
			$Body['redeemDate'] = date( "Y-m-d", $endDate );
			$Body['redeemTime'] = date( "H:i:s", $endDate );
			$Body['maxRedemption'] = intval( $maxRedemption );
			$Body['isActive'] = $isActive;
			$Body['CouponUsed'] = 1; /* 1 factor 0 items */
			$Body['activeProductCode'] = $ProductsCode;

			/* Update Coupon */
			$CouponCode = get_post_meta( $CouponID, 'PayPingCouponCode', true );
			if( isset( $CouponCode ) && $CouponCode != '' || $CouponCode != null ){
				$CouponInPayping = $this->PayPing_Get_Methods( 'coupon', $CouponCode );
			}
			if( ! isset( $CouponInPayping ) || $CouponInPayping == 404 ){
				delete_post_meta( $CouponID, 'PayPingCouponCode', $CouponCode );
				/* New Coupon */
				$Post = $this->PayPing_Req_Methods( 'coupon', 'POST', $Body );
				if( isset( $Post['DuplicateCode'] ) ){
					update_post_meta( $CouponID, 'PayPingCouponCode', $Post['DuplicateCode'] );
					/* Update Coupon */
					$Body['code'] = $Post['DuplicateCode'];
					$Method = 'PUT';
					$PUT = $this->PayPing_Req_Methods( 'coupon', $Method, $Body );
					$update++;
				}else{
					$new++;
					update_post_meta( $CouponID, 'PayPingCouponCode', $Post );
				}
			}else{
				/* Update Coupon */
				$Body['code'] = $CouponCode;
				$Method = 'PUT';
				$PUT = $this->PayPing_Req_Methods( 'coupon', $Method, $Body );
				$update++;
			}
		}
		$this->PayPing_WP_Dashboard_Notice( $new, $update, 'کد تخفیف' );
	}
	/* End Post Coupon_From_Woo_Too_PayPing */
	
	/* Post Coupon_From_Woo_Too_PayPing */
	public function add_Coupon_From_Woo_Too_PayPing( $CouponID, $notif = 0 ){
		$update = 0;
		$new = 0;
			global $woocommerce;
			
			$couponCode = wc_get_coupon_code_by_id( $CouponID );
			$coupon = new WC_Coupon( $couponCode );
			
			$isActive = get_post_status( $CouponID );

			$OffCode = get_the_title( $CouponID );
			if( empty( get_the_excerpt( $CouponID ) ) ){
				$CouponName = get_the_title( $CouponID );
			}else{
				$CouponName = get_the_excerpt( $CouponID );
			}
			
			$amount = get_post_meta( $CouponID, 'coupon_amount', true );
			$endDate = get_post_meta( $CouponID, 'date_expires', true );
			
			if( $endDate == null ||  $endDate == '' ){
				$endDate = strtotime( '+5 years', current_time( 'timestamp' ) );
			}
			$type = get_post_meta( $CouponID, 'discount_type', true );
			$maxRedemption = get_post_meta( $CouponID, 'usage_limit', true );
			if( !isset( $maxRedemption ) || empty( $maxRedemption ) ){
				$maxRedemption = 1000000;
			}

			$activeProductCode = get_post_meta( $CouponID, 'product_ids', true );
		
			if( isset( $activeProductCode ) && !empty( $activeProductCode ) ){
				$Product_ids = explode(',', $activeProductCode );
				foreach( $Product_ids as $Product_id ){
					$ProductsCode[] = get_post_meta( $Product_id, '_payping_sync', true );
				}
				$CouponUsed = 0;
			}else{
				$ProductsCode = ''; 
				$CouponUsed = 1;
			}

			if( $type === 'percent' ){
				$type = 0;
			}elseif( $type === 'fixed_cart' ){
				$type = 1;
			}
			if( $isActive === 'publish' ){
				$isActive = true;
			}else{
				$isActive = false;
			}

			$Body = [];
			$Body['name'] = $CouponName;
			$Body['userCouponCode'] = $OffCode;
			$Body['type'] = $type;
			$Body['amount'] = intval( $amount );
			$Body['redeemDate'] = date( "Y-m-d", $endDate );
			$Body['redeemTime'] = date( "H:i:s", $endDate );
			$Body['maxRedemption'] = intval( $maxRedemption );
			$Body['isActive'] = $isActive;
			$Body['CouponUsed'] = $CouponUsed; /* 1 factor 0 items */
			$Body['activeProductCode'] = $ProductsCode;
			
			$CouponCode = get_post_meta( $CouponID, 'PayPingCouponCode', true );
			if( isset( $CouponCode ) && $CouponCode != '' || $CouponCode != null ){
				$CouponInPayping = $this->PayPing_Get_Methods( 'coupon', $CouponCode );
			}
			if( ! isset( $CouponInPayping ) || $CouponInPayping == 404 ){
				delete_post_meta( $CouponID, 'PayPingCouponCode', $CouponCode );
				/* New Coupon */
				$Post = $this->PayPing_Req_Methods( 'coupon', 'POST', $Body );
				if( isset( $Post['DuplicateCode'] ) ){
					update_post_meta( $CouponID, 'PayPingCouponCode', $Post['DuplicateCode'] );
					/* Update Coupon */
					$Body['code'] = $Post['DuplicateCode'];
					$Method = 'PUT';
					$PUT = $this->PayPing_Req_Methods( 'coupon', $Method, $Body );
					$update++;
				}else{
					$new++;
					update_post_meta( $CouponID, 'PayPingCouponCode', $Post );
				}
			}else{
				/* Update Coupon */
				$Body['code'] = $CouponCode;
				$Method = 'PUT';
				$PUT = $this->PayPing_Req_Methods( 'coupon', $Method, $Body );
				$update++;
			}
		$this->PayPing_WP_Dashboard_Notice( $new, $update, 'کد تخفیف' );
	}
	/* End Post Coupon_From_Woo_Too_PayPing */

	/* Post Cats_From_Woo_Too_PayPing */
	public function Cats_From_Woo_Too_PayPing( $notif = 0 ){

		$args = array(
			 'taxonomy'     => 'product_cat',
			 'orderby'      => 'name',
			 'show_count'   => 0,
			 'pad_counts'   => 0,
			 'hierarchical' => 1,
			 'title_li'     => '',
			 'hide_empty'   => 0
		);
		$CatsInWoo = get_categories( $args );

		$update = 0;
		$new = 0;
		foreach( $CatsInWoo as $CatInWoo ){
			$CatWooID = $CatInWoo->term_id;
			$CatName = $CatInWoo->name;

			$Body = [];
			$Body['name'] = $CatName;

			$CatCode = get_term_meta( $CatWooID, 'PayPingCatCode', true );
			if( !empty( $CatCode ) ){
				$CatsInPayPing = $this->PayPing_Get_Methods(  'category' );
				foreach( $CatsInPayPing as $CatInPayPing ){
					$CatsKey[] = $CatInPayPing['code'];
				}
				if( in_array( $CatCode, $CatsKey ) ){
					/* Update Category */
					$Body['code'] = $CatCode;
					$Method = 'PUT';
					$Post = $this->PayPing_Req_Methods(  'category', $Method, $Body );
					$update++;
				}else{
					/* New Category */
					$Post = $this->PayPing_Req_Methods(  'category', 'POST', $Body );
					update_term_meta( $CatWooID, 'PayPingCatCode', $Post );
					$new++;
				}
			}else{
				/* New Category */
				$Post = $this->PayPing_Req_Methods(  'category', 'POST', $Body );
				update_term_meta( $CatWooID, 'PayPingCatCode', $Post );
				$new++;
			}

		}
		$this->PayPing_WP_Dashboard_Notice( $new, $update, 'دسته‌بندی' );
	}
	/* End Post Cats_From_Woo_Too_PayPing */

	public function PayPing_From_Woo_To_payping( $product, $currency,  $postID ){

			/* Check Name */
			if( $product->get_name() == '' ){
				$name = 'بدون عنوان';
			}else{
				$name = $product->get_name();
			}

			/* Check Description */
			$description = $product->get_description();

			/* Check Price */
			if( $product->is_on_sale() ) {
				$price = intval($product->get_sale_price());
			}else{
				$price = intval($product->get_price());
			}

			if( $currency === 'IRR' ){
				$price = $price/10;
			}elseif( $currency === 'IRT' ){
				$price = $price;
			}elseif( $currency === 'IRHR' ){
				$price = $price*100;
			}elseif( $currency === 'IRHT' ){
				$price = $price*1000;
			}

			/* check manage_stock */
			$manage_stock = $product->get_manage_stock();

			/* check unlimited */
			if( $product->get_stock_quantity() > 0 && $manage_stock === true ){
				/* Set Quantity */
				$quantity = $product->get_stock_quantity();
				$unlimited = false;
			}elseif( $product->get_stock_quantity() === NULL && $manage_stock === false ){
				$quantity = 0;
				/* unlimited */
				$unlimited = true;
			}elseif( $product->get_stock_quantity() === NULL && $manage_stock === true || $product->get_stock_quantity() < 0 && $manage_stock === true ){
				$quantity = 0;
				/* Not unlimited */
				$unlimited = false;
			}elseif( $manage_stock === 'parent' ){
				/* Not unlimited */
				$unlimited = false;  
				$parent_id = wc_get_product( $product->parent_id );
				$quantity = $parent_id->get_stock_quantity();
			}

			$items_codes = $this->PayPing_Get_Methods(  'product', 'all' );

			foreach( $items_codes as $items_code ){
				$item_codes[] = $items_code['code'];
			}

			$product_code = get_post_meta( $postID, '_payping_sync', true);
			if( $item_codes === NULL || in_array( $product_code, $item_codes ) === false ){
				$Body = array(
					'title' => $name,
					'description' => $description,
					'amount' => $price,
					'defineAmountByUser' => false,
					'quantity' => $quantity,
					'haveTax' => false,
					'unlimited' => $unlimited,
				);
				$product_code = $this->PayPing_Req_Methods(  'product', 'POST', $Body );
				update_post_meta( $postID, '_payping_sync', $product_code );    
			}else{
				$Body = array(
					'code'  => $product_code,
					'title' => $name,
					'description' => $description,
					'amount' => $price,
					'defineAmountByUser' => false,
					'quantity' => $quantity,
					'haveTax' => false,
					'unlimited' => $unlimited,
	//                'imageLink' => $stream
				);
				$product_code = $this->PayPing_Req_Methods(  'product', 'PUT', $Body );
			}
		}

	/* Get From PayPing Function */
	public function PayPing_Get_Methods( $Items = 'product', $Params = 'all' ){
		$GetURL = WC_GPP_DebugURLs( $this->Debug_Mode, $this->Debug_URL, '/v1/' . $Items . '/' . $Params );
		$GetMethod = array(
			'body' => $body,
			'timeout' => '45',
			'redirection' => '5',
			'httpsversion' => '1.0',
			'blocking' => true,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->TokenCode,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			),
			'cookies' => array()
		);
		$RGet = wp_remote_get( $GetURL, $GetMethod );

		/* Call Function Show Debug In Console */
		WC_GPP_Debug_Log( $this->Debug_Mode, $RGet, $Items );

		$header = wp_remote_retrieve_headers( $RGet );
		$request_api = $header['x-paypingrequest-id'];
		if ( is_wp_error( $RGet ) ){
			return $Message = $RGet->get_error_message();
		}else{
			$code = wp_remote_retrieve_response_code( $RGet );
			if( $code === 200 ){
				$Items = json_decode( wp_remote_retrieve_body( $RGet ), true );
				return $Items;
			}else{
				return $code;
			}
		}
		return 0;

	}
	/* End Get From PayPing Function */

	/* Request Items PayPing Function */
	public function PayPing_Req_Methods( $Items = 'product', $Method = 'POST', $Body = array(), $ItemsDel = array() ){
		if( $Method === 'DELETE' ){
			$DeleteHeader = array(
				'Accept' => 'application/json',
				'Authorization' => 'Bearer ' . $this->TokenCode,
				'Content-Type' => 'application/json',
				'cache-control' => 'no-cache'
			);
			$DeleteArgs = array(
			   'method' => 'DELETE',
			   'timeout' => 45,
			   'redirection' => 5,
			   'httpversion' => '1.0',
			   'blocking' => true,
			   'headers' => $DeleteHeader,
			   'body' => json_encode( $Body, true ),
			   'cookies' => array()
			);
			foreach( $ItemsDel as $Item ){
				$DelURL = WC_GPP_DebugURLs( $this->Debug_Mode, $this->Debug_URL, '/v1/product/' . $Item );
				$Delets = wp_remote_request( $DelURL , $DeleteArgs );
			}
		}else{
			$PostHeader = array(
				'Accept' => 'application/json',
				'Authorization' => 'Bearer ' . $this->TokenCode,
				'Content-Type' => 'application/json',
				'cache-control' => 'no-cache'
			);

			$PostArgs = array(
			   'method' => $Method,
			   'timeout' => 45,
			   'redirection' => 5,
			   'httpversion' => '1.0',
			   'blocking' => true,
			   'headers' => $PostHeader,
			   'body' => json_encode( $Body, true ),
			   'cookies' => array()
			);
			/* Call Function Set Urls API */
			$PostURL = WC_GPP_DebugURLs( $this->Debug_Mode, $this->Debug_URL, '/v1/' . $Items );
			$RPost = wp_remote_request( $PostURL, $PostArgs );
			
			/* Call Function Show Debug In Console */
			WC_GPP_Debug_Log( $this->Debug_Mode, $RPost, 'Add, Update Or Delete ' . $Items );

			$header = wp_remote_retrieve_headers( $RPost );
			$request_api = $header['x-paypingrequest-id'];

			if( is_wp_error( $RPost ) ){
				return $RPost->get_error_message();
			}else{
				$code = wp_remote_retrieve_response_code( $RPost );
				$body = json_decode( wp_remote_retrieve_body( $RPost ), true );
				if( $code === 200 ){
					if( is_array( $body ) ){
						return $body['code'];
					}else{
						return $body;
					}
				}else{
					return $body;
				}
			}
		}
	}
	/* Request Items PayPing Function */

	/* Delete Items As PayPing Function */
	public function PayPing_Delete_Methods(){
		$args = array(
			'post_type'        => array ( 'product', 'product_variation ' ),
			'posts_per_page'   => -1
		);
		$posts = get_posts( $args );
		foreach( $posts as $post ){
			$ProductsCode[] = get_post_meta( $post->ID, '_payping_sync', true);
		}

		$items_codes = $this->PayPing_Get_Methods( 'product', 'all' );
		foreach( $items_codes as $items_code ){
			$item_codes[] = $items_code['code'];
		}

		$Diff = array_diff( $item_codes, $ProductsCode );
		$this->PayPing_Req_Methods( 'product', 'DELETE', array(), $Diff );

	}
	/* Delete Items As PayPing Function */

	private function WC_GPP_GetPathFile( $FileURL = '' ){
		$imageURL = parse_url( $FileURL );
		$filePath = get_home_path().$imageURL['path'];
		return $filePath;
	}
	
		/* Send Image To Payping */
	public function PayPing_Upload_Img_Product( $filePath ){
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
				"Authorization: Bearer " . $this->TokenCode
			  ),
			));

			$response = curl_exec($curl);
			$header = curl_getinfo($curl);
			$err = curl_error($curl);
			curl_close($curl);

			if($err){
				$arr = array( 'status_code' => $header['http_code'], 'message' => 'خطایی در هنگام اتصال به پی‌پینگ رخ داده است!' );
			}else{
				if( $header['http_code'] == 200 ){
					 $arr = array( 'status_code' => $header['http_code'], 'message' => 'بارگذاری با موفقیت انجام شد.', 'file_name' => $response );
				}else{
					 $arr = array( 'status_code' => $header['http_code'], 'message' => 'بارگذاری ناموفق!' );
				}
			}
			return $arr;
		}catch( Exception $e ){
			$arr = array( 'status_code' => $header['http_code'], 'message' => 'بارگذاری ناموفق، خطا سمت سایت شما! : ' . $e->getMessage() );
			return $arr;
		}
		return false;
	}
	
	public function payping_img_sync_img(){
		if( ! empty( $_POST['src'] ) && $_POST['src'] != null ){
			$filePath = $this->WC_GPP_GetPathFile( $_POST['src'] );
		}else{
			if( has_post_thumbnail( $_POST['id'] ) ){
				$filePath = $this->WC_GPP_GetPathFile( get_the_post_thumbnail_url( $_POST['id'] ) );
			}else{
				$filePath = false;
			}
		}
		
		if( $filePath ){
			$response = $this->PayPing_Upload_Img_Product( $filePath );
			if( $response ){
				if( $response['status_code'] == 200 ){
					$SyncImg = str_replace( '"', '',$response['file_name'] );
					update_post_meta( esc_attr( $_POST["id"] ), '_payping_sync_img', $SyncImg );
					$result = $response;
				}else{
					$result = $response;
				}
			}else{
				$result = array( 'message' => 'مشکلی در تصویر وجود دارد!' );
			}
		}else{
			$result = array( 'message' => 'آدرس تصویر ارسالی صحیح نیست!' );
		}
		echo json_encode( $result );
		wp_die();
	}
	
	/* Show PayPing_WP_Dashboard_Notice */
	public function PayPing_WP_Dashboard_Notice( $new = 0, $update = 0, $Item = 'مورد' ){
		echo '<div class="notice notice-success is-dismissible">
				<p>' . $new . ' ' . $Item . ' ایجاد و ' . $update . ' ' . $Item . ' بروز شد.</p>
			 </div>';
	}
	/* End Show PayPing_WP_Dashboard_Notice */
	
	public function PP_Add_OR_Update_Product_WOO( $product_id ){
		$currency = get_woocommerce_currency();
		$product = wc_get_product( $product_id );
		if( $product->product_type === 'variable' ){
			$available_variations = $product->get_available_variations();
			$count_variation = 0;
			foreach( $available_variations as $variation ){
				$variation_id = $available_variations[$count_variation]['variation_id'];
				$product = new WC_Product_Variation( $variation_id );
				$this->PayPing_From_Woo_To_payping( $product, $currency,  $variation_id );
				$count_variation++;
			}
		}
		if( $product->product_type == 'simple' ){
			$this->PayPing_From_Woo_To_payping( $product, $currency, $product_id );
		}
	}
	
	public function PP_Add_OR_Update_Coupon_WOO( $CouponID ){
		$this->add_Coupon_From_Woo_Too_PayPing( $CouponID, $notif = 1 );
	}
	
	/* Start ReSync Item In CronJobs */
	public function PayPing_Resync_In_CronJobs(){
		$PPpSyncCode = pp_get_products_sync_code();
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.payping.ir/v1/product/BulkQuantityWP",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_POSTFIELDS => "{ \"codes\": $PPpSyncCode }",
		  CURLOPT_HTTPHEADER => array(
			"Content-Type: application/json",
			"Authorization: Bearer $this->TokenCode"
		  ),
		));
		
		$response = curl_exec($curl);
		curl_close($curl);
		$items = json_decode( $response, true );
		$counter = 1;
		foreach( $items as $item ){
			$unlimited = $item['unlimited'];
			$quantity = $item['quantity'];
			$SyncCode = $item['code'];
			global $wpdb;
			$table_name = $wpdb->prefix . "postmeta";
			$results = $wpdb->get_results("SELECT * FROM $table_name WHERE `meta_value` = \"$SyncCode\" ");
			update_post_meta( $results[0]->post_id, '_stock', $quantity );
			$counter++;
		}
		return $counter;
	}
	
}
/* end class Control_Vip_Payping */

new Payping_woo_vip();

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Item_List_Table extends WP_List_Table{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items(){
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns(){
        $columns = array(
			'image'       => __('تصویر', 'PayPing'),
            'id'          => __('شناسه و نام', 'PayPing'),
            'status'      => __('وضعیت', 'PayPing'),
            'Code'        => __('کد آیتم', 'PayPing'),
            'ImageLink'   => __('کد تصویر آیتم', 'PayPing'),
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns(){
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns(){
        return array('id' => array('id', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data(){
        $data = array();
		$args = array(
			'post_type'              => array( 'product' ),
			'post_status'            => array( 'publish' ),
			'posts_per_page'         => -1,
			'order'                  => 'DESC',
			'orderby'                => 'date',
		);
		$PList = new WP_Query( $args );
		while( $PList->have_posts() ): $PList->the_post(); global $product; $pID = get_the_ID();
		$atag = '<a href="javascript:;" id="'.$pID.'" title="برای همسان‌سازی تصویر کلیک کنید" class="pp_img_sync" data-value="'.get_the_post_thumbnail_url($pID).'"></a>';
		/* check image set in woocommerce */
		if( has_post_thumbnail() ){
			$Image = get_the_post_thumbnail($pID, array( 40, 40) );
		}else{
			$Image = '<img src="' . WOOVIPDIRU . 'assets/images/logo.png" title="این محصول تصویری ندارد." width="40" height="40"/>';
		}
		
		$pImage = get_the_post_thumbnail_url($pID);
		
		/* check status */
		$ItemCode = get_post_meta($pID, '_payping_sync', true);
		$ImageCode = get_post_meta($pID, '_payping_sync_img', true);
		
		if( $ItemCode && $ImageCode ){
			$status = '<b style="color: green;">کامل</b>';
		}elseif( $ItemCode ){
			$status = '<b style="color: orange;">محتوا</b>';
		}elseif( $ImageCode ){
			$status = '<b style="color: purple;">تصویر</b>';
		}else{
			$status = '<button id="ItemAllSync" data-product-src="'. $pImage .'" data-product-id="'. $pID .'">همسان‌سازی</button>';
		}
		
		/* Check Status Sync Product */
		if( ! empty( $ItemCode ) ){
			$SItemCode = $ItemCode;
		}else{
			$SItemCode = '<button id="ItemSync" data-product-src="'. $pImage .'" data-product-id="'. $pID .'" >همسان‌سازی</button>';
		}
		
		/* Check Status Sync Product IMG */
		if( ! empty( $ImageCode ) ){
			$SImageCode = $ImageCode;
		}else{
			$SImageCode = '<button id="ItemImgSync" data-product-src="'. $pImage .'" data-product-id="'. $pID .'">همسان‌سازی</button>';
		}
		
        $data[] = array(
					'image'       => $Image,
                    'id'          => $pID . ': ' . get_the_title(),
                    'status'      => $status,
					'Code'        => $SItemCode,
					'ImageLink'   => $SImageCode,
                    );
		endwhile; wp_reset_query();
        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name ){
        switch( $column_name ){
			case 'image':
            case 'id':
            case 'status':
            case 'Code':
            case 'ImageLink':
                return $item[ $column_name ];
            default:
                return print_r( $item, true );
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b ){
        // Set defaults
        $orderby = 'id';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = esc_attr( $_GET['order'] );
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}

/* list copoun */
class Coupon_List_Table extends WP_List_Table{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items(){
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns(){
        $columns = array(
            'id'          => __('شناسه و نام', 'PayPing'),
            'status'      => __('وضعیت', 'PayPing'),
            'Code'        => __('کد کوپون', 'PayPing'),
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns(){
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns(){
        return array('id' => array('id', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data(){
		if( isset( $_GET['paged'] ) && $_GET['paged'] != 1 ){
			$paged = esc_attr( $_GET['paged'] );
		}else{
			$paged = 1;
		}
        $data = array();
		$args = array(
			'post_type'              => array( 'shop_coupon' ),
			'post_status'            => array( 'publish' ),
			'posts_per_page'         => -1,
			'order'                  => 'DESC',
			'orderby'                => 'date',
		);
		$PList = new WP_Query( $args );
		while( $PList->have_posts() ): $PList->the_post(); global $product; $pID = get_the_ID();
		/* check status */
		$CouponCode = get_post_meta($pID, 'PayPingCouponCode', true);
		$checkMeta = $this->MetaValueCouponCode( $CouponCode );
		if( $CouponCode && $checkMeta ){
			$status = '<b style="color: green;"> همسان</b><a href="' . admin_url('admin.php?page=payping-vip-coupons&couponid=' . $pID . '&action=resync&paged=' . $paged ) . '"> مجدد </a>';
		}elseif( "Bad Request" == $CouponCode ){
			$status = '<a href="' . admin_url('admin.php?page=payping-vip-coupons&couponid=' . $pID . '&action=add&paged=' . $paged ) . '">همسان‌سازی</a>';
		}elseif( "Internal Server Error" == $CouponCode ){
			$status = '<a href="' . admin_url('admin.php?page=payping-vip-coupons&couponid=' . $pID . '&action=add&paged=' . $paged ) . '">همسان‌سازی</a>';
		}else{
			$status = '<a href="' . admin_url('admin.php?page=payping-vip-coupons&couponid=' . $pID . '&action=add&paged=' . $paged ) . '">همسان‌سازی</a>';
		}
		
        $data[] = array(
                    'id'          => $pID . ': ' . get_the_title(),
                    'status'      => $status,
					'Code'        => $CouponCode,
                    );
		endwhile; wp_reset_query();
        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name ){
        switch( $column_name ){
            case 'id':
            case 'status':
            case 'Code':
                return $item[ $column_name ];
            default:
                return print_r( $item, true );
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b ){
        // Set defaults
        $orderby = 'id';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
	
	private function MetaValueCouponCode( $CouponCode ){
		switch( $CouponCode ){
			case "Internal Server Error":
				return false;
				break;
			case "Bad Request":
				return false;
				break;
			case "Not Found":
				return false;
				break;
			case "Array":
				return false;
				break;
			default:
				return true;
		}
		
	}
	
}
// Add a new interval of 300 seconds
add_filter( 'cron_schedules', 'pp_add_every_five_minutes' );
function pp_add_every_five_minutes( $schedules ){
    $schedules['every_five_minutes'] = array(
            'interval'  => 5,
            'display'   => __( 'Every 5 Seconds', 'textdomain' )
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if( ! wp_next_scheduled( 'pp_add_every_five_minutes' ) ){
    wp_schedule_event( time(), 'every_five_minutes', 'pp_add_every_five_minutes' );
}

// Hook into that action that'll fire every three minutes
add_action( 'pp_add_every_five_minutes', 'every_five_minutes_event_func' );
function every_five_minutes_event_func(){
	$ppClass = new Payping_woo_vip();
	$SyncsCodes = $ppClass->PayPing_Resync_In_CronJobs();
	$time = date_i18n( 'Y-m-d H:s:i', current_time( 'timestamp' ) );
}

function pp_get_products_sync_code(){
	global $wpdb;
	$table_name = $wpdb->prefix . "postmeta";
	/* Get _payping_sync Product */
	$results = $wpdb->get_results("SELECT `meta_value` FROM $table_name WHERE `meta_key` = '_payping_sync' ", ARRAY_N);
	foreach( $results as $r ){
		$result[] = $r[0];
	}
	(object)$reult = $result;
	return json_encode( $reult, true );
}
