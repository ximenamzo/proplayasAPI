<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicationController extends Controller
{
    /** 🟢 Obtener publicaciones públicas o propias (filtro por visibilidad) */
    public function index(Request $request)
    {
        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id ?? null;
        $isAdmin = $auth?->role === 'admin';

        $query = Publication::with(['author:id,name,username,email,role,status'])
            ->orderBy('created_at', 'desc');

        if (!$auth) {
            $query->where('status', 'publico');
        } elseif (!$isAdmin) {
            $query->where(fn($q) =>
                $q->where('status', 'publico')
                ->orWhere('author_id', $authId)
            );
        }

        $publications = $query->get()->map(fn($pub) => [
            'id' => $pub->id,
            'type' => $pub->type,
            'title' => $pub->title,
            'description' => $pub->description,
            'link' => $pub->link,
            'doi' => $pub->doi,
            'issn' => $pub->issn,
            'file_path' => $pub->file_path,
            'cover_image' => $pub->cover_image,
            'author_id' => $pub->author_id,
            'author' => $pub->author
                ? collect($pub->author)->only(['id', 'name', 'username', 'email', 'role', 'status'])
                : null
        ]);

        return ApiResponse::success('Lista de publicaciones obtenida', $publications);
    }

    /** 🔵 Ver detalle de publicación */
    public function show($id, Request $request)
    {
        $auth = $request->user();
        $authId = $auth->sub ?? $auth->id ?? null;
    
        $publication = Publication::with(['author:id,name,username,email,role,status'])->find($id);
    
        if (!$publication) {
            return ApiResponse::notFound('Publicación no encontrada', 404);
        }
    
        $isOwner = $authId === $publication->author_id;
        $isAdmin = $auth?->role === 'admin';
    
        if ($publication->status !== 'publico' && !$isOwner && !$isAdmin) {
            return ApiResponse::unauthorized('Unauthorized: No autorizado para ver esta publicación', 403);
        }
    
        return ApiResponse::success('Detalle de publicación obtenida', [
            'id' => $publication->id,
            'type' => $publication->type,
            'title' => $publication->title,
            'description' => $publication->description,
            'link' => $publication->link,
            'doi' => $publication->doi,
            'issn' => $publication->issn,
            'file_path' => $publication->file_path,
            'cover_image' => $publication->cover_image,
            'author_id' => $publication->author_id,
            'author' => $publication->author
                ? collect($publication->author)->only(['id', 'name', 'username', 'email', 'role', 'status'])
                : null
        ]);
    }

    /** 🟡 Crear nueva publicación */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'type' => 'required|in:boletin,guia,articulo',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url',
            'doi' => 'nullable|string',
            'issn' => 'nullable|string',
            'file_path' => 'nullable|string',
            'cover_image' => 'nullable|string',
        ]);

        $publication = Publication::create([
            ...$request->only([
                'type', 'title', 'description', 'link', 'doi', 
                'issn', 'file_path', 'cover_image'
            ]),
            'author_id' => $user->sub ?? $user->id,
            'status' => 'publico'
        ]);

        return ApiResponse::created('Publicación creada exitosamente', $publication);        
    }

    /** 🟠 Editar una publicación (solo autor o admin) */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $publication = Publication::find($id);

        if (!$publication) {
            return ApiResponse::notFound('Publicación no encontrada', 404);
        }

        if (($user->sub ?? $user->id) !== $publication->author_id && $user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $request->validate([
            'title' => 'string|max:255|nullable',
            'description' => 'string|nullable',
            'link' => 'nullable|url',
            'doi' => 'nullable|string',
            'issn' => 'nullable|string',
            'file_path' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'status' => 'in:publico,archivado'
        ]);

        $publication->update($request->only([
            'title', 'description', 'link', 'doi', 'issn', 
            'file_path', 'cover_image', 'status'
        ]));

        return ApiResponse::success('Publicación actualizada correctamente', $publication);
    }

    /** 🔴 Eliminar publicación (solo autor o admin) */
    public function destroy($id, Request $request)
    {
        $user = $request->user();
        $publication = Publication::find($id);

        if (!$publication) {
            return ApiResponse::notFound('Publicación no encontrada', 404);
        }

        if (($user->sub ?? $user->id) !== $publication->author_id && $user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $publication->delete();

        return ApiResponse::success('Publicación eliminada correctamente', $publication);
    }
}
