<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /** 🟢 Obtener todos los eventos (visibilidad filtrada) */
    public function index(Request $request)
    {
        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id ?? null;
        $isAdmin = $auth?->role === 'admin';

        $query = Event::with(['author:id,name,username,email,role,status'])
                        ->orderBy('created_at', 'desc');

        if (!$auth) {
            $query->where('status', 'publico');
        } elseif (!$isAdmin) {
            $query->where(function ($q) use ($authId) {
                $q->where('status', 'publico')
                  ->orWhere('author_id', $authId);
            });
        }

        return ApiResponse::success('Lista de eventos obtenida', $query->get());
    }

    /** 🔵 Ver un event por ID */
    public function show($id, Request $request)
    {
        $auth = $request->user();
        $event = Event::with(['author:id,name,username,email,role,status'])->find($id);

        if (!$event) {
            return ApiResponse::notFound('Event no encontrado');
        }

        if ($event->status === 'archivado' && (!$auth || ($auth->id !== $event->author_id && $auth->role !== 'admin'))) {
            return ApiResponse::unauthorized('No autorizado para ver este event');
        }

        return ApiResponse::success('Event obtenido', $event);
    }

    /** 🟡 Crear nuevo event */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date',
            'link' => 'nullable|url',
            'format' => 'required|in:presencial,online',
            'location' => 'nullable|string|max:255',
        ]);

        if ($request->format === 'presencial' && !$request->location) {
            return ApiResponse::error('La ubicación es obligatoria para eventos presenciales', 422);
        }

        $event = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'date' => $request->date,
            'link' => $request->link,
            'format' => $request->format,
            'location' => $request->location,
            'status' => 'publico',
            'author_id' => $request->user()->id
        ]);

        return ApiResponse::created('Event creado correctamente', $event);
    }

    /** 🟠 Editar un event (solo el autor o admin) */
    public function update($id, Request $request)
    {
        $event = Event::find($id);

        if (!$event) {
            return ApiResponse::notFound('Event no encontrado');
        }

        $auth = $request->user();
        if ($auth->id !== $event->author_id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $request->validate([
            'title' => 'string|max:255|nullable',
            'description' => 'string|nullable',
            'date' => 'date|nullable',
            'link' => 'nullable|url',
            'format' => 'nullable|in:presencial,online',
            'location' => 'nullable|string|max:255',
            'status' => 'in:publico,archivado'
        ]);

        $event->update($request->only([
            'title', 'description', 'date', 'link', 'format', 'location', 'status'
        ]));

        return ApiResponse::success('Event actualizado correctamente', $event);
    }

    /** 🟠 Alternar entre público y archivado */
    public function toggleStatus($id, Request $request)
    {
        $event = Event::find($id);

        if (!$event) {
            return ApiResponse::notFound('Event no encontrado');
        }

        $auth = $request->user();

        if ($auth->id !== $event->author_id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $event->status = $event->status === 'publico' ? 'archivado' : 'publico';
        $event->save();

        return ApiResponse::success('Estado del event actualizado correctamente', $event);
    }

    /** 🔴 Eliminar permanentemente un event */
    public function destroy($id, Request $request)
    {
        $event = Event::find($id);

        if (!$event) {
            return ApiResponse::notFound('Event no encontrado');
        }

        $auth = $request->user();

        if ($auth->id !== $event->author_id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $event->delete();

        return ApiResponse::success('Event eliminado permanentemente');
    }
}
