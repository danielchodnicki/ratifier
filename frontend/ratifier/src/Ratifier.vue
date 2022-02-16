<template>
  <div
          id="app"
          class="stretcherContainer"
          :class=" [ { desktop } , theme ]">

    <!-- Header -->
    <div class="stretcherHeader">
      <div class="mainHeader">
        <div></div>
        <h1
                class="logotype"
                :class=" { loggedIn: isLoggedIn }">
          <font-awesome-icon icon="star"></font-awesome-icon>
          Ratifier
        </h1>
        <div class="globalButtons">
          <button
                  class="btnTransparent languageSwitcher"
                  @click=" setLanguage( 'pl' ) "
                  v-if=" $i18n.locale != 'pl' "
                  :title=" $t('Switch language') "
            >PL</button>
          <button
                  class="btnTransparent languageSwitcher"
                  @click=" setLanguage( 'en' ) "
                  v-if=" $i18n.locale != 'en' "
                  :title=" $t('Switch language') "
          >EN</button>
          <button
                  class="btnTransparent"
                  title="Tryb jasny"
                  v-if=" theme != 'themeLight'"
                  @click=" setTheme( 'themeLight' )"
                  :title=" $t('Light mode') ">
            <font-awesome-icon icon="sun"></font-awesome-icon>
          </button>
          <button
                  class="btnTransparent"
                  title="Tryb ciemny"
                  v-if=" theme != 'themeDark'"
                  @click=" setTheme( 'themeDark' )"
                  :title=" $t('Dark mode') ">
            <font-awesome-icon icon="moon"></font-awesome-icon>
          </button>
          <button
                  class="btnTransparent"
                  v-if=" isLoggedIn "
                  @click="logout"
                  :title=" $t('Logout') ">
            <font-awesome-icon
                    icon="power-off"
            ></font-awesome-icon>
          </button>
        </div>
      </div>



      <!-- Mobile menu -->
      <div
              class="mobileMenu"
              v-if=" isLoggedIn && ! desktop ">
        <button
                @click.prevent=" tab = 'search' "
                class="mobileMenuButton"
                :class=" { active: tab == 'search' } "
                :title=" $t('Search albums') ">
          <font-awesome-icon icon="search"></font-awesome-icon>
        </button>
        <button
                @click.prevent=" tab = 'userRatings' "
                class="mobileMenuButton"
                :class=" { active: tab == 'userRatings' } "
                :title=" $t('Your ratings') ">
          <font-awesome-icon icon="user"></font-awesome-icon>
        </button>
        <button
                @click.prevent=" tab = 'allRatings' "
                class="mobileMenuButton"
                :class=" { active: tab == 'allRatings' } "
                :title=" $t('Rated albums') ">
          <font-awesome-icon icon="list-ol"></font-awesome-icon>
        </button>
      </div>
    </div>


    <!-- Main content -->
    <div class="stretcherContent stretcherContainer">
      <LoaderAnimation
              v-if="loadingScreen"
              size="4em">
      </LoaderAnimation>

      <!-- Three columns -->
      <Transition
              mode="out-in"
              name="fade">
        <div
                v-if="isLoggedIn"
                class="listColumns stretcherContent">
          <Transition
                  mode="in-out"
                  name="slider">
            <AlbumList
                    class="albumList"
                    key="search"
                    v-show=" desktop || tab == 'search' "
                    :class=" { hidden : tab != 'search' }"
                    :args="searchAlbums"
                    @new-search="newSearch( $event )"
                    @load-more="loadMoreSearchResults"
                    @rate-album="rateAlbum( $event )"
            ></AlbumList>
          </Transition>
            <Transition mode="in-out" name="slider">
          <AlbumList
                  class="albumList"
                  key="userRatings"
                  v-show=" desktop || tab == 'userRatings' "
                  :class=" { hidden : tab != 'userRatings' }"
                  :args="userRatings"
                  @mounted="getUserRatings"
                  @load-more="getUserRatings"
                  @rate-album="rateAlbum( $event )"
                  @sort-albums="sortUserRatings( $event )"
          ></AlbumList>
            </Transition>
            <Transition mode="in-out" name="slider">
            <AlbumList
                    class="albumList"
                    key="allRatings"
                    v-show=" desktop || tab == 'allRatings' "
                    :class=" { hidden : tab != 'allRatings' }"
                    :args="allRatings"
                    @mounted="getAllRatings"
                    @load-more="getAllRatings"
                    @rate-album="rateAlbum( $event )"
                    @sort-albums="sortAllAlbums( $event )"
            ></AlbumList>
            </Transition>
        </div>
      </Transition>

      <!-- Login form -->
      <Transition
        mode="out-in"
        name="fade"
        >
        <LoginForm
                :username=" username "
                :password=" password "
                :error=" loginError "
                :error-msg=" loginErrorMsg "
                :logging-in=" isLoggingIn "
                v-if=" ! loadingScreen && ! isLoggedIn "
                @login="login( $event )"/>
      </Transition>
    </div>
  </div>
</template>

<script>
  import i18n from './main.js'
  import Ratifier from './vendor/ratifier-library'
  import GeneralUsage from './mixins/GeneralUsage';
  import UserInterface from './mixins/UserInterface';
  import UserManagement from './mixins/UserManagement';
  import AlbumsDataManagement from './mixins/AlbumsDataManagement';
  import LoaderAnimation from './components/LoaderAnimation.vue';
  import AlbumList from './components/AlbumList.vue'
  import LoginForm from './components/LoginForm.vue'

const ratifier = Ratifier( {
    url: 'https://web-zeppelin.pl/ratifier-backend/wp-json/',
    wpRoute: 'wp/v2/',
  });

export default {

  name: 'Ratifier',

  components: {
    LoginForm,
    LoaderAnimation,
    AlbumList,
    i18n,
  },

  mixins: [
    GeneralUsage,
    UserInterface,
    UserManagement,
    AlbumsDataManagement
  ],

  data() {
    return {
      ratifier: ratifier,
      settings: {
        ratingLevels: [1,2,3,4,5],
      },
    }
  },

  created() {

    this.setDefaults();

    window.addEventListener('resize', this.setView );

    if ( this.getCookie( 'ratifier_theme' ) ) {
      this.theme = this.getCookie( 'ratifier_theme' );
    }

    if ( this.getCookie( 'ratifier_lang' ) ) {
      this.$i18n.locale = this.getCookie( 'ratifier_lang' );
    }

  },

  destroyed() {

    window.removeEventListener('resize', this.setView );

  },

  mounted() {

    this.setView();

    this.tryLoggingIn();

  },
}
</script>

<style lang="sass">
  @import './assets/styles/style'
</style>
