Nova.booting((Vue, router, store) => {
    Vue.component(
        "index-language-toggle-field",
        require("./components/IndexField").default
    );
    Vue.component(
        "detail-language-toggle-field",
        require("./components/DetailField").default
    );
    Vue.component(
        "form-language-toggle-field",
        require("./components/FormField").default
    );

    Vue.component(
        "index-translatable-field",
        require("./components/TranslatableField/IndexField").default
    );
    Vue.component(
        "detail-translatable-field",
        require("./components/TranslatableField/DetailField").default
    );
    Vue.component(
        "form-translatable-field",
        require("./components/TranslatableField/FormField").default
    );

    let AutoTranslator = require("./mixins/auto-translator");
    AutoTranslator.init(Nova);
});
