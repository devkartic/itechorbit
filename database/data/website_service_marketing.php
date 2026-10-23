<?php

$original = require __DIR__.'/website_services.php';
$offers = [
    'laravel-development' => [
        'order' => 60,
        'summary' => 'Build a Laravel application around your business, with dependable workflows, secure access and a foundation your team can maintain.',
        'headline' => 'Software that supports the way your business works',
        'overview' => 'Turn a business requirement into a focused, dependable application. We design and build Laravel software around your users, data and day-to-day workflows, with clear responsibilities and an architecture that can evolve as your business grows.',
        'outcome' => 'From discovery through delivery, you receive reviewable progress, relevant automated tests and clear documentation. Integrations, roles, reporting and deployment requirements are agreed before implementation so the finished application solves the right problem.',
        'deliverables' => ['Requirements and workflow discovery', 'Application architecture and data modeling', 'Responsive interfaces and role-based access', 'Agreed business workflows and integrations', 'Automated tests, deployment guidance and developer handover'],
        'useCases' => [
            ['title' => 'Customer and partner portals', 'description' => 'Give customers or partners secure access to the information and actions relevant to them.'],
            ['title' => 'Internal business tools', 'description' => 'Replace repetitive administration with a focused application built around your operating processes.'],
            ['title' => 'New digital products', 'description' => 'Build an initial product with clearly scoped features and a maintainable foundation for future releases.'],
        ],
    ],
    'custom-web-applications' => [
        'order' => 70,
        'summary' => 'Replace disconnected tools and manual processes with a responsive web application designed around your customers and your team.',
        'headline' => 'Bring your ideas and workflows into one useful application',
        'overview' => 'When spreadsheets and off-the-shelf tools no longer fit, a custom web application can bring your processes together. We translate your requirements into practical user journeys, connected data and interfaces that work across desktop and mobile.',
        'outcome' => 'The engagement covers the agreed frontend and backend workflows, integrations and acceptance scenarios. Your team receives an application it can operate and extend, together with testing evidence and a documented handover.',
        'deliverables' => ['Discovery and user-journey mapping', 'Responsive interface design and implementation', 'Backend workflows, validation and access controls', 'Agreed third-party integrations', 'Acceptance testing and technical handover'],
        'useCases' => [
            ['title' => 'Client-facing platforms', 'description' => 'Offer customers a clear, secure way to interact with your services online.'],
            ['title' => 'Operational dashboards', 'description' => 'Bring important records, actions and reporting into a shared workspace.'],
            ['title' => 'Workflow automation', 'description' => 'Reduce repeated data entry and connect the steps your team currently manages by hand.'],
        ],
    ],
    'ai-workflow-integration' => [
        'order' => 80,
        'summary' => 'Put AI to work on a useful business task with a focused pilot, connected knowledge and results your team can evaluate.',
        'headline' => 'Start with a useful workflow and prove its value',
        'overview' => 'Explore AI features that fit your existing application and the information your team is allowed to use. We help you choose a focused task, connect an appropriate model provider and build a pilot around representative questions, documents or operating workflows.',
        'outcome' => 'You receive an integration with defined boundaries, practical evaluation criteria and a clear account of provider costs, latency and failure cases. Review steps and existing access controls guide how outputs are used before the scope expands.',
        'deliverables' => ['Use-case discovery and a scoped proof of concept', 'Model API integration with existing workflows', 'Approved knowledge retrieval or document processing where appropriate', 'Representative evaluation cases and review controls', 'Usage, running-cost and operational handover guidance'],
        'useCases' => [
            ['title' => 'Knowledge assistants', 'description' => 'Help users explore approved documentation through answers with supporting source references.'],
            ['title' => 'Document workflows', 'description' => 'Extract, classify or summarize information for a person to review before it enters a business process.'],
            ['title' => 'Assisted operations', 'description' => 'Draft responses, organize inquiries and suggest next steps within the tools your team already uses.'],
        ],
    ],
    'laravel-modernization' => [
        'order' => 90,
        'summary' => 'Move an aging Laravel application forward with controlled upgrades, stronger tests and a practical plan for continued development.',
        'headline' => 'Modernize with a clear view of what must keep working',
        'overview' => 'An aging application can make routine changes slow and uncertain. We review your framework, dependencies and critical workflows, then plan upgrades and focused refactoring around the behavior your business depends on.',
        'outcome' => 'Changes are delivered in manageable stages with regression coverage, staging checks and release guidance. The handover explains compatibility decisions, completed improvements and the next steps for maintaining the application.',
        'deliverables' => ['Application, dependency and compatibility assessment', 'Prioritized upgrade and refactoring roadmap', 'Regression coverage for important workflows', 'Agreed framework and dependency upgrades', 'Staging verification, release and rollback guidance'],
        'useCases' => [
            ['title' => 'Aging Laravel applications', 'description' => 'Plan a controlled path from an older framework and dependency stack toward maintainable versions.'],
            ['title' => 'Difficult-to-change codebases', 'description' => 'Reduce fragile coupling and add checks around the workflows your team changes most often.'],
            ['title' => 'Inherited business systems', 'description' => 'Understand the application before extending it, with documented risks and a staged improvement plan.'],
        ],
    ],
    'api-development' => [
        'order' => 100,
        'summary' => 'Connect your applications and services with documented APIs, dependable integrations and clear handling of errors and data.',
        'headline' => 'Make your systems work together reliably',
        'overview' => 'Connect your website, application and external services without relying on fragile manual processes. We design APIs and integrations around clear data contracts, appropriate authentication and the business workflows they need to support.',
        'outcome' => 'The implementation includes agreed validation, error handling and integration checks. Documentation and example requests help your team understand the interface, operate it and extend it as requirements change.',
        'deliverables' => ['Integration discovery and API contract design', 'Authenticated endpoints and request validation', 'Agreed third-party service connections', 'Error handling, retries and webhook verification where relevant', 'Integration tests, API documentation and handover'],
        'useCases' => [
            ['title' => 'Connected business tools', 'description' => 'Exchange records and actions between your application and the services your team depends on.'],
            ['title' => 'Frontend and mobile backends', 'description' => 'Provide documented, validated interfaces for client applications.'],
            ['title' => 'Webhook-driven workflows', 'description' => 'Handle external events with clear verification, processing and failure-recovery behavior.'],
        ],
    ],
    'enterprise-software' => [
        'order' => 110,
        'summary' => 'Bring complex operations into a secure, connected business system with clear roles, consistent workflows and useful reporting.',
        'headline' => 'Give growing operations a system that fits',
        'overview' => 'When important processes are spread across disconnected tools, teams lose time and visibility. We design business applications and ERP modules around your operating rules, responsibilities and reporting needs.',
        'outcome' => 'Work is scoped into reviewable modules with agreed permissions, data ownership and integration boundaries. Acceptance checks and operational documentation help your team introduce the system in controlled stages.',
        'deliverables' => ['Business-process discovery and module planning', 'Data models, role permissions and approval workflows', 'Agreed operational modules and interfaces', 'Reporting and integration implementation', 'Acceptance testing, release planning and operational handover'],
        'useCases' => [
            ['title' => 'Multi-team operations', 'description' => 'Give different teams the access and workflows they need while keeping shared records consistent.'],
            ['title' => 'Custom ERP modules', 'description' => 'Implement a focused business module that fits your existing systems and operating rules.'],
            ['title' => 'Approval and reporting workflows', 'description' => 'Make responsibilities, progress and important business information easier to track.'],
        ],
    ],
    'performance-optimization' => [
        'order' => 120,
        'summary' => 'Make demanding application workflows more responsive by profiling backend requests, background jobs, integrations and resource usage.',
        'headline' => 'Find the application bottlenecks behind slow workflows',
        'overview' => 'Some delays begin deeper than the browser. We investigate slow application requests, repeated work, inefficient data access and long-running processes to identify what is limiting a useful customer or business workflow.',
        'outcome' => 'Improvements are prioritized using representative workloads and verified through repeatable measurements. The work can include caching, query patterns, queue design and integration behavior, followed by practical monitoring recommendations.',
        'deliverables' => ['Application profiling and a representative performance baseline', 'Prioritized bottleneck analysis', 'Agreed request, caching and data-access improvements', 'Background-job and integration optimization where relevant', 'Before-and-after verification and monitoring recommendations'],
        'useCases' => [
            ['title' => 'Slow administrative workflows', 'description' => 'Investigate delays in dashboards, reporting, searches and other business-critical screens.'],
            ['title' => 'Growing traffic and workloads', 'description' => 'Reduce unnecessary application work before expanding infrastructure.'],
            ['title' => 'Long-running background processes', 'description' => 'Review jobs, imports and integrations that take too long or recover poorly from failures.'],
        ],
    ],
    'database-optimization' => [
        'order' => 130,
        'summary' => 'Improve database-heavy workflows with query profiling, better indexing and safer data-access patterns matched to your workload.',
        'headline' => 'Get more from the data your application already uses',
        'overview' => 'Slow queries and inefficient ORM access can affect an entire application. We review representative database workloads and execution plans to identify expensive operations, missing indexes and unnecessary data access.',
        'outcome' => 'Recommendations and agreed changes are tested against relevant queries and application behavior. You receive evidence of the improvement, migration considerations and guidance for monitoring database performance as the dataset grows.',
        'deliverables' => ['Query, schema and workload assessment', 'Execution-plan and index review', 'Agreed query and ORM improvements', 'Safe migration and data-preservation guidance', 'Before-and-after measurements and maintenance recommendations'],
        'useCases' => [
            ['title' => 'Slow searches and reports', 'description' => 'Investigate expensive query patterns behind important searches, filters and reporting screens.'],
            ['title' => 'Growing datasets', 'description' => 'Review indexing and access patterns before increasing data volume makes existing delays worse.'],
            ['title' => 'Inefficient ORM usage', 'description' => 'Reduce repeated queries, unnecessary loading and data-access patterns that add avoidable work.'],
        ],
    ],
    'maintenance-support' => [
        'order' => 140,
        'summary' => 'Keep your website and application dependable with planned updates, practical bug fixing and ongoing technical care.',
        'headline' => 'Keep useful software working as your business evolves',
        'overview' => 'A successful launch is the start of an application’s working life. We help you manage defects, dependency changes and small improvements through an agreed support scope and a clear, prioritized backlog.',
        'outcome' => 'Support work is delivered with relevant checks, release notes and practical communication about progress and remaining issues. Review frequency, access and response expectations are agreed around your application and operating needs.',
        'deliverables' => ['Application health and maintenance review', 'Prioritized bug fixes and small improvements', 'Agreed dependency and framework updates', 'Regression checks and release documentation', 'Operational recommendations and an ongoing improvement backlog'],
        'useCases' => [
            ['title' => 'Established business websites', 'description' => 'Keep important journeys dependable while making focused improvements over time.'],
            ['title' => 'Applications without an internal team', 'description' => 'Provide a clear technical point of contact for maintenance and agreed development work.'],
            ['title' => 'Post-launch improvements', 'description' => 'Turn feedback and operational findings into a prioritized, tested improvement plan.'],
        ],
    ],
];

$services = [];
foreach ($offers as $slug => $offer) {
    $base = $original[$slug];
    $services[$slug] = [
        'title' => $base['title'], 'short_title' => $base['short'], 'subtitle' => $offer['summary'],
        'descriptions' => '<h2>'.e($offer['headline']).'</h2><p>'.e($offer['overview']).'</p><h2>A clear delivery and handover</h2><p>'.e($offer['outcome']).'</p><p>Tell us about your goals and current application to discuss a scoped proposal and practical next steps.</p>',
        'problem' => $base['problem'], 'solution' => $base['solution'],
        'deliverables' => $offer['deliverables'], 'technologies' => $base['technologies'],
        'approach' => [
            'Understand your business goals, current systems and important user journeys.',
            'Review the application and agree on scope, priorities and acceptance criteria.',
            'Deliver the agreed work in reviewable stages with clear progress updates.',
            'Verify the relevant workflows and prepare the agreed release or handover.',
            'Document the outcome, operating considerations and useful next steps.',
        ],
        'use_cases' => $offer['useCases'], 'display_order' => $offer['order'],
    ];
}

return $services;
