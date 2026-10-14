import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
export function cn(...inputs: ClassValue[]) { return twMerge(clsx(inputs)); }
export function jsonLd(value: unknown) { return JSON.stringify(value).replace(/</g, '\\u003c'); }
export function dateLabel(value: string) { return value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeZone: 'UTC' }).format(new Date(value)) : ''; }
export function paginationLabel(label: string) { return label.replace(/&laquo;/g, '‹').replace(/&raquo;/g, '›').replace(/<[^>]*>/g, ''); }
