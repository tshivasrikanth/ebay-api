<div class="wrap">
<h1 class="productstit">Ebay Products</h1>
<br class="clear">
<ul id="ebayproducts-list">
    <?php if(count($productResults)){?>
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
    <?php }else{ ?>
    <li>No Products Found!</li>    
    <?php } ?>

</ul>
</div>
