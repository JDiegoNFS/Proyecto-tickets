document.addEventListener('DOMContentLoaded', () => {
    const quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Escribe tu mensaje...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['image']
            ]
        }
    });

    // Guardar el contenido HTML del Quill en el input oculto
    const formulario = document.querySelector('form');
    formulario.addEventListener('submit', function () {
        document.getElementById('mensajeOculto').value = quill.root.innerHTML;
    });

    // Detectar imágenes pegadas
    quill.root.addEventListener('paste', function (e) {
        const items = e.clipboardData.items;
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            if (item.type.indexOf("image") !== -1) {
                const file = item.getAsFile();
                const formData = new FormData();
                formData.append('imagen', file);

                fetch('../subir_imagen_pegada.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const range = quill.getSelection();
                        quill.insertEmbed(range.index, 'image', data.url);
                    } else {
                        alert("Error al subir imagen");
                    }
                })
                .catch(() => alert("Error al subir imagen"));
            }
        }
    });

    // Modal para imágenes ampliadas
    document.querySelectorAll('.chat-box img').forEach(img => {
        img.addEventListener('click', () => {
            const modal = document.getElementById('image-modal');
            const modalImg = document.getElementById('modal-image');
            modalImg.src = img.src;
            modal.style.display = 'flex';
        });
    });

    // Cerrar modal al hacer clic fuera de la imagen
    document.getElementById('image-modal').addEventListener('click', () => {
        document.getElementById('image-modal').style.display = 'none';
    });
});
