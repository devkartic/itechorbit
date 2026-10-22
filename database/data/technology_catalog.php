<?php

// Initial catalog and one-time legacy mapping. Public filters read database records only.
return [
    'laravel' => ['name' => 'Laravel', 'members' => ['Laravel', 'Laravel & PHP']],
    'php' => ['name' => 'PHP', 'members' => ['PHP']],
    'react' => ['name' => 'React', 'members' => ['React']],
    'typescript' => ['name' => 'TypeScript', 'members' => ['TypeScript']],
    'rest-apis' => ['name' => 'REST APIs', 'members' => ['REST APIs', 'REST', 'API integrations', 'Sanctum', 'APIs'], 'groups' => ['apis']],
    'sql' => ['name' => 'SQL', 'members' => ['SQL', 'MySQL', 'Eloquent', 'Query profiling', 'Databases'], 'groups' => ['databases']],
    'inertia' => ['name' => 'Inertia', 'members' => ['Inertia']],
    'seo' => ['name' => 'SEO', 'members' => ['SEO', 'Google Search Console', 'XML sitemaps', 'Structured data', 'Server-side rendering', 'HTTP diagnostics', 'Google Analytics 4', 'Google Tag Manager', 'Data layer', 'Tag Assistant', 'Analytics DebugView', 'Consent-aware measurement'], 'groups' => ['seo']],
    'ai' => ['name' => 'AI', 'members' => ['AI', 'LLM APIs', 'Retrieval (RAG)'], 'groups' => ['ai']],
    'performance' => ['name' => 'Performance', 'members' => ['Performance', 'Core Web Vitals', 'Lighthouse', 'Chrome DevTools', 'Caching & asset delivery', 'Vite'], 'groups' => ['performance']],
    'security' => ['name' => 'Security', 'members' => ['Security', 'OWASP guidance', 'Authentication & authorization', 'Dependency auditing', 'HTTP security headers'], 'groups' => ['security']],
    'testing' => ['name' => 'Testing', 'members' => ['Testing', 'PHPUnit', 'Automated testing', 'Application testing'], 'groups' => ['testing']],
    'cicd' => ['name' => 'CI/CD', 'members' => ['CI/CD']],
    'redis' => ['name' => 'Redis', 'members' => ['Redis', 'Redis where available']],
    'queues' => ['name' => 'Queues', 'members' => ['Queues']],
];
