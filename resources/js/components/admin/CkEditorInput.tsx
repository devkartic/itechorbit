import { useEffect, useMemo, useRef, useState } from 'react';
import { CKEditor } from '@ckeditor/ckeditor5-react';
import { ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough, Link, List, BlockQuote, Code, CodeBlock, RemoveFormat, FontSize, FontFamily, Table, TableToolbar, GeneralHtmlSupport, SourceEditing, WordCount, type EditorConfig } from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';
import { Textarea } from '@/components/ui/input';
import type { EditorProps } from './CkEditorField';

const sizes = ['14', '16', '18', '20', '24', '32'];
const fonts: Record<string, string> = { sans: 'Inter, sans-serif', serif: "Georgia, 'Times New Roman', serif", mono: 'Courier New, Courier, monospace' };

// Keep existing bounded formatting compatible with the public HTML sanitizer.
function editorHtml(html: string, saving = false) {
  const document = new DOMParser().parseFromString(html, 'text/html');
  document.querySelectorAll<HTMLSpanElement>('span').forEach(span => {
    if (saving) {
      const size = span.style.fontSize.replace('px', '');
      if (sizes.includes(size)) { span.dataset.fontSize = size; span.style.removeProperty('font-size'); }
      const family = span.style.fontFamily.toLowerCase();
      const font = family.includes('georgia') ? 'serif' : family.includes('courier') ? 'mono' : family.includes('inter') ? 'sans' : null;
      if (font) { span.dataset.textFont = font; span.style.removeProperty('font-family'); }
      if (!span.getAttribute('style')) span.removeAttribute('style');
    } else {
      if (sizes.includes(span.dataset.fontSize ?? '')) { span.style.fontSize = `${span.dataset.fontSize}px`; span.removeAttribute('data-font-size'); }
      if (fonts[span.dataset.textFont ?? '']) { span.style.fontFamily = fonts[span.dataset.textFont!]; span.removeAttribute('data-text-font'); }
    }
  });
  return document.body.innerHTML;
}

export function CkEditorInput({ id, label, value, required, invalid, disabled = false, onChange }: EditorProps) {
  const editor = useRef<ClassicEditor | null>(null);
  const [failed, setFailed] = useState(false);
  const [words, setWords] = useState(0);
  // Echoing normalized HTML back through CKEditor data would reset the selection after toolbar actions.
  const [data] = useState(() => editorHtml(value));
  const lastValue = useRef(value);
  function update(instance: ClassicEditor) {
    const next = editorHtml(instance.getData(), true);
    lastValue.current = next;
    onChange(next);
  }
  useEffect(() => {
    if (editor.current && value !== lastValue.current) {
      lastValue.current = value;
      editor.current.setData(editorHtml(value));
    }
  }, [value]);
  const config = useMemo<EditorConfig>(() => ({
    licenseKey: 'GPL',
    plugins: [Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough, Link, List, BlockQuote, Code, CodeBlock, RemoveFormat, FontSize, FontFamily, Table, TableToolbar, GeneralHtmlSupport, SourceEditing, WordCount],
    toolbar: { items: ['undo', 'redo', '|', 'heading', 'fontFamily', 'fontSize', '|', 'bold', 'italic', 'underline', 'strikethrough', '|', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'code', 'codeBlock', 'insertTable', 'removeFormat', '|', 'sourceEditing'], shouldNotGroupWhenFull: true },
    heading: { options: [{ model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' }, { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' }, { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }] },
    fontSize: { options: ['default', ...sizes.map(Number)] },
    fontFamily: { options: ['default', ...Object.values(fonts)] },
    link: { defaultProtocol: 'https://', allowedProtocols: ['http', 'https', 'mailto'], addTargetToExternalLinks: true },
    table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
    htmlSupport: { allow: [{ name: /^(span|div|p|h2|h3|h4|ul|ol|li|blockquote|pre|code|table|thead|tbody|tr|th|td|figure|figcaption|img|a)$/, attributes: true, classes: true, styles: true }] },
    wordCount: { onUpdate: statistics => setWords(statistics.words) },
  }), []);
  function attributes(instance: ClassicEditor) {
    const root = instance.editing.view.document.getRoot();
    if (root) instance.editing.view.change(writer => {
      writer.setAttribute('id', id, root);
      writer.setAttribute('aria-label', `${label}${required ? ' *' : ''}`, root);
      writer.setAttribute('aria-labelledby', `${id}-label`, root);
      writer.setAttribute('aria-required', String(!!required), root);
      writer.setAttribute('aria-invalid', String(!!invalid), root);
      writer.setAttribute('aria-describedby', `${id}-help ${id}-error`, root);
    });
  }
  useEffect(() => {
    const instance = editor.current;
    if (!instance) return;
    const root = instance.editing.view.document.getRoot();
    if (root) instance.editing.view.change(writer => writer.setAttribute('aria-invalid', String(!!invalid), root));
  }, [invalid]);
  if (failed) return <><p role="alert" className="form-error">CKEditor could not start. Your content is available as HTML below.</p><Textarea id={id} aria-label={`${label} HTML`} value={value} disabled={disabled} onChange={event => onChange(event.target.value)} /></>;
  return <div className="workspace-ckeditor-field" data-invalid={invalid || undefined} onBlurCapture={event => { if (event.target instanceof HTMLTextAreaElement && editor.current) update(editor.current); }}>
    <CKEditor editor={ClassicEditor} data={data} config={config} disabled={disabled} onReady={instance => { editor.current = instance; attributes(instance); }} onChange={(_, instance) => update(instance)} onAfterDestroy={() => { editor.current = null; }} onError={() => setFailed(true)} />
    <div className="workspace-ckeditor-footer"><span>CKEditor 5</span><span>{words} words</span></div>
    <p id={`${id}-help`} className="workspace-field-help">Use the toolbar to format text, add links and tables, or edit the HTML source.</p>
  </div>;
}
