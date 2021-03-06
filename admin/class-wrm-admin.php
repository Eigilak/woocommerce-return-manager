<?php
/**
 ========================
      ADMIN SETTINGS
 ========================
 */

//Exit if accessed directly
if(!defined('ABSPATH')){
	return;
}

class WRM_Admin{

    protected static $instance = null;

    public function __construct()
    {
        if(is_user_logged_in()){
            add_action('admin_menu',array($this,'wrm_add_admin_menu'));
            add_action('init',array($this, 'load_ajax_method'));
            add_action('admin_enqueue_scripts',array($this,'enqueue_scripts'),1);

        /*    add_action('admin_init','wrm_settings_init');*/
        }

    }

    //Get class instance
    public static function get_instance(){
        if(self::$instance === null){
            self::$instance = new self();
        }
        return self::$instance;
    }
/*
    function wrm_settings_init(){
        register_setting('wrmPluginPage','wrm_settings');
    }*/

    function wrm_add_admin_menu(){
        add_submenu_page('woocommerce', 'Woocommerce return manager', 'Return manager', 'manage_options', 'Wrm',array($this,'list_return_page'));
    }

    function load_ajax_method(){
        add_action( 'wp_ajax_init_get_orders',      array( &$this, 'init_get_orders' ) );
        add_action( 'wp_ajax_search_orders',        array( &$this, 'search_orders' ) );
        add_action( 'wp_ajax_delete_order',        array( &$this, 'delete_order' ) );

        wc_get_template('settings.php','','',WRM_PATH.'/admin/includes/');
    }

    function list_return_page(){

        wc_get_template('wrm-returned-orders.php','','',WRM_PATH.'/admin/templates/');
    }

    /*Enqueu */
    function enqueue_scripts(){
        /*Styles*/
              wp_enqueue_style('wrm-admin-style',WRM_URL.'/admin/assets/scss/mainStyle.css',null,WRM_VERSION);

        /*Scripts*/
        /*development*/
          // wp_enqueue_script('vue',WRM_URL.'/admin/assets/js/frameworks/vue.js','',WRM_VERSION,false);
        /*production*/
         wp_enqueue_script('vue',WRM_URL.'/assets/js/frameworks/vue_production.min.js','',WRM_VERSION,false);

        wp_enqueue_script('wrm-forms',WRM_URL.'/admin/assets/js/frameworks/vue_forms.js','',WRM_VERSION,true);
        wp_enqueue_script('wrm-admin-js',WRM_URL.'/admin/assets/js/wrm-admin.js','',WRM_VERSION,true);

        wp_localize_script( 'wrm-admin-js', 'local',
            array(
                'ajax_url' 			=> 	admin_url( 'admin-ajax.php' ),
                'fc_nonce'			=> wp_create_nonce(),
                'confirm_msg'       => __('Are you sure you want to delete','wrm'),
                'request_msg'       => __('request','wrm')
            ));

    }
    /*actions*/
    function init_get_orders(){

        global $wpdb;
        $returnedOrders=[];

        $orders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_return_manager_order");

        foreach ($orders as $order ){

            $order_id = $order->id;
            $preparedStatement = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_return_manager_product WHERE return_id=%d", $order_id);

            $products = $wpdb->get_results($preparedStatement);

            $created_at = date("d-m-Y",strtotime($order->created_at));
            $returnedOrders[]= [
                'id'            =>$order->id,
                'order_id'      =>$order->order_id,
                'name'          =>$order->name,
                'email'         =>$order->email,
                'comment'       =>$order->comment,
                'product_count' =>$order->amount_products_returned,
                'products'      =>$products,
                'created_at'    =>$created_at,
                'showComment'   => false,
                'showProduct'   =>false
            ];

        }

        wp_send_json_success($returnedOrders);
        wp_die();
    }
    function search_orders(){

        $search_key = sanitize_text_field($_REQUEST['search_query']);



        die();
    }
    function delete_order(){

        global $wpdb;
        $returnedOrders=[];
        $id = $_REQUEST['returned_id'];
        $nonce = _sanitize_text_fields($_REQUEST['nonce']);
        /*Check if the nonce from the site is the same generated from wordpress*/
        if(!isset($nonce) || !wp_verify_nonce($nonce)){
            WRM_Core::error_404(__('Hmmm... seems your nonce doesnt fit ours ','wrm'));
        }

        $deleteProduct = $wpdb->prepare("DELETE FROM {$wpdb->prefix}woocommerce_return_manager_product WHERE return_id=%d",$id);
        $deleteOrder = $wpdb->prepare("DELETE FROM {$wpdb->prefix}woocommerce_return_manager_order WHERE id=%d",$id);

        $is_deleted_product = $wpdb->query($deleteProduct);

        if(!$is_deleted_product){
            WRM_Core::error_404(__('We cant delete the product... try again ','wrm'));
        }
        else{
            $is_deleted_order = $wpdb->query($deleteOrder);
            if(!$is_deleted_order){
                WRM_Core::error_404(__('We cant delete the orders... try again ','wrm'));
            }
            wp_send_json_success(__('The order and its products is deleted','wrm'));

        }


        die;

    }

}






