<?php

namespace App\Http\Repositories;

use App\Http\Interfaces\PostInterface;
use App\Http\Resources\DefaultResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostRepository extends BaseRepository implements PostInterface
{
    /**
     * @var Post
     */
    protected mixed $modelClass = Post::class;

    public function index(Request $request): JsonResponse
    {
        $query = $this->generateQuery($request);
        $data = $query->paginate($request->get('per_page'));
        $response = DefaultResource::collection($data);

        return okWithPaginateResponse($response);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = $this->generateQuery($request);
        $data = $query->paginate($request->get('per_page'));
        $response = DefaultResource::collection($data);

        return okWithPaginateResponse($response);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = $this->generateQuery($request);
        $post = $query->findOrFail($id);

        return okResponse($post);
    }

    public function store(Request $request): JsonResponse
    {
        $model = Post::query()->create($request->all());
        $this->defaultAppendAndInclude($model, $request);

        return createdResponse($model);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $post = $post->update($request->all());
        $this->defaultAppendAndInclude($post, $request);

        return okResponse($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return okResponse($post);
    }
}
