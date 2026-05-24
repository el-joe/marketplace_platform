/**
 * rich-editor.js — Tiptap standalone rich text editor.
 *
 * Requires (install via yarn/npm):
 *   yarn add @tiptap/core @tiptap/starter-kit @tiptap/extension-link
 *            @tiptap/extension-image @tiptap/extension-placeholder
 *
 * Initialised on: [data-rich-editor]
 * Toolbar buttons carry: [data-tiptap-action] and optionally [data-level]
 */
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import $ from 'jquery';

const instances = new Map(); // editorName → Editor instance

function buildExtensions(config, uploadUrl) {
    const exts = [
        StarterKit.configure({
            heading: config.heading ? { levels: [2, 3, 4] } : false,
            blockquote: config.blockquote ?? true,
            code: config.code ?? true,
            codeBlock: config.codeBlock ?? true,
            bold: config.bold ?? true,
            italic: config.italic ?? true,
            bulletList: config.bulletList ?? true,
            orderedList: config.orderedList ?? true,
        }),
        Placeholder.configure({
            placeholder: 'Start typing…',
        }),
    ];

    if (config.link) {
        exts.push(Link.configure({ openOnClick: false, autolink: true }));
    }

    if (config.image && uploadUrl) {
        exts.push(Image.configure({ inline: false }));
    }

    return exts;
}

function syncToHidden(editor, name) {
    const $hidden = $(`#${name}-hidden`);
    $hidden.val(editor.isEmpty ? '' : editor.getHTML());
}

function initEditor(el) {
    const $el = $(el);
    const name = $el.data('rich-editor');
    const config = $el.data('toolbar') || {};
    const uploadUrl = $el.data('upload-url');
    const $hidden = $(`#${name}-hidden`);
    const initContent = $hidden.val() || '';

    if (instances.has(name)) {
        instances.get(name).destroy();
    }

    const editor = new Editor({
        element: el,
        extensions: buildExtensions(config, uploadUrl),
        content: initContent,
        onUpdate({ editor }) {
            syncToHidden(editor, name);
        },
    });

    instances.set(name, editor);

    // Toolbar button handlers
    $(el).closest('.space-y-1, div')
        .find('[data-tiptap-action]')
        .off('click.tiptap')
        .on('click.tiptap', function (e) {
            e.preventDefault();
            const action = $(this).data('tiptap-action');
            const level = parseInt($(this).data('level'), 10) || undefined;

            switch (action) {
                case 'bold': editor.chain().focus().toggleBold().run(); break;
                case 'italic': editor.chain().focus().toggleItalic().run(); break;
                case 'bulletList': editor.chain().focus().toggleBulletList().run(); break;
                case 'orderedList': editor.chain().focus().toggleOrderedList().run(); break;
                case 'blockquote': editor.chain().focus().toggleBlockquote().run(); break;
                case 'heading': editor.chain().focus().toggleHeading({ level }).run(); break;
                case 'code': editor.chain().focus().toggleCode().run(); break;
                case 'undo': editor.chain().focus().undo().run(); break;
                case 'redo': editor.chain().focus().redo().run(); break;
                case 'link': {
                    const previousUrl = editor.getAttributes('link').href || '';
                    const url = window.prompt('Enter URL', previousUrl);
                    if (url === null) break;
                    if (url === '') {
                        editor.chain().focus().extendMarkRange('link').unsetLink().run();
                    } else {
                        editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
                    }
                    break;
                }
                case 'image': {
                    if (!uploadUrl) break;
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.onchange = function () {
                        const file = this.files[0];
                        if (!file) return;
                        const fd = new FormData();
                        fd.append('file', file);
                        $.ajax({
                            url: uploadUrl, method: 'POST', data: fd,
                            processData: false, contentType: false,
                        }).done(function (res) {
                            if (res.data?.url) {
                                editor.chain().focus().setImage({ src: res.data.url }).run();
                            }
                        });
                    };
                    input.click();
                    break;
                }
            }

            // Update active state on toolbar buttons
            updateToolbarState(el, editor);
        });

    // Reflect active marks on toolbar
    editor.on('selectionUpdate', () => updateToolbarState(el, editor));
    editor.on('transaction', () => updateToolbarState(el, editor));
}

function updateToolbarState(editorEl, editor) {
    $(editorEl).closest('.space-y-1, div').find('[data-tiptap-action]').each(function () {
        const action = $(this).data('tiptap-action');
        const level = parseInt($(this).data('level'), 10) || undefined;
        let isActive = false;

        if (action === 'heading' && level) {
            isActive = editor.isActive('heading', { level });
        } else if (['bold', 'italic', 'bulletList', 'orderedList', 'blockquote', 'link', 'code'].includes(action)) {
            isActive = editor.isActive(action);
        }

        $(this).toggleClass('bg-gray-200 text-gray-900', isActive);
    });
}

/* =========================================================
   Init on DOM ready and expose for dynamic content
   ========================================================= */
function initRichEditors($scope) {
    $scope = $scope || $('body');
    $scope.find('[data-rich-editor]').each(function () {
        initEditor(this);
    });
}

window.initRichEditors = initRichEditors;

$(function () {
    initRichEditors($('body'));
});
