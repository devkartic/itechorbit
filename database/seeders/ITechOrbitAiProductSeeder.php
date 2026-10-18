<?php

namespace Database\Seeders;

use App\Models\Admin\WebsiteProduct;
use Illuminate\Database\Seeder;

class ITechOrbitAiProductSeeder extends Seeder
{
    public function run(): void
    {
        // Create once so future runs preserve changes made in the admin editor.
        WebsiteProduct::firstOrCreate(['slug' => 'itechorbit-ai-assistant'], [
            'title' => 'iTechOrbit AI Assistant',
            'short_title' => 'iTechOrbit AI Assistant',
            'subtitle' => 'Turn website questions into helpful conversations. Give visitors an AI assistant that answers from your published content and guides them toward your services.',
            'descriptions' => '<h2>A helpful first conversation on your website</h2><p>Your visitors arrive with questions. Help them understand what you offer, find relevant information and take the next step with iTechOrbit AI Assistant.</p><p>Built for website owners, the assistant uses your approved website content and FAQs to answer questions, with links to supporting pages. Visitors can explore your services in a conversation while your existing contact form remains the place to start a business inquiry.</p><h2>See it on this website</h2><p>Open the iTechOrbit AI widget to explore how a website assistant can work. Contact us for a demo or quote tailored to your website, content and expected usage.</p><h2>A setup that fits your website</h2><p>The widget can be embedded on approved website origins. We review your website and hosting requirements, connect an AI provider, configure your knowledge sources and test representative visitor questions before handover.</p><h2>Clear expectations and running costs</h2><p>Answers depend on the quality of your published content and the selected AI provider, and may be imperfect. Hosting, provider API usage and ongoing support are scoped separately according to the agreed setup. API access and billing are managed with the chosen provider.</p>',
            'problem' => 'Visitors may leave when they cannot quickly find answers about your services, process or contact options. Answering the same introductory questions also takes time from your team.',
            'solution' => 'Give visitors a conversational way to explore your website. iTechOrbit AI Assistant retrieves relevant approved content, answers with source links and offers a route to contact your business when more help is needed.',
            'deliverables' => [
                'Embeddable website assistant with approved-origin configuration',
                'Website knowledge and FAQ setup with source links in answers',
                'Brand accent, welcome message and light/dark widget appearance',
                'Gemini, OpenAI or compatible provider configuration',
                'Admin access for knowledge, synchronization and conversation review',
                'Mobile and desktop checks with representative visitor questions',
                'Installation guidance and operational handover',
            ],
            'technologies' => ['Website widget', 'Laravel', 'Knowledge retrieval (RAG)', 'Google Gemini', 'OpenAI', 'Compatible model APIs'],
            'approach' => [
                'Share your website URL, common visitor questions and the outcomes you want to support.',
                'Agree on integration, approved content, provider choice and expected running costs.',
                'Install the widget and configure its appearance, FAQs and knowledge sources.',
                'Review answers, source links and contact routes on desktop and mobile.',
                'Publish the assistant and receive guidance for keeping its knowledge up to date.',
            ],
            'use_cases' => [
                ['title' => 'Service and agency websites', 'description' => 'Help potential clients understand your capabilities, delivery process and how to start a project.'],
                ['title' => 'Product and SaaS websites', 'description' => 'Make published product information and FAQs easier to explore before visitors contact your team.'],
                ['title' => 'Business and information websites', 'description' => 'Guide visitors to relevant published pages and contact information through a simple conversation.'],
            ],
            'status' => 1, 'display_order' => 0, 'icon' => 'sparkles', 'highlight' => false,
            'price' => null, 'currency' => null, 'delivery_time' => null,
        ]);
    }
}
