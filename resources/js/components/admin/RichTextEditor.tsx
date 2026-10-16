import { useEffect, useState } from 'react';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Bold, Italic, Underline, Strikethrough, List, ListOrdered, Quote, Code, Link2, Unlink, Undo2, Redo2, Heading2, Heading3, Eraser } from 'lucide-react';
import { TextFormatting, fontSizes, fontFamilies } from './TextFormatting';
import { Textarea } from '@/components/ui/input';
export function RichTextEditor({ id, label, value, required, invalid, disabled = false, onChange }: { id: string; label: string; value: string; required?: boolean; invalid?: boolean; disabled?: boolean; onChange: (value: string) => void }) {
  const [source, setSource] = useState(() => /<(?:table|img|iframe|video|svg|script|style|object|embed|form)\b|\s(?:style|class)=/i.test(value));
  const [linkOpen, setLinkOpen] = useState(false);
  const [href, setHref] = useState('');
  const [linkError, setLinkError] = useState('');
  const editor = useEditor({
    immediatelyRender: false,
    extensions: [TextFormatting, StarterKit.configure({ heading: { levels: [2, 3] }, link: { openOnClick: false, defaultProtocol: 'https', protocols: ['http', 'https', 'mailto'] } })],
    content: value,
    editorProps: { attributes: { id, role: 'textbox', 'aria-multiline': 'true', 'aria-labelledby': `${id}-label`, 'aria-required': String(!!required), 'aria-invalid': String(!!invalid), 'aria-describedby': `${id}-help ${id}-error`, class: 'workspace-editor-content' } },
    onUpdate: ({ editor }) => onChange(editor.isEmpty ? '' : editor.getHTML()),
  });
  useEffect(() => { editor?.setEditable(!disabled, false); }, [editor, disabled]);
  const active = useEditorState({ editor, selector: ({ editor }) => ({ size: String(editor?.getAttributes('textFormatting').size ?? ''), font: String(editor?.getAttributes('textFormatting').font ?? ''), bold: editor?.isActive('bold'), italic: editor?.isActive('italic'), underline: editor?.isActive('underline'), strike: editor?.isActive('strike'), bulletList: editor?.isActive('bulletList'), orderedList: editor?.isActive('orderedList'), blockquote: editor?.isActive('blockquote'), codeBlock: editor?.isActive('codeBlock'), h2: editor?.isActive('heading', { level: 2 }), h3: editor?.isActive('heading', { level: 3 }), link: editor?.isActive('link'), undo: editor?.can().undo(), redo: editor?.can().redo() }) });
  useEffect(() => {
    if (editor && editor.getHTML() !== value && !editor.isFocused) editor.commands.setContent(value, { emitUpdate: false });
  }, [editor, value]);
  useEffect(() => { editor?.setOptions({ editorProps: { attributes: { id, role: 'textbox', 'aria-multiline': 'true', 'aria-labelledby': `${id}-label`, 'aria-required': String(!!required), 'aria-invalid': String(!!invalid), 'aria-describedby': `${id}-help ${id}-error`, class: 'workspace-editor-content' } } }); }, [editor, id, required, invalid]);
  const tools = editor ? [
    { label: 'Bold', icon: Bold, active: active?.bold, run: () => editor.chain().focus().toggleBold().run() },
    { label: 'Italic', icon: Italic, active: active?.italic, run: () => editor.chain().focus().toggleItalic().run() },
    { label: 'Underline', icon: Underline, active: active?.underline, run: () => editor.chain().focus().toggleUnderline().run() },
    { label: 'Strikethrough', icon: Strikethrough, active: active?.strike, run: () => editor.chain().focus().toggleStrike().run() },
    { label: 'Heading 2', icon: Heading2, active: active?.h2, run: () => editor.chain().focus().toggleHeading({ level: 2 }).run() },
    { label: 'Heading 3', icon: Heading3, active: active?.h3, run: () => editor.chain().focus().toggleHeading({ level: 3 }).run() },
    { label: 'Bullet list', icon: List, active: active?.bulletList, run: () => editor.chain().focus().toggleBulletList().run() },
    { label: 'Numbered list', icon: ListOrdered, active: active?.orderedList, run: () => editor.chain().focus().toggleOrderedList().run() },
    { label: 'Blockquote', icon: Quote, active: active?.blockquote, run: () => editor.chain().focus().toggleBlockquote().run() },
    { label: 'Code block', icon: Code, active: active?.codeBlock, run: () => editor.chain().focus().toggleCodeBlock().run() },
    { label: 'Insert link', icon: Link2, active: active?.link, run: () => { setHref(String(editor.getAttributes('link').href ?? 'https://')); setLinkError(''); setLinkOpen(previous => !previous); } },
    { label: 'Remove link', icon: Unlink, active: false, run: () => editor.chain().focus().unsetLink().run() },
    { label: 'Clear formatting', icon: Eraser, active: false, run: () => editor.chain().focus().unsetAllMarks().clearNodes().run() },
    { label: 'Undo', icon: Undo2, disabled: !active?.undo, run: () => editor.chain().focus().undo().run() },
    { label: 'Redo', icon: Redo2, disabled: !active?.redo, run: () => editor.chain().focus().redo().run() },
  ] : [];
  return <div className="workspace-editor-field"><div className={`workspace-editor ${invalid ? 'is-invalid' : ''}`}><div className="workspace-editor-top"><span>Content editor</span><div role="group" aria-label={`${label} editing mode`}><button type="button" aria-pressed={!source} onClick={() => setSource(false)}>Visual</button><button type="button" aria-pressed={source} onClick={() => setSource(true)}>HTML</button></div></div>{!source && <div className="workspace-editor-toolbar" role="toolbar" aria-label={`${label} formatting`}><label className="workspace-editor-select"><span className="sr-only">{label} font size</span><select aria-label={`${label} font size`} disabled={disabled || !editor} value={active?.size ?? ''} onChange={event => editor?.chain().focus().setMark('textFormatting', { size: event.target.value || null }).run()}><option value="">Default size</option>{fontSizes.map(size => <option key={size} value={size}>{size}px</option>)}</select></label><label className="workspace-editor-select"><span className="sr-only">{label} font style</span><select aria-label={`${label} font style`} disabled={disabled || !editor} value={active?.font ?? ''} onChange={event => editor?.chain().focus().setMark('textFormatting', { font: event.target.value || null }).run()}><option value="">Theme font</option>{fontFamilies.map(font => <option key={font} value={font}>{font === 'sans' ? 'Sans serif' : font === 'serif' ? 'Serif' : 'Monospace'}</option>)}</select></label>{tools.map(tool => <button key={tool.label} type="button" title={tool.label} aria-label={tool.label} aria-pressed={!!tool.active} disabled={disabled || !!tool.disabled} onClick={tool.run}><tool.icon size={16} /></button>)}</div>}
      {source ? <Textarea disabled={disabled} id={id} aria-label={`${label} HTML`} aria-describedby={`${id}-help ${id}-error`} aria-invalid={invalid} value={value} onChange={event => onChange(event.target.value)} className="workspace-editor-source" spellCheck={false} /> : editor ? <EditorContent editor={editor} /> : <div className="workspace-editor-loading">Loading editor…</div>}
      {linkOpen && !source && <div className="workspace-link-editor"><label htmlFor={`${id}-link`}>Link URL</label><div><input id={`${id}-link`} value={href} onChange={event => setHref(event.target.value)} placeholder="https://example.com" /><button type="button" onClick={() => { if (!/^(https?:\/\/[^\s]+|mailto:[^\s]+)$/i.test(href)) { setLinkError('Enter a complete https://, http:// or mailto: link.'); return; } editor?.chain().focus().extendMarkRange('link').setLink({ href }).run(); setLinkOpen(false); }}>Apply link</button><button type="button" onClick={() => setLinkOpen(false)}>Cancel</button></div>{linkError && <p role="alert">{linkError}</p>}</div>}
      <div className="workspace-editor-footer"><span>{source ? 'HTML source · formatting is preserved as entered' : 'Rich text · keyboard shortcuts supported'}</span><span>{value.replace(/<[^>]*>/g, ' ').trim().split(/\s+/).filter(Boolean).length} words</span></div></div><p id={`${id}-help`} className="workspace-field-help">Format your content visually or edit the HTML source. Existing advanced HTML opens in source mode to preserve it.</p></div>;
}
