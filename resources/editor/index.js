import {
    Alignment, Autoformat, BlockQuote, Bold, ButtonView, ClassicEditor, Code, CodeBlock,
    Essentials, FindAndReplace, FontBackgroundColor, FontColor, FontFamily, FontSize, Fullscreen,
    Heading, Highlight, HorizontalLine, IconImage, Image, ImageCaption, ImageResize, ImageStyle,
    ImageTextAlternative, ImageToolbar, Indent, IndentBlock, Italic, Link, List, ListProperties,
    Paragraph, PasteFromOffice, RemoveFormat, SelectAll, SourceEditing, SpecialCharacters,
    SpecialCharactersEssentials, Strikethrough, Subscript, Superscript, Table, TableCaption,
    TableCellProperties, TableColumnResize, TableProperties, TableToolbar, Underline,
} from 'ckeditor5';
import vietnamese from 'ckeditor5/translations/vi.js';
import 'ckeditor5/ckeditor5.css';

function libraryButton(editor) {
    editor.ui.componentFactory.add('cmsImage', locale => {
        const button = new ButtonView(locale);
        button.set({ label: 'Chèn ảnh từ thư viện', icon: IconImage, tooltip: true });
        button.bind('isEnabled').to(editor.commands.get('insertImage'), 'isEnabled');
        button.on('execute', () => window.cmsChooseImage(url => {
            editor.execute('insertImage', { source: url });
            editor.editing.view.focus();
        }));
        return button;
    });
}

const commonPlugins = [Autoformat, Essentials, Paragraph, Bold, Italic, Underline, Link, List];
const miniToolbar = ['undo', 'redo', '|', 'bold', 'italic', 'underline', 'link', '|', 'bulletedList', 'numberedList'];
const presets = {
    mini: { plugins: commonPlugins, toolbar: miniToolbar },
    full: {
        plugins: [
            ...commonPlugins, Heading, FontFamily, FontSize, FontColor, FontBackgroundColor,
            Highlight, Strikethrough, Subscript, Superscript, Code, RemoveFormat, Alignment,
            ListProperties, Indent, IndentBlock, BlockQuote, CodeBlock, HorizontalLine,
            SpecialCharacters, SpecialCharactersEssentials, Image, ImageToolbar, ImageStyle,
            ImageCaption, ImageTextAlternative, ImageResize, Table, TableToolbar, TableCaption,
            TableProperties, TableCellProperties, TableColumnResize, PasteFromOffice,
            FindAndReplace, SelectAll, SourceEditing, Fullscreen,
        ],
        toolbar: [
            'undo', 'redo', 'findAndReplace', 'selectAll', '|',
            'heading', 'fontFamily', 'fontSize', '|',
            'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', 'code',
            'fontColor', 'fontBackgroundColor', 'highlight', 'removeFormat', '-',
            'alignment', 'bulletedList', 'numberedList', 'outdent', 'indent', '|',
            'link', 'cmsImage', 'insertTable', 'blockQuote', 'codeBlock', 'horizontalLine', 'specialCharacters', '|',
            'sourceEditing', 'fullscreen',
        ],
        fontSize: { options: [10, 12, 14, 'default', 18, 20, 24, 28, 32, 36, 48] },
        list: { properties: { styles: true, startIndex: true, reversed: true } },
        image: { toolbar: ['imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|',
            'resizeImage', 'toggleImageCaption', 'imageTextAlternative'] },
        table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', '|',
            'tableProperties', 'tableCellProperties', 'toggleTableCaption'] },
    },
};

const licenseKey = document.querySelector('[data-editor-license]')?.dataset.editorLicense;
for (const source of document.querySelectorAll('textarea.ckeditor-mini, textarea.ckeditor-full')) {
    const full = source.classList.contains('ckeditor-full');
    const preset = full ? presets.full : presets.mini;
    const useLibrary = full && typeof window.cmsChooseImage === 'function';
    ClassicEditor.create({
        ...preset,
        attachTo: source,
        initialData: source.value,
        licenseKey,
        language: 'vi',
        translations: [vietnamese],
        extraPlugins: useLibrary ? [libraryButton] : [],
        toolbar: { items: preset.toolbar.filter(item => item !== 'cmsImage' || useLibrary), shouldNotGroupWhenFull: true },
    }).then(editor => {
        const sync = () => { source.value = editor.getData(); };
        editor.model.document.on('change:data', sync);
        source.form?.addEventListener('submit', sync);
    }).catch(error => {
        console.error(error);
        const status = source.form?.querySelector('[data-editor-status]');
        if (status) status.textContent = 'CKEditor chưa khởi động. Có thể nhập HTML trong ô nội dung.';
    });
}
