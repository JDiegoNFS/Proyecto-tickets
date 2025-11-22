document.querySelectorAll("th.resizable").forEach(function(th) {
    const grip = document.createElement("div");
    grip.className = "resize-grip";
    th.appendChild(grip);

    let startX, startWidth;

    grip.addEventListener("mousedown", function (e) {
        startX = e.clientX;
        startWidth = th.offsetWidth;

        document.documentElement.addEventListener("mousemove", resize);
        document.documentElement.addEventListener("mouseup", stopResize);
    });

    function resize(e) {
        const newWidth = startWidth + (e.clientX - startX);
        th.style.width = newWidth + "px";

        const index = Array.from(th.parentNode.children).indexOf(th);
        document.querySelectorAll("table tr").forEach(row => {
            if (row.children[index]) {
                row.children[index].style.width = newWidth + "px";
            }
        });
    }

    function stopResize() {
        document.documentElement.removeEventListener("mousemove", resize);
        document.documentElement.removeEventListener("mouseup", stopResize);
    }
});