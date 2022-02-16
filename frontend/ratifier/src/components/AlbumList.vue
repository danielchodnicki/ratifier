<template>
    <div class="stretcherContainer stretcherContent">
        <div class="stretcherHeader">
            <h2>{{ $t(args.title) }}</h2>
            <form
                    v-if="args.searchForm"
                    @submit.prevent="$emit('new-search' , searchQuery )">
                <input
                        v-model="searchQuery"
                        class="input"
                        :placeholder="$t('Title or artist')">
                <br>
                <input
                        type="submit"
                        :value="$t('Search')"
                        class="btn">
            </form>
            <form
                v-if="args.orderBy"
                @submit.prevent="$emit( 'sort-albums' , orderBy )"
                >
                <font-awesome-icon icon="list-ol"
                    style="margin-right: .6em"></font-awesome-icon>
                <select
                        class="input"
                    @change=" orderBy = $event.target.value "
                >
                    <option
                        v-for="( label , key ) in args.orderBy"
                        :value="key"
                    >{{ $t(key) }}</option>
                </select>
                <br>
                <input
                        type="submit"
                        class="btn"
                        :value="orderByButtonText">
            </form>
        </div>
        <div class="stretcherContent">
            <div
                v-if=" args.albums.length &&  args.searchForm "
                >
                {{ $t('Results from Spotify') }}<br>
                <img
                        class="spotify"
                        src="../assets/Spotify_Logo_RGB_Green.png"
                        alt="Spotify">
            </div>
            <transition-group
                    name="fade"
                    tag="div"
            >
                <AlbumBox
                        v-for=" album in args.albums "
                        :key=" album.album_id "
                        :album=" album "
                        :ratingLevels=" args.ratingLevels "
                        @rate-album=" $emit('rate-album' , $event ) "
                ></AlbumBox>
            </transition-group>
            <LoaderAnimation
                    v-if="args.isLoading"
                    center="true"
                    size="2em">
            </LoaderAnimation>
            <button
                    class="btn"
                    v-if="args.hasMore && ! args.isLoading"
                    @click="$emit('load-more' )">
                {{ $t('Load more') }}</button>
        </div>
    </div>
</template>

<script>
    import i18n from '../main.js'
    import AlbumBox from './AlbumBox.vue'
    import LoaderAnimation from "./LoaderAnimation";
    export default {
        name: 'AlbumList',
        components: {
            LoaderAnimation,
            AlbumBox,
        },
        data() {
            return {
                searchQuery: '',
                orderBy: '',
            }
        },
        computed: {
            orderByButtonText: function() {
                return this.orderBy == this.args.currentOrderBy ? this.$t('Refresh') : this.$t('Sort');
            }
        },
        methods: {
        },
        props: [ 'args' ],
        created() {
            this.orderBy = this.args.currentOrderBy;
        },
        mounted() {
            this.$emit( 'mounted' );

        },
        emits: [ 'mounted' , 'load-more' , 'new-search' , 'rate-album' , 'sort-albums' ]
    }
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

    h3 {
        margin: 40px 0 0;
    }
    ul {
        list-style-type: none;
        padding: 0;
    }
    li {
        display: inline-block;
        margin: 0 10px;
    }
    a {
        color: #42b983;
    }
</style>
