window.addEventListener("load", function() {
    const loader = document.getElementById("loader");
    const contenido = document.getElementById("contenido");

    // Solo ejecutar si los elementos existen
    if (loader && contenido) {
        setTimeout(() => {
            loader.style.display = "none";
            contenido.style.display = "block";
        }, 1000);
    }
});