const input = document.getElementById('fileInput');
const fileStatus = document.getElementById('fileStatus');
const uploadButton = document.getElementById("uploadButton");
const defaultPic = document.getElementById("defaultPicture");
const profilePic = document.getElementById("profilePicture");

input.addEventListener('change', () => {
    if (input.files.length) {
        const file = input.files[0];
        defaultPic.style.display = "none";
        profilePic.style.display = "flex";
        profilePic.src = URL.createObjectURL(file);
        uploadButton.style.display = "flex";
    }
    else {
        fileStatus.textContent = 'No file chosen';
    }
});