<?php

namespace App\Http\Controllers;

use App\Models\DocumentoInstitucional;
use Illuminate\Http\Request;

class DocumentacionController extends Controller
{
    // ── Admin ──────────────────────────────────────────────────────────────

    public function index()
    {
        $documentos = DocumentoInstitucional::orderBy('categoria')
            ->orderBy('orden')
            ->orderBy('titulo')
            ->get();

        return view('documentacion.index', compact('documentos'));
    }

    public function create()
    {
        $categorias = DocumentoInstitucional::distinct()->orderBy('categoria')->pluck('categoria');
        return view('documentacion.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'categoria'   => 'required|string|max:100',
            'titulo'      => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'url'         => 'required|url|max:2000',
            'orden'       => 'nullable|integer|min:0|max:999',
            'activo'      => 'nullable|boolean',
        ]);

        $data['orden']  = $data['orden'] ?? 0;
        $data['activo'] = $request->boolean('activo', true);

        DocumentoInstitucional::create($data);

        return redirect()->route('documentacion.index')
            ->with('success', 'Documento creado correctamente.');
    }

    public function edit(DocumentoInstitucional $documento)
    {
        $categorias = DocumentoInstitucional::distinct()->orderBy('categoria')->pluck('categoria');
        return view('documentacion.edit', compact('documento', 'categorias'));
    }

    public function update(Request $request, DocumentoInstitucional $documento)
    {
        $data = $request->validate([
            'categoria'   => 'required|string|max:100',
            'titulo'      => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'url'         => 'required|url|max:2000',
            'orden'       => 'nullable|integer|min:0|max:999',
            'activo'      => 'nullable|boolean',
        ]);

        $data['orden']  = $data['orden'] ?? 0;
        $data['activo'] = $request->boolean('activo');

        $documento->update($data);

        return redirect()->route('documentacion.index')
            ->with('success', 'Documento actualizado correctamente.');
    }

    public function destroy(DocumentoInstitucional $documento)
    {
        $documento->delete();

        return redirect()->route('documentacion.index')
            ->with('success', 'Documento eliminado.');
    }

    // ── Portal de Padres ───────────────────────────────────────────────────

    public function padres()
    {
        $estudiante = session('padre_estudiante');
        if (!$estudiante) return redirect()->route('padres.portal');

        $grupos = DocumentoInstitucional::where('activo', true)
            ->orderBy('categoria')
            ->orderBy('orden')
            ->orderBy('titulo')
            ->get()
            ->groupBy('categoria');

        return view('padres.documentacion', compact('estudiante', 'grupos'));
    }
}
