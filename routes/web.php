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
use App\Http\Controllers\NotasV2Controller;
use App\Http\Controllers\InformeNotasController;
use App\Http\Controllers\SolicitudCorreccionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FechasController;
use App\Http\Controllers\EnglishAcqController;
use App\Http\Controllers\ControlPlanillaController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\SalvavidasController;
use App\Http\Controllers\DeroterosController;
use App\Http\Controllers\BoletinController;
use App\Http\Controllers\CertificadosController;
use App\Http\Controllers\InformeAnualController;
use App\Http\Controllers\PiarController;
use App\Http\Controllers\PiarMatController;
use App\Http\Controllers\PiarCaractController;
use App\Http\Controllers\ControlFechasController;
use App\Http\Controllers\ParametrosController;
use App\Http\Controllers\WorldOfficeController;
use App\Http\Controllers\RutasController;
use App\Http\Controllers\LlamadasController;
use App\Http\Controllers\VigilanciaController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\InventarioAseoController;
use App\Http\Controllers\ListadosEspecialesController;
use App\Http\Controllers\CircularesController;
use App\Http\Controllers\HorariosController;
use App\Http\Controllers\CalendarioAcademicoController;
use App\Http\Controllers\AsistenciaPersonalController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ListadoEstudiantesController;
use App\Http\Controllers\ObservacionesController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\DocumentacionController;
use App\Http\Controllers\AsignacionesResumenController;

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

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth'])->name('dashboard');

Route::post('/verificar-padre', [PadreVerificacionController::class, 'verificar'])->name('padre.verificar');

// Consulta pública temporal — English Acquisition (sin autenticación)
Route::match(['get','post'], '/ingles', [EnglishAcqController::class, 'consultaPublica'])->name('ingles.consulta');

Route::middleware('padre.verificado')->group(function () {
    Route::get('/padres/portal', [PadresController::class, 'portal'])->name('padres.portal');
    Route::get('/padres/estado-cuenta', [PadresController::class, 'estadoCuenta'])->name('padres.estado_cuenta');
    Route::get('/padres/notas', [PadresController::class, 'notas'])->name('padres.notas');
    Route::get('/padres/boletines', [PadresController::class, 'boletines'])->name('padres.boletines');
    Route::get('/padres/english-acq', [EnglishAcqController::class, 'padres'])->name('padres.english_acq');
    Route::get('/padres/asistencia', [AsistenciaController::class, 'padres'])->name('padres.asistencia');
    Route::get('/padres/salvavidas', [SalvavidasController::class, 'padres'])->name('padres.salvavidas');
    Route::get('/padres/derroteros', [DeroterosController::class, 'padres'])->name('padres.derroteros');
    Route::get('/padres/calendario', [CalendarioAcademicoController::class, 'padres'])->name('padres.calendario');
    Route::get('/padres/atencion-docentes', [PadresController::class, 'atencionDocentes'])->name('padres.atencion_docentes');
    Route::get('/padres/conducto-regular', [PadresController::class, 'conductoRegular'])->name('padres.conducto_regular');
    Route::get('/padres/circulares', [PadresController::class, 'circulares'])->name('padres.circulares');
    Route::get('/padres/circulares/{circular}', [PadresController::class, 'circularShow'])->name('padres.circulares.show');
    Route::get('/padres/documentacion', [DocumentacionController::class, 'padres'])->name('padres.documentacion');
    Route::get('/padres/bitacora', [PadresController::class, 'bitacora'])->name('padres.bitacora');
    Route::post('/padres/bitacora/{id}/confirmar', [PadresController::class, 'bitacoraConfirmar'])->name('padres.bitacora.confirmar');
    Route::post('/padres/bitacora/{id}/comentar', [PadresController::class, 'bitacoraComentar'])->name('padres.bitacora.comentar');
    Route::delete('/padres/bitacora/comentarios/{id}', [PadresController::class, 'bitacoraComentarBorrar'])->name('padres.bitacora.comentar.destroy');
});

Route::middleware(['auth'])->group(function () {

    // ── Panel de Control: solo SuperAd ───────────────────────────────────────
    Route::middleware('profile:SuperAd')->group(function () {
        Route::get('/admin/usuarios', [AdminController::class, 'usuarios'])->name('admin.usuarios');
        Route::post('/admin/usuarios', [AdminController::class, 'storeUsuario'])->name('admin.usuarios.store');
        Route::delete('/admin/usuarios/{user}', [AdminController::class, 'destroyUsuario'])->name('admin.usuarios.destroy');
        Route::post('/admin/docentes', [AdminController::class, 'storeDocente'])->name('admin.docentes.store');
        Route::post('/admin/docentes/{codigo}/toggle', [AdminController::class, 'toggleDocente'])->name('admin.docentes.toggle');
        Route::post('/admin/docentes/{codigo}/estado', [AdminController::class, 'setEstadoDocente'])->name('admin.docentes.estado');
        Route::get('/admin/directores', [AdminController::class, 'directores'])->name('admin.directores');
        Route::post('/admin/dir-grupo', [AdminController::class, 'asignarDirGrupo'])->name('admin.dir_grupo');
    });

    // ── Directores de grupo (solo lectura): Ori* ────────────────────────────
    Route::middleware('profile:SuperAd,Admin,Ori*')->group(function () {
        Route::get('/orientacion/directores', [AdminController::class, 'directores'])->name('orientacion.directores');
        Route::get('/admin/asignaciones', [AdminController::class, 'asignaciones'])->name('admin.asignaciones');
        Route::post('/admin/asignaciones/mover', [AdminController::class, 'moverAsignaciones'])->name('admin.asignaciones.mover');
        Route::post('/admin/asignaciones/mover-una', [AdminController::class, 'moverUnaAsignacion'])->name('admin.asignaciones.mover_una');
        Route::get('/admin/asignaciones/horario', [AdminController::class, 'verHorarioAsignacion'])->name('admin.asignaciones.horario');
        Route::post('/admin/asignaciones/horario/slot', [AdminController::class, 'asignarSlot'])->name('admin.asignaciones.horario.slot');
        Route::get('/admin/fechas', [FechasController::class, 'index'])->name('admin.fechas');
        Route::post('/admin/fechas', [FechasController::class, 'upsert'])->name('admin.fechas.upsert');
        Route::delete('/admin/fechas/{codigo}', [FechasController::class, 'destroy'])->name('admin.fechas.destroy');
        Route::get('/notas/reporte', [NotasController::class, 'reporte'])->name('notas.reporte');
        Route::get('/informes/notas', [InformeNotasController::class, 'index'])->name('informes.notas');
        Route::get('/english-acq/informe', [EnglishAcqController::class, 'informe'])->name('english-acq.informe');
        Route::post('/english-acq/entregar', [EnglishAcqController::class, 'entregar'])->name('english-acq.entregar');
        Route::get('/english-acq/proyecto/asignaciones', [EnglishAcqController::class, 'proyectoAsignaciones'])->name('english-acq.proyecto.asignaciones');
        Route::post('/english-acq/proyecto/asignar', [EnglishAcqController::class, 'asignarProyectoDocente'])->name('english-acq.proyecto.asignar');
        Route::get('/control/planilla', [ControlPlanillaController::class, 'index'])->name('control.planilla');
        Route::get('/nomina', [NominaController::class, 'index'])->name('nomina.index');
        Route::post('/nomina', [NominaController::class, 'store'])->name('nomina.store');
        Route::put('/nomina/{id}', [NominaController::class, 'update'])->name('nomina.update');
        Route::delete('/nomina/{id}', [NominaController::class, 'destroy'])->name('nomina.destroy');
        Route::post('/nomina/{id}/vacaciones', [NominaController::class, 'storeVacacion'])->name('nomina.vacaciones.store');
        Route::delete('/nomina/vacaciones/{vid}', [NominaController::class, 'destroyVacacion'])->name('nomina.vacaciones.destroy');
        Route::post('/nomina/{id}/incapacidades', [NominaController::class, 'storeIncapacidad'])->name('nomina.incapacidades.store');
        Route::delete('/nomina/incapacidades/{iid}', [NominaController::class, 'destroyIncapacidad'])->name('nomina.incapacidades.destroy');


        // ── Circulares ────────────────────────────────────────────────────────
    });

    // ── Circulares: lectura (incluye COR001) ─────────────────────────────────
    Route::middleware('profile:SuperAd,Admin,Ori*,SEC001,COR001')->group(function () {
        Route::get('/circulares', [CircularesController::class, 'index'])->name('circulares.index');
        Route::get('/circulares/{circular}', [CircularesController::class, 'show'])->name('circulares.show');
        Route::get('/circulares/{circular}/pdf', [CircularesController::class, 'pdf'])->name('circulares.pdf');
    });

    // ── Circulares: gestión (sin COR001) ─────────────────────────────────────
    Route::middleware('profile:SuperAd,Admin,Ori*,SEC001')->group(function () {
        Route::get('/circulares/nueva', [CircularesController::class, 'create'])->name('circulares.create');
        Route::post('/circulares', [CircularesController::class, 'store'])->name('circulares.store');
        Route::get('/circulares/{circular}/editar', [CircularesController::class, 'edit'])->name('circulares.edit');
        Route::put('/circulares/{circular}', [CircularesController::class, 'update'])->name('circulares.update');
        Route::delete('/circulares/{circular}', [CircularesController::class, 'destroy'])->name('circulares.destroy');
    });

    Route::middleware('profile:SuperAd,Admin,Ori*')->group(function () {
        // ── Documentación institucional ───────────────────────────────────────
        Route::get('/documentacion', [DocumentacionController::class, 'index'])->name('documentacion.index');
        Route::get('/documentacion/crear', [DocumentacionController::class, 'create'])->name('documentacion.create');
        Route::post('/documentacion', [DocumentacionController::class, 'store'])->name('documentacion.store');
        Route::get('/documentacion/{documento}/editar', [DocumentacionController::class, 'edit'])->name('documentacion.edit');
        Route::put('/documentacion/{documento}', [DocumentacionController::class, 'update'])->name('documentacion.update');
        Route::delete('/documentacion/{documento}', [DocumentacionController::class, 'destroy'])->name('documentacion.destroy');

        // ── Listados Especiales ───────────────────────────────────────────────
        Route::get('/listados-especiales', [ListadosEspecialesController::class, 'index'])->name('listados.index');
        Route::post('/listados-especiales/grupo/crear', [ListadosEspecialesController::class, 'crearGrupo'])->name('listados.grupo.crear');
        Route::post('/listados-especiales/grupo/eliminar', [ListadosEspecialesController::class, 'eliminarGrupo'])->name('listados.grupo.eliminar');
        Route::post('/listados-especiales/docente', [ListadosEspecialesController::class, 'asignarDocente'])->name('listados.docente.asignar');
        Route::post('/listados-especiales/proyecto/asignar', [ListadosEspecialesController::class, 'asignarProyecto'])->name('listados.proyecto.asignar');
        Route::post('/listados-especiales/musica/asignar', [ListadosEspecialesController::class, 'asignarMusica'])->name('listados.musica.asignar');
        Route::post('/listados-especiales/quitar', [ListadosEspecialesController::class, 'quitar'])->name('listados.quitar');
    });

    // ── Resumen de Asignaciones (solo lectura): Ori* y Coordinación ─────────
    Route::middleware('profile:Ori*,COR*')->group(function () {
        Route::get('/asignaciones/resumen', [AsignacionesResumenController::class, 'index'])->name('asignaciones.resumen');
    });

    // ── Salvavidas: índice de Google Sites (SuperAd, Admin, COR001) ─────────
    Route::middleware('profile:SuperAd,Admin,COR001')->group(function () {
        Route::get('/salvavidas/links', [SalvavidasController::class, 'links'])->name('salvavidas.links');
    });

    // ── Copia de Seguridad (SuperAd, Admin, Contab, Sec*) ───────────────────
    Route::middleware('profile:SuperAd,Admin,Contab,Sec*')->group(function () {
        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::get('/backup/descargar', [BackupController::class, 'descargar'])->name('backup.descargar');
    });

    // ── Cartera lectura + seguimiento de llamadas (Admin + Contab + SEC001 + SecC100/Paola) ──
    Route::middleware('profile:SuperAd,Admin,Contab,SEC001,SecC100')->group(function () {
        Route::get('/cartera', [CarteraController::class, 'index'])->name('cartera.index');
        Route::get('/cartera/seguimiento/informe', [CarteraController::class, 'informeSeguimiento'])->name('cartera.seguimiento.informe');
        Route::get('/cartera/por-cc', [CarteraController::class, 'carteraPorCC'])->name('cartera.por_cc');
        Route::get('/cartera/estudiante/{codigo}', [CarteraController::class, 'estudiante'])->name('cartera.estudiante');
        Route::post('/cartera/estudiante/{codigo}/seguimiento', [CarteraController::class, 'storeSeguimiento'])->name('cartera.seguimiento.store');
        Route::put('/cartera/seguimiento/{id}', [CarteraController::class, 'updateSeguimiento'])->name('cartera.seguimiento.update');
        Route::delete('/cartera/seguimiento/{id}', [CarteraController::class, 'destroySeguimiento'])->name('cartera.seguimiento.destroy');
    });

    // ── Control de Pagos: lectura + seguimiento cartera (Admin + Contab + SEC001) ────────────────
    Route::middleware('profile:SuperAd,Admin,Contab,SEC001')->group(function () {
        Route::get('/control/estudiante', [ControlEstudianteController::class, 'index'])->name('control.estudiante');
        Route::post('/control/estudiante/observacion', [ControlEstudianteController::class, 'saveObservacion'])->name('control.estudiante.observacion.save');
        Route::get('/pagos', [PagosController::class, 'index'])->name('pagos.index');
        Route::get('/cartera/deudores', [CarteraController::class, 'deudores'])->name('cartera.deudores');
        Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
        Route::get('/facturacion/exportar', [FacturacionController::class, 'exportarExcel'])->name('facturacion.exportar');
        Route::get('/facturacion/auto', [FacturacionController::class, 'autoIndex'])->name('facturacion.auto');
        Route::get('/pagos/exportar', [PagosController::class, 'exportarExcel'])->name('pagos.exportar');
        Route::get('/cartera/exportar-informe', [CarteraController::class, 'exportarInforme'])->name('cartera.exportar.informe');
        Route::get('/cartera/deudores/exportar', [CarteraController::class, 'exportarDeudores'])->name('cartera.exportar.deudores');
        Route::get('/world-office', [WorldOfficeController::class, 'index'])->name('world-office.index');
        Route::get('/listado-estudiantes', [ListadoEstudiantesController::class, 'index'])->name('listado-estudiantes.index');
        Route::post('/listado-estudiantes/exportar', [ListadoEstudiantesController::class, 'exportar'])->name('listado-estudiantes.exportar');
        Route::get('/importacion/registro-pagos', [ImportacionController::class, 'show'])->name('importacion.registro_pagos.show');
        Route::get('/importacion/facturacion', [ImportacionController::class, 'showFacturacion'])->name('importacion.facturacion.show');
        Route::get('/parametros', [ParametrosController::class, 'index'])->name('parametros.index');
    });

    // ── Control de Pagos: escritura (solo Admin/SuperAd) ─────────────────────
    Route::middleware('profile:SuperAd,Admin')->group(function () {
        Route::get('/pagos/crear', [PagosController::class, 'create'])->name('pagos.create');
        Route::post('/pagos', [PagosController::class, 'store'])->name('pagos.store');
        Route::get('/pagos/{id}/editar', [PagosController::class, 'edit'])->name('pagos.edit');
        Route::put('/pagos/{id}', [PagosController::class, 'update'])->name('pagos.update');
        Route::delete('/pagos/{id}', [PagosController::class, 'destroy'])->name('pagos.destroy');
        Route::get('/admin/exenciones-cartera', [\App\Http\Controllers\ExencionCarteraController::class, 'index'])->name('admin.exenciones-cartera.index');
        Route::post('/admin/exenciones-cartera', [\App\Http\Controllers\ExencionCarteraController::class, 'store'])->name('admin.exenciones-cartera.store');
        Route::delete('/admin/exenciones-cartera/{id}', [\App\Http\Controllers\ExencionCarteraController::class, 'destroy'])->name('admin.exenciones-cartera.destroy');
        Route::get('/facturacion/crear', [FacturacionController::class, 'create'])->name('facturacion.create');
        Route::post('/facturacion', [FacturacionController::class, 'store'])->name('facturacion.store');
        Route::get('/facturacion/{id}/editar', [FacturacionController::class, 'edit'])->name('facturacion.edit');
        Route::put('/facturacion/{id}', [FacturacionController::class, 'update'])->name('facturacion.update');
        Route::delete('/facturacion/{id}', [FacturacionController::class, 'destroy'])->name('facturacion.destroy');
        Route::post('/facturacion/auto/preview', [FacturacionController::class, 'autoPreview'])->name('facturacion.auto.preview');
        Route::post('/facturacion/auto/generar', [FacturacionController::class, 'autoGenerar'])->name('facturacion.auto.generar');
        Route::delete('/facturacion/auto/lote/{lote}', [FacturacionController::class, 'autoEliminarLote'])->name('facturacion.auto.lote.destroy');
        Route::post('/importacion/registro-pagos', [ImportacionController::class, 'importarRegistroPagos'])->name('importacion.registro_pagos');
        Route::delete('/importacion/registro-pagos/lote/{lote}', [ImportacionController::class, 'eliminarLotePagos'])->name('importacion.registro_pagos.lote.destroy');
        Route::post('/importacion/facturacion', [ImportacionController::class, 'importarFacturacion'])->name('importacion.facturacion');
        Route::delete('/importacion/facturacion/lote/{lote}', [ImportacionController::class, 'eliminarLoteFacturacion'])->name('importacion.facturacion.lote.destroy');
        Route::get('/alumnos/crear', [AlumnoController::class, 'create'])->name('alumnos.create');
        Route::post('/alumnos', [AlumnoController::class, 'store'])->name('alumnos.store');

        // ── Parámetros de Facturación ─────────────────────────────────────────
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

    // ── Retención de boletines: SuperAd + Coordinaciones ─────────────────────
    Route::middleware('profile:SuperAd,COR001,COR002')->group(function () {
        Route::get('/admin/retencion-boletines', [\App\Http\Controllers\RetencionBoletinController::class, 'index'])->name('admin.retencion-boletines.index');
        Route::post('/admin/retencion-boletines', [\App\Http\Controllers\RetencionBoletinController::class, 'store'])->name('admin.retencion-boletines.store');
        Route::delete('/admin/retencion-boletines/{id}', [\App\Http\Controllers\RetencionBoletinController::class, 'destroy'])->name('admin.retencion-boletines.destroy');
    });

    // ── World Office: escritura habilitada también para Contab ───────────────
    Route::middleware('profile:SuperAd,Admin,Contab')->group(function () {
        Route::post('/world-office/plantilla', [WorldOfficeController::class, 'guardarPlantilla'])->name('world-office.plantilla.store');
        Route::post('/world-office/exportar', [WorldOfficeController::class, 'exportarCSV'])->name('world-office.exportar');
    });

    // ── Estudiantes lectura: SuperAd, Admin, Ori, Sec*, COR* ─────────────────
    Route::middleware('profile:SuperAd,Admin,Ori,Sec*,COR*')->group(function () {
        Route::get('/alumnos', [AlumnoController::class, 'index'])->name('alumnos.index');
        Route::get('/alumnos/imprimir-lista', [AlumnoController::class, 'printList'])->name('alumnos.print_list');
        Route::get('/alumnos/{codigo}', [AlumnoController::class, 'show'])->name('alumnos.show');
        Route::get('/alumnos/{codigo}/imprimir', [AlumnoController::class, 'printView'])->name('alumnos.print');
    });

    // ── Estudiantes edicion: SuperAd, Admin, Ori, Sec* (sin COR*) ───────────
    Route::middleware('profile:SuperAd,Admin,Ori,Sec*')->group(function () {
        Route::get('/alumnos/{codigo}/editar', [AlumnoController::class, 'edit'])->name('alumnos.edit');
        Route::put('/alumnos/{codigo}', [AlumnoController::class, 'update'])->name('alumnos.update');
    });

    // ── Rutas de transporte: SuperAd, Admin y Sec* ────────────────────────────
    Route::middleware('profile:SuperAd,Admin,Sec*')->group(function () {
        Route::get('/rutas', [RutasController::class, 'index'])->name('rutas.index');
    });

    // ── Seguimiento Académico: SuperAd, Admin, Sec* ──────────────────────────
    Route::middleware('profile:SuperAd,Admin,Sec*')->group(function () {
        Route::get('/derroteros', [DeroterosController::class, 'index'])->name('derroteros.index');
        Route::get('/salvavidas/reporte', [SalvavidasController::class, 'reporte'])->name('salvavidas.reporte');
    });

    // ── Calendario académico: Admin y Secretaría (con edición) ───────────────
    Route::middleware('profile:SuperAd,Admin,SEC001,SecC100')->group(function () {
        Route::get('/calendario', [CalendarioAcademicoController::class, 'index'])->name('calendario.index');
    });

    // ── Calendario: gestión de eventos (solo SuperAd y Admin) ────────────────
    Route::middleware('profile:SuperAd,Admin')->group(function () {
        Route::post('/calendario/{fecha}/eventos',  [CalendarioAcademicoController::class, 'crearEvento'])     ->name('calendario.evento.crear');
        Route::put('/calendario/evento/{id}',       [CalendarioAcademicoController::class, 'actualizarEvento'])->name('calendario.evento.actualizar');
        Route::delete('/calendario/evento/{id}',    [CalendarioAcademicoController::class, 'eliminarEvento'])  ->name('calendario.evento.eliminar');
    });

    // ── Calendario académico: Docentes y Coordinadores (solo lectura) ────────
    Route::middleware('profile:DOC*,COR*')->group(function () {
        Route::get('/calendario/docente', [CalendarioAcademicoController::class, 'docente'])->name('calendario.docente');
    });

    // ── Asistencia registro: SuperAd y Sec* ──────────────────────────────────
    Route::middleware('profile:SuperAd,Sec*')->group(function () {
        Route::get('/asistencia/registro', [AsistenciaController::class, 'registro'])->name('asistencia.registro');
        Route::post('/asistencia/guardar', [AsistenciaController::class, 'guardar'])->name('asistencia.guardar');
    });

    // ── Llamadas por inasistencia: SuperAd, Admin y Sec* ─────────────────────
    Route::middleware('profile:SuperAd,Admin,Sec*')->group(function () {
        Route::get('/llamadas',         [LlamadasController::class, 'index'])  ->name('llamadas.index');
        Route::post('/llamadas',        [LlamadasController::class, 'store'])  ->name('llamadas.store');
    });

    // ── Reporte de llamadas: SuperAd, Admin, Sec*, COR* (incluye Martha) ────
    Route::middleware('profile:SuperAd,Admin,Sec*,COR*')->group(function () {
        Route::get('/llamadas/reporte', [LlamadasController::class, 'reporte'])->name('llamadas.reporte');
    });

    // ── Asistencia reporte: todos los autenticados ────────────────────────────
    Route::get('/asistencia/reporte', [AsistenciaController::class, 'reporte'])->name('asistencia.reporte');

    // ── PIAR Anexo 2 + Caracterizaciones ────────────────────────────────────
    Route::middleware('profile:SuperAd,Ori,Admin,DOC*,COR*,Piar')->group(function () {
        // Índice unificado (Anexo 2 + Caracterizaciones)
        Route::get('/piar/anexo2',                              [PiarCaractController::class, 'index'])   ->name('piar.anexo2.index');

        // Impresión completa Anexo 2 por estudiante (Ori/SuperAd) — debe ir ANTES de las rutas con {codigoMat}
        Route::get('/piar/anexo2/{codigo}/imprimir-completo',   [PiarCaractController::class, 'imprimirAnexo2'])->name('piar.anexo2.imprimir.est');
        // Impresión combinada Anexo 1 + Anexo 2
        Route::get('/piar/{codigo}/imprimir-todos',             [PiarController::class, 'imprimirTodos'])       ->name('piar.imprimir.todos');

        // Anexo 2 – Ajustes por período
        Route::get('/piar/anexo2/{codigo}/{codigoMat}',         [PiarMatController::class, 'form'])       ->name('piar.anexo2.form');
        Route::post('/piar/anexo2/{codigo}/{codigoMat}',        [PiarMatController::class, 'guardar'])    ->name('piar.anexo2.guardar');
        Route::get('/piar/anexo2/{codigo}/{codigoMat}/imprimir',[PiarMatController::class, 'imprimir'])   ->name('piar.anexo2.imprimir');

        // Plan Casero (Anexo 3) — la impresión por estudiante debe ir ANTES de las rutas con {codigoMat}
        Route::get('/piar/plan-casero/{codigo}/imprimir',     [PiarMatController::class, 'imprimirPlanCasero'])->name('piar.plan_casero.imprimir.est');
        Route::get('/piar/plan-casero/{codigo}/{codigoMat}',  [PiarMatController::class, 'formPlanCasero'])   ->name('piar.plan_casero.form');
        Route::post('/piar/plan-casero/{codigo}/{codigoMat}', [PiarMatController::class, 'guardarPlanCasero'])->name('piar.plan_casero.guardar');

        // Caracterización por materia
        Route::get('/piar/caracterizacion/mat/{codigo}/{codigoMat}',  [PiarCaractController::class, 'formMat'])   ->name('piar.caract.mat.form');
        Route::post('/piar/caracterizacion/mat/{codigo}/{codigoMat}', [PiarCaractController::class, 'guardarMat'])->name('piar.caract.mat.guardar');

        // Caracterización por director de grupo
        Route::get('/piar/caracterizacion/dir/{codigo}',  [PiarCaractController::class, 'formDir'])   ->name('piar.caract.dir.form');
        Route::post('/piar/caracterizacion/dir/{codigo}', [PiarCaractController::class, 'guardarDir'])->name('piar.caract.dir.guardar');
    });

    // ── PIAR: aprobación de etapas (Ori, Piar y SuperAd) ─────────────────────
    Route::middleware('profile:SuperAd,Ori,Piar')->group(function () {
        Route::post('/piar/aprobar/caract-mat/{codigo}/{codigoMat}', [PiarCaractController::class, 'aprobarMat'])->name('piar.aprobar.caract.mat');
        Route::post('/piar/aprobar/caract-dir/{codigo}',             [PiarCaractController::class, 'aprobarDir'])->name('piar.aprobar.caract.dir');
        Route::post('/piar/aprobar/ajustes/{codigo}/{codigoMat}',    [PiarMatController::class,    'aprobar'])   ->name('piar.aprobar.ajustes');
        Route::post('/piar/aprobar/plan-casero/{codigo}/{codigoMat}/{periodo}',[PiarMatController::class,    'aprobarPlanCasero'])->name('piar.aprobar.plan_casero');
    });

    // ── Corrección de notas: SuperAd, Admin, DOC*, Ori* ─────────────────────
    Route::middleware('profile:SuperAd,Admin,DOC*,COR*,Ori*')->group(function () {
        Route::get('/correcciones', [SolicitudCorreccionController::class, 'index'])->name('correcciones.index');
        Route::get('/correcciones/nueva', [SolicitudCorreccionController::class, 'create'])->name('correcciones.create');
        Route::post('/correcciones', [SolicitudCorreccionController::class, 'store'])->name('correcciones.store');
    });
    Route::middleware('profile:SuperAd,Admin')->group(function () {
        Route::post('/correcciones/{id}/aprobar', [SolicitudCorreccionController::class, 'aprobar'])->name('correcciones.aprobar');
        Route::post('/correcciones/{id}/rechazar', [SolicitudCorreccionController::class, 'rechazar'])->name('correcciones.rechazar');
    });

    // ── Docentes y Coordinadores: SuperAd, Admin, DOC*, COR* ─────────────────
    Route::middleware('profile:SuperAd,Admin,DOC*,COR*')->group(function () {
        Route::get('/notas', [NotasController::class, 'index'])->name('notas.index');
        Route::post('/notas/guardar', [NotasController::class, 'guardar'])->name('notas.guardar');
        Route::get('/notas-v2', [NotasV2Controller::class, 'index'])->name('notas.v2.index');
        Route::post('/notas-v2/columna', [NotasV2Controller::class, 'agregarColumna'])->name('notas.v2.columna.store');
        Route::delete('/notas-v2/columna/{id}', [NotasV2Controller::class, 'eliminarColumna'])->name('notas.v2.columna.destroy');
        Route::patch('/notas-v2/columna/{id}/peso', [NotasV2Controller::class, 'actualizarPeso'])->name('notas.v2.columna.peso');
        Route::patch('/notas-v2/columna/{id}/nombre', [NotasV2Controller::class, 'actualizarNombre'])->name('notas.v2.columna.nombre');
        Route::post('/notas-v2/guardar', [NotasV2Controller::class, 'guardar'])->name('notas.v2.guardar');
        Route::post('/notas-v2/entregar', [NotasV2Controller::class, 'entregar'])->name('notas.v2.entregar');
        Route::get('/english-acq', [EnglishAcqController::class, 'docente'])->name('english-acq.docente');
        Route::post('/english-acq/registrar', [EnglishAcqController::class, 'registrar'])->name('english-acq.registrar');
        Route::delete('/english-acq/{id}', [EnglishAcqController::class, 'eliminar'])->name('english-acq.eliminar');
        Route::get('/salvavidas', [SalvavidasController::class, 'index'])->name('salvavidas.index');
        Route::post('/salvavidas/guardar', [SalvavidasController::class, 'guardar'])->name('salvavidas.guardar');
        Route::get('/derroteros/resolver', [DeroterosController::class, 'docente'])->name('derroteros.docente');
        Route::post('/derroteros/resolver', [DeroterosController::class, 'resolver'])->name('derroteros.resolver');
        Route::get('/vigilancias', [VigilanciaController::class, 'docente'])->name('vigilancias.docente');
    });

    // ── PIAR: SuperAd, Ori y Piar ────────────────────────────────────────────
    Route::middleware('profile:SuperAd,Ori,Piar')->group(function () {
        // ── Control de etapas PIAR ───────────────────────────────────────────
        Route::get('/control/piar-etapas',  [ControlFechasController::class, 'index'])   ->name('control.piar_fechas');
        Route::post('/control/piar-etapas', [ControlFechasController::class, 'guardar']) ->name('control.piar_fechas.guardar');

        Route::get('/piar', [PiarController::class, 'buscar'])->name('piar.buscar');
        Route::get('/piar/informe', [PiarController::class, 'informe'])->name('piar.informe');
        Route::get('/piar/crear/{codigo}', [PiarController::class, 'crear'])->name('piar.crear');
        Route::post('/piar/guardar/{codigo}', [PiarController::class, 'guardar'])->name('piar.guardar');
        Route::delete('/piar/eliminar/{codigo}', [PiarController::class, 'eliminar'])->name('piar.eliminar');
        Route::get('/piar/imprimir/{codigo}', [PiarController::class, 'imprimir'])->name('piar.imprimir');
    });

    // ── Horarios: SuperAd, Admin, Sec*, DOC*, COR* ───────────────────────────
    Route::middleware('profile:SuperAd,Admin,Sec*,DOC*,COR*')->group(function () {
        Route::get('/derroteros/horarios', [DeroterosController::class, 'horarios'])->name('derroteros.horarios');
        Route::post('/derroteros/horarios', [DeroterosController::class, 'guardarHorario'])->name('derroteros.horario.guardar');
    });

    // ── Tablero de franjas (drag & drop): SuperAd, Admin ────────────────────
    Route::middleware('profile:SuperAd,Admin')->group(function () {
        Route::get('/derroteros/tablero', [DeroterosController::class, 'tablero'])->name('derroteros.tablero');
        Route::post('/derroteros/tablero/guardar', [DeroterosController::class, 'tableroGuardar'])->name('derroteros.tablero.guardar');
        Route::post('/derroteros/tablero/autoasignar', [DeroterosController::class, 'tableroAutoAsignar'])->name('derroteros.tablero.autoasignar');
        Route::post('/derroteros/tablero/confirmar', [DeroterosController::class, 'tableroConfirmar'])->name('derroteros.tablero.confirmar');
    });

    // ── Estadísticas de derroteros: solo SuperAd ─────────────────────────────
    Route::middleware('profile:SuperAd')->group(function () {
        Route::get('/derroteros/estadisticas', [DeroterosController::class, 'estadisticas'])->name('derroteros.estadisticas');
    });

    // ── Boletines: SuperAd, Admin, Sec*, DOC*, COR*, Ori* ────────────────────
    Route::middleware('profile:SuperAd,Admin,Sec*,DOC*,COR*,Ori*')->group(function () {
        Route::get('/informes/boletin', [BoletinController::class, 'buscar'])->name('informes.boletin');
        Route::get('/boletines/{codigo}', [BoletinController::class, 'ver'])->name('boletines.ver');
        Route::get('/informes/promedios/{codigo}', [BoletinController::class, 'promedios'])->name('informes.promedios');
    });

    // ── Certificados de notas (consolidado individual): SuperAd, Admin, SEC001, SecC100 ──
    // La impresión/PDF se hace desde el navegador (window.print), igual que los boletines.
    Route::middleware('profile:SuperAd,Admin,SEC001,SecC100')->group(function () {
        Route::get('/certificados/notas',           [CertificadosController::class, 'buscar'])->name('certificados.buscar');
        Route::get('/certificados/notas/{codigo}',  [CertificadosController::class, 'ver'])   ->name('certificados.ver');
    });

    // ── Informe anual de desempeño (años anteriores): SuperAd, Admin, SEC001, SecC100 ──
    // La impresión/PDF se hace desde el navegador (window.print), igual que los boletines.
    Route::middleware('profile:SuperAd,Admin,SEC001,SecC100')->group(function () {
        Route::get('/informes/anual',          [InformeAnualController::class, 'buscar'])->name('informe-anual.buscar');
        Route::get('/informes/anual/{codigo}', [InformeAnualController::class, 'ver'])   ->name('informe-anual.ver');
    });

    // ── Observaciones 2026: SuperAd, Admin, DOC* ────────────────────────────
    Route::middleware('profile:SuperAd,Admin,DOC*')->group(function () {
        Route::get('/observaciones',  [ObservacionesController::class, 'index'])->name('observaciones.index');
        Route::post('/observaciones', [ObservacionesController::class, 'store'])->name('observaciones.store');
    });

    // ── Bitácora: carga masiva de observaciones por curso (solo SuperAd + Coord.) ─
    Route::middleware('profile:SuperAd,COR001,COR002')->group(function () {
        Route::get('/bitacora/masiva',  [BitacoraController::class, 'masivaForm'])    ->name('bitacora.masiva');
        Route::post('/bitacora/masiva', [BitacoraController::class, 'masivaGuardar']) ->name('bitacora.masiva.guardar');
    });

    // ── Bitácora: tareas a un curso/grupo (Docentes + SuperAd) ───────────────
    // Un solo texto compartido para todo el grupo de la asignación (incluye
    // grupos de proyecto GP* y subgrupos de Artes/Música 7A-1).
    Route::middleware('profile:SuperAd,DOC*')->group(function () {
        Route::get('/bitacora/tareas',  [BitacoraController::class, 'tareasForm'])    ->name('bitacora.tareas');
        Route::post('/bitacora/tareas', [BitacoraController::class, 'tareasGuardar']) ->name('bitacora.tareas.guardar');
    });

    // ── Bitácora: registro individual + hilos (SuperAd + Coordinadores + Docentes) ─
    Route::middleware('profile:SuperAd,COR001,COR002,DOC*')->group(function () {
        Route::get('/bitacora',         [BitacoraController::class, 'index'])  ->name('bitacora.index');
        Route::post('/bitacora',        [BitacoraController::class, 'store'])  ->name('bitacora.store');
        Route::put('/bitacora/{id}',    [BitacoraController::class, 'update']) ->name('bitacora.update');
        Route::delete('/bitacora/{id}', [BitacoraController::class, 'destroy'])->name('bitacora.destroy');
        // Hilos de comentarios sobre una anotación
        Route::post('/bitacora/{id}/comentar',       [BitacoraController::class, 'comentar'])       ->name('bitacora.comentar');
        Route::delete('/bitacora/comentarios/{id}',  [BitacoraController::class, 'borrarComentario'])->name('bitacora.comentarios.destroy');
    });

    // ── Bitácora: consulta de agenda por estudiante (Docentes + SuperAd + Secretarías + Orientadores) ─
    // SuperAd/Admin/Secretarías/Orientadores ven toda la agenda; el docente director de grupo, la de
    // sus estudiantes; el resto, solo sus propias anotaciones. Solo lectura, con hilos.
    Route::middleware('profile:SuperAd,DOC*,Sec*,Ori*')->group(function () {
        Route::get('/bitacora/consulta', [BitacoraController::class, 'consulta'])->name('bitacora.consulta');
    });

    // ── Bitácora del Estudiante: configuración de catálogos (solo SuperAd) ───
    Route::middleware('profile:SuperAd')->group(function () {
        Route::get('/bitacora/config',                [BitacoraController::class, 'config'])         ->name('bitacora.config');
        Route::post('/bitacora/categorias',           [BitacoraController::class, 'storeCategoria']) ->name('bitacora.categorias.store');
        Route::put('/bitacora/categorias/{id}',       [BitacoraController::class, 'updateCategoria'])->name('bitacora.categorias.update');
        Route::delete('/bitacora/categorias/{id}',    [BitacoraController::class, 'destroyCategoria'])->name('bitacora.categorias.destroy');
        Route::post('/bitacora/plantillas',           [BitacoraController::class, 'storePlantilla']) ->name('bitacora.plantillas.store');
        Route::put('/bitacora/plantillas/{id}',       [BitacoraController::class, 'updatePlantilla'])->name('bitacora.plantillas.update');
        Route::delete('/bitacora/plantillas/{id}',    [BitacoraController::class, 'destroyPlantilla'])->name('bitacora.plantillas.destroy');
    });

    // ── Asistencia personal: SuperAd y SecA ven el estado; SecA registra ────
    Route::middleware('profile:SuperAd,Admin,SecA')->group(function () {
        Route::get('/asistencia-personal',          [AsistenciaPersonalController::class, 'index'])         ->name('asistencia-personal.index');
        Route::get('/asistencia-personal/registro', [AsistenciaPersonalController::class, 'registro'])      ->name('asistencia-personal.registro');
        Route::post('/asistencia-personal/registro',[AsistenciaPersonalController::class, 'guardarRegistro'])->name('asistencia-personal.guardar');
    });

    Route::middleware('profile:SuperAd')->group(function () {
        Route::get('/asistencia-personal/permisos',         [AsistenciaPersonalController::class, 'permisos'])       ->name('asistencia-personal.permisos');
        Route::post('/asistencia-personal/permisos',        [AsistenciaPersonalController::class, 'crearPermiso'])    ->name('asistencia-personal.permisos.crear');
        Route::delete('/asistencia-personal/permisos/{id}', [AsistenciaPersonalController::class, 'eliminarPermiso']) ->name('asistencia-personal.permisos.eliminar');
        Route::get('/asistencia-personal/reemplazos',        [AsistenciaPersonalController::class, 'reemplazos'])       ->name('asistencia-personal.reemplazos');
        Route::post('/asistencia-personal/reemplazos',       [AsistenciaPersonalController::class, 'asignarReemplazo']) ->name('asistencia-personal.reemplazos.asignar');
        Route::delete('/asistencia-personal/reemplazos/{id}',[AsistenciaPersonalController::class, 'quitarReemplazo'])  ->name('asistencia-personal.reemplazos.quitar');
    });

    // ── Vigilancias (admin): solo SuperAd ───────────────────────────────────
    Route::middleware('profile:SuperAd')->group(function () {
        Route::get('/vigilancias/admin', [VigilanciaController::class, 'admin'])->name('vigilancias.admin');
        Route::post('/vigilancias/asignaciones', [VigilanciaController::class, 'guardarAsignaciones'])->name('vigilancias.asignaciones.guardar');
        Route::post('/vigilancias/calendario', [VigilanciaController::class, 'guardarCalendario'])->name('vigilancias.calendario.guardar');
        Route::delete('/vigilancias/calendario/{id}', [VigilanciaController::class, 'eliminarCalendario'])->name('vigilancias.calendario.eliminar');
        Route::get('/vigilancias/reasignaciones', [VigilanciaController::class, 'reasignaciones'])->name('vigilancias.reasignaciones');
        Route::post('/vigilancias/reasignar/una', [VigilanciaController::class, 'reasignarUna'])->name('vigilancias.reasignar.una');
        Route::post('/vigilancias/reasignar/bloque', [VigilanciaController::class, 'reasignarBloque'])->name('vigilancias.reasignar.bloque');
        Route::post('/vigilancias/docente/agregar', [VigilanciaController::class, 'agregarDocente'])->name('vigilancias.docente.agregar');
    });

    // ── Control de vigilancias: SuperAd, ConvCor28 (legacy) y COR* ───────
    Route::middleware('profile:SuperAd,ConvCor28,COR*')->group(function () {
        Route::get('/vigilancias/control', [VigilanciaController::class, 'control'])->name('vigilancias.control');
    });

    // ── Horarios ─────────────────────────────────────────────────────────
    Route::get('/horarios', [HorariosController::class, 'index'])->name('horarios.index');
    Route::get('/horarios/curso', [HorariosController::class, 'porCurso'])->name('horarios.por_curso');
    Route::get('/horarios/docente', [HorariosController::class, 'porDocente'])->name('horarios.por_docente');
    Route::get('/horarios/disponibilidad', [HorariosController::class, 'disponibilidad'])->name('horarios.disponibilidad');
    Route::middleware('profile:SuperAd')->group(function () {
        Route::get('/horarios/conflictos', [HorariosController::class, 'conflictos'])->name('horarios.conflictos');
    });

    // ── Mi Horario: vista personal del docente y coordinador ──────────────
    Route::middleware('profile:DOC*,COR*')->group(function () {
        Route::get('/horarios/mi-horario', [HorariosController::class, 'miHorario'])->name('horarios.mi_horario');
    });

    // ── Notificaciones (cualquier usuario interno autenticado) ────────────
    Route::get('/notificaciones/nuevas',       [\App\Http\Controllers\NotificacionesController::class, 'nuevas'])->name('notificaciones.nuevas');
    Route::post('/notificaciones/{id}/leer',   [\App\Http\Controllers\NotificacionesController::class, 'leer'])  ->name('notificaciones.leer');
    Route::post('/notificaciones/leer-todas',  [\App\Http\Controllers\NotificacionesController::class, 'leerTodas'])->name('notificaciones.leer_todas');

    // ── Inventario de uniformes (INVCBI): SuperAd, Admin, Secretarías ─────
    Route::middleware('profile:SuperAd,Admin,Sec*')->group(function () {
        Route::get('/inventario',                 [InventarioController::class, 'dashboard'])    ->name('inventario.dashboard');

        Route::get('/inventario/productos',        [InventarioController::class, 'productos'])    ->name('inventario.productos');
        Route::post('/inventario/productos',       [InventarioController::class, 'productoStore'])->name('inventario.productos.store');
        Route::put('/inventario/productos/{id}',   [InventarioController::class, 'productoUpdate'])->name('inventario.productos.update');

        // Precios y costos + facturación: solo Admin y SuperAd.
        Route::middleware('profile:SuperAd,Admin')->group(function () {
            Route::get('/inventario/precios',          [InventarioController::class, 'precios'])     ->name('inventario.precios');
            Route::put('/inventario/precios/{id}',     [InventarioController::class, 'precioUpdate'])->name('inventario.precios.update');

            Route::get('/inventario/facturar',         [InventarioController::class, 'facturarIndex'])->name('inventario.facturar');
            Route::post('/inventario/facturar',        [InventarioController::class, 'facturar'])     ->name('inventario.facturar.guardar');
        });

        // Devoluciones y cambios (secretarías, Admin y SuperAd).
        Route::get('/inventario/cambios',          [InventarioController::class, 'cambiosIndex'])  ->name('inventario.cambios');
        Route::post('/inventario/cambios',         [InventarioController::class, 'cambioStore'])   ->name('inventario.cambios.guardar');
        Route::get('/inventario/api/venta',        [InventarioController::class, 'ventaPorNumero']) ->name('inventario.api.venta');

        Route::get('/inventario/proveedores',      [InventarioController::class, 'proveedores'])      ->name('inventario.proveedores');
        Route::post('/inventario/proveedores',     [InventarioController::class, 'proveedorStore'])   ->name('inventario.proveedores.store');

        Route::get('/inventario/compras',          [InventarioController::class, 'compras'])      ->name('inventario.compras');
        Route::get('/inventario/compras/nueva',    [InventarioController::class, 'compraCreate'])  ->name('inventario.compras.create');
        Route::post('/inventario/compras',         [InventarioController::class, 'compraStore'])   ->name('inventario.compras.store');

        Route::get('/inventario/ventas',           [InventarioController::class, 'ventas'])       ->name('inventario.ventas');
        Route::get('/inventario/ventas/nueva',     [InventarioController::class, 'ventaCreate'])   ->name('inventario.ventas.create');
        Route::post('/inventario/ventas',          [InventarioController::class, 'ventaStore'])    ->name('inventario.ventas.store');
        Route::post('/inventario/ventas/{id}/anular', [InventarioController::class, 'ventaAnular'])->name('inventario.ventas.anular');

        // Búsquedas para el escáner / POS (JSON)
        Route::get('/inventario/api/producto',     [InventarioController::class, 'buscarProducto'])  ->name('inventario.api.producto');
        Route::get('/inventario/api/estudiante',   [InventarioController::class, 'buscarEstudiante'])->name('inventario.api.estudiante');

        // ── Inventario de ASEO ──
        Route::get('/aseo',                  [InventarioAseoController::class, 'dashboard'])     ->name('aseo.dashboard');
        Route::get('/aseo/elementos',        [InventarioAseoController::class, 'elementos'])     ->name('aseo.elementos');
        Route::post('/aseo/elementos',       [InventarioAseoController::class, 'elementoStore']) ->name('aseo.elementos.store');
        Route::put('/aseo/elementos/{id}',   [InventarioAseoController::class, 'elementoUpdate'])->name('aseo.elementos.update');
        Route::get('/aseo/dependencias',     [InventarioAseoController::class, 'dependencias'])  ->name('aseo.dependencias');
        Route::post('/aseo/dependencias',    [InventarioAseoController::class, 'dependenciaStore'])->name('aseo.dependencias.store');
        Route::get('/aseo/compras',          [InventarioAseoController::class, 'compras'])       ->name('aseo.compras');
        Route::get('/aseo/compras/nueva',    [InventarioAseoController::class, 'compraCreate'])  ->name('aseo.compras.create');
        Route::post('/aseo/compras',         [InventarioAseoController::class, 'compraStore'])   ->name('aseo.compras.store');
        Route::get('/aseo/salidas',          [InventarioAseoController::class, 'salidas'])       ->name('aseo.salidas');
        Route::get('/aseo/salidas/nueva',    [InventarioAseoController::class, 'salidaCreate'])  ->name('aseo.salidas.create');
        Route::post('/aseo/salidas',         [InventarioAseoController::class, 'salidaStore'])   ->name('aseo.salidas.store');
    });
});

Route::post('/padres/salir', function () {
    session()->forget(['padre_verificado', 'padre_cedula', 'padre_codigo', 'padre_estudiante']);
    return redirect('/');
})->name('padres.salir');
