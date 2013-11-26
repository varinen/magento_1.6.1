	var KialaTrackingControlsCreator = Class.create();
	
	KialaTrackingControlsCreator.prototype = {
		initialize: function(){
			
		},
		
		log : function (message, level){
			if (this.logging) {
				if(level == ""){
					level = "log";
				}
				eval("console." + level + "(message)");
			}
		},
		
		createPrintLabelbutton : function(){
			var printLabelButton = new Element('button',{'id' : 'print_labels',  'type' : 'button'});
			var labelButton = new Element('span');
			labelButton.update("Print all labels");
			printLabelButton.appendChild(labelButton);
			printLabelButton.addClassName('scalable Kiala');
			printLabelButton.observe('click', function() {
				window.location = requestPrintAllLabelsUrl + orderReference;
			});
			return printLabelButton;
		},
		
		addPrintLabelButton : function(parentElement){
			parentElement.appendChild(this.createPrintLabelbutton());
		},
		
		hideAddTrackingCodeRow : function() {
			$$("div#shipment_tracking_info tfoot tr").first().hide();
		},
		
		getTrackingCodeTable : function() {
			return $('tracking_numbers_table').select("tfoot").first();
		}, 
		
		getCreateNewLabelButton : function(){
			var createNewLabelButton = new Element('button',{'id' : 'create_label',  'type' : 'button'});
			var labelButton = new Element('span');
			labelButton.update("Create new Kiala label");
			createNewLabelButton.appendChild(labelButton);
			createNewLabelButton.addClassName('scalable kiala');
			createNewLabelButton.observe('click', function() {
				KialaTrackingControls.requestNewlabel();
			});
			return createNewLabelButton;
		},
		
		requestNewlabel : function (){
			KialaTrackingControls.log("Starting request for a new label.","debug");
			new Ajax.Request(requestNewTrackingCodeUrl,
			  {
			    method:'post',
			    parameters: {order: orderReference},
			    onSuccess: function(transport){
			    	KialaTrackingControls.log("New label created successfull.","debug");
			    	KialaTrackingControls.insertNewTrackingCodeData(transport);
			    },
			    onFailure: function(){ this.log("Could not retrieve new TrackingCode","error"); }
			  });
		},
		
		updateFields : function(parcel) {
	        trackingControl.add();
	        var trackingNumberIndex = $$('tbody#track_row_container tr').length - 1;
	        $('trackingC' + trackingNumberIndex).value= 'Kiala';
			$('trackingT' + trackingNumberIndex).value = 'Kiala';
			$('trackingN' + trackingNumberIndex).value = parcel.barcode;
		},
		
		insertNewTrackingCodeData : function (transport){
	    	if(transport.responseText.isJSON()){
	    		var parcel = transport.responseText.evalJSON()
		    	window.location = requestPrintLabelUrl + '?orderReference=' + orderReference + '&barcode=' + parcel.barcode;
		    	this.updateFields(parcel);
	    	}
		},

		getCreateNewLabelButtonCell : function(){
			var cell = new Element('td',{'colspan' : '4', 'style' : 'padding: 8px; background-color: #FFFFFF;'});
			cell.addClassName('a-center last');
			cell.appendChild(this.getCreateNewLabelButton());
			return cell;
		},
		
		add : function() {
			var tableFooter = this.getTrackingCodeTable();
			var row = new Element('tr', {'id' : 'Kiala_tracking_controls'});
			row.appendChild(this.getCreateNewLabelButtonCell());
			tableFooter.appendChild(row);
		}
			
	}
	
	var KialaTrackingControls = new KialaTrackingControlsCreator();
	
	document.observe('dom:loaded', function() {
		if(carrierCode == "kiala"){
			KialaTrackingControls.add();
		}
	});
	
