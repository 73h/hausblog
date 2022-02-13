window.addEventListener("DOMContentLoaded", function () {
    setArticleEvents();
    cmsPhotos();
});

let gallery = null;

function setArticleEvents() {
    document.querySelectorAll("main article .photos").forEach(function (article) {
        article.addEventListener("click", function (e) {
            if (gallery != null) closePhotoGallery(gallery);
            if (gallery !== this) {
                gallery = this;
                loadPhotos(this, e.target);
            } else gallery = null;
        });
    });
}

function loadPhotos(div_photos, div_photo) {
    const photos = div_photos.querySelectorAll(".photo");
    let cnt = 0;
    photos.forEach(function (photo) {
        photo.addEventListener("load", function () {
            if (this === div_photo) this.style.zIndex = photos.length + 1;
            else this.style.zIndex = photos.length - cnt;
            cnt++;
            if (cnt === photos.length) {
                openPhotoGallery(div_photos);
            }
        });
        photo.src = photo.getAttribute("data-photo");
    });
}

function openPhotoGallery(div_photos) {
    div_photos.classList.add('show');
    div_photos.style.height = div_photos.offsetWidth + 'px';
}

function closePhotoGallery(div_photos) {
    div_photos.classList.remove('show');
    div_photos.style.height = null;
}

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
