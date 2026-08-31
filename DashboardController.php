<?php

namespace App\Http\Controllers;

use App\Models\{
    Banner,
    Blog,
    BlogCategory,
    Contact,
    Department,
    Directorate,
    Event,
    EventCategory,
    Faq,
    Gallery,
    GalleryCategory,
    History,
    Publication,
    PublicationCategory,
    Service,
    Team,
    User,
    News
};
use LaravelDaily\LaravelCharts\Classes\LaravelChart;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
public function __construct()
{
    $this->middleware(['auth:admin,web', 'set_guard:admin,web']);
    // $this->middleware('permission:dashboard_access', ['guard' => 'web']); 
}
    public function index()
    {

        try {
            $counts = [
                'Banner' => Banner::count(),
                'Blog' => Blog::count(),
                'News' => News::count(),
                'BlogCategory' => BlogCategory::count(),
                'Contact' => Contact::count(),
                'Department' => Department::count(),
                'Directorate' => Directorate::count(),
                'Event' => Event::count(),
                'EventCategory' => EventCategory::count(),
                'Faq' => Faq::count(),
                'Gallery' => Gallery::count(),
                'Publication' => Publication::count(),
                'PublicationCategory' => PublicationCategory::count(),
                'Service' => Service::count(),
                // 'Team' => Team::count(),
                'User' => User::count(),
            ];
            $settings1 = [
                'chart_title'           => 'Directorates',
                'chart_type'            => 'bar',
                'report_type'           => 'group_by_date',
                'model'                 => 'App\Models\Directorate',
                'group_by_field'        => 'created_at',
                'group_by_period'       => 'day',
                'aggregate_function'    => 'count',
                'filter_field'          => 'created_at',
                'filter_days'           => '10',
                'group_by_field_format' => 'Y-m-d H:i:s',
                'column_class'          => 'col-md-12',
                'entries_number'        => '5',
                'translation_key'       => 'user',
                'continuous_time'       => true,
            ];
            $settings2 = [
                'chart_title'           => 'Departments',
                'chart_type'            => 'bar',
                'report_type'           => 'group_by_date',
                'model'                 => 'App\Models\Department',
                'group_by_field'        => 'created_at',
                'group_by_period'       => 'day',
                'aggregate_function'    => 'count',
                'filter_field'          => 'created_at',
                'filter_days'           => '10',
                'group_by_field_format' => 'Y-m-d H:i:s',
                'column_class'          => 'col-md-12',
                'entries_number'        => '5',
                'translation_key'       => 'user',
                'continuous_time'       => true,
            ];
            $settings3 = [
                'chart_title'           => 'Services',
                'chart_type'            => 'bar',
                'report_type'           => 'group_by_date',
                'model'                 => 'App\Models\Service',
                'group_by_field'        => 'created_at',
                'group_by_period'       => 'day',
                'aggregate_function'    => 'count',
                'filter_field'          => 'created_at',
                'filter_days'           => '10',
                'group_by_field_format' => 'Y-m-d H:i:s',
                'column_class'          => 'col-md-12',
                'entries_number'        => '5',
                'translation_key'       => 'user',
                'continuous_time'       => true,
            ];

            $chart1 = new LaravelChart($settings1, $settings2, $settings3);
            return view('admin.dashboard', compact('counts', 'chart1'));
        } catch (\Throwable $th) {
            $user = auth()->user();
dd($user->roles, $user->getAllPermissions());

            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function profile()
{
    $admin = auth()->user();
    return view('admin.profile', compact('admin'));
}
}
