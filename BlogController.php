<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Comment;
use Carbon\Carbon;
use App\Models\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:blog_access', ['only' => ['index', 'show']]);
        $this->middleware('permission:blog_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:blog_edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:blog_delete', ['only' => ['destroy', 'delete', 'permanent', 'restore']]);
    }

    public function index()
    {
        try {
            $blogs = Blog::orderByDesc('updated_at')->get();
            return view('admin.blog.index', compact('blogs'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function create()
    {
        try {
            $blogcategories = BlogCategory::where('status', 1)->latest()->get();
            return view('admin.blog.create', compact('blogcategories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'blog_title' => 'required',
                'description' => 'required',
                'blog_image.*' => 'required|mimes:jpeg,jpg,bmp,png,svg',
                'link_video' => 'nullable|url',
                'blog_category_id' => 'required',

            ]);
            $images = [];
            if ($request->hasfile('blog_image')) {
                foreach ($request->file('blog_image') as $file) {
                    $imageName = uniqid() . '.' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/Blog'), $imageName);
                    $images[] = $imageName;
                }
            } else {
                $images = "default.png";
            }

            $blog = Blog::create([
                'blog_title' => $request->blog_title,
                'blog_slug' => Str::slug($request->blog_title),
                'description' => $request->description,
                'blog_image' => $imageName,
                'posted_by' => Auth::user()->id,
                'link_video' => $request->link_video,
                'blog_category_id' => $request->blog_category_id,
            ]);
            $blog->save();

            $log = new Log();
            $log->action = "A New Blog Created";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.blogs')->with('successMsg', 'Blog Successfully Saved!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            //
            $blogcategories = BlogCategory::where('status', 1)->latest()->get();
            $blog = Blog::find($id);
            return view('admin.blog.edit', compact('blog', 'blogcategories'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function comments($id)
    {
        try {
            $blog = Blog::find($id);
            return view('admin.blog.comments', compact('blog'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function statusComment($id)
    {
        try {
            $item = Comment::findOrFail($id);
            $item->status = !$item->status;
            $item->save();
            $log = new Log();
            $log->action = "A Comment activated / deactivated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Comment Activated / Deactivated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'blog_title' => 'required',
                'description' => 'required',
                'blog_image.*' => 'required|mimes:jpeg,jpg,bmp,png,svg',
                'link_video' => 'nullable|url',
                'blog_category_id' => 'required',

            ]);
            $blog = Blog::find($id);

            $images = [];
            if ($request->hasfile('blog_image')) {
                foreach ($request->file('blog_image') as $file) {
                    $slug = Str::slug($request->title);
                    $currentDate = Carbon::now()->toDateString();
                    $imageName = $slug . '-' . $currentDate . '-' . uniqid() . '.' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/Blog'), $imageName);
                    $images[] = $imageName;
                }
                $blog->blog_image = $images;
            }
            $blog->blog_title = $request->blog_title;
            $blog->description = $request->description;
            $blog->link_video = $request->link_video;
            $blog->posted_by = Auth::user()->id;
            $blog->blog_category_id = $request->blog_category_id;
            $blog->blog_slug = Str::slug($request->blog_title);
            $blog->save();

            $log = new Log();
            $log->action = "A blog information updated";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->route('admin.blogs')->with('successMsg', 'Blog Updated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(int $id)
    {
        try {
            $blog = Blog::onlyTrashed()->findOrFail($id);
            $blog->restore();
            return redirect()->back()->with('successMsg', 'Blog Restored!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function onlyTrashed()
    {
        try {
            $posts = Blog::onlyTrashed()->get();
            return view('admin.blog.trashed', compact('posts'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function permanent(Request $request, $id)
    {
        try {
            Blog::onlyTrashed()->find($id)->forceDelete();

            $log = new Log();
            $log->action = " An Blog permanently deleted";
            $log->user_id = Auth::user()->id;
            $log->save();
            return redirect()->back()->with('successMsg', 'Permanently Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $blog = Blog::find($id);
            $blog->delete();

            $log = new Log();
            $log->action = "A Blog deleted";
            $log->user_id = Auth::user()->id;
            $log->save();

            return redirect()->back()->with('successMsg', 'Deleted Succesfully!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('infoMsg', $th->getMessage());
        }
    }
}
