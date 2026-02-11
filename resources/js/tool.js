Nova.booting((Vue, router, store) => {
    router.addRoutes([
        {
            name: 'translation-matrix',
            path: '/translation-matrix',
            component: require('./tools/TranslationMatrix/Tool').default,
        },
    ])
})
