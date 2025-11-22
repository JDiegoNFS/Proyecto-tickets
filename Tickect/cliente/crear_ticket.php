<?php
require_once '../includes/auth.php';
verificarRol('cliente');
require_once '../includes/db.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";
$error = "";

$departamentos = $pdo->query("SELECT id, nombre FROM departamentos WHERE tipo = 'usuario' ORDER BY nombre")->fetchAll();
$categorias = $pdo->query("SELECT id, nombre, departamento_id FROM categorias ORDER BY nombre")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoria_id = intval($_POST["categoria_id"] ?? 0);
    $departamento_id = intval($_POST["departamento_id"] ?? 0);
    $descripcion = trim($_POST["descripcion"] ?? "");
    $imagenes_pegadas = $_POST["imagenes_pegadas"] ?? "";

    if ($categoria_id && $departamento_id && $descripcion !== "") {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO tickets (categoria_id, departamento_id, usuario_id, cliente_id, descripcion, imagenes_pegadas, estado, fecha_creacion)
                VALUES (?, ?, NULL, ?, ?, ?, 'pendiente', NOW())
            ");
            $stmt->execute([$categoria_id, $departamento_id, $usuario_id, $descripcion, $imagenes_pegadas]);

            $ticket_id = $pdo->lastInsertId();

            // Procesar archivos adjuntos
            if (!empty($_FILES['archivos']['name'][0])) {
                $directorio = "../uploads/tickets/";
                if (!is_dir($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                $permitidos = [
                    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                    'application/pdf', 'text/plain',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/zip', 'application/x-rar-compressed'
                ];

                foreach ($_FILES['archivos']['name'] as $i => $nombre) {
                    $tmp = $_FILES['archivos']['tmp_name'][$i];
                    $tipo = $_FILES['archivos']['type'][$i];
                    $tamaño = $_FILES['archivos']['size'][$i];
                    $nombre_limpio = basename($nombre);
                    $ruta = $directorio . uniqid() . "_" . $nombre_limpio;

                    if (in_array($tipo, $permitidos) && $tamaño <= 10 * 1024 * 1024) { // 10MB max
                        if (move_uploaded_file($tmp, $ruta)) {
                            $ruta_db = str_replace("../", "", $ruta);
                            $stmtArchivo = $pdo->prepare("
                                INSERT INTO archivos_tickets (ticket_id, nombre_archivo, ruta_archivo, tipo_archivo, tamaño_archivo) 
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmtArchivo->execute([$ticket_id, $nombre_limpio, $ruta_db, $tipo, $tamaño]);
                        }
                    }
                }
            }

            $accion = "Ticket creado por el Cliente";
            $stmtHist = $pdo->prepare("
                INSERT INTO historial_tickets (ticket_id, usuario_id, rol, accion)
                VALUES (?, ?, 'cliente', ?)
            ");
            $stmtHist->execute([$ticket_id, $usuario_id, $accion]);

            $pdo->commit();
            
            // Redirigir a la vista principal con mensaje de éxito
            header("Location: ver_tickets.php?mensaje=ticket_creado&ticket_id=$ticket_id");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "❌ Error al registrar el ticket: " . $e->getMessage();
        }
    } else {
        $error = "⚠️ Todos los campos son obligatorios.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Ticket - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_usuario_crear_ticket.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="page-container">
        <div class="header-section">
            <div class="breadcrumb">
                <a href="ver_tickets.php" class="breadcrumb-link">
                    <i class="fas fa-arrow-left"></i> Volver a Mis Tickets
                </a>
            </div>
            <h1 class="page-title">
                <i class="fas fa-plus-circle"></i>
                Crear Nuevo Ticket
            </h1>
            <p class="page-subtitle">Completa el formulario para registrar tu solicitud</p>
        </div>

        <div class="form-container">
            <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="ticket-form" id="ticketForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="departamento_id" class="form-label">
                            <i class="fas fa-building"></i>
                            Departamento <span class="required">*</span>
                        </label>
                        <select name="departamento_id" id="departamento_id" required class="form-select">
                            <option value="">Selecciona un departamento...</option>
                            <?php foreach ($departamentos as $depto): ?>
                                <option value="<?php echo $depto['id']; ?>">
                                    <?php echo htmlspecialchars($depto['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-help">Primero selecciona el departamento que atenderá tu solicitud</div>
                    </div>

                    <div class="form-group">
                        <label for="categoria_id" class="form-label">
                            <i class="fas fa-tag"></i>
                            Categoría del Ticket <span class="required">*</span>
                        </label>
                        <select name="categoria_id" id="categoria_id" required class="form-select" disabled>
                            <option value="">Primero selecciona un departamento...</option>
                        </select>
                        <div class="form-help">Selecciona la categoría que mejor describe tu solicitud</div>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="descripcion" class="form-label">
                        <i class="fas fa-comment-alt"></i>
                        Descripción Detallada <span class="required">*</span>
                        <span class="paste-hint">
                            <i class="fas fa-magic"></i>
                            Puedes pegar imágenes con Ctrl+V
                        </span>
                    </label>
                    <div class="rich-text-container">
                        <div 
                            id="descripcion" 
                            class="rich-text-editor"
                            contenteditable="true"
                            data-placeholder="Describe detalladamente tu problema o solicitud. Incluye pasos para reproducir el error, capturas de pantalla si es necesario, y cualquier información adicional que pueda ayudar a resolver tu caso."
                        ></div>
                        <input type="hidden" name="descripcion" id="descripcion_hidden">
                        <input type="hidden" name="imagenes_pegadas" id="imagenes_pegadas">
                    </div>
                    <div class="form-help">
                        <i class="fas fa-info-circle"></i>
                        Sé específico y detallado para obtener una respuesta más rápida
                    </div>
                    <div class="char-counter">
                        <span id="charCount">0</span> / 2000 caracteres
                    </div>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">
                        <i class="fas fa-paperclip"></i>
                        Archivos Adjuntos
                    </label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="upload-content">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h4>Arrastra archivos aquí o haz clic para seleccionar</h4>
                            <p>Tipos permitidos: JPG, PNG, GIF, PDF, DOC, XLS, TXT, ZIP, RAR</p>
                            <p>Tamaño máximo: 10MB por archivo</p>
                        </div>
                        <input type="file" 
                               name="archivos[]" 
                               id="archivos" 
                               multiple 
                               class="file-input"
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                    </div>
                    <div class="file-list" id="fileList"></div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Crear Ticket
                    </button>
                    <a href="ver_tickets.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <div class="info-panel">
            <h3><i class="fas fa-lightbulb"></i> Consejos para un ticket efectivo</h3>
            <ul>
                <li><i class="fas fa-check"></i> Proporciona información específica y detallada</li>
                <li><i class="fas fa-check"></i> Incluye pasos para reproducir el problema</li>
                <li><i class="fas fa-check"></i> Adjunta capturas de pantalla si es necesario</li>
                <li><i class="fas fa-check"></i> Menciona el impacto en tu trabajo</li>
            </ul>
        </div>
    </div>

    <script>
        // Variables globales
        let pastedImages = [];
        
        // Editor de texto enriquecido
        const editor = document.getElementById('descripcion');
        const charCount = document.getElementById('charCount');
        const descripcionHidden = document.getElementById('descripcion_hidden');
        const imagenesPegadas = document.getElementById('imagenes_pegadas');
        
        // Configurar placeholder
        function setupPlaceholder() {
            if (editor.textContent.trim() === '') {
                editor.textContent = editor.dataset.placeholder;
                editor.classList.add('placeholder');
            }
        }
        
        function removePlaceholder() {
            if (editor.classList.contains('placeholder')) {
                editor.textContent = '';
                editor.classList.remove('placeholder');
            }
        }
        
        // Inicializar placeholder
        setupPlaceholder();
        
        // Eventos del editor
        editor.addEventListener('focus', removePlaceholder);
        editor.addEventListener('blur', setupPlaceholder);
        editor.addEventListener('input', function() {
            removePlaceholder();
            updateCharCount();
        });
        
        // Contador de caracteres
        function updateCharCount() {
            const text = editor.textContent || editor.innerText || '';
            const length = text.length;
            charCount.textContent = length;
            
            if (length > 1800) {
                charCount.style.color = '#ff6b6b';
            } else if (length > 1500) {
                charCount.style.color = '#ffa726';
            } else {
                charCount.style.color = '#4caf50';
            }
        }
        
        // Manejo de pegar imágenes
        editor.addEventListener('paste', function(e) {
            const items = e.clipboardData.items;
            
            for (let item of items) {
                if (item.type.indexOf('image') !== -1) {
                    e.preventDefault();
                    
                    const file = item.getAsFile();
                    const reader = new FileReader();
                    
                    reader.onload = function(event) {
                        const img = document.createElement('img');
                        img.src = event.target.result;
                        img.style.maxWidth = '100%';
                        img.style.height = 'auto';
                        img.style.borderRadius = '8px';
                        img.style.margin = '10px 0';
                        img.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                        
                        // Insertar imagen en el editor
                        const selection = window.getSelection();
                        if (selection.rangeCount > 0) {
                            const range = selection.getRangeAt(0);
                            range.deleteContents();
                            range.insertNode(img);
                            range.setStartAfter(img);
                            range.collapse(true);
                            selection.removeAllRanges();
                            selection.addRange(range);
                        } else {
                            editor.appendChild(img);
                        }
                        
                        // Guardar imagen para envío
                        pastedImages.push(event.target.result);
                        imagenesPegadas.value = JSON.stringify(pastedImages);
                        
                        updateCharCount();
                    };
                    
                    reader.readAsDataURL(file);
                }
            }
        });
        
        // Manejo de archivos adjuntos
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('archivos');
        const fileList = document.getElementById('fileList');
        
        // Drag and drop
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            handleFiles(files);
        });
        
        // Click para seleccionar archivos
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });
        
        function handleFiles(files) {
            Array.from(files).forEach(file => {
                if (validateFile(file)) {
                    addFileToList(file);
                }
            });
        }
        
        function validateFile(file) {
            const allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'text/plain',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip', 'application/x-rar-compressed'
            ];
            
            if (!allowedTypes.includes(file.type)) {
                alert(`Tipo de archivo no permitido: ${file.name}`);
                return false;
            }
            
            if (file.size > 10 * 1024 * 1024) {
                alert(`Archivo demasiado grande: ${file.name} (máximo 10MB)`);
                return false;
            }
            
            return true;
        }
        
        function addFileToList(file) {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <div class="file-info">
                    <i class="fas fa-file"></i>
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">${formatFileSize(file.size)}</span>
                </div>
                <button type="button" class="remove-file" onclick="removeFile(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            fileList.appendChild(fileItem);
        }
        
        function removeFile(button) {
            button.parentElement.remove();
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Datos de categorías para filtrado
        const categorias = <?php echo json_encode($categorias); ?>;
        
        // Filtrado de categorías por departamento
        document.getElementById('departamento_id').addEventListener('change', function() {
            const departamentoId = this.value;
            const categoriaSelect = document.getElementById('categoria_id');
            
            // Limpiar opciones actuales
            categoriaSelect.innerHTML = '<option value="">Selecciona una categoría...</option>';
            
            if (departamentoId) {
                // Filtrar categorías por departamento
                const categoriasFiltradas = categorias.filter(cat => cat.departamento_id == departamentoId);
                
                if (categoriasFiltradas.length > 0) {
                    categoriasFiltradas.forEach(categoria => {
                        const option = document.createElement('option');
                        option.value = categoria.id;
                        option.textContent = categoria.nombre;
                        categoriaSelect.appendChild(option);
                    });
                    categoriaSelect.disabled = false;
                } else {
                    categoriaSelect.innerHTML = '<option value="">No hay categorías disponibles para este departamento</option>';
                    categoriaSelect.disabled = true;
                }
            } else {
                categoriaSelect.innerHTML = '<option value="">Primero selecciona un departamento...</option>';
                categoriaSelect.disabled = true;
            }
        });
        
        // Validación del formulario
        document.getElementById('ticketForm').addEventListener('submit', function(e) {
            const categoria = document.getElementById('categoria_id').value;
            const departamento = document.getElementById('departamento_id').value;
            const descripcion = editor.textContent || editor.innerText || '';
            
            // Guardar contenido del editor
            descripcionHidden.value = editor.innerHTML;
            
            if (!categoria || !departamento || !descripcion.trim()) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios.');
                return false;
            }
            
            if (descripcion.trim().length < 10) {
                e.preventDefault();
                alert('La descripción debe tener al menos 10 caracteres.');
                return false;
            }
        });
    </script>
</body>
</html>
