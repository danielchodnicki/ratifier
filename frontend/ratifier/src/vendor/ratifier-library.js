const Ratifier = function( config ) {

    if ( typeof config.perPage == 'undefined' ) {
        config.perPage = 3;
    }
    return {
        config: config,
        login( user , pass ) {
            console.log( user, pass );
            console.log( 'Basic ' + btoa( user + ':' + pass ) );
            return fetch( config.url + 'ratifier/v1/login' , {
                headers: {
                    //'X-WP-Nonce': config.wpNonce,
                    'Authorization' : 'Basic ' + btoa( user + ':' + pass ),
                },
                method: 'POST',
            })
                .then( result => result.json() )
                .then( result => {
                    if ( result.hasOwnProperty('success' ) && result.success ) {
                        config.wpNonce = result.data.wp_nonce;
                        config.auth = 'Basic ' + btoa( user + ':' + pass );
                        return result;
                    }
                    else {
                        result.success = false;
                        return result;
                    }
                } )
                .catch( e => e );
        },
        getConfig( data ) {
           return this.config[data];
        },
        getRatedAlbums: async function( offset = 0 , order = 'date_desc' ) {
            let page = offset + 1;
            return fetch( config.url + 'ratifier/v1/ratedAlbums'
                + '?order_by=' + order
                + '&page=' + page +
                '&posts_per_page=' + config.perPage ,
                {
                headers: {
                    'X-WP-Nonce': config.wpNonce,
                    "Authorization" : config.auth,
                },
            })
                .then(result => result.json())
                .then( data => data.data );
        },
        searchAlbums: async function( query , offset = 0 ) {
            return fetch( config.url + 'ratifier/v1/search/?s=' + query + '&offset=' + offset , {
                headers: {
                    'X-WP-Nonce': config.wpNonce,
                    "Authorization" : config.auth,
                },
            }).then(result => result.json())
                .then(data => data.data);
        },
        getAlbumDataById: async function( albumId ) {
            return fetch(config.url + config.wpRoute + 'albums?album_ids=' + albumId, {
                headers: {
                    'X-WP-Nonce': config.wpNonce,
                    "Authorization" : config.auth,
                },
                success : function( response ) {
                    return response;
                }
            }).then(result => result.json());
        },
        getUserRatings: function( offset = 0 , order = 'date_desc' ) {
            let page = offset + 1;
            return fetch(
                config.url
                + 'ratifier/v1/userRatings?&page=' + page
                + '&posts_per_page=' + config.perPage
                + '&order_by=' + order, {
                headers: {
                    'X-WP-Nonce': this.config.wpNonce,
                    "Authorization" : config.auth,
                },
            })
                .then(result => result.json())
                .then( data => data.data );
        },
        addRating: function( ratingData ) {
            return fetch(config.url + 'ratifier/v1/rating/', {
                headers: {
                    'X-WP-Nonce': config.wpNonce,
                    "Authorization" : config.auth,
                    'Content-Type': 'application/json',
                },
                method: "POST",
                body: JSON.stringify(ratingData),
            }).then(result => result.json());
        }
    }
}

export default Ratifier;