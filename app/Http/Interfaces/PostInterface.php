<?php

namespace App\Http\Interfaces;

use App\Models\Post;
use Illuminate\Http\Request;

interface PostInterface
{
    public function index(Request $request);

    public function adminIndex(Request $request);

    public function show(Request $request, Post $post);

    public function store(Request $request);

    public function update(Request $request, Post $post);

    public function destroy(Post $post);
}
