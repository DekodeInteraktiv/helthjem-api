const heltHjemClearLogin = document.getElementById('hcnsg_clear_login');
const heltHjemClearCache = document. getElementById('hcnsg_clear_clear_cache');
const hetlHjemZipCode = document.getElementById('hcnsg_zipcode');
const hetlHjemCountryCode = document.getElementById('hcnsg_country');
const hetlHjemTransportID = document.getElementById('hcnsg_transportID');
const heltHjemGetNearby = document.getElementById('checkpickup_submit');
const nearbyPointsWrapp = document.getElementById( 'hcnsg_pick-up-points' )

if ( heltHjemClearLogin ) {
    heltHjemClearLogin.addEventListener( 'click', (e)=>{
        e.preventDefault();

        fetch(
            hcnsg.url,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: new URLSearchParams({
                    action: 'hcnsg_clear_login',
                    nonce: hcnsg.nonce
                })
            }
        ).then(response=>response.json()).then(result=>{
            alert(result);
            setTimeout(()=>{
                window.location.reload();
            }, 1000);
        });
    } );
}

if ( heltHjemClearCache ) {
    heltHjemClearCache.addEventListener( 'click', (e)=>{
        e.preventDefault();

        fetch(
            hcnsg.url,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: new URLSearchParams({
                    action: 'hcnsg_clear_cache',
                    nonce: hcnsg.nonce
                })
            }
        ).then(response=>response.json()).then(result=>{
            alert(result);
        });
    } );
}

if ( hetlHjemZipCode && hetlHjemCountryCode && heltHjemGetNearby ) {
    heltHjemGetNearby.addEventListener( 'click', (e)=>{
        e.preventDefault();
        const defaultText = heltHjemGetNearby.innerText;
        heltHjemGetNearby.innerText += ' ...Requesting...';

        fetch(
            hcnsg.url,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: new URLSearchParams({
                    action: 'hcnsg_get_nearby_points',
                    nonce: hcnsg.nonce,
                    country: hetlHjemCountryCode.value,
                    zip: hetlHjemZipCode.value,
                    transport: hetlHjemTransportID.value
                })
            }
        ).then( response => response.json() ).then( result => {
            console.log(result);
            heltHjemGetNearby.innerText = defaultText;
            const responseHTML = document.createElement('div');
            switch( typeof result ) {
                case 'string':
                    responseHTML.innerText = result;
                break;
                case 'object':
                    if( 'errors' in result ) {
                        const errors = result.errors;
                        for ( const [key, value] of Object.entries(errors) ) {
                            responseHTML.innerText += key + ': ' + value;
                        }
                    } else {
                        responseHTML.innerHTML = '<pre>' + JSON.stringify( result, null, ' ' ) + '</pre>';
                    }
                break;
                default:
                    console.log(result);
                break;
            }
            nearbyPointsWrapp.innerHTML = '';
            nearbyPointsWrapp.appendChild( responseHTML);
        });
    } );
}
