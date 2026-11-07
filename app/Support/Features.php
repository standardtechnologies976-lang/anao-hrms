<?php

namespace App\Support;

class Features
{
    /**
     * All assignable HRMS modules.
     * key => group, label, route name, icon (Flux/Heroicon name)
     */
    public static function assignable(): array
    {
        return [
            // Registry
            'citizens'         => ['group' => 'Registry',  'label' => 'Master Citizen Index', 'route' => 'citizens.index',        'icon' => 'identification'],
            'appointments'     => ['group' => 'Registry',  'label' => 'Appointments',        'route' => 'appointments.index',    'icon' => 'calendar-days'],

            // Clinical
            'triage'           => ['group' => 'Clinical',  'label' => 'Triage',               'route' => 'triage.index',          'icon' => 'clipboard-document-list'],
            'physician'        => ['group' => 'Clinical',  'label' => 'Physician (CPOE)',     'route' => 'physician.index',       'icon' => 'clipboard-document-check'],
            'nursing'          => ['group' => 'Clinical',  'label' => 'Nursing / Ward',       'route' => 'nursing.index',         'icon' => 'heart'],
            'laboratory'       => ['group' => 'Clinical',  'label' => 'Laboratory',           'route' => 'laboratory.index',      'icon' => 'beaker'],
            'radiology'        => ['group' => 'Clinical',  'label' => 'Radiology',            'route' => 'radiology.index',       'icon' => 'bolt'],
            'pharmacy'         => ['group' => 'Clinical',  'label' => 'Medicine & Dispensing','route' => 'pharmacy.index',        'icon' => 'cube'],

            // Community & Social
            'mswd'             => ['group' => 'Community', 'label' => 'MSWD / Social Services','route' => 'mswd.index',            'icon' => 'hand-raised'],
            'household-survey' => ['group' => 'Community', 'label' => 'Household Survey',      'route' => 'household-survey.index','icon' => 'home-modern'],
            'bhw-portal'       => ['group' => 'Community', 'label' => 'BHW Portal',           'route' => 'bhw-portal.index',      'icon' => 'users'],
            'surveys'          => ['group' => 'Community', 'label' => 'Surveys',              'route' => 'surveys.index',         'icon' => 'clipboard-document-check'],

            // Insight & Tools
            'dashboards'       => ['group' => 'Insight',   'label' => 'Executive Dashboards', 'route' => 'dashboards.index',      'icon' => 'chart-bar'],
            'health-intel'     => ['group' => 'Insight',   'label' => 'Health Intelligence',  'route' => 'health-intel.index',    'icon' => 'cpu-chip'],
            'import-export'    => ['group' => 'Tools',     'label' => 'Import / Export',      'route' => 'import-export.index',   'icon' => 'arrows-right-left'],
        ];
    }

    /** Which feature key a route name belongs to, or null. */
    public static function featureForRoute(?string $name): ?string
    {
        if (! $name) {
            return null;
        }
        // Feature key is the segment before the first dot: citizens.index / citizens.create / citizens.edit -> citizens
        $key = str_contains($name, '.') ? substr($name, 0, strpos($name, '.')) : $name;
        return array_key_exists($key, self::assignable()) ? $key : null;
    }

    /** Assignable features grouped by their 'group' label, preserving order. */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::assignable() as $key => $meta) {
            $groups[$meta['group']][$key] = $meta;
        }
        return $groups;
    }
}
