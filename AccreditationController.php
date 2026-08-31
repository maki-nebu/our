<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Accreditation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AccreditationController extends Controller
{
    public function index(Request $request)
    {
        $locale = app()->getLocale(); // 'en' or 'am'

        // Base query: only active
        $query = Accreditation::query()->where('is_active', 1);

        // Search (title/organization/description)
        if ($search = $request->input('q')) {
            $query->where(function($q) use ($search, $locale) {
                $q->where("title_$locale", 'like', "%{$search}%")
                  ->orWhere("issuing_organization_{$locale}", 'like', "%{$search}%")
                  ->orWhere("description_{$locale}", 'like', "%{$search}%");
            });
        }

        // Filter by issuing organization
        if ($org = $request->input('org')) {
            $query->where("issuing_organization_{$locale}", $org);
        }

        // Filter: soon to expire (within X days)
        if ($request->has('expiring_within')) {
            $days = (int) $request->input('expiring_within', 90);
            $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
        }

        // Sort: newest, oldest, soonest-expiry, featured
        $sort = $request->input('sort', 'order');
        switch ($sort) {
            case 'newest':
                $query->orderBy('issue_date', 'desc');
                break;
            case 'oldest':
                $query->orderBy('issue_date', 'asc');
                break;
            case 'expiry_soon':
                $query->orderBy('expiry_date', 'asc');
                break;
            case 'featured':
                $query->orderByDesc('is_featured')->orderBy('order');
                break;
            default:
                $query->orderBy('order')->orderByDesc('is_featured');
        }

        // Pagination
        $accreditations = $query->paginate(9)->withQueryString();

        // Stats for header
        $total = Accreditation::where('is_active',1)->count();
        $featuredCount = Accreditation::where('is_active',1)->where('is_featured',1)->count();
        $expiringSoon = Accreditation::where('is_active',1)
            ->whereBetween('expiry_date', [now(), now()->addDays(90)])
            ->count();

        // Unique issuing organizations for filter dropdown
        $organizations = Accreditation::select("issuing_organization_$locale as name")
            ->where('is_active',1)
            ->groupBy("issuing_organization_$locale")
            ->pluck('name')->filter()->values();

        return view('front.accreditations.index', compact(
            'accreditations','total','featuredCount','expiringSoon','organizations'
        ));
    }
}
