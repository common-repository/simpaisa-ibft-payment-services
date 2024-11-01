
jQuery(function () {

	        //CNIC VALIDATION
			jQuery(document.body).on('keyup', '#sp_ibft_cnic', function (e) {
			
				var cnic =  /^[0-9+]{5}-[0-9+]{7}-[0-9]{1}$/;
				var currentValue = jQuery(this).val();
				//cnic.test(currentValue) && 
				if (currentValue.length == 13) {
					jQuery("#sp_ibft_cnic").css('border', '1px solid green');	
					jQuery(".simpaisa-ibft-cnic-err").html("");	 
				} else {
					jQuery("#sp_ibft_cnic").css('border', '1px solid red');
					jQuery(".simpaisa-ibft-cnic-err").html("Invalid CNIC");
				}	
			});

			//BANK VALIDATION
			jQuery(document.body).on('change', '#sp_ibft_bank', function (e) {
				
				var bank = jQuery(this).val();	

				if (bank > 0) {
					jQuery("#sp_ibft_bank").css('border', '1px solid green');	
					jQuery(".simpaisa-ibft-bank-err").html("");	 
					return true;
				} else {
					jQuery("#sp_ibft_bank").css('border', '1px solid red');
					jQuery(".simpaisa-ibft-bank-err").html("Bank should be selected");
					return false;
				}	
			});

			//CNIC VALIDATION
			jQuery(document.body).on('keyup', '#sp_ibft_account', function (e) {
				var currentValue = jQuery(this).val();

				if (currentValue.length <= 15) {
					jQuery("#sp_ibft_account").css('border', '1px solid green');	
					jQuery(".simpaisa-ibft-account-err").html("");	 
					return true;
				} else {
					jQuery("#sp_ibft_account").css('border', '1px solid red');
					jQuery(".simpaisa-ibft-account-err").html("Invalid Account No");
					return false;
				}	
			});

			//ON PAYMENT METHOD CHANGE
			jQuery(document.body).on('change', '[name="payment_method"]', function (e) {
				if(jQuery(this).val() == "simpaisa_woo_ibft"){
					jQuery(".simpaisa-ibft-detail").show();
				 	jQuery(".simpaisa-ibft-otp").hide(); 
				 	jQuery(".simpaisa-ibft-detail").find('input').val("");
				 	jQuery(".simpaisa-ibft-otp").find('input').val("");
				 	jQuery("#sp_ibft_otp").val("");
				 	jQuery('#sp_ibft_type').val(1);
				 	var url = window.location.href;
    				url = url.split('?')[0];
				 	window.history.replaceState({}, document.title, url);
				}
			});
		
		 
	    jQuery(document).ajaxStop(function () {
	    		if (window.location.href.indexOf("verify_otp") > -1) {
				 
			 	 jQuery('html, body').animate({
					  scrollTop: jQuery(".payment_method_simpaisa_woo_ibft").offset().top
				 }, 2000);
				 jQuery('#sp_ibft_type').val(2);
				 jQuery(".simpaisa-ibft-detail").find('input').val("");
				 jQuery(".simpaisa-ibft-otp").find('input').val("");
				 jQuery(".simpaisa-ibft-detail").hide();
				 jQuery(".simpaisa-ibft-otp").show(); 
				 jQuery('.sp_ibft_field').focus(); 	
	    	}

	    });

		var sendRequest = function () {
			var payment_method = jQuery('input[name="payment_method"]:checked').val()
			var bank = jQuery("#sp_ibft_bank").val();	
			if (payment_method == 'simpaisa_woo_ibft') {
				
			} else {
				var checkout_form = jQuery('form.woocommerce-checkout');
				checkout_form.submit();
			}

		};
			

		/// ON PLACE ORDER BTN VALIDATION
		var checkout_forms = jQuery('form.checkout');
		checkout_forms.on('checkout_place_order',sendRequest);
});