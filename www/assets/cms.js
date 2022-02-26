const input_photo = document.querySelector("#photos");
const textarea_content = document.querySelector("#content");

window.addEventListener("DOMContentLoaded", function () {
    cmsPhotos();
    if (textarea_content) addEmoticonsEvents();
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

function addEmoticonsEvents() {
    document.querySelectorAll(".emoticon").forEach(function (emoticon) {
        emoticon.addEventListener("click", function () {
            textarea_content.insertAtCaret(this.getAttribute('data-emoticon'));
            textarea_content.focus();
        });
    });
}

HTMLTextAreaElement.prototype.insertAtCaret = function (text) {
    text = text || '';
    if (document.selection) {
        // IE
        this.focus();
        const sel = document.selection.createRange();
        sel.text = text;
    } else if (this.selectionStart || this.selectionStart === 0) {
        // Others
        const startPos = this.selectionStart;
        const endPos = this.selectionEnd;
        this.value = this.value.substring(0, startPos) +
            text +
            this.value.substring(endPos, this.value.length);
        this.selectionStart = startPos + text.length;
        this.selectionEnd = startPos + text.length;
    } else {
        this.value += text;
    }
};
