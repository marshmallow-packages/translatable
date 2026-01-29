let AutoTranslator = {
    buttonText: "Translate with DeepL",
    loadingText: "translating...",
    init: function (Nova) {
        let self = this;
        (async () => {
            let settings = await self.apiRequest("settings", "GET");
            this.buttonText = settings.button_text;
            this.loadingText = settings.loading_text;
            if (settings.active) {
                self.initAutoTranslator(Nova);
            }
        })();
    },
    initAutoTranslator: function (Nova) {
        let self = this;

        Nova.$on("resource-loaded", function (data) {
            if (data.mode == "update") {
                setTimeout(() => {
                    const translator = document.getElementById(
                        "mm-translation-language-toggle",
                    );
                    if (!translator) {
                        return;
                    }

                    const translating =
                        translator.getAttribute("data-translating");
                    const source = translator.getAttribute("data-source");
                    const target = translator.getAttribute("data-target");

                    if (
                        translating == false ||
                        translating == "false" ||
                        !source ||
                        !target ||
                        source === target
                    ) {
                        return;
                    }

                    let form_wrapper = document.querySelector(
                        `[data-form-unique-id]`,
                    );
                    form_wrapper.querySelectorAll("input").forEach((input) => {
                        if (input.getAttribute("auto-translator-loaded")) {
                            return;
                        }

                        if (["file"].includes(input.getAttribute("type"))) {
                            return;
                        }

                        if (input.closest("div.multiselect")) {
                            return;
                        }

                        self.initAutoTranslatorForInput(
                            input.getAttribute("dusk"),
                            source,
                            target,
                            input.closest("div"),
                        );
                    });
                    form_wrapper
                        .querySelectorAll("textarea")
                        .forEach((input) => {
                            if (input.getAttribute("auto-translator-loaded")) {
                                return;
                            }

                            let field_id = input.getAttribute("id");
                            if (field_id && field_id.startsWith("tiny_")) {
                                self.initAutoTranslatorForTinyMce(
                                    field_id.substring(5),
                                    source,
                                    target,
                                    input.closest("div"),
                                );
                            } else {
                                self.initAutoTranslatorForInput(
                                    input.getAttribute("dusk"),
                                    source,
                                    target,
                                    input.closest("div"),
                                );
                            }
                        });

                    form_wrapper
                        .querySelectorAll(".tiptap.ProseMirror")
                        .forEach((tiptapEditor) => {
                            if (
                                tiptapEditor.getAttribute(
                                    "auto-translator-loaded",
                                )
                            ) {
                                return;
                            }

                            self.initAutoTranslatorForTipTap(
                                tiptapEditor,
                                source,
                                target,
                            );
                        });
                }, 200);
            }
        });
    },
    initAutoTranslatorForInput: function (
        field_name,
        source,
        target,
        wrapper = null,
    ) {
        input = document.querySelector(`[dusk="${field_name}"]`);
        if (!input) {
            return;
        }
        input.setAttribute("auto-translator-loaded", true);
        wrapper = wrapper
            ? wrapper
            : input.closest("div[index]").querySelectorAll("div")[1];
        wrapper.append(this.getTextButton(input, source, target));
    },
    initAutoTranslatorForTinyMce: function (
        field_name,
        source,
        target,
        wrapper = null,
    ) {
        input = document.querySelector(`[id="tiny_${field_name}"]`);
        if (!input) {
            return;
        }
        input.setAttribute("auto-translator-loaded", true);

        wrapper = wrapper ? wrapper : input.closest("div");
        wrapper.append(this.getTinyMceButton(field_name, source, target));
    },
    initAutoTranslatorForTipTap: function (tiptapEditor, source, target) {
        tiptapEditor.setAttribute("auto-translator-loaded", true);

        let editorWrapper = tiptapEditor.closest(".nova-tiptap-editor");
        if (editorWrapper) {
            let buttonContainer = document.createElement("div");
            buttonContainer.classList.add("mt-2");
            buttonContainer.append(
                this.getTipTapButton(tiptapEditor, source, target),
            );

            editorWrapper.parentElement.insertBefore(
                buttonContainer,
                editorWrapper.nextSibling,
            );
        }
    },

    getDefaultButtonIconAndLabel: function () {
        return (
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" /></svg>' +
            this.buttonText
        );
    },

    getLoadingButtonIconAndLabel: function () {
        return (
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 mr-1 animate-spin"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>' +
            this.loadingText
        );
    },

    getDefaultButton: function (clickEvent) {
        let self = this;
        let text_button = document.createElement("button");
        text_button.innerHTML = this.getDefaultButtonIconAndLabel();
        text_button.classList.add("link", "text-xs", "flex");
        text_button.type = "button";

        text_button.addEventListener("click", async function (event) {
            let button = event.currentTarget;

            /** Mark the clicked button as loading. */
            button.classList.add("link-default");
            button.disabled = true;
            button.innerHTML = self.getLoadingButtonIconAndLabel();

            try {
                /** Run the translator. */
                await clickEvent();
            } catch (error) {
                console.error("Auto-translator error:", error);
            }

            /** Mark the clicked button as normal. */
            button.classList.remove("link-default");
            button.disabled = false;
            button.innerHTML = self.getDefaultButtonIconAndLabel();
        });

        return text_button;
    },
    getTextButton: function (input, source, target) {
        return this.getDefaultButton(async () => {
            let translation = await this.runTranslator(
                source,
                target,
                input.value,
                false,
            );
            if (translation !== null && translation !== undefined) {
                input.value = translation;
                input.dispatchEvent(new Event("input", { bubbles: true }));
            }
        });
    },
    getTinyMceButton: function (field_name, source, target) {
        return this.getDefaultButton(async () => {
            let tiny = tinymce.get(`tiny_${field_name}`);
            let translation = await this.runTranslator(
                source,
                target,
                tiny.getContent(),
                true,
            );
            if (translation !== null && translation !== undefined) {
                tiny.setContent(translation);
                tiny.setDirty(true);
                tiny.focus();
            }
        });
    },
    getTipTapButton: function (tiptapEditor, source, target) {
        return this.getDefaultButton(async () => {
            let translation = await this.runTranslator(
                source,
                target,
                tiptapEditor.innerHTML,
                true,
            );
            if (translation !== null && translation !== undefined) {
                tiptapEditor.innerHTML = translation;
                tiptapEditor.dispatchEvent(
                    new Event("input", { bubbles: true }),
                );
                tiptapEditor.focus();
            }
        });
    },
    runTranslator: async function (source, target, text, html_handling = true) {
        let response = await this.apiRequest("translate", "POST", {
            source: source,
            target: target,
            text: text,
            html_handling: html_handling,
        });
        return response ? response.text : null;
    },
    apiRequest: async function (path, method, body) {
        try {
            const rawResponse = await fetch(
                "/nova-vendor/auto-translator/" + path,
                {
                    method: method,
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: body ? JSON.stringify(body) : null,
                },
            );

            if (!rawResponse.ok) {
                console.error(
                    "Auto-translator API error:",
                    rawResponse.status,
                    rawResponse.statusText,
                );
                return null;
            }

            return await rawResponse.json();
        } catch (error) {
            console.error("Auto-translator fetch error:", error);
            return null;
        }
    },
};

module.exports = AutoTranslator;
