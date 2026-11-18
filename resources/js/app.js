import Alpine from 'alpinejs';

const richTextEditors = document.querySelectorAll('[data-rich-text-editor]');

if (richTextEditors.length > 0) {
    import('./policy-editor').then(({ initializeRichTextEditor }) => {
        richTextEditors.forEach(initializeRichTextEditor);
    });
}

window.Alpine = Alpine;

Alpine.start();
