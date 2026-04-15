<?php

namespace App\Http\Controllers;

use App\Models\Circular;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CircularesController extends Controller
{
    public function index(Request $request)
    {
        $año = $request->input('año', date('Y'));

        $circulares = Circular::whereYear('created_at', $año)
            ->orderByDesc('numero')
            ->get();

        $años = Circular::selectRaw('YEAR(created_at) as año')
            ->groupBy('año')
            ->orderByDesc('año')
            ->pluck('año');

        return view('circulares.index', compact('circulares', 'año', 'años'));
    }

    public function create()
    {
        $numero = Circular::siguienteNumero((int) date('Y'));
        return view('circulares.create', compact('numero'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero'      => 'required|string|max:50|unique:circulares,numero',
            'fecha'       => 'required|date',
            'asunto'      => 'required|string|max:255',
            'dirigido_a'  => 'required|string|max:255',
            'emitido_por' => 'required|string|max:255',
            'cargo'       => 'nullable|string|max:255',
            'contenido'   => 'nullable|string',
            'estado'      => 'required|in:borrador,publicada',
            'link'        => 'nullable|url|max:500',
            'grados'      => 'nullable|array',
            'grados.*'    => 'string|in:PJ,J,T,1,2,3,4,5,6,7,8,9,10,11',
        ]);

        $data['grados'] = !empty($data['grados']) ? $data['grados'] : null;

        // Solo SuperAd puede publicar; cualquier otro siempre queda en borrador
        if (auth()->user()->PROFILE !== 'SuperAd') {
            $data['estado']      = 'borrador';
            $data['emitido_por'] = 'Luz Ángela Vega Buenahora';
            $data['cargo']       = 'Directora General';
        }

        $circular = Circular::create($data);

        return redirect()->route('circulares.show', $circular)
            ->with('success', "Circular {$circular->numero} guardada.");
    }

    public function show(Circular $circular)
    {
        return view('circulares.show', compact('circular'));
    }

    public function edit(Circular $circular)
    {
        return view('circulares.edit', compact('circular'));
    }

    public function update(Request $request, Circular $circular)
    {
        $data = $request->validate([
            'numero'      => 'required|string|max:50|unique:circulares,numero,' . $circular->id,
            'fecha'       => 'required|date',
            'asunto'      => 'required|string|max:255',
            'dirigido_a'  => 'required|string|max:255',
            'emitido_por' => 'required|string|max:255',
            'cargo'       => 'nullable|string|max:255',
            'contenido'   => 'nullable|string',
            'estado'      => 'required|in:borrador,publicada',
            'link'        => 'nullable|url|max:500',
            'grados'      => 'nullable|array',
            'grados.*'    => 'string|in:PJ,J,T,1,2,3,4,5,6,7,8,9,10,11',
        ]);

        $data['grados'] = !empty($data['grados']) ? $data['grados'] : null;

        // Solo SuperAd puede cambiar estado y firmante
        if (auth()->user()->PROFILE !== 'SuperAd') {
            $data['estado']      = $circular->estado; // conserva el estado actual
            $data['emitido_por'] = $circular->emitido_por;
            $data['cargo']       = $circular->cargo;
        }

        $circular->update($data);

        return redirect()->route('circulares.show', $circular)
            ->with('success', 'Circular actualizada.');
    }

    public function destroy(Circular $circular)
    {
        $numero = $circular->numero;
        $circular->delete();

        return redirect()->route('circulares.index')
            ->with('success', "Circular {$numero} eliminada.");
    }

    public function pdf(Circular $circular)
    {
        $pdf = Pdf::loadView('circulares.pdf', compact('circular'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("{$circular->numero}.pdf");
    }
}
