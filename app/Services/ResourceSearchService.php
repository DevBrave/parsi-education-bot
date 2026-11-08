<?php

namespace App\Services;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ResourceSearchService
{
    public function query(string $query): Builder
    {
        $normalizedQuery = mb_strtolower(trim($query));
        $baseQuery = Resource::with(['course', 'resourceType', 'files'])
            ->where('status', 'approved')
            ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM resource_files WHERE resource_files.resource_id = resources.id) THEN 0 ELSE 1 END ASC')
            ->latest();


        if ($normalizedQuery === '') {
            return $baseQuery;
        }
        $tokens = preg_split('/\s+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($tokens)) {
            $tokens = [$normalizedQuery];
        }

        // Course acronym mapping (e.g., 'os' -> Operating Systems, 'ca' -> Computer Architecture)
        $courseAcronymMap = Cache::remember('courses:acronym_map', now()->addHours(12), function () {
            $map = [];
            foreach (\App\Models\Course::all(['id', 'name']) as $c) {
                $words = preg_split('/[\s\-_]+/', $c->name);
                $acronym = '';
                foreach ($words as $w) {
                    $w = trim($w);
                    if ($w !== '' && !in_array(strtolower($w), ['and', 'for', 'the', 'of', 'in', 'to', 'a', 'an', '&'])) {
                        $acronym .= mb_substr($w, 0, 1);
                    }
                }
                if (mb_strlen($acronym) >= 2) {
                    $map[mb_strtolower($acronym)][] = $c->id;
                }
            }
            return $map;
        });

        foreach ($tokens as $token) {
            $variants = [$token];
            if (str_ends_with($token, 's') && mb_strlen($token) > 3) {
                $variants[] = rtrim($token, 's');
            }

            $matchingCourseIds = $courseAcronymMap[$token] ?? [];

            $baseQuery->where(function ($sub) use ($variants, $token, $matchingCourseIds) {
                if (!empty($matchingCourseIds)) {
                    $sub->orWhereIn('course_id', $matchingCourseIds);
                }

                foreach ($variants as $v) {
                    $sub->orWhere('title', 'like', "%{$v}%")
                        ->orWhere('description', 'like', "%{$v}%")
                        ->orWhereHas('course', function ($cq) use ($v, $token) {
                            $cq->where('name', 'like', "%{$v}%")
                                ->orWhere('course_code', 'like', "%{$v}%");
                            if (is_numeric($token)) {
                                $cq->orWhere('year', (int) $token);
                            }
                        })
                        ->orWhereHas('resourceType', function ($rq) use ($v) {
                            $rq->where('code', 'like', "%{$v}%")
                                ->orWhere('display_name', 'like', "%{$v}%");
                        })
                        ->orWhereHas('files', function ($fq) use ($v) {
                            $fq->where('original_filename', 'like', "%{$v}%");
                        });
                }
            });
        }


        return $baseQuery;
    }
}
