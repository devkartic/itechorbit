export type Seo = { title: string; description: string; canonical: string; noindex?: boolean; type?: string; image?: string | null; imageAlt?: string };
export type Shared = { recaptcha?: { enabled: boolean; siteKey: string; version?: 'v2' | 'v3' }; publicPage?: PublicPageContent | null; site: { name: string; url: string; aiService?: { title: string; slug: string } | null }; auth: { user: { id: number; name: string; publicProfileUrl?: string | null } | null; csrfToken?: string }; flash: { success?: string; status?: string }; [key: string]: unknown };
export type Article = { id: number; title: string; url: string; excerpt: string; image: string | null; category: { id: number; name: string } | null; author: { name: string }; tags: string[]; date: string; readingMinutes: number; body?: string; codebase?: string; toc?: { id: string; title: string; level: number }[]; updatedAt?: string };
export type Project = { id: number; name: string; category: string | null; role: string | null; technologies: string[]; image: string | null; url: string; excerpt: string; description?: string; contributions?: string; liveUrl?: string | null };
export type Technology = { id: number; name: string; slug: string; url: string };
export type Service = { imageWidth?: number | null; imageHeight?: number | null; description: string; image: string | null; slug: string; title: string; short: string; icon: string; summary: string; problem: string | null; solution: string | null; deliverables: string[]; technologies: string[]; technologyIds?: number[]; technologyLinks?: Technology[]; url: string; highlight?: boolean; approach?: string[] | null; useCases?: { title: string; description: string }[] | null };
export type Paginated<T> = { data: T[]; current_page: number; last_page: number; total: number; links: { url: string | null; label: string; active: boolean }[] };

export type Product = Service & { price: string | null; currency: string | null; deliveryTime: string | null };

export type ServiceSummary = Omit<Service, 'description' | 'problem' | 'solution' | 'approach' | 'imageWidth' | 'imageHeight'>;
export type ProductSummary = Omit<Product, 'description' | 'problem' | 'solution' | 'approach' | 'imageWidth' | 'imageHeight'>;

export type PublicPageContent = { heading: string; introduction: string; cta_label: string | null; cta_url: string | null };
