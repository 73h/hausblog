const lds_ripple = document.querySelector('.lds-ripple');
let gallery = null;

window.addEventListener("DOMContentLoaded", function () {
    setArticleEvents();
    lds_ripple.style.visibility = 'hidden';
});

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
    lds_ripple.style.visibility = 'visible';
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
    lds_ripple.style.visibility = 'hidden';
    div_photos.classList.add('show');
    div_photos.style.height = div_photos.offsetWidth + 'px';
    div_photos.scrollIntoView();
}

function closePhotoGallery(div_photos) {
    div_photos.classList.remove('show');
    div_photos.style.height = null;
}
