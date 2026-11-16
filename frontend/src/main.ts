import { createApp } from 'vue'
import { Quasar, Dialog, Loading, Notify } from 'quasar'
import { createPinia } from 'pinia'
import '@quasar/extras/material-icons/material-icons.css'
import 'quasar/src/css/index.sass'
import './style.css'
import './css/app.scss'
import App from './App.vue'
import router from './router'
import { bootAxios } from './boot/axios'

bootAxios()

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(Quasar, {
  plugins: { Dialog, Loading, Notify },
})

app.mount('#app')
