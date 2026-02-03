let commandHistory = [];
let cursor = 0;
let draft = "";
let hasPressedEnter = false;
const input = document.getElementById("commandInputField");
console.log("\n\nIMPORTED");

function incrementCursorDown() {
    if (cursor >= commandHistory.length) return;

    cursor++;
    if (cursor == commandHistory.length) {
        input.value = draft;
    } else {
        input.value = commandHistory[cursor];
    }
}

function incrementCursorUp() {
    if (cursor <= 0) return;

    if (cursor == commandHistory.length) {
        draft = input.value;
    }
    cursor--;
    input.value = commandHistory[cursor];
}

function addToHistory(cmd) {
    if (cmd == "") return;
    commandHistory.push(cmd);
    sessionStorage.setItem("commandHistory", JSON.stringify(commandHistory));
}

function loadHistory() {
    const historyJson = sessionStorage.getItem("commandHistory");
    commandHistory = historyJson ? JSON.parse(historyJson) : [];
    cursor = commandHistory.length;
}

input.addEventListener("keydown", (e) => {
    switch (e.key) {
        case "Enter": {
            if (hasPressedEnter) return;
            hasPressedEnter = true;
            const cmd = input.value;
            addToHistory(cmd);
            break;
        }
        case "ArrowUp": {
            incrementCursorUp();
            break;
        }
        case "ArrowDown": {
            incrementCursorDown();
            break;
        }
    }
});

window.addEventListener("load", () => {
    loadHistory();
    const history = document.querySelector(".history-container");
    const lastLine = history.lastElementChild;
    if (lastLine) {
        lastLine.scrollIntoView({
            behavior: "auto",
            block: "end"
        });
    }
});

document.addEventListener("keydown", function (e) {
    if (e.key == "Escape") {
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