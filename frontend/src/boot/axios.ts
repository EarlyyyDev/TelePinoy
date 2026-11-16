import axios from 'axios'

export function bootAxios() {
  axios.defaults.baseURL = import.meta.env.VITE_API_URL ?? '/api'
  axios.defaults.headers.common.Accept = 'application/json'
}
