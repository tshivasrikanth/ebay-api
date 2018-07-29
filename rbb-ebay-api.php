<?php
/*
  Plugin Name: Ebay API (RBB)
  Plugin URI: http://www.rentbuybash.com
  Description: This is ebay API plugin, custom options with amdin setting page.
  Author: Jordan R
  Version: 1.0
  Author URI: http://www.rentbuybash.com
 */

class MySettingsPage {
   /**
     * Start up
     */
    public function __construct() {
        wp_register_style( 'ebayapirbbstyle', plugins_url('css/style.css',__FILE__ ));
        register_activation_hook(__FILE__, array($this, 'create_ebay_database_table'));
        add_action('admin_menu', array($this,'awesome_page_create'));

        add_filter('cron_schedules', array($this, 'ebaySchedules'));
        add_action('ebayProductsRunMe', array($this, 'ebayPullProductsApi'));
        wp_next_scheduled('ebayProductsRunMe');
        if (!wp_next_scheduled('ebayProductsRunMe')) {
          wp_schedule_event(time(), 'ebay_30_sec', 'ebayProductsRunMe');
        }
    }
    public function ebaySchedules($schedules) {
        $schedules['ebay_30_sec'] = array(
            'interval' => 30,
            'display' => esc_html__('Every 30 Sec'),
        );
        return $schedules;
    }
    public function create_ebay_database_table() {
        global $wpdb;
        $ebayProducts = $wpdb->prefix.'EbayProducts';
        $ebaySearched = $wpdb->prefix.'EbaySearched';
        $this->create_ebay_products_db_table($ebayProducts);
        $this->create_ebay_searched_db_table($ebaySearched);
    }
    public function create_ebay_products_db_table($table_name) {
        global $wpdb;
        if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
            $sql = 'CREATE TABLE ' . $table_name . ' ( `id` INTEGER(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY, `itemId` VARCHAR(255), `categoryId` VARCHAR(225), `categoryName` VARCHAR(255),`itemObject` BLOB)';
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
        $s_capability = 'manage_options';
        $s_menu_slug = 'ebay_products_rbb';
        $s_function = array($this,'ebay_products_page_display');
        add_submenu_page( $parent_slug, $s_page_title, $s_menu_title, $capability, $s_menu_slug, $s_function);
    }
    public function ebay_products_page_display() {
        wp_enqueue_style('ebayapirbbstyle');
        $productResults = $this->getProductResults(0,10);
        include 'products-file.php';
    }
    public function ebay_api_page_display() {
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
        if (isset($_POST['apikey'])) {
            $value = sanitize_text_field($_POST['apikey']);
            update_option('ebayapikey', $value);
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
        $value = get_option('ebayapikey');
        $searchKey = get_option('searchkey');
        $categoryKey = get_option('categorykey');
        if (isset($_POST['searchkey'])) {
            if($this->checkForDbValue($searchKey)){
                $results =  "Search Key is Already in Database";
            }else{
                $results = $this->call_ebay_api();
            }
        }
        $searchKeyResults = $this->getSearchKeyResults();
        include 'form-file.php';
    }
    public function constructApiUrl($pageNo){
        // API request variables
        $endpoint = 'http://svcs.ebay.com/services/search/FindingService/v1';  // URL to call
        $version = '1.0.0';  // API version supported by your application
        $appid = get_option('ebayapikey');  // Replace with your own AppID
        $globalid = 'EBAY-US';  // Global ID of the eBay site you want to search (e.g., EBAY-DE)
        $query = get_option('searchkey');  // You may want to supply your own query
        $safequery = urlencode($query);  // Make the query URL-friendly
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
            $ebayProductsA['itemObject'] = serialize($itemObject);
            if(count($catA) == 0){
                $this->sendToTable($ebayProducts,$ebayProductsA);
            }else{
                if(!in_array($catName,$catA)){
                    $this->sendToTable($ebayProducts,$ebayProductsA);
                }
            }
        }
    }
    public function excludeCategories(){
        $categoryIdsA = $this->listCategoryIds();
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
    public function getSingleSearchKey(){
        global $wpdb;
        $ebaySearched = $wpdb->prefix.'EbaySearched';
        $results = $wpdb->get_row( "SELECT searchKey,pageNumber FROM $ebaySearched WHERE id > 0 ORDER BY RAND() LIMIT 1", OBJECT );
        return $results;
    }
    public function ebayPullProductsApi(){
        $searchValues = $this->getSingleSearchKey();
        if(count($searchValues)){
            $searchKey = $searchValues->searchKey;
            $pageNumber = $searchValues->pageNumber+1;
            update_option('searchkey', $searchValues->searchKey);
            $apicall = $this->constructApiUrl($pageNumber);
            $resp = simplexml_load_file($apicall);    
            if ($resp->ack == "Success") {
                $xml = json_decode(json_encode((array) $resp), 1);
                $this->updateDataToEbaySearchTable($xml);
                $this->addDataToEbayProductTable($xml);
            }
        }
    }
}
if (is_admin()){
    $MySettingsPage = new MySettingsPage();
}
