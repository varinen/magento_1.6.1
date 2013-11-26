/*
 * 
 * show preloader
 */
function showLoadingImage() {
    var element = document.getElementById('loader');
    if (typeof(element) != 'undefined' && element != null)
    {

        $$('dl.sp-methods div.kialapoints').each(function(e) {
            e.update();
            e.innerHTML;
        });

        $('loader').show();
    }
    // exists.
}


/* 
 * Kiala javascript functions
 */
function updateKialapoint() {
    if (window.kialaWindow != undefined) {
        window.kialaWindow.setCloseCallback(null);
        window.kialaWindow.close();
    }
    showLoadingImage();

    //Update the shipping methods (trigger same ajax call as shipping address save in standard checkout)
    window.shipping.save();

    //Or in case of onestepcheckout, trigger a billing address change
    fireEvent($('billing:postcode'), 'change');
}

/**
 * Triggered when a user selects the input from the frontend (select button)
 */
function selectKialapointFrontend(saveUrl) {
    new Ajax.Request(saveUrl,
            {
                method: 'get',
                onCreate: showLoadingImage,
                onComplete: function(transport) {
                    var json = transport.responseText.evalJSON();

                    if (json.success) {
                        window.updateKialapoint();
                    } else {
                        window.showKialaSelectError();
                    }

                }
            });
}




function showKialaSelectError() {
    alert('Invalid map calback requested');
}

/*
 * Popup overlay functions
 */
function showKialaWindow(url, type, width, height) {
    var settings = {
        className: 'dialog',
        width: width,
        height: height,
        minimizable: false,
        maximizable: false,
        closable: false,
        draggable: false,
        showEffectOptions: {
            duration: 0.4
        },
        hideEffectOptions: {
            duration: 0.4
        }
    };

    if (type == 'ajax') {
        if (window.kialaWindow && window.kialaWindow.visible) {
            new Ajax.Request(url, {
                onComplete: function(transport) {
                    var json = transport.responseText.evalJSON();

                    window.kialaWindow.setHTMLContent(json.html);
                }
            });
        } else {
            window.kialaWindow = new Window(settings);
            new Ajax.Request(url, {
                onComplete: function(transport) {
                    var json = transport.responseText.evalJSON();

                    window.kialaWindow.setHTMLContent(json.html);
                }
            });
        }
    } else if (type == 'iframe') {
        if (window.kialaWindow && window.kialaWindow.visible) {
            window.kialaWindow.setSize(width, height, true)
            window.kialaWindow.setURL(url);
        } else {
            window.kialaWindow = new Window({
                className: 'dialog',
                width: width,
                height: height,
                minimizable: false,
                maximizable: false,
                showEffectOptions: {
                    duration: 0.4
                },
                hideEffectOptions: {
                    duration: 0.4
                },
                url: url
            });
        }
    } else {

    }

    if (window.kialaWindow && !window.kialaWindow.visible) {
        window.kialaWindow.setZIndex(100);
        window.kialaWindow.showCenter(true);

        //Close on click with overlay
        setTimeout(function() {
            Event.observe('overlay_modal', 'click', function() {
                window.kialaWindow.close();
            });
        }, 500);
    }


}

function saveKialaLanguage(url) {
    if ($('kiala-language').value) {
        //Save language
        new Ajax.Request(url, {
            method: 'get',
            parameters: {language: $('kiala-language').getValue()},
            onComplete: function(transport) {
                //Save shippingmethod
                $('shipping_method_kiala_kialalanguage').setValue($('kiala-language').getValue());

                window.kialaWindow.close();

                if ($('onestepcheckout-form') == undefined) {
                    shippingMethod.save();
                } else {
                    $('onestepcheckout-form').submit();
                }

            }
        });
    } else {
//        alert('Please select language');
    }
}

function isKialaSelected() {
    var kialaSelected = false;
    $$("input[id^='s_method_kiala_kiala']").each(function(i) {
        if ($F(i)) {
            kialaSelected = $F(i);
        }
    });

    return kialaSelected;
}

/**
 * Adds kialapoint checked validation
 */
Validation.add('validate-kialapoint', 'Please select a Kiala Point where you will collect your order!', function(value) {
    if (isKialaSelected()) {
        if (value) {
            return true;
        } else {
            return false;
        }
    } else {
        return true;
    }
});

Validation.add('validate-kialalanguage', 'Please select language', function(value, elm) {
    if (isKialaSelected()) {
        if ($('shipping_method_kiala_kialapoint').value) {
            //Check user language
            if (value) {
                return true;
            } else {

                if ($('onestepcheckout-form') == undefined) {
                    //Standard checkout
                    showKialaWindow($('shipping_method_kiala_kialalanguage').getAttribute('data-saveurl'), 'ajax', 250, 105);

                    return false;
                } else {
                    //Onestep checkout
                    if ($$('#onestepcheckout-form input.validation-failed').length == 0) {

                        showKialaWindow($('shipping_method_kiala_kialalanguage').getAttribute('data-saveurl'), 'ajax', 250, 105);

                    } else if ($$('#onestepcheckout-form input.validation-failed').length == 1 && $$('#onestepcheckout-form input.validate-kialalanguage.validation-failed.').length == 1) {

                        showKialaWindow($('shipping_method_kiala_kialalanguage').getAttribute('data-saveurl'), 'ajax', 250, 105);

                    } else {
//                        return false;
                    }

                    return false;
                }
            }
        } else {
            return true;
        }
    } else {
        return true;
    }
});