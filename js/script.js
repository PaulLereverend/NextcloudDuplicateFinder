var total_size = 0;
var nb_element = 0;

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
                    deleteElement(el.path, el.infos.id, el.infos.size);
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
                if (el.infos.mimetype.substr(0, el.infos.mimetype.indexOf('/')) == 'image' ||
                    el.infos.mimetype.substr(0, el.infos.mimetype.indexOf('/')) == 'video') {
                    var params = {
                        file: el.path,
                        fileId: el.infos.id,
                        x: 500,
                        y: 500,
                        forceIcon: 0
                    };

                    const previewUrl = OC.generateUrl('/core/preview.png?') + $.param(params);
                    img.style.backgroundImage = "url('" + previewUrl + "')";
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
                total_size += el.infos.size;

            });
            document.getElementById('loader-container').style.display = 'none';
            if (result.length == 0) {
                document.getElementById('title').innerHTML = "0 duplicate file found";
            } else {
                nb_element = result.length;
                updateTitle();
            }
            element.appendChild(group);
        });
}
function deleteElement(path, id, size) {
    let fileClient = OC.Files.getClient();
    fileClient.remove(path);
    document.getElementById(id).remove();
    total_size -= size;
    nb_element--;
    updateTitle();
}
function getGroupeDiv() {
    let div = document.createElement("div");
    div.setAttribute('class', 'duplicates');
    return div;
}
function updateTitle() {
    document.getElementById('title').innerHTML = nb_element + ' files found. Total: ' + Math.round(total_size / 1000000) + 'MB';
}