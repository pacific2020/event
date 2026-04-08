import './assets/main.css'
import '@fortawesome/fontawesome-free/css/all.css'
import '@fortawesome/fontawesome-free/js/all.js'

import { createApp } from 'vue'
import App from './App.vue'

import { plugin, defaultConfig } from '@formkit/vue'
import config from './../formkit.config'

import formkitConfig from '@/formkit.config.ts'
import { createPinia } from 'pinia'
import router from './router'
import toast, { POSITION } from "vue-toastification";
import "vue-toastification/dist/index.css"
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'

import VConsole from 'vconsole';
if (import.meta.env.DEV) {
  new VConsole();
}


const app = createApp(App)

const queryClient = new QueryClient()

// ✅ install plugin
app.use(VueQueryPlugin, {
  queryClient
})

 
app.use(router);
app.use(createPinia());




/* -----------------------------
Toast Notifications
----------------------------- */
app.use(toast, {
  position: POSITION.TOP_RIGHT,
  timeout: 3000,
  closeOnClick: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  hideProgressBar: false,
  icon: true
})
app.use(plugin, defaultConfig(formkitConfig))
app .mount('#app')


