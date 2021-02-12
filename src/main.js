import Vue from 'vue'
import App from './App.vue'
import i18n from './i18n'
import vueResource from 'vue-resource'

Vue.config.productionTip = false
Vue.use(vueResource)

new Vue({
  i18n,
  render: h => h(App)
}).$mount('#app')
