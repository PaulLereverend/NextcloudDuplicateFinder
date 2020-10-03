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

                function get_icons(actions, el) {
                    var icons = document.createElement("div");
                    icons.setAttribute('class', 'fileactions');

                    actions.forEach(icon_info => {
                        var icon_link = document.createElement('a');
                        icon_link.setAttribute('href', icon_info.url.call(null, el));
                        icon_link.setAttribute('class', 'action permanent');
                        icon_link.setAttribute('title', icon_info.description)

                        var action_icon = document.createElement('span');
                        action_icon.setAttribute('class', 'icon ' + icon_info.icon);
                        icon_link.appendChild(action_icon);

                        // var action_text = document.createElement('span');
                        // action_text.innerText = icon_info.icon;
                        // icon_link.appendChild(action_text);

                        icons.appendChild(icon_link);
                    });

                    return icons;
                }

                // Get icons
                var actions = [{
                    'icon': 'icon-file',
                    'url': function (el) {
                        let path = el.path;
                        let dir = OC.dirname(path);
                        return OC.generateUrl('/apps/files/?dir='+ dir +'&openfile=' + el.infos.id);
                    },
                    'description': 'Show file'
                }, {
                    'icon': 'icon-details',
                    'url': function (el) {
                        return OC.generateUrl('/f/' + el.infos.id);
                    },
                    'description': 'Show details'
                }];
                var icons = get_icons(actions, el);
                label.appendChild(icons);

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
                    // Show size
                    size = document.createElement("div");
                    size.setAttribute('class', 'filesize');
                    size.innerHTML = OC.Util.humanFileSize(el.infos.size);
                    group.prepend(size);
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
    document.getElementById('title').innerHTML = nb_element + ' files found. Total: ' + OC.Util.humanFileSize(total_size);
}