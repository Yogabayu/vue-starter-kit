<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        // Optional filters: commentable_type, commentable_id
        $query = Comment::query()->with(['user']);
        if ($request->filled('commentable_type')) {
            $query->where('commentable_type', $request->string('commentable_type'));
        }
        if ($request->filled('commentable_id')) {
            $query->where('commentable_id', $request->integer('commentable_id'));
        }
        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'commentable_type' => ['required', 'string', 'max:255'],
            'commentable_id' => ['required', 'integer'],
            'content' => ['required', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $comment = Comment::create($data + ['is_approved' => true]);
        return response()->json($comment->load('user'), 201);
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
