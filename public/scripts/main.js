document.addEventListener("keydown", function(e) {
    if (e.key == "Escape"){
            const form = document.createElement("form");
            form.method = "POST";
            form.action = window.location.href;
            
            const input = document.createElement("input");
            input.type = "hidden";;
            input.name = "action";
            input.value = "closeScroll";
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
    }
})