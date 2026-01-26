const select = document.getElementById("rankSelect");
const createButton = document.getElementById("createGameButton");
const nameInput = document.getElementById("newMapNameInput");

let canCreate = false;
select.addEventListener("change", () => {
    select.style.color = select.selectedOptions[0].style.color;
    if (nameInput.value != "") {
        enableButton()
    }
    else {
        disabledButton();
    }
});

    nameInput.addEventListener("input", () => {
    console.log(select.selectedOptions[0].value)
    if (select.selectedOptions[0].value != "" && nameInput.value != "") {
        enableButton()
    }
    else {
        disableButton()
    }
});
function disableButton() {
    createButton.disabled = true;
    createButton.classList.remove("button-large");

}
function enableButton() {
    createButton.disabled = false;
    createButton.classList.add("button-large");

}