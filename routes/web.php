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
});

Route::middleware(['auth'])->group(function () {
    Route::get('/control/estudiante', [ControlEstudianteController::class, 'index'])->name('control.estudiante');
    Route::get('/pagos', [PagosController::class, 'index'])->name('pagos.index');
    Route::get('/cartera', [CarteraController::class, 'index'])->name('cartera.index');
    Route::get('/alumnos', [AlumnoController::class, 'index'])->name('alumnos.index');
    Route::get('/alumnos/crear', [AlumnoController::class, 'create'])->name('alumnos.create');
    Route::post('/alumnos', [AlumnoController::class, 'store'])->name('alumnos.store');
    Route::get('/alumnos/{codigo}', [AlumnoController::class, 'show'])->name('alumnos.show');
    Route::get('/alumnos/{codigo}/editar', [AlumnoController::class, 'edit'])->name('alumnos.edit');
    Route::put('/alumnos/{codigo}', [AlumnoController::class, 'update'])->name('alumnos.update');
    Route::get('/alumnos/{codigo}/imprimir', [AlumnoController::class, 'printView'])->name('alumnos.print');
    Route::get('/admin/fechas', [FechasController::class, 'index'])->name('admin.fechas');
    Route::post('/admin/fechas', [FechasController::class, 'upsert'])->name('admin.fechas.upsert');
    Route::delete('/admin/fechas/{codigo}', [FechasController::class, 'destroy'])->name('admin.fechas.destroy');
    Route::get('/admin/usuarios', [AdminController::class, 'index'])->name('admin.usuarios');
    Route::post('/admin/usuarios', [AdminController::class, 'storeUsuario'])->name('admin.usuarios.store');
    Route::delete('/admin/usuarios/{user}', [AdminController::class, 'destroyUsuario'])->name('admin.usuarios.destroy');
    Route::post('/admin/docentes/{codigo}/toggle', [AdminController::class, 'toggleDocente'])->name('admin.docentes.toggle');
    Route::post('/admin/asignaciones/mover', [AdminController::class, 'moverAsignaciones'])->name('admin.asignaciones.mover');
    Route::post('/admin/asignaciones/mover-una', [AdminController::class, 'moverUnaAsignacion'])->name('admin.asignaciones.mover_una');
    Route::get('/notas', [NotasController::class, 'index'])->name('notas.index');
    Route::get('/notas/reporte', [NotasController::class, 'reporte'])->name('notas.reporte');
    Route::post('/notas/guardar', [NotasController::class, 'guardar'])->name('notas.guardar');
    Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
    Route::get('/facturacion/crear', [FacturacionController::class, 'create'])->name('facturacion.create');
    Route::post('/facturacion', [FacturacionController::class, 'store'])->name('facturacion.store');
    Route::get('/pagos/crear', [PagosController::class, 'create'])->name('pagos.create');
    Route::post('/pagos', [PagosController::class, 'store'])->name('pagos.store');
    Route::get('/importacion/registro-pagos', [ImportacionController::class, 'show'])->name('importacion.registro_pagos.show');
    Route::post('/importacion/registro-pagos', [ImportacionController::class, 'importarRegistroPagos'])->name('importacion.registro_pagos');
    Route::get('/importacion/facturacion', [ImportacionController::class, 'showFacturacion'])->name('importacion.facturacion.show');
    Route::post('/importacion/facturacion', [ImportacionController::class, 'importarFacturacion'])->name('importacion.facturacion');
});

Route::post('/padres/salir', function () {
    session()->forget(['padre_verificado', 'padre_cedula', 'padre_codigo', 'padre_estudiante']);
    return redirect('/');
})->name('padres.salir');
