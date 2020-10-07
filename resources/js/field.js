Nova.booting((Vue, router, store) => {
  Vue.component('index-language-toggle-field', require('./components/IndexField'))
  Vue.component('detail-language-toggle-field', require('./components/DetailField'))
  Vue.component('form-language-toggle-field', require('./components/FormField'))
})
