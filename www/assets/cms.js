window.addEventListener("DOMContentLoaded", function () {
    cmsPhotos();
});

function cmsPhotos() {
    document.querySelectorAll("main article .cms-photos .cms-photo").forEach(function (photo) {
        photo.addEventListener("click", function () {
            let input_photo = document.querySelector("#photos");
            let photos = input_photo.value === '' ? [] : input_photo.value.split(";");
            if (this.classList.contains('cms-selected')) {
                this.classList.remove('cms-selected');
                photos.splice(photos.indexOf(this.getAttribute('data-photo')), 1);
            } else {
                this.classList.add('cms-selected');
                photos.push(this.getAttribute('data-photo'));
            }
            input_photo.value = photos.length === 0 ? '' : photos.join(';');
        });
    });
}