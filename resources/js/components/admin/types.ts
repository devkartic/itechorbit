import type { Paginated } from '@/lib/types';
export type Value = string | number | boolean | null | string[];
export type Row = Record<string, Value>;
export type Field = { name: string; label: string; type: string; required: boolean; accept?: string };
export type TableFilters = { key: string; page: number; per_page: number; sort: string; direction: 'asc' | 'desc'; status: string };
export type TableResponse = { records: Paginated<Row>; filters: TableFilters };
