<?php
/**
 * PASSBALL Cup - Crear Equipo
 */
$tituloPagina = 'Crear Equipo';
require_once __DIR__ . '/../controllers/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

$stmt = $pdo->prepare("SELECT id FROM equipo_miembros WHERE usuario_id = ? AND estado = 'activo'");
$stmt->execute([$usuario['id']]);
if ($stmt->fetch()) {
    $_SESSION['flash_error'] = 'Ya perteneces a un equipo';
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="section-header">
    <h1 class="section-title">⚽ Crear Equipo</h1>
    <a href="index.php" class="btn btn-secondary btn-sm">← Volver</a>
</div>

<div class="card form-card">
    <form id="crearEquipoForm">
        <div class="form-group">
            <label for="nombre">Nombre del Equipo *</label>
            <input type="text" id="nombre" name="nombre" required minlength="3" maxlength="100" 
                   placeholder="Ej: Los Tigres" class="form-input">
        </div>

        <div class="form-group">
            <label for="color">Color del Equipo</label>
            <div class="color-picker-row">
                <input type="color" id="color" name="color" value="#7c4293" class="color-input">
                <span id="colorHex" class="color-hex">#7c4293</span>
            </div>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción (opcional)</label>
            <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"
                      placeholder="Cuéntanos sobre tu equipo..." class="form-input"></textarea>
        </div>

        <button type="submit" class="btn btn-primary" id="btnCrear">
            Crear Equipo
        </button>
    </form>
</div>

<script src="../assets/js/app.js"></script>
<script src="../assets/js/equipos.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
