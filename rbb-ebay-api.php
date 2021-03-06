<?php
/*
  Plugin Name: Ebay API
  Plugin URI: 
  Description: This is ebay API plugin, custom options with amdin setting page.
  Author: Jordan R
  Version: 1.0
  Author URI: 
 */

class EbayPage {
   /**
     * Start up
     */
    public function __construct() {
        add_action('wp_loaded', array($this,'ebayAddStyles'));
        register_activation_hook(__FILE__, array($this, 'create_ebay_database_table'));
        add_action('admin_menu', array($this,'awesome_page_create'));
    }
    public function ebayAddStyles() {
        wp_register_style( 'ebayapirbbstyle', plugins_url('css/style.css',__FILE__ ));
    }
    public function create_ebay_database_table() {
        global $wpdb;
        $ebayProducts = $wpdb->prefix.'EbayProducts';
        $EbayProductsDesc = $wpdb->prefix.'EbayProductsDesc';
        $ebaySearched = $wpdb->prefix.'EbaySearched';
        $this->create_ebay_products_db_table($ebayProducts);
        $this->create_ebay_products_des_db_table($EbayProductsDesc);
        $this->create_ebay_searched_db_table($ebaySearched);
    }
    public function create_ebay_products_db_table($table_name) {
        global $wpdb;
        if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
            $sql = 'CREATE TABLE ' . $table_name . ' ( `id` INTEGER(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY, `itemId` VARCHAR(255), `categoryId` VARCHAR(225), `categoryName` VARCHAR(255), `addedtocommerce` tinyint(1), `post_id` INTEGER(10) UNSIGNED,`itemObject` BLOB)';
            require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
            dbDelta($sql);
        }
    }    
    public function create_ebay_products_des_db_table($table_name) {
        global $wpdb;
        if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
            $sql = 'CREATE TABLE ' . $table_name . ' ( `id` INTEGER(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY, `itemId` VARCHAR(255), `timeStamp` TIMESTAMP, `updated` tinyint(1), `itemObjectDes` BLOB)';
            require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
            dbDelta($sql);
        }
    }    
    public function create_ebay_searched_db_table($table_name) {
        global $wpdb;
        if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
            $sql = 'CREATE TABLE ' . $table_name . ' ( `id` INTEGER(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY, `searchKey` VARCHAR(255), `pageNumber` INTEGER(10), `totalPages` INTEGER(10), `timestamp` VARCHAR(255))';
            require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
            dbDelta($sql);
        }
    }    
    public function awesome_page_create() {
        $page_title = 'Ebay API';
        $menu_title = 'Ebay API';
        $capability = 'manage_options';
        $menu_slug = 'ebay_api_rbb';
        $function = array($this,'ebay_api_page_display');
        add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function);
        $parent_slug = $menu_slug;
        $s_page_title = 'Ebay Products';
        $s_menu_title = 'Ebay Products';
        $s_menu_slug = 'ebay_products_rbb';
        $s_function = array($this,'ebay_products_page_display');
        add_submenu_page( $parent_slug, $s_page_title, $s_menu_title, $capability, $s_menu_slug, $s_function);
        $st_page_title = 'Ebay Settings';
        $st_menu_title = 'Ebay Settings';
        $st_menu_slug = 'ebay_settings_rbb';
        $st_function = array($this,'ebay_settings_page_display');
        add_submenu_page( $parent_slug, $st_page_title, $st_menu_title, $capability, $st_menu_slug, $st_function);
    }
    public function ebay_settings_page_display() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }
        if (!empty($_POST))
        {
            if (! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'EbayNonce' ) )
            {
                print 'Sorry, your nonce did not verify.';
                exit;
            }
        }
		if (isset($_POST['apikey']) && strlen($_POST['apikey']) ) {
            $value = sanitize_text_field($_POST['apikey']);
            update_option('ebayapikey', $value);
		}
		if (isset($_POST['trackingid']) && strlen($_POST['trackingid']) ) {
            $trackingId = sanitize_text_field($_POST['trackingid']);
            update_option('trackingid', $trackingId);
		}
		if (isset($_POST['customerid']) && strlen($_POST['customerid']) ) {
            $customerId = sanitize_text_field($_POST['customerid']);
            update_option('customerid', $customerId);
		}
		if (isset($_POST['networkid']) && strlen($_POST['networkid']) ) {
            $networkId = sanitize_text_field($_POST['networkid']);
            update_option('networkid', $networkId);
        }

        $value = get_option('ebayapikey');
        $trackingId = get_option('trackingid');
        $customerId = get_option('customerid');
        $networkId = get_option('networkid');
        include 'settings-file.php';
    }
    public function ebay_products_page_display() {
        wp_enqueue_style('ebayapirbbstyle');
        $productResults = $this->getProductResults(0,10);
        include 'products-file.php';
    }
	public function processForTesting(){
		echo "Testing Enabled";
		//$this->updateAllProductRows();
		//global $EbayUpDtProdDes;
		//$EbayUpDtProdDes->updateEbayProducts();
		//global $EbayCommerce;
		//$EbayCommerce->processEbayProducts();
		//global $EbayUpdateProductsForEach;
		//$EbayUpdateProductsForEach->updateEbayEachItem();
		die;
	}
    public function ebay_api_page_display() {
		
        //$this->processForTesting();
		
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }
        if (!empty($_POST))
        {
            if (! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'EbayNonce' ) )
            {
                print 'Sorry, your nonce did not verify.';
                exit;
            }
        }
        $categoriesResults = $this->listCategoryIds();
        if (isset($_POST['searchkey'])) {

            $searchKey = sanitize_text_field($_POST['searchkey']);
            update_option('searchkey', $searchKey);

            $categoryKey = sanitize_text_field($_POST['categorykey']);
            update_option('categorykey', $categoryKey);

            if(count($categoriesResults)){
                foreach($categoriesResults as $key => $catItem){
                    $catFlag = 0;
                    if(isset($_POST[$key])) $catFlag = 1;
                    update_option($key, $catFlag);
                }
            }
        }

        $searchKey = get_option('searchkey');
        $categoryKey = get_option('categorykey');
        if (isset($_POST['searchkey'])) {
            if($this->checkForDbValue($searchKey)){
                $results =  "Search Key is Already in Database";
            }else{
                $results = $this->call_ebay_api();
            }
        }
		if(count($_POST['delete_list'])){
			$this->deleteSearchKeys($_POST['delete_list']);
		}
        $searchKeyResults = $this->getSearchKeyResults();
        include 'form-file.php';
    }
	public function deleteSearchKeys($deleteA){
		global $wpdb;
		$ebaySearched = $wpdb->prefix.'EbaySearched';
		$qStr = "DELETE FROM $ebaySearched WHERE ";
		$qStr .= "searchKey IN ('";
		$qStr .= implode("','",$deleteA);
		$qStr .= "')";
		$wpdb->query( $qStr );
	}
    public function constructApiUrl($pageNo){
        // API request variables
        $endpoint = 'http://svcs.ebay.com/services/search/FindingService/v1';  // URL to call
        $version = '1.0.0';  // API version supported by your application
        $appid = get_option('ebayapikey');  // Replace with your own AppID
        $globalid = 'EBAY-US';  // Global ID of the eBay site you want to search (e.g., EBAY-DE)
        $query = get_option('searchkey');  // You may want to supply your own query
        $safequery = urlencode($query);  // Make the query URL-friendly
        $trackingId = get_option('trackingid');
        $customerId = get_option('customerid');
        $networkId = get_option('networkid');
        $i = '0';  // Initialize the item filter index to 0
        // Construct the findItemsByKeywords HTTP GET call 
        $apicall = "$endpoint?";
        $apicall .= "OPERATION-NAME=findItemsByKeywords";
        $apicall .= "&SERVICE-VERSION=$version";
        $apicall .= "&SECURITY-APPNAME=$appid";
        $apicall .= "&GLOBAL-ID=$globalid";
        $apicall .= "&keywords=$safequery";
        $apicall .= "&paginationInput.entriesPerPage=10";
        $apicall .= "&paginationInput.pageNumber=$pageNo";
		$apicall .= "&outputSelector(0)=PictureURLLarge";
		$apicall .= "&affiliate.networkId=$networkId";
		$apicall .= "&affiliate.trackingId=$trackingId";
		$apicall .= "&affiliate.customId=$customerId";
        return $apicall;
    }
    public function call_ebay_api(){
        $apicall = $this->constructApiUrl(1);
        $resp = simplexml_load_file($apicall);
        if ($resp->ack == "Success") {
            $xml = json_decode(json_encode((array) $resp), 1);
            $this->addDataToEbaySearchTable($xml);
            $this->addDataToEbayProductTable($xml);
            $results =  "Search Key is Successfully saved";
        }
        else {
            $results  = "<h3>Oops! The request was not successful. Make sure you are using a valid ";
            $results .= "AppID for the Production environment.</h3>";
        }  
        return $results;
    }
    public function updateDataToEbaySearchTable($data){
        global $wpdb;
        $ebaySearched = $wpdb->prefix.'EbaySearched';
        $ebaySearchedWhereA['searchKey'] = get_option('searchkey');
        $ebaySearchedA['pageNumber'] = $data['paginationOutput']['pageNumber'];
        $wpdb->update($ebaySearched, $ebaySearchedA, $ebaySearchedWhereA);
    }
    public function addDataToEbaySearchTable($data){
        global $wpdb;
        $ebaySearched = $wpdb->prefix.'EbaySearched';
        $ebaySearchedA['id'] = "";
        $ebaySearchedA['searchKey'] = get_option('searchkey');
        $ebaySearchedA['pageNumber'] = $data['paginationOutput']['pageNumber'];
        $ebaySearchedA['totalPages'] = $data['paginationOutput']['totalPages'];
        $ebaySearchedA['timestamp'] = $data['timestamp'];
        $this->sendToTable($ebaySearched,$ebaySearchedA);
    }
    public function addDataToEbayProductTable($data){
        global $wpdb;
        $catA = $this->excludeCategories();
        $ebayProducts = $wpdb->prefix.'EbayProducts';
        $items = $data['searchResult']['item'];
        foreach($items as $item){
            $itemObject = $item;
            $catName = $item['primaryCategory']['categoryName'];
            $ebayProductsA['id'] = "";
            $ebayProductsA['itemId'] = $item['itemId'];
            $ebayProductsA['categoryId'] = $item['primaryCategory']['categoryId'];
            $ebayProductsA['categoryName'] = $catName;
            $ebayProductsA['addedtocommerce'] = 0;
            $ebayProductsA['post_id'] = 0;
            $ebayProductsA['itemObject'] = serialize($itemObject);
            $itemFound = $this->getProductCountOfItemId($ebayProducts,array("itemId" => $item['itemId']));
            if(!$itemFound){
                if(count($catA) == 0){
                    $this->sendToTable($ebayProducts,$ebayProductsA);
                }else{
                    if(!in_array($catName,$catA)){
                        $this->sendToTable($ebayProducts,$ebayProductsA);
                    }
                }
            }
        }
    }
    public function excludeCategories(){
		global $EbayPage;
		$EbayPage = new EbayPage();
        $categoryIdsA = $EbayPage->listCategoryIds();
        if(count($categoryIdsA)){
            foreach($categoryIdsA as $key => $catItem){
                $excludeCat = get_option($key);
                if(!$excludeCat){
                    unset($categoryIdsA[$key]);
                }
            }
        }
        return $categoryIdsA;
    }
    public function sendToTable($table,$values){
        global $wpdb;
        $wpdb->insert($table,$values);
    }
    public function getSearchKeyResults(){
        global $wpdb;
        $ebaySearched = $wpdb->prefix.'EbaySearched';
        $results = $wpdb->get_results( "SELECT * FROM $ebaySearched WHERE id > 0 ORDER BY id DESC", OBJECT );
        return $results;
    }
    public function checkForDbValue($searchKey){
        global $wpdb;
        $ebaySearched = $wpdb->prefix.'EbaySearched';
        $results = $wpdb->get_row( "SELECT count(*) as count FROM $ebaySearched WHERE searchKey = '$searchKey'", OBJECT );
        return $results->count;
    }
    public function getProductResults($start,$limit){
        global $wpdb;
        $excludeStr = "";
        $categoryIdsA = $this->excludeCategories();
        if(count($categoryIdsA)){
            $excludeStr = "AND categoryName NOT IN ('";
            $excludeStr .= implode("','",$categoryIdsA);
            $excludeStr .= "')";
        }        
        $ebayProducts = $wpdb->prefix.'EbayProducts';
        $query = "SELECT * FROM $ebayProducts WHERE id > 0 $excludeStr ORDER BY id DESC LIMIT $start,$limit";
        $results = $wpdb->get_results( $query, OBJECT );
        return $results;
    }
    public function listCategoryIds(){
        global $wpdb;
        $ebayProducts = $wpdb->prefix.'EbayProducts';
        $results = $wpdb->get_results( "SELECT DISTINCT categoryName FROM $ebayProducts WHERE id > 0 ORDER BY id DESC", OBJECT );
        $carA = array();
        foreach($results as $value){
            $catM = "ebay_".str_replace(" ","_",$value->categoryName);
            $catA[$catM] = $value->categoryName;
        }
        return $catA;
    }
    public function getProductCountOfCat($cat){
        global $wpdb;
        $ebayProducts = $wpdb->prefix.'EbayProducts';
        $results = $wpdb->get_row( "SELECT count(*) as count FROM $ebayProducts WHERE categoryName = '$cat'", OBJECT );
        return $results->count;
    }
    public function getProductCountOfItemId($table,$field){
        global $wpdb;
		foreach($field as $key => $value){
			$sqlValue = "$key = '$value'";
		}
        $results = $wpdb->get_row( "SELECT count(*) as count FROM $table WHERE $sqlValue", OBJECT );
        return $results->count;
    }
    public function getSingleSearchKey(){
        global $wpdb;
        $ebaySearched = $wpdb->prefix.'EbaySearched';
        $results = $wpdb->get_row( "SELECT searchKey,pageNumber FROM $ebaySearched WHERE id > 0 ORDER BY RAND() LIMIT 1", OBJECT );
        return $results;
    }
    public function ebayPullProductsApi(){
        global $EbayPage;
        global $wpdb;
        $searchValues = $EbayPage->getSingleSearchKey();
        if(count($searchValues)){
            $searchKey = $searchValues->searchKey;
            $pageNumber = $searchValues->pageNumber+1;
            update_option('searchkey', $searchValues->searchKey);
            $apicall = $EbayPage->constructApiUrl($pageNumber);
            $resp = simplexml_load_file($apicall);    
            if ($resp->ack == "Success") {
                $xml = json_decode(json_encode((array) $resp), 1);
                $EbayPage->updateDataToEbaySearchTable($xml);
                $EbayPage->addDataToEbayProductTable($xml);
            }
        }
    }
    public function updateAllProductRows(){
      global $wpdb;
      $ebayProducts = $wpdb->prefix.'EbayProducts';
      $wpdb->query("UPDATE $ebayProducts SET addedtocommerce = 0 WHERE itemid > 0");
    }
}

global $EbayPage;
$EbayPage = new EbayPage();

class EbayCronPage {
    public function __construct() {
        //echo '<pre>'; print_r( _get_cron_array() ); echo '</pre>';
        add_filter('cron_schedules', array($this,'ebay_add_cron_interval'));
        register_activation_hook(__FILE__, array($this,'ebay_cron_activation'));
        add_action('ebay_cron_run', array($this,'ebayCallMyFunction'));
        register_deactivation_hook(__FILE__, array($this,'ebay_cron_deactivation'));
    }
    public function ebay_cron_deactivation() {
        wp_clear_scheduled_hook('ebay_cron_run');
    }
    public function ebay_cron_activation(){
        if (! wp_next_scheduled ( 'ebay_cron_run' )) {
            wp_schedule_event(time(), 'FifteenMinutes', 'ebay_cron_run');
        }
    }
    public function ebay_add_cron_interval( $schedules ) {
        $schedules['FifteenMinutes'] = array(
            'interval' => 900,
            'display' => __( 'Every 15 Minutes' ),
        );
        return $schedules;
    }
    public function ebayCallMyFunction(){
        global $EbayPage;
        $EbayPage::ebayPullProductsApi();
    }
}

global $EbayCronPage;
$EbayCronPage = new EbayCronPage();

class EbayCommerce {
    public function __construct() {
        add_filter('cron_schedules', array($this,'ebay_add_comm_cron_interval'));
        register_activation_hook(__FILE__, array($this,'ebay_comm_cron_activation'));
        add_action('ebay_comm_cron_run', array($this,'processEbayProducts'));
        register_deactivation_hook(__FILE__, array($this,'ebay_comm_cron_deactivation'));
    }
    public function ebay_comm_cron_deactivation() {
        wp_clear_scheduled_hook('ebay_comm_cron_run');
    }
    public function ebay_comm_cron_activation(){
        if (! wp_next_scheduled ( 'ebay_comm_cron_run' )) {
            wp_schedule_event(time(), 'TwentyThreeMinutes', 'ebay_comm_cron_run');
        }
    }
    public function ebay_add_comm_cron_interval( $schedules ) {
        $schedules['TwentyThreeMinutes'] = array(
            'interval' => 1380,
            'display' => __( 'Every 23 Minutes' ),
        );
        return $schedules;
    }
	public function constructDescription($prodObj){
		$SpecificsA = $prodObj['ItemSpecifics']['NameValueList'];
		$desStr = "<table>";
		foreach($SpecificsA as $spec){
			$desStr .= "<tr>";	
			$desStr .= "<td>".$spec['Name']."</td>";	
			$desStr .= "<td>".$spec['Value']."</td>";
			$desStr .= "</tr>";	
		}
		$desStr .= "</table>";
		return $desStr;
	}
    public function addProductsToWooCommerce($prodObj1,$prodObj2){
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

        $user_id = get_current_user();
		$desStr = $this->constructDescription($prodObj2);
		$post_id = wp_insert_post( array(
            'post_author' => $user_id,
            'post_title' => $prodObj1['title'],
            'post_content' => $desStr . "</br>" . $prodObj2['Description'],
            'post_status' => 'draft',
            'post_type' => "product",
        ));
        wp_set_object_terms( $post_id, 'external', 'product_type' );
        update_post_meta( $post_id, '_product_url', $prodObj1['viewItemURL'] );
        update_post_meta( $post_id, '_visibility', 'visible' );
        update_post_meta( $post_id, '_stock_status', 'instock');
        update_post_meta( $post_id, 'total_sales', '0' );
        update_post_meta( $post_id, '_downloadable', 'no' );
        update_post_meta( $post_id, '_virtual', 'yes' );
        update_post_meta( $post_id, '_regular_price', $prodObj2['CurrentPrice']);
        update_post_meta( $post_id, '_sale_price', $prodObj2['CurrentPrice']);
        update_post_meta( $post_id, '_purchase_note', '' );
        update_post_meta( $post_id, '_featured', 'no' );
        update_post_meta( $post_id, '_weight', '' );
        update_post_meta( $post_id, '_length', '' );
        update_post_meta( $post_id, '_width', '' );
        update_post_meta( $post_id, '_height', '' );
        update_post_meta( $post_id, '_sku', $prodObj1['itemId'] );
        update_post_meta( $post_id, '_product_attributes', array() );
        update_post_meta( $post_id, '_sale_price_dates_from', '' );
        update_post_meta( $post_id, '_sale_price_dates_to', '' );
        update_post_meta( $post_id, '_price', $prodObj2['CurrentPrice'] );
        update_post_meta( $post_id, '_sold_individually', '' );
        update_post_meta( $post_id, '_manage_stock', 'no' );
        update_post_meta( $post_id, '_backorders', 'no' );
        update_post_meta( $post_id, '_stock', '' );
		
        $this->updateProductRow($prodObj1['itemId'],$post_id);
		
		if(strlen($prodObj1['pictureURLLarge'])){
			$media = media_sideload_image($prodObj1['pictureURLLarge'], $post_id);
		}else if(strlen($prodObj1['galleryURL'])){
			$media = media_sideload_image($prodObj1['galleryURL'], $post_id);
		}
		$this->addImageToMediaLibrabry($media,$post_id);
		
		$term_ids = $this->createAndAttachCategories($prodObj2);
		if(count($term_ids)) wp_set_object_terms($post_id, $term_ids, 'product_cat');

    }
    public function updateProductRow($itemId,$post_id){
        global $wpdb;
        $ebayProducts = $wpdb->prefix.'EbayProducts';
        $ebayProductsWhereA['itemId'] = $itemId;
        $ebayProductsA['addedtocommerce'] = 1;
        $ebayProductsA['post_id'] = $post_id;
        $wpdb->update($ebayProducts, $ebayProductsA, $ebayProductsWhereA);
    }
    public function getProductsForCommerce(){
		global $EbayPage;
		$EbayPage = new EbayPage();
		global $wpdb;
        $excludeStr = "";
        $categoryIdsA = $EbayPage->excludeCategories();
        if(count($categoryIdsA)){
            $excludeStr = "AND categoryName NOT IN ('";
            $excludeStr .= implode("','",$categoryIdsA);
            $excludeStr .= "')";
        }        
        $ebayProducts = $wpdb->prefix.'EbayProducts';
        $query = "SELECT * FROM $ebayProducts WHERE id > 0 $excludeStr AND addedtocommerce = 0 ORDER BY id ASC LIMIT 10";
        $results = $wpdb->get_results( $query, OBJECT );
		return $results;
    }
	public function getProductsFromDesTable(){
		global $wpdb;
		$ebayProducts = $wpdb->prefix.'EbayProducts';
		$ebayProductsDesc = $wpdb->prefix.'EbayProductsDesc';
		$query = "SELECT ep.itemObject,epd.itemObjectDes FROM $ebayProductsDesc as epd LEFT JOIN $ebayProducts as ep on epd.itemId = ep.itemId WHERE ep.addedtocommerce = 0 ORDER BY epd.id ASC LIMIT 10";
        $results = $wpdb->get_results( $query, OBJECT );
		return $results;
	}
	public function getProductsToUpdate(){
		global $wpdb;
		$ebayProducts = $wpdb->prefix.'EbayProducts';
		$ebayProductsDesc = $wpdb->prefix.'EbayProductsDesc';
		$query = "SELECT ep.post_id,epd.itemObjectDes FROM $ebayProductsDesc as epd LEFT JOIN $ebayProducts as ep on epd.itemId = ep.itemId WHERE epd.updated = 1 LIMIT 10";
        $results = $wpdb->get_results( $query, OBJECT );
		return $results;
	}
	public function processNewProducts(){
		$pResults = $this->getProductsFromDesTable();
        foreach($pResults as $prodval){
            $procProd1 = unserialize($prodval->itemObject);
            $procProd2 = unserialize($prodval->itemObjectDes);
            $this->addProductsToWooCommerce($procProd1,$procProd2);
        }
	}
	public function updateProductsToWooCommerce($post_id,$procProdObj){
		update_post_meta( $post_id, '_price', $procProdObj['CurrentPrice']);
		update_post_meta( $post_id, '_regular_price', $procProdObj['CurrentPrice']);
		update_post_meta( $post_id, '_sale_price', $procProdObj['CurrentPrice']);
		$this->updateProdDesTable($procProdObj['ItemID']);
	}
	public function updateProdDesTable($itemId){
		global $wpdb;
		$table = $wpdb->prefix.'EbayProductsDesc';
		$date = new DateTime();
        $ebayProductsDescWhereA['itemId'] = $itemId;
		$ebayProductsDescA['updated'] = 0;
        $wpdb->update($table, $ebayProductsDescA, $ebayProductsDescWhereA);
	}
	public function processOldProducts(){
		$pResults = $this->getProductsToUpdate();
        foreach($pResults as $prodval){
            $post_id = $prodval->post_id;
            $procProdObj = unserialize($prodval->itemObjectDes);
            $this->updateProductsToWooCommerce($post_id,$procProdObj);
        }
	}
    public function processEbayProducts(){
		$this->processNewProducts();
		$this->processOldProducts();
    }
	public function addImageToMediaLibrabry($media,$post_id){
		// therefore we must find it so we can set it as featured ID
        if(!empty($media) && !is_wp_error($media)){
            $args = array(
                'post_type' => 'attachment',
                'posts_per_page' => -1,
                'post_status' => 'any',
                'post_parent' => $post_id
            );

            // reference new image to set as featured
            $attachments = get_posts($args);

            if(isset($attachments) && is_array($attachments)){
                foreach($attachments as $attachment){
                    // grab source of full size images (so no 300x150 nonsense in path)
                    $image = wp_get_attachment_image_src($attachment->ID, 'full');
                    // determine if in the $media image we created, the string of the URL exists
                    if(strpos($media, $image[0]) !== false){
                        // if so, we found our image. set it as thumbnail
                        set_post_thumbnail($post_id, $attachment->ID);
                        // only want one image
                        break;
                    }
                }
            }
        }
	}
	public function createAndAttachCategories($procProd2){
		$SpecificsA = $procProd2['ItemSpecifics']['NameValueList'];
		$parent_cat = "";
		$child_cat = "";
		foreach($SpecificsA as $spec){
			if($spec['Name'] == "Platform"){
				$parent_cat = $spec['Value'];
			}
			if($spec['Name'] == "Genre"){
				$child_cat = $spec['Value'];
			}
		}
		
		$pid = 0;
		if(!empty($parent_cat)){
			$pid = $this->createMyCategory($parent_cat,0);
		}
		
		$cid = 0;
		if(!empty($child_cat)){
			$cid = $this->createMyCategory($child_cat,$pid);
		}
		$term_idA = array();
		if($pid > 0) $term_idA[] = $pid;
		if($cid > 0) $term_idA[] = $cid;
		return $term_idA;
	}

	public function createMyCategory($term,$parentId){
		$cat = get_term_by('name', $term, 'product_cat');
		if(empty($cat)){
			$cat = wp_insert_term(
						$term, 
						'product_cat',
						array(
							'description'=> "",
							'slug' => str_replace(" ","_",$term),
							'parent' => $parentId,
						));
			$catA = (array) $cat;
			$term_id = $catA['term_id'];
		}else{
			$term_id = $cat->term_id;
		}
		return $term_id;
	}
}

global $EbayCommerce;
$EbayCommerce = new EbayCommerce();

class EbayUpDtProdDes {
    public function __construct() {
        add_filter('cron_schedules', array($this,'ebay_add_prod_cron_interval'));
        register_activation_hook(__FILE__, array($this,'ebay_prod_cron_activation'));
        add_action('ebay_prod_cron_run', array($this,'updateEbayProducts'));
        register_deactivation_hook(__FILE__, array($this,'ebay_prod_cron_deactivation'));
    }
    public function ebay_prod_cron_deactivation() {
        wp_clear_scheduled_hook('ebay_prod_cron_run');
    }
    public function ebay_prod_cron_activation(){
        if (! wp_next_scheduled ( 'ebay_prod_cron_run' )) {
            wp_schedule_event(time(), 'NineteenMinutes', 'ebay_prod_cron_run');
        }
    }
    public function ebay_add_prod_cron_interval( $schedules ) {
        $schedules['NineteenMinutes'] = array(
            'interval' => 1140,
            'display' => __( 'Every 19 Minutes' ),
        );
        return $schedules;
    }
	public function updateEbayProducts(){
		global $EbayCommerce;
		$EbayCommerce = new EbayCommerce();
		$pResults = $EbayCommerce->getProductsForCommerce();
		$items = array();
		foreach($pResults as $prodval){
            $items[] = $prodval->itemId; 
        }
		$itemSrt = implode(",",$items);
		$this->getSingleItemResults($itemSrt);
	}
	// Use itemId from selected most watched item as input for a GetMultipleItems call
	public function getSingleItemResults ($selectedItemIDs) {
		global $EbayUpDtProdDes;
		$EbayUpDtProdDes = new EbayUpDtProdDes();
		$s_endpoint = "http://open.api.ebay.com/shopping?";  // Shopping URL to call
		$appid = get_option('ebayapikey');  // You will need to supply your own AppID
		$responseEncoding = 'XML';  // Type of response we want back

		// Construct the GetMultipleItems call 
		$apicallb  = "$s_endpoint";
		$apicallb .= "callname=GetMultipleItems";
		$apicallb .= "&version=967";
		$apicallb .= "&appid=$appid";
		$apicallb .= "&itemid=$selectedItemIDs";
		$apicallb .= "&responseencoding=$responseEncoding";
		$apicallb .= "&includeselector=Details,ItemSpecifics,TextDescription";

		// Load the call and capture the document returned by eBay API
		$resp = simplexml_load_file($apicallb);
		if ($resp->Ack == "Success") {
            $xml = json_decode(json_encode((array) $resp), 1);
            $EbayUpDtProdDes->addDataToEbayProductDesTable($xml);
        }
	}
	
	public function addDataToEbayProductDesTable($data){
        global $wpdb;
		global $EbayPage;
		if(!count($data['Item'][0]) && count($data['Item'])){
			$dataM['Item'][0] = $data['Item'];
		}else{
			$dataM = $data;
		}
		$EbayPage = new EbayPage();
        $ebayProductsDesc = $wpdb->prefix.'EbayProductsDesc';
        $items = $dataM['Item'];
		$date = new DateTime();
        foreach($items as $item){
            $itemObjectDes = $item;
            $ebayProductsDescA['id'] = "";
            $ebayProductsDescA['itemId'] = $item['ItemID'];
            $ebayProductsDescA['timeStamp'] = gmdate('Y-m-d H:i:s');
            $ebayProductsDescA['updated'] = 0;
            $ebayProductsDescA['itemObjectDes'] = serialize($itemObjectDes);
			$itemFound = $EbayPage->getProductCountOfItemId($ebayProductsDesc,array("itemId" => $item['ItemID']));
			if(!$itemFound){
				$EbayPage->sendToTable($ebayProductsDesc,$ebayProductsDescA);
			}else{
				$this->updateToTable($ebayProductsDesc,$itemObjectDes,$item['ItemID']);
			}
        }
    }
    public function updateToTable($table,$itemObjectDes,$itemId){
        global $wpdb;
		$date = new DateTime();
        $ebayProductsDescWhereA['itemId'] = $itemId;
		$ebayProductsDescA['timeStamp'] = gmdate('Y-m-d H:i:s');
		$ebayProductsDescA['updated'] = 1;
		$ebayProductsDescA['itemObjectDes'] = serialize($itemObjectDes);
        $wpdb->update($table, $ebayProductsDescA, $ebayProductsDescWhereA);
    }

}

global $EbayUpDtProdDes;
$EbayUpDtProdDes = new EbayUpDtProdDes();

class EbayUpdateProductsForEach {
    public function __construct() {
        add_filter('cron_schedules', array($this,'ebay_add_each_item_cron_interval'));
        register_activation_hook(__FILE__, array($this,'ebay_each_item_cron_activation'));
        add_action('ebay_each_item_cron_run', array($this,'updateEbayEachItem'));
        register_deactivation_hook(__FILE__, array($this,'ebay_each_item_cron_deactivation'));
    }
    public function ebay_each_item_cron_deactivation() {
        wp_clear_scheduled_hook('ebay_each_item_cron_run');
    }
    public function ebay_each_item_cron_activation(){
        if (! wp_next_scheduled ( 'ebay_each_item_cron_run' )) {
            wp_schedule_event(time(), 'TwentySevenMinutes', 'ebay_each_item_cron_run');
        }
    }
    public function ebay_add_each_item_cron_interval( $schedules ) {
        $schedules['TwentySevenMinutes'] = array(
            'interval' => 1620,
            'display' => __( 'Every 27 Minutes' ),
        );
        return $schedules;
    }
	public function getProductsToProcEach(){
		global $wpdb;
		$ebayProductsDesc = $wpdb->prefix.'EbayProductsDesc';
		$query = "SELECT epd.itemId FROM $ebayProductsDesc as epd WHERE epd.timeStamp < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) LIMIT 10";
		$results = $wpdb->get_results( $query, OBJECT );
		return $results;
	}
	public function updateEbayEachItem(){
		global $EbayUpDtProdDes;
		$pResults = $this->getProductsToProcEach();
		$items = array();
		foreach($pResults as $prodval){
			$items[] = $prodval->itemId; 
        }
		$itemSrt = implode(",",$items);		
		$EbayUpDtProdDes->getSingleItemResults($itemSrt);
	}
}

global $EbayUpdateProductsForEach;
$EbayUpdateProductsForEach = new EbayUpdateProductsForEach();