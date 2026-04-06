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
        Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
        Route::get('/facturacion/crear', [FacturacionController::class, 'create'])->name('facturacion.create');
        Route::post('/facturacion', [FacturacionController::class, 'store'])->name('facturacion.store');
        Route::get('/importacion/registro-pagos', [ImportacionController::class, 'show'])->name('importacion.registro_pagos.show');
        Route::post('/importacion/registro-pagos', [ImportacionController::class, 'importarRegistroPagos'])->name('importacion.registro_pagos');
        Route::get('/importacion/facturacion', [ImportacionController::class, 'showFacturacion'])->name('importacion.facturacion.show');
        Route::post('/importacion/facturacion', [ImportacionController::class, 'importarFacturacion'])->name('importacion.facturacion');
        Route::get('/alumnos/crear', [AlumnoController::class, 'create'])->name('alumnos.create');
        Route::post('/alumnos', [AlumnoController::class, 'store'])->name('alumnos.store');
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
