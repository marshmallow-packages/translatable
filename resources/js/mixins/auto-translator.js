let AutoTranslator = {
    buttonText: 'Translate with DeepL',
    init: function(Nova) {
        let self = this;
        (async () => {
            let settings = await self.apiRequest('settings', 'GET');
            this.buttonText = settings.button_text;
            if (settings.active) {
                self.initAutoTranslator(Nova);
            }
        })();
    },
    initAutoTranslator: function (Nova) {
        let self = this;
        Nova.$on('resource-loaded', function (data) {
            if (data.mode == 'update') {
                setTimeout(() => {
                    const translator = document.getElementById('mm-translation-language-toggle');
                    if (!translator) {
                        return;
                    }

                    const translating = translator.getAttribute('data-translating');
                    const source = translator.getAttribute('data-source');
                    const target = translator.getAttribute('data-target');

                    if (translating == false || translating == 'false' || !source || !target) {
                        return;
                    }

                    (async () => {
                        let fields = await self.apiRequest('fields', 'POST', {
                            resourceName: data.resourceName,
                            resourceId: data.resourceId,
                        });

                        for (const [field_name, field_type] of Object.entries(fields.fields)) {
                            switch (field_type) {
                                case 'Laravel\\Nova\\Fields\\Text':
                                    input = document.querySelector(`[dusk="${field_name}"]`);
                                    input.closest('div[index]').querySelectorAll('div')[1].append(
                                        self.getTextButton(input, source, target)
                                    );
                                    break;

                                case 'Marshmallow\\Nova\\TinyMCE\\TinyMCE':
                                    input = document.querySelector(`[id="tiny_${field_name}"]`);
                                    input.closest('div').append(
                                        self.getTinyMceButton(field_name, source, target)
                                    );
                                    break;

                                default:
                                    console.error(`Sorry, ${field_type} is not implemented yet.`);
                                }
                        }
                    })();
                }, 200);
            }
        });
    },
    getDefaultButton: function (clickEvent) {
        let text_button = document.createElement('button');
        text_button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" /></svg>' + this.buttonText;

        text_button.classList.add('link-default', 'text-xs', 'flex', 'w-full');
        text_button.type = 'button';

        text_button.addEventListener('click', clickEvent);

        return text_button;
    },
    getTextButton: function (input, source, target) {
        return this.getDefaultButton(async () => {
            translation = await this.runTranslator(
                source,
                target,
                input.value
            );
            input.value = translation;
        });
    },
    getTinyMceButton: function (field_name, source, target) {
        return this.getDefaultButton(async () => {
            let tiny = tinymce.get(`tiny_${field_name}`);
            translation = await this.runTranslator(
                source,
                target,
                tiny.getContent()
            );
            tiny.setContent(translation);
        });
    },
    runTranslator: async function(source, target, text) {
        let response = await this.apiRequest('translate', 'POST', {
            source: source,
            target: target,
            text: text,
        });
        return response.text;
    },
    apiRequest: async function (path, mehod, body) {
        const rawResponse = await fetch(`/nova-vendor/auto-translator/${path}`, {
            method: mehod,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: body ? JSON.stringify(body) : null,
        });
        return await rawResponse.json();
    }
}

module.exports = AutoTranslator;
