<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Interfaces\PostInterface;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Post
 */
class PostController extends Controller
{
    public function __construct(public PostInterface $postRepository)
    {
    }

    /**
     * Post Get all
     *
     * @response {
     *  "id": "integer",
     *  "name": "string",
     *  "email": "string",
     *  "status": "integer",
     *  "file_id": "integer",
     *  "type": "integer",
     *  "balance": "numeric",
     *  "user_id": "integer",
     *  "email_verified_at": "date",
     *  "password": "string",
     *  "remember_token": "string",
     *  "deleted_at": "date",
     *  "created_at": "date",
     *  "updated_at": "date",
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->postRepository->index($request);
        $data = $query->paginate($request->get('per_page'));
        $response = DefaultResource::collection($data);

        return okWithPaginateResponse($response);
    }

    /**
     * Post adminIndex get All
     *
     * @response {
     *  "id": "integer",
     *  "name": "string",
     *  "email": "string",
     *  "status": "integer",
     *  "file_id": "integer",
     *  "type": "integer",
     *  "balance": "numeric",
     *  "user_id": "integer",
     *  "email_verified_at": "date",
     *  "password": "string",
     *  "remember_token": "string",
     *  "deleted_at": "date",
     *  "created_at": "date",
     *  "updated_at": "date",
     * }
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = $this->postRepository->adminIndex($request);
        $data = $query->paginate($request->get('per_page'));
        $response = DefaultResource::collection($data);

        return okWithPaginateResponse($response);
    }

    /**
     * Post view
     *
     * @queryParam post required
     *
     * @param  Post  $post
     *
     * @response {
     *  "id": "integer",
     *  "name": "string",
     *  "email": "string",
     *  "status": "integer",
     *  "file_id": "integer",
     *  "type": "integer",
     *  "balance": "numeric",
     *  "user_id": "integer",
     *  "email_verified_at": "date",
     *  "password": "string",
     *  "remember_token": "string",
     *  "deleted_at": "date",
     *  "created_at": "date",
     *  "updated_at": "date",
     * }
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $post = $this->postRepository->show($request, $id);

        return okResponse($post);
    }

    /**
     * Post create
     *
     * @bodyParam name string
     * @bodyParam email string
     * @bodyParam status integer
     * @bodyParam file_id integer
     * @bodyParam type integer
     * @bodyParam balance numeric
     * @bodyParam user_id integer
     * @bodyParam email_verified_at date
     * @bodyParam password string
     * @bodyParam remember_token string
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->postRepository->store($request);

        return createdResponse($post);
    }

    /**
     * Post update
     *
     * @queryParam post required
     *
     * @bodyParam name string
     * @bodyParam email string
     * @bodyParam status integer
     * @bodyParam file_id integer
     * @bodyParam type integer
     * @bodyParam balance numeric
     * @bodyParam user_id integer
     * @bodyParam email_verified_at date
     * @bodyParam password string
     * @bodyParam remember_token string
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post = $this->postRepository->update($request, $post);

        return okResponse($post);
    }

    /**
     * Post delete
     *
     * @queryParam post required
     */
    public function destroy(Post $post): JsonResponse
    {
        $this->postRepository->destroy($post);

        return okResponse($post);
    }
}
