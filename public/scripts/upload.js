const input = document.getElementById('fileInput');
const fileStatus = document.getElementById('fileStatus');
const uploadButton = document.getElementById("uploadButton");
const defaultPic = document.getElementById("defaultPicture");
const profilePic = document.getElementById("profilePicture");

input.addEventListener('change', () => {
    if (input.files.length) {
        const file = input.files[0];
        defaultPic.hidden = true;

        profilePic.src = URL.createObjectURL(file);
        profilePic.hidden = false;
        
        uploadButton.hidden = false;
    }
    else {
        fileStatus.textContent = 'No file chosen';
    }
});