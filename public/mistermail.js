jQuery(document).ready(function(){
    var selectors = jQuery('input[name=\'add\'] , button[name=\'add\'], #add-to-cart, #AddToCartText ,#AddToCart, .product-form__cart-submit');
    selectors.addClass('mistermail-cart-btn');

    var shop_name=Shopify.shop;
    var customer_id=$('#mistermail-customer-id').val();
    var product_id=$('#mistermail-product-id').val();
    var pathname = window.location.pathname;
    var last_part=pathname.substr(pathname.lastIndexOf('/') + 1);
    var fullUrl=window.location.href;

    if(product_id != "" && customer_id != "") //customer login
    {
        jQuery.getJSON('/cart.js', function(cart) {
            var items=cart.items;
            var item_count=cart.item_count;
            var cart_value=cart.total_price;
            jQuery.ajax({
                method:'Post',
                data:{fullUrl:fullUrl,customer_id:customer_id,shop:shop_name,items:items,item_count:item_count,cart_value:cart_value,pageView:1,product_id:product_id},
                url:'https://app.themistermail.com/MisterMailScript',
                success:function(resp) {    
                }
            });
        }); 
    }
});  

jQuery(document).on('click','.mistermail-cart-btn',function(){
    var shop_name=Shopify.shop;
    var customer_id=$('#mistermail-customer-id').val();
    var fullUrl=window.location.href;
    if(customer_id != "") //customer login
    {
        jQuery.getJSON('/cart.js', function(cart) {
            var items=cart.items;
            var item_count=cart.item_count;
            var cart_value=cart.total_price;
            jQuery.ajax({
                method:'Post',
                data:{fullUrl:fullUrl,cart:1,customer_id:customer_id,shop:shop_name,items:items,item_count:item_count,cart_value:cart_value},
                url:'https://app.themistermail.com/MisterMailScript',
                success:function(resp) {    
                }
            });
        }); 
    }
});  


  