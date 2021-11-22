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
});
