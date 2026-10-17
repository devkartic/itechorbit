import { useRef, useState, type KeyboardEvent } from 'react';
import { Code2, Database, Layers3, ArrowUpRight, ShieldCheck, Terminal } from 'lucide-react';
const layers = [
  { id: 'interface', label: 'Interface', technology: 'React + TypeScript', icon: Layers3,
    title: 'An interface that makes sense.', description: 'Accessible components. Clear interactions. A shared design language.',
    file: 'ProjectCard.tsx', code: 'type Project = {\n  name: string;\n  url: string;\n};\n\nexport function ProjectCard({ name, url }: Project) {\n  return <a href={url}>{name}</a>;\n}' },
  { id: 'backend', label: 'Application', technology: 'Laravel + PHP', icon: Code2,
    title: 'Business logic. Clear boundaries.', description: 'Validate the input. Model the domain. Keep decisions explicit.',
    file: 'routes/web.php', code: '<?php\n\nuse App\\Http\\Controllers\\ProjectController;\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::middleware("auth")->group(function () {\n    Route::resource("projects", ProjectController::class);\n});' },
  { id: 'data', label: 'Data', technology: 'SQL + Eloquent', icon: Database,
    title: 'The right data. At the right time.', description: 'Scoped queries, eager-loaded relationships and predictable pagination.',
    file: 'ArticleQuery.php', code: '$articles = Post::query()\n    ->where("status", 1)\n    ->where("is_private", 0)\n    ->with(["user", "category"])\n    ->latest()\n    ->paginate(12);' },
];
export function ArchitecturePreview() {
  const [selected, setSelected] = useState(1);
  const buttons = useRef<(HTMLButtonElement | null)[]>([]);
  function navigate(event: KeyboardEvent<HTMLButtonElement>, index: number) {
    let next: number;
    if (event.key === 'ArrowRight') next = (index + 1) % layers.length;
    else if (event.key === 'ArrowLeft') next = (index + layers.length - 1) % layers.length;
    else if (event.key === 'Home') next = 0;
    else if (event.key === 'End') next = layers.length - 1;
    else return;
    event.preventDefault(); setSelected(next); buttons.current[next]?.focus();
  }
  return <div className="hero-visual architecture-preview">
    <div className="visual-orbit orbit-one" aria-hidden="true" /><div className="visual-orbit orbit-two" aria-hidden="true" />
    <span className="orbital-point point-one" aria-hidden="true" /><span className="orbital-point point-two" aria-hidden="true" />
    <div className="visual-badge"><span className="status-dot" />Explore the architecture</div>
    <div className="studio-window"><div className="studio-toolbar"><span className="window-dots" aria-hidden="true"><i /><i /><i /></span><span>itechorbit / engineering studio</span><Code2 size={17} /></div>
      <div className="studio-body"><div className="studio-caption"><span>ARCHITECTURE EXPLORER</span><span>0{selected + 1} / 03</span></div>
        <div role="tablist" aria-label="Architecture layers" className="architecture-tabs">{layers.map((item, index) => <button key={item.id} ref={node => { buttons.current[index] = node; }} id={`layer-tab-${item.id}`} role="tab" aria-selected={selected === index} aria-controls={`layer-panel-${item.id}`} tabIndex={selected === index ? 0 : -1} onClick={() => setSelected(index)} onKeyDown={event => navigate(event, index)}><item.icon size={16} />{item.label}</button>)}</div>
        {layers.map((item, index) => <div key={item.id} id={`layer-panel-${item.id}`} role="tabpanel" aria-labelledby={`layer-tab-${item.id}`} tabIndex={0} hidden={selected !== index}>
          <h2>{item.title}</h2><p className="architecture-description">{item.description}</p>
          <div className="studio-code"><div className="code-tab"><Terminal size={14} /><span>{item.file}</span><span className="code-language">{item.technology}</span></div><pre><code>{item.code}</code></pre></div>
        </div>)}
        <p className="architecture-footnote"><ShieldCheck size={14} />Illustrative patterns. Real engineering principles.</p>
      </div>
    </div>
    <div className="floating-note"><span><Layers3 size={20} /></span><div><strong>Every layer. One clear vision.</strong><p>Select a layer to see how it connects.</p></div><ArrowUpRight size={18} /></div>
  </div>;
}
