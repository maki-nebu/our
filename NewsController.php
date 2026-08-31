<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Display a listing of news with categories, featured and popular news.
     */
public function index(Request $request)
{
    // Optional: filter by category slug
    $categorySlug = $request->query('category');

    // Main news list with category + comments count
    $newsQuery = News::with(['category:id,name_en,name_am'])
        ->withCount(['comments' => function ($q) {
            $q->where('status', 1); // count only approved comments
        }])
        ->where('is_published', true);

    if ($categorySlug) {
        $newsQuery->whereHas('category', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    $news = $newsQuery->latest('published_at')->paginate(12);

    // Featured stories (take 2) - only if not filtering by category
    $featuredNews = collect();
    if (!$categorySlug) {
        $featuredNews = News::with(['category:id,name_en,name_am'])
            ->withCount(['comments' => function ($q) {
                $q->where('status', 1);
            }])
            ->where('is_published', true)
            ->where('is_featured', true)
            ->latest('published_at')
            ->take(2)
            ->get();
    }

    // Popular news (top 5 by views) - cache this for better performance
    $popularNews = News::with(['category:id,name_en,name_am'])
        ->withCount(['comments' => function ($q) {
            $q->where('status', 1);
        }])
        ->where('is_published', true)
        ->orderByDesc('views')
        ->take(5)
        ->get();

    // Fetch categories dynamically with published news count
    $categories = NewsCategory::withCount(['news' => function ($q) {
        $q->where('is_published', true);
    }])->select('id', 'name_en', 'name_am', 'slug')->get();

    return view('front.news', compact('news', 'categories', 'featuredNews', 'popularNews'));
}


    public function publicIndex()
    {
        $news = News::with('category')->orderBy('created_at', 'desc')->paginate(10);

        return view('admin.news.index', compact('news'));
    }

    /**
     * Show the form for creating a new news article.
     */
    public function create()
    {
        $categories = NewsCategory::all();
        return view('admin.news.create', compact('categories'));
    }

    /**
     * Store a newly created news article.
     */
    public function store(Request $request)
{
    $request->validate([
        'title_en'     => 'required|string|max:255',
        'title_am'     => 'required|string|max:255',
        'excerpt_en'   => 'required|string|max:500',
        'excerpt_am'   => 'required|string|max:500',
        'content_en'   => 'required|string',
        'content_am'   => 'required|string',
        'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'category_id'  => 'required|integer|exists:news_categories,id',
        'published_at' => 'nullable|date',
        'is_published' => 'nullable|boolean',
        'is_featured'  => 'nullable|boolean', // ✅ add this
    ]);

    $data = [
        'title_en'     => $request->title_en,
        'title_am'     => $request->title_am,
        'excerpt_en'   => $request->excerpt_en,
        'excerpt_am'   => $request->excerpt_am,
        'content_en'   => $request->content_en,
        'content_am'   => $request->content_am,
        'category_id'  => $request->category_id,
        'is_published' => $request->has('is_published') ? 1 : 0,
        'is_featured'  => $request->has('is_featured') ? 1 : 0, // ✅ store featured toggle
        'published_at' => $request->published_at ?? null,
        'views'        => 0,
    ];

    // Handle image upload
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . $image->getClientOriginalName();
        $image->storeAs('public/news', $imageName);
        $data['image'] = 'news/' . $imageName;
    }

    // Generate unique slug
    $slug = Str::slug($request->title_en);
    $originalSlug = $slug;
    $count = 1;
    while (News::where('slug', $slug)->exists()) {
        $slug = $originalSlug . '-' . $count;
        $count++;
    }
    $data['slug'] = $slug;

    News::create($data);

    return redirect()->route('admin.news.index')->with('success', 'News created successfully!');
}

    /**
     * Show the form for editing a news article.
     */
    public function edit(News $news)
    {
        $categories = NewsCategory::all();
        return view('admin.news.edit', compact('news', 'categories'));
    }

    /**
     * Update a news article.
     */
public function update(Request $request, News $news)
{
    // 1️⃣ Validate input
    $request->validate([
        'title_en'     => 'required|string|max:255',
        'title_am'     => 'required|string|max:255',
        'excerpt_en'   => 'required|string|max:500',
        'excerpt_am'   => 'required|string|max:500',
        'content_en'   => 'required|string',
        'content_am'   => 'required|string',
        'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'category_id'  => 'required|integer|exists:news_categories,id',
        'published_at' => 'nullable|date',
        'is_published' => 'required|boolean',
        'is_featured'  => 'required|boolean',
    ]);

    // 2️⃣ Collect data for update
    $data = $request->only([
        'title_en', 'title_am', 'excerpt_en', 'excerpt_am',
        'content_en', 'content_am', 'category_id', 'published_at'
    ]);

    // Boolean fields
    $data['is_published'] = (bool) $request->input('is_published');
    $data['is_featured']  = (bool) $request->input('is_featured');

    // 3️⃣ Handle image upload
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '_' . $image->getClientOriginalName();
        $image->storeAs('public/news', $imageName);
        $data['image'] = 'news/' . $imageName;
    }

    // 4️⃣ Update slug if title changed
    if ($news->title_en !== $request->title_en) {
        $slug = Str::slug($request->title_en);
        $originalSlug = $slug;
        $count = 1;
        while (News::where('slug', $slug)->where('id', '!=', $news->id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        $data['slug'] = $slug;
    }

    // 5️⃣ Force update
    $news->fill($data)->save();

    // 6️⃣ Redirect with success message
    return redirect()->route('admin.news.index')->with('success', 'News updated successfully!');
}

    /**
     * Delete a news article.
     */
  public function destroy($id)
{
    $news = News::findOrFail($id);

    // Delete the image file if exists
    if ($news->image && file_exists(storage_path('app/public/' . $news->image))) {
        unlink(storage_path('app/public/' . $news->image));
    }

    $news->delete();

    return redirect()->route('admin.news.index')->with('success', 'News deleted successfully.');
}


    /**
     * Calculate read time in minutes.
     */
    private function calculateReadTime($content)
    {
        $wordCount = str_word_count(strip_tags($content));
        $minutes = ceil($wordCount / 200);
        return max(1, $minutes);
    }

    /**
     * Display a single news article.
     */
public function show($slug)
{
    // Load news item with comments + replies + count
    $newsItem = News::with([
        'category:id,name_en,name_am',
        'comments' => function($query) {
            $query->whereNull('parent_id') // Only top-level comments
                  ->where('status', 1)
                  ->orderBy('created_at', 'desc');
        },
        'comments.replies' => function($query) {
            $query->where('status', 1)
                  ->orderBy('created_at', 'asc');
        }
    ])
    ->withCount(['comments' => function ($q) {
        $q->where('status', 1);
    }])
    ->where('slug', $slug)
    ->where('is_published', true)
    ->firstOrFail();

    $readTime = $this->calculateReadTime($newsItem->content_en);

    // Increment views
    $newsItem->increment('views');

    // Related news - only if category exists
    $relatedNews = collect();
    if ($newsItem->category) {
        $relatedNews = News::with(['category:id,name_en,name_am'])
            ->where('category_id', $newsItem->category->id)
            ->where('id', '!=', $newsItem->id)
            ->where('is_published', true)
            ->latest('published_at')
            ->take(3)
            ->get();
    }

    return view('front.news-detail', compact('newsItem', 'readTime', 'relatedNews'));
}


    
}
