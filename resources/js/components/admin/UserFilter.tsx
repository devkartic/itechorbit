import { useEffect, useId, useRef, useState, type KeyboardEvent } from 'react';
import { Check, ChevronDown, Search } from 'lucide-react';
import type { FilterUser } from './types';

export function UserFilter({ users, value, onChange }: { users: FilterUser[]; value: string; onChange: (value: string) => void }) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');
  const [active, setActive] = useState(0);
  const root = useRef<HTMLDivElement>(null);
  const trigger = useRef<HTMLButtonElement>(null);
  const input = useRef<HTMLInputElement>(null);
  const list = useRef<HTMLDivElement>(null);
  const id = useId();
  const options = [
    { value: 'mine', label: 'My records' },
    { value: 'all', label: 'All users' },
    ...users.map(user => ({ value: String(user.id), label: `${user.name} (#${user.id})` })),
  ];
  const matches = options.filter(option => option.label.toLocaleLowerCase().includes(search.trim().toLocaleLowerCase()));
  const selected = options.find(option => option.value === value)?.label ?? 'Select a user';

  useEffect(() => {
    if (!open) return;
    input.current?.focus();
    const outside = (event: PointerEvent) => {
      if (!root.current?.contains(event.target as Node)) setOpen(false);
    };
    document.addEventListener('pointerdown', outside);
    return () => document.removeEventListener('pointerdown', outside);
  }, [open]);
  useEffect(() => { list.current?.children[active]?.scrollIntoView({ block: 'nearest' }); }, [active, search]);

  function close() { setOpen(false); trigger.current?.focus(); }
  function choose(next: string) { onChange(next); close(); }
  function toggle() { setSearch(''); setActive(0); setOpen(previous => !previous); }
  function keys(event: KeyboardEvent) {
    if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); close(); }
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      setActive(previous => matches.length ? (previous + (event.key === 'ArrowDown' ? 1 : -1) + matches.length) % matches.length : 0);
    }
    if (event.key === 'Enter') { event.preventDefault(); if (matches[active]) choose(matches[active].value); }
  }

  return <div className="workspace-user-picker" ref={root} onBlur={event => { if (!event.currentTarget.contains(event.relatedTarget)) setOpen(false); }}>
    <button ref={trigger} type="button" className="workspace-filter workspace-user-filter" role="combobox" aria-label="Filter by user" aria-expanded={open} aria-controls={`${id}-list`} aria-haspopup="listbox" onClick={toggle} onKeyDown={event => { if (['ArrowDown', 'ArrowUp'].includes(event.key)) { event.preventDefault(); setSearch(''); setActive(0); setOpen(true); } }}>
      <span>Records for</span><strong>{selected}</strong><ChevronDown size={15} aria-hidden="true" />
    </button>
    {open && <div className="workspace-user-picker-popup" onKeyDown={keys}>
      <label className="workspace-user-picker-search"><Search size={16} aria-hidden="true" /><input ref={input} type="search" role="combobox" aria-label="Search users" aria-expanded="true" aria-controls={`${id}-list`} aria-autocomplete="list" aria-activedescendant={matches[active] ? `${id}-option-${active}` : undefined} maxLength={200} placeholder="Search by name or user ID…" value={search} onChange={event => { setSearch(event.target.value); setActive(0); }} /></label>
      <div ref={list} id={`${id}-list`} role="listbox" aria-label="Users" className="workspace-user-picker-options">
        {matches.map((option, index) => <button key={option.value} id={`${id}-option-${index}`} type="button" role="option" aria-selected={value === option.value} data-value={option.value} data-active={active === index} tabIndex={-1} onMouseDown={event => event.preventDefault()} onClick={() => choose(option.value)}><span>{option.label}</span>{value === option.value && <Check size={15} aria-hidden="true" />}</button>)}
      </div>
      {!matches.length && <p className="workspace-user-picker-empty" role="status">No users found.</p>}
    </div>}
  </div>;
}
