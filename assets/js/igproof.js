jQuery(function () {
    jQuery(window).on('load', function () {

        if (ig_getCookie('wooproof_stopped') === "1") return;

        jQuery.ajax({
            url: postigproof.ajax_url,
            type: 'post',
            data: {
                action: 'get_igproof'
            },
            success: function (response) {
                jQuery('body').append(response);

                jQuery('.ig-proof-close').on('click', function () {
                    ig_setCookie('wooproof_stopped', "1", postigproof.cookie_expiry);
                    jQuery('ul.ig-proof-ul').hide();
                });

            }
        });

        transitionProof(1);
    });


});


function transitionProof(count) {


    if (ig_getCookie('wooproof_stopped') == "1") return;

    var childElem = jQuery('ul.ig-proof-ul').find('li:nth-child(' + count + ')');

    if (childElem != null) {
        var repeat_orders = postigproof.repeat_orders;
        jQuery(childElem).addClass('active');

        //Uncomment below line for debugging styles
        //return

        //console.log(count);
        setTimeout(function () {
            jQuery(childElem).removeClass('active');
        }, postigproof.slide_show_time); //how long each item will show
        var interval_between_items = +postigproof.interval_between_items + +postigproof.slide_show_time;
        if(repeat_orders === null) {
            var li_count = jQuery('ul.ig-proof-ul > li').length + 1;
        } else {
            var li_count = jQuery('ul.ig-proof-ul > li').length;
        }
        if(repeat_orders === null && li_count !== 0 && count >= li_count) {
            jQuery('ul.ig-proof-ul').hide();
        }
        if (count < li_count) {
            count++;
        } else {
            count = 1;
        }

        setTimeout(function () {
            transitionProof(count);
        }, interval_between_items); //time between each item = (this number - "long each item will show") i.e 13000 - 8000 = 5000

    } else {
        transitionProof(1);
        return;
    }
}


function ig_setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function ig_getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}


// Count Clicks of Social Proof for WooCommerce widget on the pages
function wooproof_product_counter(product) {

    jQuery.ajax({
        url: postigproof.ajax_url,
        type: 'post',
        data: {
            action: 'wooProof_clicks_counter',
            product: product
        },
        success: function (result) {
            //
        }
    });

}