<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Publication;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicationController extends BaseContentController
{
    protected string $modelClass = Publication::class;
    protected array $searchableFields = ['title', 'description', 'type'];
    protected string $type = 'publicacion';


    /** 🟡 Crear publicación */
    public function store(Request $request)
    {        
        try {
            $request->validate([
                'type' => 'required|in:boletin,guia,articulo',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'link' => 'nullable|url',
                'doi' => 'nullable|string',
                'issn' => 'nullable|string',
                'cover_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'cover_image_url' => 'nullable|url',
                'file_file' => 'nullable|file|mimes:pdf,docx,xlsx|max:20480',
                'file_url' => 'nullable|url'
            ]);
    
            $coverPath = null;
            $filePath = null;
    
            if ($request->hasFile('cover_image_file')) {
                $coverPath = FileUploadService::uploadImage($request->file('cover_image_file'), 'covers');
            } elseif ($request->filled('cover_image_url')) {
                $coverPath = $request->input('cover_image_url');
            }
        
            if ($request->hasFile('file_file')) {
                $filePath = FileUploadService::uploadFile($request->file('file_file'), 'docs');
            } elseif ($request->filled('file_url')) {
                $filePath = $request->input('file_url');
            }
    
            $publication = Publication::create([
                'type' => $request->type,
                'title' => $request->title,
                'description' => $request->description,
                'link' => $request->link,
                'doi' => $request->doi,
                'issn' => $request->issn,
                'file_path' => $filePath,
                'cover_image' => $coverPath,
                'author_id' => $request->user()->id,
                'status' => 'publico'
            ]); 
    
            return ApiResponse::created('Publicación creada correctamente', $publication);
            
        } catch (ValidationException $e) {
            return ApiResponse::error('Error de validación', 422, [
                'errors' => $e->errors()
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error inesperado al crear publicación: ' . $e->getMessage());
            return ApiResponse::error('Error inesperado al guardar publicación', 500, [
                'debug' => $e->getMessage(),
            ]);
        }
    }

    /** 🟠 Editar publicación */
    public function update($id, Request $request)
    {
        $pub = Publication::find($id);

        if (!$pub) return ApiResponse::notFound('Publicación no encontrada');

        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id;

        if ($pub->author_id !== $authId && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $request->validate([
            'title' => 'string|max:255|nullable',
            'description' => 'string|nullable',
            'link' => 'nullable|url',
            'doi' => 'nullable|string',
            'issn' => 'nullable|string'
        ]);

        $pub->update($request->only(['title', 'description', 'link', 'doi', 'issn']));

        return ApiResponse::success('Publicación actualizada correctamente', $pub);
    }
}
