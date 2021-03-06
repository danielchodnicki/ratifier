import Vue from 'vue'
import VueI18n from 'vue-i18n'
import messages from './lang'
import Ratifier from './Ratifier.vue'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faTimes , faUserSecret , faUsers , faStar , faUserPlus , faUser , faPowerOff , faSearch , faListOl , faMoon , faCloudMoon , faSun } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

Vue.use( VueI18n );

const i18n = new VueI18n({
  locale: 'en',
  fallbackLocale: 'pl',
  messages
});

library.add( faTimes , faUserSecret , faUsers , faStar , faUserPlus , faUser , faPowerOff , faSearch , faListOl , faMoon , faCloudMoon , faSun )

Vue.component('font-awesome-icon', FontAwesomeIcon )

Vue.config.productionTip = true

new Vue({
  render: h => h(Ratifier),
  i18n,
}).$mount('#app')
