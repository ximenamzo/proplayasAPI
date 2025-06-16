<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Event;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventController extends BaseContentController
{
    protected string $modelClass = Event::class;
    protected array $searchableFields = ['title', 'description', 'location', 'type', 'format'];
    protected string $type = 'evento';

    
    /** 🟡 Crear evento */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'type' => 'required|in:event,taller,clase,curso,seminario,foro,conferencia,congreso',
                'description' => 'required|string',
                'date' => 'required|date',
                'link' => 'required|url',
                'format' => 'required|in:presencial,online',
                'location' => 'nullable|string|max:255',
                'participants' => 'nullable|json',
                'participants.*' => 'string|max:255',
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

            $event = Event::create([
                'title' => $request->title,
                'type' => $request->type,
                'description' => $request->description,
                'date' => $request->date,
                'link' => $request->link,
                'format' => $request->format,
                'location' => $request->location,
                'participants' => $request->participants,
                'cover_image' => $coverPath,
                'file_path' => $filePath,
                'author_id' => $request->user()->id,
                'status' => 'publico'
            ]);

            return ApiResponse::created('Evento creado correctamente', $event);

        } catch (ValidationException $e) {
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error al crear evento:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al guardar evento', 500, ['debug' => $e->getMessage()]);
        }
    }

    /** 🟠 Actualizar evento */
    public function update($id, Request $request)
    {
        $event = Event::find($id);
        if (!$event) return ApiResponse::notFound('Evento no encontrado');

        $auth = $request->user();
        if ($event->author_id !== $auth->id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|in:event,taller,clase,curso,seminario,foro,conferencia,congreso',
            'description' => 'nullable|string',
            'date' => 'nullable|date',
            'link' => 'nullable|url',
            'format' => 'nullable|in:presencial,online',
            'location' => 'nullable|string|max:255',
            'participants' => 'nullable|json',
            'participants.*' => 'string|max:255'
        ]);

        $event->update($request->only([
            'title', 'type', 'description', 'date', 'link', 'format', 'location', 'participants'
        ]));

        $updatedEvent = Event::with(['author:id,name,username,email,role,degree,postgraduate'])->find($id);

        return ApiResponse::success('Evento actualizado correctamente', $updatedEvent);
    }
}
