import '../../../css/portfolio.css';
import { ArrowDown, ArrowUpRight, Download, Code2, Braces, Layers3, MoveUpRight, Fingerprint, Workflow, Circle, ArrowRight } from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { ContactForm } from '@/components/ContactForm';
import type { Project } from '@/lib/types';
import { dateLabel } from '@/lib/utils';

export type PortfolioTemplate = 'studio' | 'executive' | 'blueprint' | 'editorial' | 'spotlight';
export type Profile = { name: string; portfolioId: string | null; designation?: string | null; introduction: string; objective?: string; additional: string; image: string | null; cv: string | null; skills: string[]; services: { name: string; title: string; about_service: string }[]; experiences: { designation: string; organization: string; responsibilities: string; join_date: string | null; resign_date: string | null; descriptions: string }[]; socials: { name: string; url: string }[] };
export type PortfolioData = { profile: Profile; projects: Project[]; template: PortfolioTemplate };
const names = { studio: 'Studio', executive: 'Executive', blueprint: 'Blueprint', editorial: 'Editorial', spotlight: 'Spotlight' };
const initials = (name: string) => name.trim().split(/\s+/).slice(0, 2).map(word => word[0]).join('');

function Rich({ html, className = '' }: { html?: string; className?: string }) {
  return html ? <div className={`pf-rich ${className}`} dangerouslySetInnerHTML={{ __html: html }} /> : null;
}
function Section({ id, title, label, children, className = '' }: { id: string; title: string; label: string; children: ReactNode; className?: string }) {
  return <section className={`pf-section ${className}`} id={id}><div className="pf-section-heading"><div><p className="pf-kicker"><span />{label}</p><h2>{title}</h2></div><span className="pf-section-glyph" aria-hidden="true"><ArrowUpRight size={25} strokeWidth={1} /></span></div>{children}</section>;
}
function Actions({ profile }: { profile: Profile }) {
  return <div className="pf-actions">{profile.cv && <a href={profile.cv} download className="pf-button"><span>Download CV</span><Download size={16} /></a>}<a href="#connect" className={`pf-button ${profile.cv ? 'pf-button-secondary' : ''}`}><span>Discuss an opportunity</span><ArrowUpRight size={17} /></a></div>;
}
function Socials({ profile }: { profile: Profile }) {
  return profile.socials.length ? <div className="pf-socials">{profile.socials.map(social => <a key={social.url} href={social.url} target="_blank" rel="noopener noreferrer">{social.name}<MoveUpRight size={13} /></a>)}</div> : null;
}
function SectionNav({ profile, projects }: { profile: Profile; projects: Project[] }) {
  const links = [profile.skills.length > 0 && ['expertise', 'Skills'], profile.experiences.length > 0 && ['experience', 'Experience'], projects.length > 0 && ['projects', 'Work'], ['connect', 'Connect']].filter(Boolean) as string[][];
  return <div className="pf-local-header"><a href="#portfolio-top" className="pf-personal-brand" aria-label={`${profile.name} portfolio top`}><span>{initials(profile.name)}</span><span>PERSONAL<br />PORTFOLIO</span></a><nav aria-label="Portfolio sections">{links.map(([id, label]) => <a key={id} href={`#${id}`}>{label}{id === 'connect' && <ArrowUpRight size={13} />}</a>)}</nav></div>;
}
function Identity({ profile, projects, technical = false }: { profile: Profile; projects: Project[]; technical?: boolean }) {
  return <div className={`pf-identity-stage ${technical ? 'is-technical' : ''}`}>
    <div className="pf-orbit pf-orbit-one" aria-hidden="true" /><div className="pf-orbit pf-orbit-two" aria-hidden="true" />
    <div className="pf-identity-card"><div className="pf-identity-bar"><span><Fingerprint size={14} />{technical ? 'EXPERTISE MAP' : 'THE PERSON BEHIND THE WORK'}</span><span className="pf-decor-dot" aria-hidden="true" /></div>
      {profile.image ? <img className="pf-portrait" src={profile.image} width={440} height={480} alt={profile.name} fetchPriority="high" /> : <div className="pf-monogram" aria-hidden="true"><span className="pf-monogram-grid" /><Braces className="pf-monogram-icon" size={30} strokeWidth={1} /><span className="pf-initials">{initials(profile.name)}</span><span className="pf-monogram-caption">IDEAS → SYSTEMS → EXPERIENCES</span></div>}
      <div className="pf-identity-footer"><span>{profile.designation || 'Software engineering'}</span><ArrowDown size={16} /></div>
    </div>
    {profile.skills.length > 0 && <div className="pf-floating-chip pf-floating-stack"><span className="pf-floating-icon"><Code2 size={19} /></span><div><small>CORE TOOLKIT</small><strong>{profile.skills.slice(0, 2).join(' + ')}</strong></div></div>}
    {projects.length > 0 && <div className="pf-floating-chip pf-floating-work"><span className="pf-work-count">{String(projects.length).padStart(2, '0')}</span><div><small>THE WORK ARCHIVE</small><strong>Published projects</strong></div><ArrowUpRight size={17} /></div>}
    {technical && profile.skills.length > 2 && <div className="pf-technical-nodes">{profile.skills.slice(2, 5).map(skill => <span key={skill}><Circle size={7} fill="currentColor" />{skill}</span>)}</div>}
  </div>;
}
function Hero({ profile, projects, variant, children }: { profile: Profile; projects: Project[]; variant: PortfolioTemplate; children?: ReactNode }) {
  return <header className="pf-hero"><div className="pf-hero-copy"><div className="pf-hero-label"><span className="pf-label-line" /><span>Professional portfolio</span></div><h1>{profile.name}<span className="pf-name-dot">.</span></h1>{profile.designation && <p className="pf-candidate-role">{profile.designation}</p>}<Rich html={profile.objective} className="pf-objective" /><Actions profile={profile} /><Socials profile={profile} />{children}</div><Identity profile={profile} projects={projects} technical={variant === 'blueprint'} /></header>;
}
function Skills({ profile }: { profile: Profile }) {
  return profile.skills.length ? <Section id="expertise" label="The toolkit" title="Technology & expertise" className="pf-profile-row pf-expertise-row"><div className="pf-profile-content"><div className="pf-skills">{profile.skills.map(skill => <span key={skill}><Code2 size={16} strokeWidth={1.5} aria-hidden="true" />{skill}</span>)}</div></div></Section> : null;
}
function About({ profile }: { profile: Profile }) {
  return profile.introduction || profile.additional ? <Section id="about" label="The person behind the work" title="About me" className="pf-profile-row pf-about-row"><div className="pf-profile-content"><Rich html={profile.introduction} /><Rich html={profile.additional} className="pf-additional" /></div></Section> : null;
}
function Experience({ profile }: { profile: Profile }) {
  return profile.experiences.length ? <Section id="experience" label="Career journey" title="Experience that shapes the work"><div className="pf-timeline">{profile.experiences.map((experience, i) => <article key={i} className="pf-experience"><div className="pf-experience-index"><span>{String(i + 1).padStart(2, '0')}</span><p className="pf-date">{experience.join_date ? dateLabel(experience.join_date) : ''}{experience.join_date && ' — '}{experience.resign_date ? dateLabel(experience.resign_date) : 'Present'}</p></div><div className="pf-experience-body"><div className="pf-experience-top"><div><h3>{experience.designation}</h3><p className="pf-organization">{experience.organization}</p></div>{!experience.resign_date && <span className="pf-current-role"><span />Current role</span>}</div><Rich html={experience.responsibilities} /><Rich html={experience.descriptions} /></div></article>)}</div></Section> : null;
}
function ProjectVisual() {
  return <div className="pf-project-accent" aria-hidden="true"><span className="pf-project-accent-icon"><Code2 size={25} strokeWidth={1.4} /></span><span className="pf-project-accent-lines"><i /><i /><i /></span><span className="pf-project-accent-orbit" /></div>;
}
function Projects({ projects }: { projects: Project[] }) {
  const [category, setCategory] = useState<string | null>(null);
  const categories = [...new Set(projects.map(project => project.category).filter((item): item is string => !!item))];
  const visible = category ? projects.filter(project => project.category === category) : projects;
  return projects.length ? <Section id="projects" label="Selected work" title="Built with purpose.">
    <div className="pf-project-toolbar"><p>Ideas made tangible.<span> Explore the work, the role, and the technology.</span></p>{categories.length > 1 && <div className="pf-project-filters" role="group" aria-label="Filter portfolio projects"><button type="button" aria-pressed={category === null} onClick={() => setCategory(null)}>All work <span>{projects.length}</span></button>{categories.map(item => <button key={item} type="button" aria-pressed={category === item} onClick={() => setCategory(item)}>{item}</button>)}</div>}</div>
    <p className="sr-only" role="status">{visible.length} portfolio projects shown</p>
    <div className="pf-projects">{visible.map(project => <article key={project.id} className={`pf-project ${project.image ? 'has-project-image' : 'is-text-project'}`}>{project.image ? <a className="pf-project-image" href={project.url} aria-label={`View ${project.name}`}><img src={project.image} width={720} height={480} loading="lazy" alt={`${project.name} project screenshot`} /><span className="pf-project-arrow"><ArrowUpRight size={20} /></span></a> : <ProjectVisual /> }<div className="pf-project-content"><div className="pf-project-meta"><p className="pf-kicker">{project.category || 'Engineering project'}</p><span>{String(projects.indexOf(project) + 1).padStart(2, '0')}</span></div><h3><a href={project.url}>{project.name}</a></h3>{project.role && <p className="pf-project-role"><span>MY CONTRIBUTION</span>{project.role}</p>}<p className="pf-project-excerpt">{project.excerpt}</p><div className="pf-project-tags">{project.technologies.map(technology => <span key={technology}>{technology}</span>)}</div><a className="pf-text-link" href={project.url}>Explore the project <ArrowRight size={16} /></a></div></article>)}</div>
  </Section> : null;
}
function Services({ profile }: { profile: Profile }) {
  return profile.services.length ? <Section id="services" label="How I contribute" title="From challenge to solution"><div className="pf-services">{profile.services.map((service, i) => <article key={i}><div className="pf-service-top"><span><Layers3 size={22} strokeWidth={1.4} /></span><small>{String(i + 1).padStart(2, '0')} / EXPERTISE</small></div><h3>{service.title || service.name}</h3><Rich html={service.about_service} /></article>)}</div></Section> : null;
}
function Facts({ profile, projects }: { profile: Profile; projects: Project[] }) {
  const facts = [[projects.length, 'Published projects'], [profile.skills.length, 'Technologies'], [profile.experiences.length, 'Experience entries']].filter(([count]) => Number(count) > 0);
  return facts.length ? <div className="pf-facts" aria-label="Portfolio overview"><div className="pf-facts-label"><Workflow size={24} strokeWidth={1.3} /><span>THE PROFILE<br /><strong>AT A GLANCE</strong></span></div>{facts.map(([count, label]) => <div key={label}><strong>{String(count).padStart(2, '0')}</strong><span>{label}</span></div>)}<ArrowUpRight className="pf-facts-arrow" size={28} strokeWidth={1.2} aria-hidden="true" /></div> : null;
}
function Contact({ profile, preview }: { profile: Profile; preview: boolean }) {
  return <section className="pf-contact pf-personal-contact" id="connect"><div className="pf-contact-copy"><p className="pf-kicker">Contact {profile.name}</p><h2>Let’s discuss<br /><span>your opportunity.</span></h2><p>Hiring for your team? Share the role, what you’re building and the next steps directly with {profile.name}.</p><Socials profile={profile} />{preview && <p>Form preview — messages cannot be sent from this preview.</p>}</div><div className="pf-contact-form">{profile.portfolioId ? <ContactForm recipient={{ name: profile.name, portfolio_id: profile.portfolioId }} preview={preview} /> : <p>Contact details are not available for this profile.</p>}</div></section>;
}
export function PortfolioView({ profile, projects, template, preview = false }: PortfolioData & { preview?: boolean }) {
  const style = names[template] ? template : 'studio';
  const canvas = useRef<HTMLDivElement>(null);
  useEffect(() => {
    if (!canvas.current || !('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver(entries => {
      for (const entry of entries) {
        if (entry.isIntersecting) { entry.target.classList.add('is-in-view'); observer.unobserve(entry.target); }
      }
    }, { threshold: 0.08 });
    canvas.current.querySelectorAll('.pf-section, .pf-facts, .pf-contact').forEach(section => observer.observe(section));
    return () => observer.disconnect();
  }, [style]);
  const about = <About profile={profile} />; const experience = <Experience profile={profile} />; const work = <Projects projects={projects} />; const skills = <Skills profile={profile} />; const services = <Services profile={profile} />;
  const hero = <Hero profile={profile} projects={projects} variant={style}>{projects.length > 0 && <a href="#projects" className="pf-scroll-link"><span><ArrowDown size={15} /></span>Explore the work</a>}</Hero>;
  return <div ref={canvas} id="portfolio-top" className={`portfolio-view pf-${style}`} data-template={style} onClick={preview ? event => { if ((event.target as Element).closest('a')) event.preventDefault(); } : undefined}>
    <div className="shell pf-shell"><SectionNav profile={profile} projects={projects} />
      {style === 'executive' ? <div className="pf-executive-stage">{hero}<div className="pf-executive-signoff"><span>ENGINEERING / PERSPECTIVE / EXECUTION</span><Fingerprint size={22} strokeWidth={1} /></div></div>
        : style === 'blueprint' ? <><div className="pf-terminal-bar"><span><span className="pf-terminal-dot" /> engineer.profile</span><span>HUMAN AT THE CORE / ENGINEERING AT THE EDGES</span></div>{hero}</>
        : style === 'editorial' ? <><div className="pf-editorial-masthead"><span>THE ENGINEERING EDITION</span><span>INDEPENDENT THINKING. INTENTIONAL CRAFT.</span></div>{hero}</>
        : style === 'spotlight' ? <div className="pf-spotlight-stage">{hero}<div className="pf-spotlight-ticker" aria-hidden="true"><span>ENGINEER.</span><span>CREATE.</span><span>ITERATE.</span><ArrowUpRight size={48} strokeWidth={1} /></div></div>
        : hero}
      {(style === 'studio' || style === 'executive') && <Facts profile={profile} projects={projects} />}
      <div className="pf-profile-sections">{skills}{experience}{work}{services}{about}</div>
      <Contact profile={profile} preview={preview} /><footer className="pf-signature"><span><Fingerprint size={15} />{profile.name} / Professional portfolio</span><a href="/register">Create your free portfolio <ArrowUpRight size={12} /></a></footer>
    </div>
  </div>;
}
