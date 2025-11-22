// Permite redimensionar columnas como en Excel
document.querySelectorAll("table.resizable th").forEach((th, index) => {
    const grip = document.createElement("div");
    grip.style.position = "absolute";
    grip.style.right = 0;
    grip.style.top = 0;
    grip.style.width = "6px";
    grip.style.cursor = "col-resize";
    grip.style.userSelect = "none";
    grip.style.height = "100%";
    grip.classList.add("resize-grip");

    let startX, startWidth;

    grip.addEventListener("mousedown", (e) => {
        startX = e.pageX;
        startWidth = th.offsetWidth;
        document.body.style.cursor = "col-resize";

        function onMouseMove(eMove) {
            const diffX = eMove.pageX - startX;
            const newWidth = startWidth + diffX;
            th.style.width = newWidth + "px";
            document.querySelectorAll("table.resizable tr").forEach(row => {
                if (row.children[index]) {
                    row.children[index].style.width = newWidth + "px";
                }
            });
        }

        function onMouseUp() {
            document.removeEventListener("mousemove", onMouseMove);
            document.removeEventListener("mouseup", onMouseUp);
            document.body.style.cursor = "";
        }

        document.addEventListener("mousemove", onMouseMove);
        document.addEventListener("mouseup", onMouseUp);
    });

    th.style.position = "relative";
    th.appendChild(grip);
});