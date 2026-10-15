<?php

namespace App\Services;

use App\Models\User;

class PortfolioPresentation
{
    public const TEMPLATES = [
        'studio' => ['name' => 'Studio', 'description' => 'A confident violet-accented bento layout for versatile full-stack engineers.'],
        'executive' => ['name' => 'Executive', 'description' => 'A refined navy resume with a persistent profile rail and a clear career narrative.'],
        'blueprint' => ['name' => 'Blueprint', 'description' => 'A dark technical canvas with mint accents and project-first engineering detail.'],
        'editorial' => ['name' => 'Editorial', 'description' => 'An ivory editorial layout with expressive serif typography and generous reading space.'],
        'spotlight' => ['name' => 'Spotlight', 'description' => 'A bold cobalt introduction and a visual showcase for product-focused engineers.'],
    ];

    public function __construct(private PublicContent $content) {}

    public function template(User $user): string
    {
        return array_key_exists($user->portfolio_template ?? '', self::TEMPLATES) ? $user->portfolio_template : 'studio';
    }

    public function data(User $user): array
    {
        $user->load(['basic', 'skills' => fn ($q) => $q->where('status', 1), 'projects' => fn ($q) => $q->where('status', 1),
            'services' => fn ($q) => $q->where('status', 1), 'experiences' => fn ($q) => $q->where('status', 1), 'social_links' => fn ($q) => $q->where('status', 1)]);
        $basic = $user->basic?->status ? $user->basic : null;

        return [
            'profile' => ['name' => $user->name, 'portfolioId' => $user->portfolio_id,
                'designation' => $basic?->designation, 'introduction' => $this->content->html($basic?->self_introduction),
                'objective' => $this->content->html($basic?->career_objective), 'additional' => $this->content->html($basic?->additional_information),
                'image' => $this->content->media($basic?->profile_image_path),
                'cv' => $basic?->cv_public_status ? $this->content->media($basic?->cv_path) : null,
                'skills' => $user->skills->pluck('name'),
                'services' => $user->services->map(fn ($service) => [...$service->only(['name', 'title']), 'about_service' => $this->content->html($service->about_service)]),
                'experiences' => $user->experiences->map(fn ($experience) => [...$experience->only(['designation', 'organization', 'join_date', 'resign_date']), 'responsibilities' => $this->content->html($experience->responsibilities), 'descriptions' => $this->content->html($experience->descriptions)]),
                'socials' => $user->social_links->map(fn ($social) => ['name' => $social->name, 'url' => $this->content->externalUrl($social->social_url)])->filter(fn ($social) => $social['url'])->values(),
            ],
            'projects' => $user->projects->map(fn ($project) => $this->content->project($project)),
            'template' => $this->template($user),
        ];
    }
}
