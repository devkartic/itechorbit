<?php

namespace App\Services;

use App\Models\Admin\Link;
use App\Models\Admin\Permission;

class SidebarNavigation
{
    public function items(): array
    {
        $user = auth()->user();
        if (! $user || ! $user->status || ! $user->hasVerifiedEmail()) {
            return [];
        }

        return Link::with('module.menu')->where('status', 1)->get()
            ->filter(function ($link) use ($user) {
                if (! $link->module?->status || ! $link->module?->menu?->status) {
                    return false;
                }
                $path = trim($link->url, '/');
                if (! preg_match('#^[a-z][a-z0-9-]*(?:/[a-z0-9-]+)*$#D', $path)) {
                    return false;
                }
                // Presentation is configurable; the existing access policies are not.
                if ($path === 'workspace/email-notices' || str_starts_with($path, 'workspace/email-notices/')) {
                    return in_array((int) $user->role_id, [1, 2], true);
                }
                if (in_array($path, ['home', 'workspace/portfolio-templates'], true)) {
                    return true;
                }

                return Permission::has_permission($link->id, 'index');
            })->map(fn ($link) => [
                'id' => $link->id, 'label' => $link->name, 'url' => '/'.trim($link->url, '/'),
                'group' => $link->module->menu->name, 'groupId' => $link->module->menu->id, 'groupIcon' => $this->icon($link->module->menu->fa_icon),
                'module' => $link->module->name, 'moduleId' => $link->module->id, 'moduleIcon' => $this->icon($link->module->fa_icon),
                'icon' => $this->icon($link->fa_icon), 'order' => $link->order_number,
                '_ordering' => [$link->module->menu->order_number, $link->module->order_number, $link->order_number],
            ])->sort(function ($left, $right) {
                foreach (['group', 'module', 'label'] as $level => $name) {
                    $leftOrder = $left['_ordering'][$level];
                    $rightOrder = $right['_ordering'][$level];
                    $comparison = (($leftOrder === null) <=> ($rightOrder === null))
                        ?: ((int) $leftOrder <=> (int) $rightOrder)
                        ?: strnatcasecmp($left[$name], $right[$name])
                        ?: strcmp($left[$name], $right[$name]);
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                    // Identically named sections/modules remain separate and contiguous.
                    if ($name !== 'label') {
                        $comparison = $left[$name.'Id'] <=> $right[$name.'Id'];
                        if ($comparison !== 0) {
                            return $comparison;
                        }
                    }
                }

                return strcmp($left['url'], $right['url']) ?: ($left['id'] <=> $right['id']);
            })->map(function ($item) {
                unset($item['_ordering']);

                return $item;
            })->values()->all();
    }

    private function icon(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        preg_match_all('/\bfa-([a-z0-9-]+)\b/', $value, $matches);
        foreach ($matches[1] as $name) {
            if (! in_array($name, ['solid', 'regular', 'brands', 'light', 'thin', 'duotone', 'fw'], true)) {
                return $name;
            }
        }

        return preg_match('/^[a-z][a-z0-9-]*$/D', $value) ? $value : null;
    }
}
