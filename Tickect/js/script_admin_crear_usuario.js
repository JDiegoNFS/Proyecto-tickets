document.getElementById("buscador").addEventListener("keyup", function () {
    const filtro = this.value.toLowerCase();
    const filas = document.querySelectorAll(".container-tabla tbody tr");

    filas.forEach(fila => {
        const usuario = fila.cells[1].textContent.toLowerCase();
        fila.style.display = usuario.includes(filtro) ? "" : "none";
    });
});

$(document).ready(function() {
    $('#select-departamento').select2({
        placeholder: "Selecciona un departamento",
        allowClear: true,
        width: '100%'
    });
});