<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Webinar;
use Illuminate\Http\Request;

class WebinarController extends Controller
{
    /** 🟢 Obtener todos los webinars (visibilidad filtrada) */
    public function index(Request $request)
    {
        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id ?? null;
        $isAdmin = $auth?->role === 'admin';

        $query = Webinar::with(['author:id,name,username,email,role,status'])
                        ->orderBy('created_at', 'desc');

        if (!$auth) {
            $query->where('status', 'publico');
        } elseif (!$isAdmin) {
            $query->where(function ($q) use ($authId) {
                $q->where('status', 'publico')
                  ->orWhere('author_id', $authId);
            });
        }

        return ApiResponse::success('Lista de webinars obtenida', $query->get());
    }

    /** 🔵 Ver un webinar por ID */
    public function show($id, Request $request)
    {
        $auth = $request->user();
        $webinar = Webinar::with(['author:id,name,username,email,role,status'])->find($id);

        if (!$webinar) {
            return ApiResponse::notFound('Webinar no encontrado');
        }

        if ($webinar->status === 'archivado' && (!$auth || ($auth->id !== $webinar->author_id && $auth->role !== 'admin'))) {
            return ApiResponse::unauthorized('No autorizado para ver este webinar');
        }

        return ApiResponse::success('Webinar obtenido', $webinar);
    }

    /** 🟡 Crear nuevo webinar */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date_format:Y-m-d H:i:s',
            'link' => 'nullable|url',
            'format' => 'required|in:presencial,online',
            'location' => 'nullable|string|max:255',
        ]);

        if ($request->format === 'presencial' && !$request->location) {
            return ApiResponse::error('La ubicación es obligatoria para eventos presenciales', 422);
        }

        $webinar = Webinar::create([
            'title' => $request->title,
            'description' => $request->description,
            'date' => $request->date,
            'link' => $request->link,
            'format' => $request->format,
            'location' => $request->location,
            'status' => 'publico',
            'author_id' => $request->user()->id
        ]);

        return ApiResponse::created('Webinar creado correctamente', $webinar);
    }

    /** 🟠 Editar un webinar (solo el autor o admin) */
    public function update($id, Request $request)
    {
        $webinar = Webinar::find($id);

        if (!$webinar) {
            return ApiResponse::notFound('Webinar no encontrado');
        }

        $auth = $request->user();
        if ($auth->id !== $webinar->author_id && $auth->role !== 'admin') {
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

        $webinar->update($request->only([
            'title', 'description', 'date', 'link', 'format', 'location', 'status'
        ]));

        return ApiResponse::success('Webinar actualizado correctamente', $webinar);
    }

    /** 🟠 Alternar entre público y archivado */
    public function toggleStatus($id, Request $request)
    {
        $webinar = Webinar::find($id);

        if (!$webinar) {
            return ApiResponse::notFound('Webinar no encontrado');
        }

        $auth = $request->user();

        if ($auth->id !== $webinar->author_id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $webinar->status = $webinar->status === 'publico' ? 'archivado' : 'publico';
        $webinar->save();

        return ApiResponse::success('Estado del webinar actualizado correctamente', $webinar);
    }

    /** 🔴 Eliminar permanentemente un webinar */
    public function destroy($id, Request $request)
    {
        $webinar = Webinar::find($id);

        if (!$webinar) {
            return ApiResponse::notFound('Webinar no encontrado');
        }

        $auth = $request->user();

        if ($auth->id !== $webinar->author_id && $auth->role !== 'admin') {
            return ApiResponse::unauthorized('No autorizado');
        }

        $webinar->delete();

        return ApiResponse::success('Webinar eliminado permanentemente');
    }
}
