window.addEventListener("DOMContentLoaded", function () {
    setArticleEvents();
});

function setArticleEvents() {
    document.querySelectorAll("main article").forEach(function (article) {
        article.addEventListener("click", function () {
            loadPhotos(this);
        });
        loadThumbnails(article);
    });
}

function loadThumbnails(article) {
    article.querySelectorAll(".photo").forEach(function (photo) {
        photo.addEventListener("load", function () {
            this.classList.add('loaded');
        });
        photo.src = photo.getAttribute("data-thumbnail");
    });
}

function loadPhotos(article) {
    const photos = article.querySelectorAll(".photo");
    let cnt = 0;
    photos.forEach(function (photo) {
        photo.addEventListener("load", function () {
            cnt++;
            if (cnt === photos.length) {
                console.log("FotosPhotos loaded")
            }
        });
        photo.src = photo.getAttribute("data-photo");
    });
}
