<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Project;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectController extends BaseContentController
{
    protected string $modelClass = Project::class;
    protected array $searchableFields = ['title', 'description', 'location'];
    protected array $jsonSearchableFields = ['participants'];
    protected string $type = 'proyecto';

    /** 🟡 Crear proyecto */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'date' => 'date|required',
                'location' => 'nullable|string|max:255',
                'link' => 'nullable|url',
                'file_file' => 'nullable|file|mimes:pdf,docx,xlsx|max:20480',
                'file_url' => 'nullable|url',
                'cover_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'cover_image_url' => 'nullable|url',
                'participants' => 'nullable|json',
                'participants.*' => 'string|max:255'

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

            $project = Project::create([
                'title' => $request->title,
                'description' => $request->description,
                'date' => $request->date,
                'location' => $request->location,
                'link' => $request->link,
                'file_path' => $filePath,
                'cover_image' => $coverPath,
                'participants' => $request->participants,
                'author_id' => $request->user()->id,
                'status' => 'publico'
            ]);

            return ApiResponse::created('Proyecto creado correctamente', $project);

        } catch (ValidationException $e) {
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error al crear evento:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al guardar evento', 500, ['debug' => $e->getMessage()]);
        }
    }

    /** 🟠 Actualizar proyecto */
    public function update($id, Request $request)
    {
        $project = Project::find($id);
        if (!$project) return ApiResponse::notFound('Proyecto no encontrado');

        $auth = $request->user();
        if ($project->author_id !== $auth->id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'date' => 'date|nullable',
            'location' => 'nullable|string|max:255',
            'link' => 'nullable|url',
            'participants' => 'nullable|json',
            'participants.*' => 'string|max:255'
        ]);

        $project->update($request->only([
            'title', 'description', 'date', 'location', 'link', 'participants'
        ]));

        return ApiResponse::updated('Proyecto actualizado correctamente', $project);
    }
}