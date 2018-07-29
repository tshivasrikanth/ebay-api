<div class="wrap">
<!-- Pagination -->
<div class="tablenav bottom">
    <h1 class="productstit">Ebay Products</h1>
    <div class="tablenav-pages">
        <span class="displaying-num">11 items</span>
        <span class="pagination-links">
            <span class="tablenav-pages-navspan" aria-hidden="true">«</span>
            <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>
            <span class="screen-reader-text">Current Page</span>
            <span id="table-paging" class="paging-input">
                <span class="tablenav-paging-text">1 of
                    <span class="total-pages">3</span>
                </span>
            </span>
            <a class="next-page" href="http://rbb.local/wp-admin/edit.php?mode=list&amp;paged=2">
                <span class="screen-reader-text">Next page</span>
                <span aria-hidden="true">›</span>
            </a>
            <a class="last-page" href="http://rbb.local/wp-admin/edit.php?mode=list&amp;paged=3">
                <span class="screen-reader-text">Last page</span>
                <span aria-hidden="true">»</span>
            </a>
        </span>
    </div>
    <br class="clear">
</div>
<ul id="ebayproducts-list">
    <?php foreach($productResults as $productVal){ ?>
    <?php 
        $unserVal = unserialize($productVal->itemObject);
        $glryUrl = $unserVal['galleryURL'];
        $title = $unserVal['title'];
    ?>
    <li>
        <div class="ebayproductimg" style="background-image:url('<?php echo $glryUrl; ?>')"></div>
        <div class="ebayData">
            <div class="ebayproducttit">
                <h3><?php echo $title; ?></h3>
            </div>
            <div class="ebayproductcat">
                <h4><?php echo $productVal->categoryName; ?></h4>
            </div>
        </div>
        <br class="clear">
    </li>
    <?php } ?>
</ul>

<!-- Pagination -->
<div class="tablenav bottom">
    <div class="tablenav-pages">
        <span class="displaying-num">11 items</span>
        <span class="pagination-links">
            <span class="tablenav-pages-navspan" aria-hidden="true">«</span>
            <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>
            <span class="screen-reader-text">Current Page</span>
            <span id="table-paging" class="paging-input">
                <span class="tablenav-paging-text">1 of
                    <span class="total-pages">3</span>
                </span>
            </span>
            <a class="next-page" href="http://rbb.local/wp-admin/edit.php?mode=list&amp;paged=2">
                <span class="screen-reader-text">Next page</span>
                <span aria-hidden="true">›</span>
            </a>
            <a class="last-page" href="http://rbb.local/wp-admin/edit.php?mode=list&amp;paged=3">
                <span class="screen-reader-text">Last page</span>
                <span aria-hidden="true">»</span>
            </a>
        </span>
    </div>
    <br class="clear">
</div>
</div>
