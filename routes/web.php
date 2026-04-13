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
use App\Http\Controllers\SolicitudCorreccionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FechasController;
use App\Http\Controllers\EnglishAcqController;
use App\Http\Controllers\ControlPlanillaController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\SalvavidasController;
use App\Http\Controllers\DeroterosController;
use App\Http\Controllers\BoletinController;
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
use App\Http\Controllers\ListadosEspecialesController;
use App\Http\Controllers\CircularesController;
use App\Http\Controllers\HorariosController;
use App\Http\Controllers\CalendarioAcademicoController;
use App\Http\Controllers\AsistenciaPersonalController;
use App\Http\Controllers\BackupController;

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
        Route::get('/admin/asignaciones', [AdminController::class, 'asignaciones'])->name('admin.asignaciones');
        Route::post('/admin/asignaciones/mover', [AdminController::class, 'moverAsignaciones'])->name('admin.asignaciones.mover');
        Route::post('/admin/asignaciones/mover-una', [AdminController::class, 'moverUnaAsignacion'])->name('admin.asignaciones.mover_una');
        Route::get('/admin/asignaciones/horario', [AdminController::class, 'verHorarioAsignacion'])->name('admin.asignaciones.horario');
        Route::post('/admin/asignaciones/horario/slot', [AdminController::class, 'asignarSlot'])->name('admin.asignaciones.horario.slot');
        Route::get('/admin/fechas', [FechasController::class, 'index'])->name('admin.fechas');
        Route::post('/admin/fechas', [FechasController::class, 'upsert'])->name('admin.fechas.upsert');
        Route::delete('/admin/fechas/{codigo}', [FechasController::class, 'destroy'])->name('admin.fechas.destroy');
        Route::get('/notas/reporte', [NotasController::class, 'reporte'])->name('notas.reporte');
        Route::get('/english-acq/informe', [EnglishAcqController::class, 'informe'])->name('english-acq.informe');
        Route::post('/english-acq/entregar', [EnglishAcqController::class, 'entregar'])->name('english-acq.entregar');
        Route::get('/control/planilla', [ControlPlanillaController::class, 'index'])->name('control.planilla');
        Route::get('/nomina', [NominaController::class, 'index'])->name('nomina.index');

        // ── Circulares ────────────────────────────────────────────────────────
        Route::get('/circulares', [CircularesController::class, 'index'])->name('circulares.index');
        Route::get('/circulares/nueva', [CircularesController::class, 'create'])->name('circulares.create');
        Route::post('/circulares', [CircularesController::class, 'store'])->name('circulares.store');
        Route::get('/circulares/{circular}', [CircularesController::class, 'show'])->name('circulares.show');
        Route::get('/circulares/{circular}/editar', [CircularesController::class, 'edit'])->name('circulares.edit');
        Route::put('/circulares/{circular}', [CircularesController::class, 'update'])->name('circulares.update');
        Route::delete('/circulares/{circular}', [CircularesController::class, 'destroy'])->name('circulares.destroy');
        Route::get('/circulares/{circular}/pdf', [CircularesController::class, 'pdf'])->name('circulares.pdf');

        // ── Listados Especiales ───────────────────────────────────────────────
        Route::get('/listados-especiales', [ListadosEspecialesController::class, 'index'])->name('listados.index');
        Route::post('/listados-especiales/grupo/crear', [ListadosEspecialesController::class, 'crearGrupo'])->name('listados.grupo.crear');
        Route::post('/listados-especiales/grupo/eliminar', [ListadosEspecialesController::class, 'eliminarGrupo'])->name('listados.grupo.eliminar');
        Route::post('/listados-especiales/docente', [ListadosEspecialesController::class, 'asignarDocente'])->name('listados.docente.asignar');
        Route::post('/listados-especiales/proyecto/asignar', [ListadosEspecialesController::class, 'asignarProyecto'])->name('listados.proyecto.asignar');
        Route::post('/listados-especiales/musica/asignar', [ListadosEspecialesController::class, 'asignarMusica'])->name('listados.musica.asignar');
        Route::post('/listados-especiales/quitar', [ListadosEspecialesController::class, 'quitar'])->name('listados.quitar');
    });

    // ── Copia de Seguridad (SuperAd, Admin, Contab, Sec*) ───────────────────
    Route::middleware('profile:SuperAd,Admin,Contab,Sec*')->group(function () {
        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::get('/backup/descargar', [BackupController::class, 'descargar'])->name('backup.descargar');
    });

    // ── Control de Pagos: lectura (Admin + Contab + SecC100) ────────────────
    Route::middleware('profile:SuperAd,Admin,Contab,SecC100')->group(function () {
        Route::get('/control/estudiante', [ControlEstudianteController::class, 'index'])->name('control.estudiante');
        Route::get('/pagos', [PagosController::class, 'index'])->name('pagos.index');
        Route::get('/cartera', [CarteraController::class, 'index'])->name('cartera.index');
        Route::get('/cartera/deudores', [CarteraController::class, 'deudores'])->name('cartera.deudores');
        Route::get('/cartera/estudiante/{codigo}', [CarteraController::class, 'estudiante'])->name('cartera.estudiante');
        Route::get('/cartera/seguimiento/informe', [CarteraController::class, 'informeSeguimiento'])->name('cartera.seguimiento.informe');
        Route::get('/cartera/por-cc', [CarteraController::class, 'carteraPorCC'])->name('cartera.por_cc');
        Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
        Route::get('/facturacion/auto', [FacturacionController::class, 'autoIndex'])->name('facturacion.auto');
        Route::get('/world-office', [WorldOfficeController::class, 'index'])->name('world-office.index');
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
        Route::post('/cartera/estudiante/{codigo}/seguimiento', [CarteraController::class, 'storeSeguimiento'])->name('cartera.seguimiento.store');
        Route::delete('/cartera/seguimiento/{id}', [CarteraController::class, 'destroySeguimiento'])->name('cartera.seguimiento.destroy');
        Route::put('/cartera/seguimiento/{id}', [CarteraController::class, 'updateSeguimiento'])->name('cartera.seguimiento.update');
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
        Route::post('/world-office/plantilla', [WorldOfficeController::class, 'guardarPlantilla'])->name('world-office.plantilla.store');
        Route::post('/world-office/exportar', [WorldOfficeController::class, 'exportarCSV'])->name('world-office.exportar');
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

    // ── Estudiantes: SuperAd, Admin, Ori, Sec* ───────────────────────────────
    Route::middleware('profile:SuperAd,Admin,Ori,Sec*')->group(function () {
        Route::get('/alumnos', [AlumnoController::class, 'index'])->name('alumnos.index');
        Route::get('/alumnos/imprimir-lista', [AlumnoController::class, 'printList'])->name('alumnos.print_list');
        Route::get('/alumnos/{codigo}', [AlumnoController::class, 'show'])->name('alumnos.show');
        Route::get('/alumnos/{codigo}/editar', [AlumnoController::class, 'edit'])->name('alumnos.edit');
        Route::put('/alumnos/{codigo}', [AlumnoController::class, 'update'])->name('alumnos.update');
        Route::get('/alumnos/{codigo}/imprimir', [AlumnoController::class, 'printView'])->name('alumnos.print');
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
    Route::middleware('profile:SuperAd,Admin,SecC100')->group(function () {
        Route::get('/calendario', [CalendarioAcademicoController::class, 'index'])->name('calendario.index');
    });

    // ── Calendario: edición de eventos (solo SuperAd y Admin) ────────────────
    Route::middleware('profile:SuperAd,Admin')->group(function () {
        Route::put('/calendario/{fecha}/evento', [CalendarioAcademicoController::class, 'guardarEvento'])->name('calendario.evento.guardar');
        Route::delete('/calendario/{fecha}/evento', [CalendarioAcademicoController::class, 'eliminarEvento'])->name('calendario.evento.eliminar');
    });

    // ── Calendario académico: Docentes (solo lectura) ─────────────────────────
    Route::middleware('profile:DOC*')->group(function () {
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
        Route::get('/llamadas/reporte', [LlamadasController::class, 'reporte'])->name('llamadas.reporte');
    });

    // ── Asistencia reporte: todos los autenticados ────────────────────────────
    Route::get('/asistencia/reporte', [AsistenciaController::class, 'reporte'])->name('asistencia.reporte');

    // ── PIAR Anexo 2 + Caracterizaciones ────────────────────────────────────
    Route::middleware('profile:SuperAd,Ori,Admin,DOC*')->group(function () {
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

        // Plan Casero
        Route::get('/piar/plan-casero/{codigo}/{codigoMat}',  [PiarMatController::class, 'formPlanCasero'])   ->name('piar.plan_casero.form');
        Route::post('/piar/plan-casero/{codigo}/{codigoMat}', [PiarMatController::class, 'guardarPlanCasero'])->name('piar.plan_casero.guardar');

        // Caracterización por materia
        Route::get('/piar/caracterizacion/mat/{codigo}/{codigoMat}',  [PiarCaractController::class, 'formMat'])   ->name('piar.caract.mat.form');
        Route::post('/piar/caracterizacion/mat/{codigo}/{codigoMat}', [PiarCaractController::class, 'guardarMat'])->name('piar.caract.mat.guardar');

        // Caracterización por director de grupo
        Route::get('/piar/caracterizacion/dir/{codigo}',  [PiarCaractController::class, 'formDir'])   ->name('piar.caract.dir.form');
        Route::post('/piar/caracterizacion/dir/{codigo}', [PiarCaractController::class, 'guardarDir'])->name('piar.caract.dir.guardar');
    });

    // ── PIAR: aprobación de etapas (Ori y SuperAd) ──────────────────────────
    Route::middleware('profile:SuperAd,Ori')->group(function () {
        Route::post('/piar/aprobar/caract-mat/{codigo}/{codigoMat}', [PiarCaractController::class, 'aprobarMat'])->name('piar.aprobar.caract.mat');
        Route::post('/piar/aprobar/caract-dir/{codigo}',             [PiarCaractController::class, 'aprobarDir'])->name('piar.aprobar.caract.dir');
        Route::post('/piar/aprobar/ajustes/{codigo}/{codigoMat}',    [PiarMatController::class,    'aprobar'])   ->name('piar.aprobar.ajustes');
    });

    // ── Corrección de notas: SuperAd, Admin, DOC*, Ori* ─────────────────────
    Route::middleware('profile:SuperAd,Admin,DOC*,Ori*')->group(function () {
        Route::get('/correcciones', [SolicitudCorreccionController::class, 'index'])->name('correcciones.index');
        Route::get('/correcciones/nueva', [SolicitudCorreccionController::class, 'create'])->name('correcciones.create');
        Route::post('/correcciones', [SolicitudCorreccionController::class, 'store'])->name('correcciones.store');
    });
    Route::middleware('profile:SuperAd,Admin')->group(function () {
        Route::post('/correcciones/{id}/aprobar', [SolicitudCorreccionController::class, 'aprobar'])->name('correcciones.aprobar');
        Route::post('/correcciones/{id}/rechazar', [SolicitudCorreccionController::class, 'rechazar'])->name('correcciones.rechazar');
    });

    // ── Docentes: SuperAd, Admin, DOC* ───────────────────────────────────────
    Route::middleware('profile:SuperAd,Admin,DOC*')->group(function () {
        Route::get('/notas', [NotasController::class, 'index'])->name('notas.index');
        Route::post('/notas/guardar', [NotasController::class, 'guardar'])->name('notas.guardar');
        Route::get('/notas-v2', [NotasV2Controller::class, 'index'])->name('notas.v2.index');
        Route::post('/notas-v2/columna', [NotasV2Controller::class, 'agregarColumna'])->name('notas.v2.columna.store');
        Route::delete('/notas-v2/columna/{id}', [NotasV2Controller::class, 'eliminarColumna'])->name('notas.v2.columna.destroy');
        Route::patch('/notas-v2/columna/{id}/peso', [NotasV2Controller::class, 'actualizarPeso'])->name('notas.v2.columna.peso');
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

    // ── PIAR: SuperAd y Ori ──────────────────────────────────────────────────
    Route::middleware('profile:SuperAd,Ori')->group(function () {
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

    // ── Horarios: SuperAd, Admin, Sec*, DOC* ────────────────────────────────
    Route::middleware('profile:SuperAd,Admin,Sec*,DOC*')->group(function () {
        Route::get('/derroteros/horarios', [DeroterosController::class, 'horarios'])->name('derroteros.horarios');
        Route::post('/derroteros/horarios', [DeroterosController::class, 'guardarHorario'])->name('derroteros.horario.guardar');
    });

    // ── Boletines: SuperAd, Admin, Sec*, DOC*, Ori* ──────────────────────────
    Route::middleware('profile:SuperAd,Admin,Sec*,DOC*,Ori*')->group(function () {
        Route::get('/informes/boletin', [BoletinController::class, 'buscar'])->name('informes.boletin');
        Route::get('/boletines/{codigo}', [BoletinController::class, 'ver'])->name('boletines.ver');
        Route::get('/informes/promedios/{codigo}', [BoletinController::class, 'promedios'])->name('informes.promedios');
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

    // ── Control de vigilancias: SuperAd y ConvCor28 ──────────────────────
    Route::middleware('profile:SuperAd,ConvCor28')->group(function () {
        Route::get('/vigilancias/control', [VigilanciaController::class, 'control'])->name('vigilancias.control');
    });

    // ── Horarios ─────────────────────────────────────────────────────────
    Route::get('/horarios', [HorariosController::class, 'index'])->name('horarios.index');
    Route::get('/horarios/curso', [HorariosController::class, 'porCurso'])->name('horarios.por_curso');
    Route::get('/horarios/docente', [HorariosController::class, 'porDocente'])->name('horarios.por_docente');
    Route::get('/horarios/disponibilidad', [HorariosController::class, 'disponibilidad'])->name('horarios.disponibilidad');
    Route::middleware('profile:SuperAd,Ori')->group(function () {
        Route::get('/horarios/conflictos', [HorariosController::class, 'conflictos'])->name('horarios.conflictos');
    });

    // ── Mi Horario: vista personal del docente ────────────────────────────
    Route::middleware('profile:DOC*')->group(function () {
        Route::get('/horarios/mi-horario', [HorariosController::class, 'miHorario'])->name('horarios.mi_horario');
    });

    // ── Notificaciones (cualquier usuario interno autenticado) ────────────
    Route::get('/notificaciones/nuevas',       [\App\Http\Controllers\NotificacionesController::class, 'nuevas'])->name('notificaciones.nuevas');
    Route::post('/notificaciones/{id}/leer',   [\App\Http\Controllers\NotificacionesController::class, 'leer'])  ->name('notificaciones.leer');
    Route::post('/notificaciones/leer-todas',  [\App\Http\Controllers\NotificacionesController::class, 'leerTodas'])->name('notificaciones.leer_todas');
});

Route::post('/padres/salir', function () {
    session()->forget(['padre_verificado', 'padre_cedula', 'padre_codigo', 'padre_estudiante']);
    return redirect('/');
})->name('padres.salir');
