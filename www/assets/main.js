const lds_ripple = document.querySelector('.lds-ripple');
const photo_gallery_left = document.querySelector('.photo-gallery-left');
const photo_gallery_right = document.querySelector('.photo-gallery-right');
let gallery = null;
let last_scroll_position = 0;

window.addEventListener("DOMContentLoaded", function () {
    setArticleEvents();
    lds_ripple.style.visibility = 'hidden';
    if (photo_gallery_left) {
        photo_gallery_left.style.visibility = 'hidden';
        photo_gallery_right.style.visibility = 'hidden';
        photo_gallery_left.addEventListener('click', function () {
            photoGallerySwipe(-1)
        });
        photo_gallery_right.addEventListener('click', function () {
            photoGallerySwipe(1)
        });
    }
});

function setArticleEvents() {
    document.querySelectorAll("main article .photos").forEach(function (article) {
        article.addEventListener("click", function (e) {
            let same_gallery = false;
            if (gallery != null && e.target !== photo_gallery_left && e.target !== photo_gallery_right) {
                closePhotoGallery(gallery);
                if (gallery === this) {
                    same_gallery = true;
                }
            }
            if (gallery !== this) {
                gallery = this;
                loadPhotos(this, e.target);
            }
            if (same_gallery) gallery = null;
        });
    });
}

function loadPhotos(div_photos, div_photo) {
    lds_ripple.style.visibility = 'visible';
    const photos = div_photos.querySelectorAll(".photo");
    let cnt = 0;
    photos.forEach(function (photo) {
        photo.addEventListener("load", function () {
            if (this === div_photo) this.style.zIndex = 1;
            else this.style.zIndex = 0;
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
    let width = div_photos.offsetWidth;
    div_photos.style.height = width + 'px';
    if (div_photos.querySelectorAll(".photo").length > 1) {
        div_photos.append(photo_gallery_left, photo_gallery_right);
        photo_gallery_left.style.visibility = 'visible';
        photo_gallery_right.style.visibility = 'visible';
        photo_gallery_left.style.top = (Math.round(width / 2) - 24) + 'px';
        photo_gallery_right.style.top = (Math.round(width / 2) - 24) + 'px';
    }
    last_scroll_position = window.pageYOffset;
    let top = window.pageYOffset + div_photos.getBoundingClientRect().top - 50;
    window.scrollTo({top: top, behavior: 'smooth'});
}

function closePhotoGallery(div_photos) {
    photo_gallery_left.style.visibility = 'hidden';
    photo_gallery_right.style.visibility = 'hidden';
    div_photos.classList.remove('show');
    div_photos.style.height = null;
    window.scrollTo({top: last_scroll_position, behavior: 'smooth'});
}

function photoGallerySwipe(direction) {
    const photos = gallery.querySelectorAll(".photo");
    let index = -1;
    let current_index = 0;
    photos.forEach(function (photo) {
        index++;
        if (parseInt(photo.style.zIndex) === 1) {
            current_index = index;
            photo.style.zIndex = 0;
        }
    });
    current_index = current_index + direction;
    if (current_index === photos.length) current_index = 0;
    else if (current_index < 0) current_index = photos.length - 1;
    photos[current_index].style.zIndex = 1;
}
