<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\NewsPost;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NewsController extends BaseContentController
{
    protected string $modelClass = NewsPost::class;
    protected array $searchableFields = ['title', 'content', 'category'];
    protected array $jsonSearchableFields = ['tags'];
    protected string $type = 'noticia';

    /** 🟡 Crear noticia */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'category' => 'required|string|max:255',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:100',
                'cover_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'cover_image_url' => 'nullable|url',
                'link' => 'nullable|url'
            ]);

            $coverPath = null;

            if ($request->hasFile('cover_image_file')) {
                $coverPath = FileUploadService::uploadImage($request->file('cover_image_file'), 'covers');
            } elseif ($request->filled('cover_image_url')) {
                $coverPath = $request->input('cover_image_url');
            }

            $newsPost = NewsPost::create([
                'title' => $request->title,
                'content' => $request->content,
                'category' => $request->category,
                'tags' => $request->tags,
                'cover_image' => $coverPath,
                'link' => $request->link,
                'author_id' => $request->user()->id,
                'status' => 'publico'
            ]);

            return ApiResponse::created('Post (noticia) creada correctamente', $newsPost);

        } catch (ValidationException $e) {
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error al crear evento:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al guardar evento', 500, ['debug' => $e->getMessage()]);
        }
    }

    /** 🟠 Actualizar noticia */
    public function update($id, Request $request)
    {
        $newsPost = NewsPost::find($id);
        if (!$newsPost) return ApiResponse::notFound('Noticia no encontrada');

        $auth = $request->user();
        if ($newsPost->author_id !== $auth->id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'link' => 'nullable|url'
        ]);

        $newsPost->update($request->only([
            'title', 'content', 'category', 'tags'
        ]));

        return ApiResponse::success('Noticia actualizada correctamente', $newsPost);
    }
}