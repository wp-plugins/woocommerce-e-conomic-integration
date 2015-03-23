/*jQuery(document).ready(function() {
    jQuery("li.wp-first-item").hide();
});*/

function sync_orders() {
    var data = {
        action: 'sync_orders'
    };
    alert('Synkroniseringen kan ta lång tid beroende på hur många ordrar som ska exporteras. \nEtt meddelande visas på denna sida när synkroniseringen är klar. Lämna ej denna sida, då avbryts exporten!');
    jQuery(".order_load").show();
    jQuery.ajax({
		url: ajaxurl,
		data: data,
		dataType: "json",
	}).done(function(data) {
		alert(data.status);
		jQuery("#result").html(data.msg);
		jQuery(".order_load").hide();
	});
}

function sync_contacts() {
    var data = {
        action: 'sync_contacts'
    };
    alert('Synkroniseringen kan ta lång tid beroende på hur många kunder som ska importeras. \nEtt meddelande visas på denna sida när synkroniseringen är klar. Lämna ej denna sida, då avbryts importen!');
    jQuery(".customer_load").show();
    jQuery.ajax({
		url: ajaxurl,
		data: data,
		dataType: "json",
	}).done(function(data) {
		alert(data.status);
		jQuery("#result").html(data.msg);
		jQuery(".customer_load").hide();
	});
}

function sync_products() {
    var data = {
        action: 'sync_products'
    };
    alert('Synkroniseringen kan ta lång tid beroende på hur många produkter som ska exporteras. \nEtt meddelande visas på denna sida när synkroniseringen är klar. Lämna ej denna sida, då avbryts exporten!');
	jQuery(".product_load").show();
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		dataType: "json",
	}).always(function(data) {
		alert(data.status);
		jQuery("#result").html(data.msg);
		jQuery(".product_load").hide();
	});
}


function sync_shippings() {
    var data = {
        action: 'sync_shippings'
    };
    alert('Ett meddelande visas på denna sida när synkroniseringen är klar. Lämna ej denna sida, då avbryts exporten!');
	jQuery(".shipping_load").show();
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		dataType: "json",
	}).done(function(data) {
		alert(data.status);
		jQuery("#result").html(data.msg);
		jQuery(".shipping_load").hide();
	});
}

function send_support_mail(form) {
    var data = jQuery('form#'+form).serialize();
    jQuery.post(ajaxurl, data, function(response) {
        if(response == "success0"){
			alert("Message sent successfully!");
		}else{
			alert("Problem sending message, please try again later.");
		}
    });
}


function test_connection(){
	jQuery(".test_warning").hide(function(){
		jQuery(".test_load").show();
	});
	var data = {
        action: 'test_connection'
    };
    jQuery.post(ajaxurl, data, function(response) {
		jQuery(".test_load").hide(function(){
			jQuery(".test_warning").show();
			alert(response);
		});
    });
}