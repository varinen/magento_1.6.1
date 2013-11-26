	KialaTrackingControlsCreator.prototype = Object.extend(new KialaTrackingControlsCreator(), {

		getTrackingCodeTable : function() {
			return $('shipment_tracking_info').select("tfoot").first();
		}, 
		
		updateFields : function(parcel) {
	        $('tracking_number').value = parcel.barcode;
			$$('div#shipment_tracking_info select[name="carrier"]').first().value= 'kiala';
			$('tracking_title').value = 'Kiala';
			detectTrackingInformationReload();
			$('shipment_tracking_info').select("tfoot td.last button").first().click();
		},
			
	});

	var KialaTrackingControls = new KialaTrackingControlsCreator();
	
	function detectTrackingInformationReload(){
		if($('Kiala_tracking_controls') == null){
			KialaTrackingControls.add();
		} else{
			setTimeout(detectTrackingInformationReload, 10);
		}
	}
