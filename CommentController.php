<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\News; // assuming you have a News model
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:comment_access')->only('index');
        $this->middleware('permission:comment_create')->only(['create', 'store']);
        $this->middleware('permission:comment_edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('permission:comment_delete')->only('destroy');
    }

    /**
     * Display a listing of all comments.
     */
    public function index()
    {
        $comments = Comment::with('news')->orderBy('created_at', 'desc')->get();
        return view('admin.comments.index', compact('comments'));
    }

    /**
     * Show the form for creating a new comment.
     */
public function create()
{
    $newsList = News::orderBy('title_en')->get(); // order by English title
    return view('admin.comments.create', compact('newsList'));
}


    /**
     * Store a comment from frontend (news detail page)
     */
    public function storeFrontend(Request $request, $newsId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = Comment::create([
            'blog_id' => $newsId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => 1, // Auto-approve frontend comments
        ]);

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'name' => $comment->name,
                'content' => $comment->content,
                'created_at' => $comment->created_at->diffForHumans(),
            ]
        ]);
    }

    /**
     * Store a newly created comment in storage (admin).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
            'blog_id' => 'required|exists:news,id', // validate the news/blog id
        ]);

        $comment = Comment::create([
            'blog_id' => $validated['blog_id'], // use blog_id from request
            'name' => $validated['name'],
            'email' => $validated['email'],
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => $request->status ?? 1,
        ]);

        return redirect()->route('admin.comments.index')->with('success', 'Comment added successfully.');
    }

    /**
     * Show the form for editing the specified comment.
     */
public function edit($id)
{
    $comment = Comment::findOrFail($id);

    // Use the correct column for ordering news
    $newsList = News::orderBy('title_en')->get();

    return view('admin.comments.edit', compact('comment', 'newsList'));
}


    /**
     * Update the specified comment in storage.
     */
    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        $request->validate([
            'blog_id' => 'required|exists:news,id',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'content' => 'required|string|max:1000',
        ]);
$comment->update([
    'blog_id' => $request->blog_id, 
    'name' => $request->name,
    'email' => $request->email,
    'content' => $request->content,
    'status' => $request->status ?? $comment->status,
    'parent_id' => $request->parent_id ?? $comment->parent_id,
]);


        return redirect()->route('admin.comments.index')
            ->with('success', 'Comment updated successfully.');
    }

    /**
     * Delete a comment by ID.
     */
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return redirect()->route('admin.comments.index')
            ->with('success', 'Comment deleted successfully.');
    }

    /**
     * Toggle comment status (approve/unapprove).
     */
    public function toggleStatus($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->status = !$comment->status;
        $comment->save();

        return redirect()->route('admin.comments.index')
            ->with('success', 'Comment status updated successfully.');
    }
}
