jQuery(document).ajaxComplete(()=>{
    const nearbyPoints = document.querySelectorAll('.hcnsg-nearby-points');

    if ( nearbyPoints ) {
        const placeOrder = document.getElementById('place_order');

        nearbyPoints.forEach(el=>{
            const shippingMethod = el.parentElement.querySelector('input[type="radio"]');
            const nearbyInputs = el.querySelectorAll('input[type="radio"]');
            const nerabyHiddenInput = el.querySelector('input[type="hidden"]');

            if ( jQuery(shippingMethod).is(':checked') ) {
                placeOrder.classList.add('disabled');
            }

            shippingMethod.addEventListener( 'click', ()=>{
                if ( jQuery(shippingMethod).is(':checked') ) {
                    placeOrder.classList.add('disabled');
                }else{
                    placeOrder.classList.remove('disabled');
                }
            } );

            if ( nearbyInputs ) {
                nearbyInputs.forEach(inp => {
                    inp.addEventListener('click', () => {
                        placeOrder.classList.remove('disabled');
                    });
                });
            }
            if ( nerabyHiddenInput ) {
                placeOrder.classList.remove('disabled');
            }

        });
    }

});
