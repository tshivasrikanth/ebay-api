<h1>Ebay API</h1>
<form method="POST">
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="apikey">API KEY</label>
            </th>
            <td>
                <input type="text" class="required regular-text" name="apikey" id="apikey" value="<?php echo $value; ?>">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="apikey">SEARCH KEY</label>
            </th>
            <td>
                <input type="text" class="regular-text" name="searchkey" id="searchkey" value="<?php echo $searchKey; ?>">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="trackingid">Tracking Id</label>
            </th>
            <td>
                <input type="text" class="regular-text" name="trackingid" id="trackingid" value="<?php echo $trackingId; ?>">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="customerid">Customer Id</label>
            </th>
            <td>
                <input type="text" class="regular-text" name="customerid" id="customerid" value="<?php echo $customerId; ?>">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="networkid">Network Id</label>
            </th>
            <td>
                <input type="text" class="regular-text" name="networkid" id="networkid" value="<?php echo $networkId; ?>">
            </td>
        </tr>
        <?php if(count($categoriesResults)){?>
        <tr>
            <th scope="row">
                <label for="apikey">EXCLUDE CATEGORIES</label>
            </th>
            <td>
                <?php foreach($categoriesResults as $key => $catVal){ ?>
                    <?php 
                        $checked = "";
                        $catGetVal = get_option($key);
                        if($catGetVal) $checked = 'checked="checked"';
                    ?>
                    <input <?php echo $checked ?> type="checkbox" class="regular-text" name="<?php echo $key; ?>" > <?php echo $catVal; echo " (". $this->getProductCountOfCat($catVal) . " Products in DB)"; ?><br/>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <th scope="row">
                <?php wp_nonce_field( 'EbayNonce' ); ?>
                <input type="submit" value="Submit" class="button button-primary button-large">
            </th>
            <td></td>
        </tr>   
</table>
</form>
<div>
<?php if(count($results)){?>
<div class="notice notice-success is-dismissible">
    <p><?php echo $results; ?></p>
</div>
<?php } ?>
<h4>Available Search Keys</h4>
<table class="widefat fixed" cellspacing="0">
    <thead>
        <tr>
            <th class="manage-column" scope="col">Search Key</th>
            <th class="manage-column" scope="col">Page Number(pulled)</th>
            <th class="manage-column" scope="col">Total Pages</th>
            <th class="manage-column" scope="col">Timestamp</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($searchKeyResults as $searchVal){ ?>
        <tr class="alternate">
            <td><?php echo $searchVal->searchKey; ?></td>
            <td><?php echo $searchVal->pageNumber; ?></td>
            <td><?php echo $searchVal->totalPages; ?></td>
            <td><?php echo $searchVal->timestamp; ?>  </td>
        </tr>
    <?php } ?>    
    </tbody>    
</table>
</div>