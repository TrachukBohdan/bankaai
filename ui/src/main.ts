import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { setupPrimeVue } from './plugins/primevue'

const app = createApp(App)

setupPrimeVue(app)
app.use(createPinia())
app.use(router)

app.mount('#app')
