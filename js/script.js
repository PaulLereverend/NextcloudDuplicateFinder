loadList();

function loadList() {
    var baseUrl = OC.generateUrl('/apps/duplicatefinder');
    var element = document.getElementById('container');
    $.getJSON(baseUrl + '/files')
        .then(function (result) {
            let previous_hash = '';

            result.forEach(el => {
                var div = document.createElement("div");
                div.setAttribute('id', el.infos.id);
                div.setAttribute('class', 'element')
                var img = document.createElement("div");

                var button = document.createElement("button");
                button.innerHTML = '<span class="icon icon-delete"></span>';
                button.setAttribute('class', 'button-delete');
                button.addEventListener("click", function () {
                    deleteElement(el.path, el.infos.id);
                });
                div.appendChild(button);

                var label = document.createElement("div");
                var path = document.createElement("h1");
                path.setAttribute('class', 'path');
                path.innerHTML = el.path;
                label.appendChild(path);

                var hash = document.createElement("h1");
                hash.setAttribute('class', 'hash');
                hash.innerHTML = el.hash;
                label.appendChild(hash);


                img.setAttribute('class', 'thumbnail');
                if (el.infos.mimetype == 'image/jpeg' ||
                    el.infos.mimetype == 'image/png' ||
                    el.infos.mimetype == 'image/gif' ||
                    el.infos.mimetype == 'text/plain') {
                    console.log('test');
                    img.style.backgroundImage = "url('/index.php/core/preview?fileId=" + el.infos.id + "&x=500&y=500&forceIcon=0')";
                } else {
                    const iconUrl = OC.MimeType.getIconUrl(el.infos.mimetype);
                    img.style.backgroundImage = "url(" + iconUrl + ")";
                }

                div.appendChild(img);
                div.appendChild(label);

                if (el.hash == previous_hash) {
                    group.appendChild(div);
                } else {
                    if (typeof group != "undefined") {
                        element.appendChild(group);
                    }
                    group = getGroupeDiv();
                    group.appendChild(div);
                }
                previous_hash = el.hash;

            });
            element.appendChild(group);
            console.log(result);
        });
}
function deleteElement(path, id) {
    let fileClient = OC.Files.getClient();
    fileClient.remove(path);
    document.getElementById(id).remove();
}
function getGroupeDiv() {
    let div = document.createElement("div");
    div.setAttribute('class', 'duplicates');
    return div;
}