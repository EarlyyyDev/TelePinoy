import { defineConfig } from '#q-app/wrappers'

export default defineConfig({
  css: ['app.scss'],
  extras: ['material-icons'],
  framework: {
    config: {
      dark: true,
    },
    plugins: ['Notify', 'Dialog', 'Loading'],
  },
  build: {
    target: {
      browser: ['es2022'],
      node: 'node20',
    },
  },
  devServer: {
    host: '0.0.0.0',
    port: 9000,
    open: false,
  },
  sourceFiles: {
    rootComponent: 'src/App.vue',
  },
})
