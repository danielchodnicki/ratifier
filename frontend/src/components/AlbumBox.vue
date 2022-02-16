<template>
    <div class="albumBox">
        <div class="album-data">
            <b>{{ album.title }}</b>
            <br>
            <i>{{ album.artist }}  ({{ album.year }})</i>
        </div>
        <div>
            <div class="rating-data">
                <div
                        class="ratingDataSlot"
                        :title=" $t('Average score') ">
                    <span class="starPercentageContainer">
                        <span
                                class="starPercentage"
                                :style="{ width: starWidth }"
                        >
                            <font-awesome-icon icon="star" />
                        </span>
                        <font-awesome-icon icon="star" />
                    </span>
                    {{ album.rating_production ? album.rating_production : '?' }}
                </div>
                <div
                        class="ratingDataSlot"
                        :title=" $t('Number of votes') ">
                    <span style="color:rgba(255,146,144,1)"><font-awesome-icon icon="users" /></span>
                    {{ album.counter ? album.counter : '0'  }}
                </div>
                <button
                        class="ratingDataSlot addRatingButton"
                        :title=" $t('Your rating') "
                        @click="vote = ! vote">
                    <font-awesome-icon icon="user-plus" />
                    {{ album.user_ratings.rating_production ? album.user_ratings.rating_production : '?' }}
                </button>
            </div>
        </div>
        <transition
                @after-enter="SliderDownAfterEnter"
                @enter="SliderDownEnter"
                @before-leave="SliderDownBeforeLeave"
                @leave="SlideDownLeave"
                :css="false"
        >
            <div
                    v-if="vote || album.waiting"
                    class="starsCointainer">
                <div>
                    <span>
                        <button
                                class="starRating"
                                v-for="i in ratingLevels"
                                @click="rateAlbum( album.album_id , i )"
                                @mouseenter=" onStarHover(i) "
                                @mouseout=" onStarHover(false) "
                                :class="[ { selected: starLit(i) } , { loading: album.waiting } ]"
                        >
                            <font-awesome-icon icon="star" />
                            <LoaderAnimation
                                    v-show=" album.waiting && votedFor == i "></LoaderAnimation>
                        </button>

                    </span>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
    import LoaderAnimation from './LoaderAnimation.vue'
    import SliderDown from './SliderDown'
    export default {
        name: 'AlbumBox',
        components: {
            LoaderAnimation,
        },
        mixins: [ SliderDown ],
        props: [ 'album' , 'ratingLevels' ],
        data() {
            return {
                starHovered: false,
                vote: false,
                votedFor: false,
            }
        },
        computed: {
            starWidth() {
                return ( this.album.rating_production / this.ratingLevels.length ) * 100 + '%';
            }
        },
        methods: {
            onStarHover(i) {
                this.starHovered = i;
            },
            starLit(i) {
                if ( this.starHovered ) {
                    if ( i <= this.starHovered ) {
                        return true;
                    }
                }
                else if ( i <= this.album.user_ratings.rating_production ) {
                    return true;
                }
            },
            rateAlbum( album_id , rating ) {
                this.$set( this.album , 'waiting' , true );
                this.vote = false;
                this.votedFor = rating;
                this.$emit('rate-album' , { album_id , rating } )
            },
        },
        emits: [ 'rate-album' ]
    }
</script>

