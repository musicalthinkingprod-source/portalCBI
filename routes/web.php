<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PadreVerificacionController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\ControlEstudianteController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\PadresController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\NotasController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FechasController;
use App\Http\Controllers\EnglishAcqController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\SalvavidasController;
use App\Http\Controllers\DeroterosController;
use App\Http\Controllers\BoletinController;
use App\Http\Controllers\PiarController;
use App\Http\Controllers\ParametrosController;
use App\Http\Controllers\WorldOfficeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::post('/verificar-padre', [PadreVerificacionController::class, 'verificar'])->name('padre.verificar');

Route::middleware('padre.verificado')->group(function () {
    Route::get('/padres/portal', fn() => view('padres.portal'))->name('padres.portal');
    Route::get('/padres/estado-cuenta', [PadresController::class, 'estadoCuenta'])->name('padres.estado_cuenta');
    Route::get('/padres/notas', [PadresController::class, 'notas'])->name('padres.notas');
    Route::get('/padres/boletines', [PadresController::class, 'boletines'])->name('padres.boletines');
    Route::get('/padres/english-acq', [EnglishAcqController::class, 'padres'])->name('padres.english_acq');
    Route::get('/padres/asistencia', [AsistenciaController::class, 'padres'])->name('padres.asistencia');
    Route::get('/padres/salvavidas', [SalvavidasController::class, 'padres'])->name('padres.salvavidas');
    Route::get('/padres/derroteros', [DeroterosController::class, 'padres'])->name('padres.derroteros');
});

Route::middleware(['auth'])->group(function () {

    // ── Panel de Control: solo SuperAd ───────────────────────────────────────
    Route::middleware('profile:SuperAd')->group(function () {
        Route::get('/admin/usuarios', [AdminController::class, 'usuarios'])->name('admin.usuarios');
        Route::post('/admin/usuarios', [AdminController::class, 'storeUsuario'])->name('admin.usuarios.store');
        Route::delete('/admin/usuarios/{user}', [AdminController::class, 'destroyUsuario'])->name('admin.usuarios.destroy');
        Route::post('/admin/docentes', [AdminController::class, 'storeDocente'])->name('admin.docentes.store');
        Route::post('/admin/docentes/{codigo}/toggle', [AdminController::class, 'toggleDocente'])->name('admin.docentes.toggle');
        Route::get('/admin/directores', [AdminController::class, 'directores'])->name('admin.directores');
        Route::post('/admin/dir-grupo', [AdminController::class, 'asignarDirGrupo'])->name('admin.dir_grupo');
        Route::get('/admin/asignaciones', [AdminController::class, 'asignaciones'])->name('admin.asignaciones');
        Route::post('/admin/asignaciones/mover', [AdminController::class, 'moverAsignaciones'])->name('admin.asignaciones.mover');
        Route::post('/admin/asignaciones/mover-una', [AdminController::class, 'moverUnaAsignacion'])->name('admin.asignaciones.mover_una');
        Route::get('/admin/fechas', [FechasController::class, 'index'])->name('admin.fechas');
        Route::post('/admin/fechas', [FechasController::class, 'upsert'])->name('admin.fechas.upsert');
        Route::delete('/admin/fechas/{codigo}', [FechasController::class, 'destroy'])->name('admin.fechas.destroy');
        Route::get('/notas/reporte', [NotasController::class, 'reporte'])->name('notas.reporte');
        Route::get('/english-acq/informe', [EnglishAcqController::class, 'informe'])->name('english-acq.informe');
    });

    // ── Control de Pagos: SuperAd y Admin ────────────────────────────────────
    Route::middleware('profile:SuperAd,Admin')->group(function () {
        Route::get('/control/estudiante', [ControlEstudianteController::class, 'index'])->name('control.estudiante');
        Route::get('/pagos', [PagosController::class, 'index'])->name('pagos.index');
        Route::get('/pagos/crear', [PagosController::class, 'create'])->name('pagos.create');
        Route::post('/pagos', [PagosController::class, 'store'])->name('pagos.store');
        Route::get('/cartera', [CarteraController::class, 'index'])->name('cartera.index');
        Route::get('/cartera/deudores', [CarteraController::class, 'deudores'])->name('cartera.deudores');
        Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
        Route::get('/facturacion/crear', [FacturacionController::class, 'create'])->name('facturacion.create');
        Route::post('/facturacion', [FacturacionController::class, 'store'])->name('facturacion.store');
        Route::get('/facturacion/auto', [FacturacionController::class, 'autoIndex'])->name('facturacion.auto');
        Route::post('/facturacion/auto/preview', [FacturacionController::class, 'autoPreview'])->name('facturacion.auto.preview');
        Route::post('/facturacion/auto/generar', [FacturacionController::class, 'autoGenerar'])->name('facturacion.auto.generar');
        Route::delete('/facturacion/auto/lote/{lote}', [FacturacionController::class, 'autoEliminarLote'])->name('facturacion.auto.lote.destroy');
        // ── Plantilla World Office ────────────────────────────────────────────
        Route::get('/world-office', [WorldOfficeController::class, 'index'])->name('world-office.index');
        Route::post('/world-office/plantilla', [WorldOfficeController::class, 'guardarPlantilla'])->name('world-office.plantilla.store');
        Route::post('/world-office/exportar', [WorldOfficeController::class, 'exportarCSV'])->name('world-office.exportar');
        Route::get('/importacion/registro-pagos', [ImportacionController::class, 'show'])->name('importacion.registro_pagos.show');
        Route::post('/importacion/registro-pagos', [ImportacionController::class, 'importarRegistroPagos'])->name('importacion.registro_pagos');
        Route::delete('/importacion/registro-pagos/lote/{lote}', [ImportacionController::class, 'eliminarLotePagos'])->name('importacion.registro_pagos.lote.destroy');
        Route::get('/importacion/facturacion', [ImportacionController::class, 'showFacturacion'])->name('importacion.facturacion.show');
        Route::post('/importacion/facturacion', [ImportacionController::class, 'importarFacturacion'])->name('importacion.facturacion');
        Route::delete('/importacion/facturacion/lote/{lote}', [ImportacionController::class, 'eliminarLoteFacturacion'])->name('importacion.facturacion.lote.destroy');
        Route::get('/alumnos/crear', [AlumnoController::class, 'create'])->name('alumnos.create');
        Route::post('/alumnos', [AlumnoController::class, 'store'])->name('alumnos.store');

        // ── Parámetros de Facturación ─────────────────────────────────────────
        Route::get('/parametros', [ParametrosController::class, 'index'])->name('parametros.index');

        Route::post('/parametros/centro-costos', [ParametrosController::class, 'storeCentroCostos'])->name('parametros.centro_costos.store');
        Route::delete('/parametros/centro-costos/{codigo}', [ParametrosController::class, 'destroyCentroCostos'])->name('parametros.centro_costos.destroy');

        Route::post('/parametros/conceptos', [ParametrosController::class, 'storeConcepto'])->name('parametros.conceptos.store');
        Route::delete('/parametros/conceptos/{codigo}', [ParametrosController::class, 'destroyConcepto'])->name('parametros.conceptos.destroy');

        Route::post('/parametros/costo-pension', [ParametrosController::class, 'storeCostoPension'])->name('parametros.costo_pension.store');
        Route::delete('/parametros/costo-pension/{codigo}', [ParametrosController::class, 'destroyCostoPension'])->name('parametros.costo_pension.destroy');

        Route::post('/parametros/costo-transporte', [ParametrosController::class, 'storeCostoTransporte'])->name('parametros.costo_transporte.store');
        Route::delete('/parametros/costo-transporte/{codigo}', [ParametrosController::class, 'destroyCostoTransporte'])->name('parametros.costo_transporte.destroy');

        Route::post('/parametros/pension', [ParametrosController::class, 'storePension'])->name('parametros.pension.store');
        Route::delete('/parametros/pension/{id}', [ParametrosController::class, 'destroyPension'])->name('parametros.pension.destroy');

        Route::post('/parametros/transporte', [ParametrosController::class, 'storeTransporte'])->name('parametros.transporte.store');
        Route::delete('/parametros/transporte/{id}', [ParametrosController::class, 'destroyTransporte'])->name('parametros.transporte.destroy');

        Route::post('/parametros/nivelacion', [ParametrosController::class, 'storeNivelacion'])->name('parametros.nivelacion.store');
        Route::delete('/parametros/nivelacion/{id}', [ParametrosController::class, 'destroyNivelacion'])->name('parametros.nivelacion.destroy');

        Route::post('/parametros/listado-transporte', [ParametrosController::class, 'storeListadoTransporte'])->name('parametros.listado_transporte.store');
        Route::delete('/parametros/listado-transporte/{id}', [ParametrosController::class, 'destroyListadoTransporte'])->name('parametros.listado_transporte.destroy');

        Route::post('/parametros/observaciones', [ParametrosController::class, 'storeObservacion'])->name('parametros.observaciones.store');
        Route::delete('/parametros/observaciones/{id}', [ParametrosController::class, 'destroyObservacion'])->name('parametros.observaciones.destroy');
    });

    // ── Estudiantes: SuperAd, Admin, Ori, Sec* ───────────────────────────────
    Route::middleware('profile:SuperAd,Admin,Ori,Sec*')->group(function () {
        Route::get('/alumnos', [AlumnoController::class, 'index'])->name('alumnos.index');
        Route::get('/alumnos/{codigo}', [AlumnoController::class, 'show'])->name('alumnos.show');
        Route::get('/alumnos/{codigo}/editar', [AlumnoController::class, 'edit'])->name('alumnos.edit');
        Route::put('/alumnos/{codigo}', [AlumnoController::class, 'update'])->name('alumnos.update');
        Route::get('/alumnos/{codigo}/imprimir', [AlumnoController::class, 'printView'])->name('alumnos.print');
    });

    // ── Seguimiento Académico: SuperAd, Admin, Sec* ──────────────────────────
    Route::middleware('profile:SuperAd,Admin,Sec*')->group(function () {
        Route::get('/derroteros', [DeroterosController::class, 'index'])->name('derroteros.index');
        Route::get('/salvavidas/reporte', [SalvavidasController::class, 'reporte'])->name('salvavidas.reporte');
    });

    // ── Asistencia registro: SuperAd y Sec* ──────────────────────────────────
    Route::middleware('profile:SuperAd,Sec*')->group(function () {
        Route::get('/asistencia/registro', [AsistenciaController::class, 'registro'])->name('asistencia.registro');
        Route::post('/asistencia/guardar', [AsistenciaController::class, 'guardar'])->name('asistencia.guardar');
    });

    // ── Asistencia reporte: todos los autenticados ────────────────────────────
    Route::get('/asistencia/reporte', [AsistenciaController::class, 'reporte'])->name('asistencia.reporte');

    // ── Docentes: SuperAd, Admin, DOC* ───────────────────────────────────────
    Route::middleware('profile:SuperAd,Admin,DOC*')->group(function () {
        Route::get('/notas', [NotasController::class, 'index'])->name('notas.index');
        Route::post('/notas/guardar', [NotasController::class, 'guardar'])->name('notas.guardar');
        Route::get('/english-acq', [EnglishAcqController::class, 'docente'])->name('english-acq.docente');
        Route::post('/english-acq/registrar', [EnglishAcqController::class, 'registrar'])->name('english-acq.registrar');
        Route::delete('/english-acq/{id}', [EnglishAcqController::class, 'eliminar'])->name('english-acq.eliminar');
        Route::get('/salvavidas', [SalvavidasController::class, 'index'])->name('salvavidas.index');
        Route::post('/salvavidas/guardar', [SalvavidasController::class, 'guardar'])->name('salvavidas.guardar');
        Route::get('/derroteros/resolver', [DeroterosController::class, 'docente'])->name('derroteros.docente');
        Route::post('/derroteros/resolver', [DeroterosController::class, 'resolver'])->name('derroteros.resolver');
    });

    // ── PIAR: SuperAd y Ori ──────────────────────────────────────────────────
    Route::middleware('profile:SuperAd,Ori')->group(function () {
        Route::get('/piar', [PiarController::class, 'buscar'])->name('piar.buscar');
        Route::get('/piar/crear/{codigo}', [PiarController::class, 'crear'])->name('piar.crear');
        Route::post('/piar/guardar/{codigo}', [PiarController::class, 'guardar'])->name('piar.guardar');
    });

    // ── Horarios y boletines: SuperAd, Admin, Sec*, DOC* ────────────────────
    Route::middleware('profile:SuperAd,Admin,Sec*,DOC*')->group(function () {
        Route::get('/derroteros/horarios', [DeroterosController::class, 'horarios'])->name('derroteros.horarios');
        Route::post('/derroteros/horarios', [DeroterosController::class, 'guardarHorario'])->name('derroteros.horario.guardar');
        Route::get('/informes/boletin', [BoletinController::class, 'buscar'])->name('informes.boletin');
        Route::get('/boletines/{codigo}', [BoletinController::class, 'ver'])->name('boletines.ver');
    });
});

Route::post('/padres/salir', function () {
    session()->forget(['padre_verificado', 'padre_cedula', 'padre_codigo', 'padre_estudiante']);
    return redirect('/');
})->name('padres.salir');
