<script>
    export default {
        name: 'AlbumsDataManagement',
        data() {
            return {
                searchAlbums: {},
                allRatings: {},
                userRatings: {},
                defaults: {},
            }
        },
        methods: {
            async getAllRatings() {
                this.allRatings.isLoading = true;
                let ratings = await this.ratifier.getRatedAlbums( ++this.allRatings.offset , this.allRatings.currentOrderBy );
                console.log(ratings);
                this.allRatings.isLoading = false;
                this.allRatings.hasMore = false;
                this.allRatings.albums = this.allRatings.albums.concat( ratings );
                if ( ! ratings.length || ratings.length < this.ratifier.getConfig( 'perPage' ) ) {
                    this.allRatings.hasMore = false;
                }
                else {
                    this.allRatings.hasMore = true;
                }
            },

            async getUserRatings() {
                this.userRatings.isLoading = true;
                let ratings = await this.ratifier.getUserRatings( ++this.userRatings.offset , this.userRatings.currentOrderBy )
                this.userRatings.isLoading = false;
                this.userRatings.hasMore = false;
                this.userRatings.albums = this.userRatings.albums.concat( ratings );
                if ( ratings.length < this.ratifier.config.perPage ) {
                    this.userRatings.hasMore = false;
                }
                else {
                    this.userRatings.hasMore = true;
                }
            },

            sortAllAlbums( orderBy ) {
                this.allRatings.albums = [];
                this.allRatings.offset = -1;
                this.allRatings.currentOrderBy = orderBy;
                this.getAllRatings();
            },

            sortUserRatings( orderBy ) {
                this.userRatings.albums = [];
                this.userRatings.offset = -1;
                this.userRatings.currentOrderBy = orderBy;
                this.getUserRatings();
            },

            async rateAlbum( data ) {
                let album = await this.ratifier.addRating({
                    album_id: data.album_id,
                    rating_production: data.rating,
                });
                this.updateAlbumData( album.data[0] );
            },

            updateAlbumData: function( data ) {
                let lists = [ 'allRatings' , 'userRatings' , 'searchAlbums' ];
                for ( let arr of lists ) {
                    for ( let i = 0 ; i < this[arr].albums.length ; i++ ) {
                        if ( this[arr].albums[i].album_id == data.album_id ) {
                            this.$set(this[arr].albums, i, data );
                            break;
                        }
                    }
                }
            },

            removeAlbumData() {
                let lists = [ 'allRatings' , 'userRatings' , 'searchAlbums' ];
                for ( let arr of lists ) {
                    this[arr].albums = [];
                }
            },

            loadMoreSearchResults() {
                this.searchAlbums.isLoading = true;
                this.ratifier.searchAlbums( this.searchAlbums.searchQuery , ++this.searchAlbums.offset )
                    .then(results => {
                        if ( ! results.length ) {
                            this.searchAlbums.hasMore = false;
                        }
                        else {
                            this.searchAlbums.albums = this.searchAlbums.albums.concat(results);
                            this.searchAlbums.hasMore = true;
                        }
                        this.searchAlbums.isLoading = false;
                    })
            },

            async newSearch( searchQuery ) {
                this.searchAlbums.albums = [];
                this.searchAlbums.isLoading = true;
                this.searchAlbums.offset = 0;
                let results = await this.ratifier.searchAlbums( searchQuery );
                this.searchAlbums.albums = results;
                this.searchAlbums.isLoading = false;
                this.searchAlbums.hasMore = true;
            },

            userRatingsDefaults() {
                return {
                    albums: [],
                    hasMore: false,
                    isLoading: false,
                    offset: -1,
                    title: 'Your ratings',
                    ratingLevels: [1,2,3,4,5],
                    currentOrderBy: 'user_rating_desc',
                    orderBy: {
                        'user_rating_desc' : 'Twoją oceną malejąco',
                        'user_rating_asc': 'Twoją oceną rosnąco',
                        'rating_desc' : 'Oceną malejąco',
                        'rating_asc' : 'Oceną rosnąco',
                        'artist_desc' : 'Nazwą wykonawcy malejąco',
                        'artist_asc' : 'Nazwą wykonawcy rosnąco',
                        'title_desc' : 'Tytułem albumu malejąco',
                        'title_asc' : 'Tytułem albumu rosnąco',
                        'year_desc' : 'Rokiem wydania malejąco',
                        'year_asc' : 'Rokiem wydania rosnąco',
                    }
                }
            },

            allRatingsDefaults() {
                return {
                    albums: [],
                    hasMore: false,
                    isLoading: false,
                    offset: -1,
                    title: 'Rated albums',
                    ratingLevels: [1,2,3,4,5],
                    currentOrderBy: 'rating_desc',
                    orderBy: {
                        'rating_desc' : 'Oceną malejąco',
                        'rating_asc' : 'Oceną rosnąco',
                        'artist_desc' : 'Nazwą wykonawcy malejąco',
                        'artist_asc' : 'Nazwą wykonawcy rosnąco',
                        'title_desc' : 'Tytułem albumu malejąco',
                        'title_asc' : 'Tytułem albumu rosnąco',
                        'year_desc' : 'Rokiem wydania malejąco',
                        'year_asc' : 'Rokiem wydania rosnąco',
                    }
                }
            },

            searchAlbumsDefaults() {
                return {
                    albums: [],
                    hasMore: false,
                    isLoading: false,
                    offset: -1,
                    title: 'Search albums',
                    ratingLevels: [1,2,3,4,5],
                    searchForm: true,
                }
            },

            setDefaults() {
                this.userRatings = this.userRatingsDefaults();
                this.allRatings = this.allRatingsDefaults();
                this.searchAlbums = this.searchAlbumsDefaults();
            },
        },
    }
</script>

