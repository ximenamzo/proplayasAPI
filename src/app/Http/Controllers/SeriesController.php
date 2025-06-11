<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Series;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SeriesController extends BaseContentController
{
    protected string $modelClass = Series::class;
    protected array $searchableFields = ['title', 'description'];
    protected string $type = 'serie';

    /** 🟡 Crear serie */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'url' => 'nullable|url',
                'description' => 'nullable|string',
                'cover_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'cover_image_url' => 'nullable|url'
            ]);

            $coverPath = null;

            if ($request->hasFile('cover_image_file')) {
                $coverPath = FileUploadService::uploadImage($request->file('cover_image_file'), 'covers');
            } elseif ($request->filled('cover_image_url')) {
                $coverPath = $request->input('cover_image_url');
            }

            $series = Series::create([
                'title' => $request->title,
                'url' => $request->url,
                'description' => $request->description,
                'cover_image' => $coverPath,
                'author_id' => $request->user()->id,
                'status' => 'publico'
            ]);

            return ApiResponse::created('Serie creada correctamente', $series);

        } catch (ValidationException $e) {
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error al crear evento:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al guardar evento', 500, ['debug' => $e->getMessage()]);
        }
    }

    /** 🟠 Actualizar serie */
    public function update($id, Request $request)
    {
        $series = Series::find($id);

        if (!$series) return ApiResponse::notFound('Serie no encontrada');

        $auth = $request->user();

        if ($series->author_id !== $auth->id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'url' => 'nullable|url',
            'description' => 'nullable|string',
        ]);

        $series->update($request->only([
            'title', 'url', 'description'
        ]));

        $updatedSeries = Series::with(['author:id,name,username,email,role,degree,postgraduate'])->find($series->id);

        return ApiResponse::success('Serie actualizada correctamente', $updatedSeries);
    }
}
