const input_photo = document.querySelector("#photos");

window.addEventListener("DOMContentLoaded", function () {
    cmsPhotos();
});

function cmsPhotos() {
    document.querySelectorAll("main article .cms-photos .cms-photo").forEach(function (photo) {
        photo.addEventListener("click", function () {
            let photos = input_photo.value === '' ? [] : input_photo.value.split(";");
            if (this.classList.contains('cms-selected')) {
                this.classList.remove('cms-selected');
                this.parentNode.firstElementChild.innerHTML = '';
                photos.splice(photos.indexOf(this.getAttribute('data-photo')), 1);
            } else {
                this.classList.add('cms-selected');
                photos.push(this.getAttribute('data-photo'));
            }
            input_photo.value = photos.length === 0 ? '' : photos.join(';');
            setPhotoPositions();
        });
    });
}

function setPhotoPositions() {
    let photos = input_photo.value === '' ? [] : input_photo.value.split(";");
    photos.forEach(function (pk_photo, i) {
        let photo_position = document.querySelector("#photo-position-" + pk_photo);
        photo_position.innerHTML = i + 1;
    })
}
