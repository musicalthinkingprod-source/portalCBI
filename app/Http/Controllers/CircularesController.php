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
            'contenido'   => 'nullable|string|max:2100000',
            'estado'      => 'required|in:borrador,publicada',
            'link'        => 'nullable|url|max:500',
            'grados'      => 'nullable|array',
            'grados.*'    => 'string|in:PJ,J,T,1,2,3,4,5,6,7,8,9,10,11',
        ], [
            'contenido.max' => 'El contenido supera los 2 MB. Reduce o elimina imágenes.',
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
            'contenido'   => 'nullable|string|max:2100000',
            'estado'      => 'required|in:borrador,publicada',
            'link'        => 'nullable|url|max:500',
            'grados'      => 'nullable|array',
            'grados.*'    => 'string|in:PJ,J,T,1,2,3,4,5,6,7,8,9,10,11',
        ], [
            'contenido.max' => 'El contenido supera los 2 MB. Reduce o elimina imágenes.',
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
        // Reemplaza iframes por un recuadro con enlace (DomPDF no ejecuta iframes).
        $contenidoPdf = $this->transformarIframesParaPdf($circular->contenido ?? '');

        // Se pasa una copia "aplanada" para el PDF sin mutar el modelo.
        $circularPdf = clone $circular;
        $circularPdf->contenido = $contenidoPdf;

        $pdf = Pdf::loadView('circulares.pdf', ['circular' => $circularPdf])
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'isRemoteEnabled'       => true,  // permite cargar imágenes de Google Drive
                'isHtml5ParserEnabled'  => true,
                'defaultFont'           => 'Arial',
            ]);

        return $pdf->stream("{$circular->numero}.pdf");
    }

    /**
     * Reemplaza <iframe> por un bloque con enlace legible en PDF.
     * Para iframes de Google Drive de tipo "preview", extrae el ID y apunta al viewer.
     */
    private function transformarIframesParaPdf(string $html): string
    {
        if ($html === '' || stripos($html, '<iframe') === false) {
            return $html;
        }

        return preg_replace_callback(
            '#<iframe\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\'][^>]*>.*?</iframe>#is',
            function ($m) {
                $src = $m[1];
                $url = $src;
                $etiqueta = 'Abrir contenido embebido';

                // Drive preview → URL del visor
                if (preg_match('#drive\.google\.com/file/d/([^/]+)/preview#', $src, $d)) {
                    $url = 'https://drive.google.com/file/d/' . $d[1] . '/view';
                    $etiqueta = 'Ver documento en Google Drive';
                } elseif (preg_match('#youtube\.com/embed/([^?/]+)#', $src, $y)) {
                    $url = 'https://www.youtube.com/watch?v=' . $y[1];
                    $etiqueta = 'Ver video en YouTube';
                } elseif (preg_match('#player\.vimeo\.com/video/(\d+)#', $src, $v)) {
                    $url = 'https://vimeo.com/' . $v[1];
                    $etiqueta = 'Ver video en Vimeo';
                }

                $urlSafe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                return '<div style="margin:12px 0; padding:14px; border:1px dashed #94a3b8; background:#f8fafc; text-align:center; font-size:12px;">'
                     . '<b>📎 ' . htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') . '</b><br>'
                     . '<a href="' . $urlSafe . '" style="color:#1e3a8a; word-break:break-all;">' . $urlSafe . '</a>'
                     . '</div>';
            },
            $html
        );
    }
}
