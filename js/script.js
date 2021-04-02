(function() {
  var baseUrl = OC.generateUrl('/apps/duplicatefinder/api');
  var element = document.getElementById('container');
  var loader = document.getElementById('loader-container');
  var loaderBtn = document.getElementById('loader-btn');
  var title = document.getElementById('title');
  var groupedResult = {
    groupedItems: [],
    totalSize: 0,
    itemCount: 0,
    uniqueTotalSize: 0
  };
  var limit = 20;
  var offset = 0;

  function render(items) {
    loader.style.display = 'none';

    if (groupedResult.itemCount == 0) {
      updateTitle("0 duplicate file found");
    } else {
      updateTitleWithStats();

      items.forEach( (duplicate) => {
        var groupDOM = getGroupElement(duplicate);
        element.appendChild(groupDOM);
      });
    }
  }

  function updateTitle(s_title) {
    title.innerHTML = s_title;
  }

  function updateTitleWithStats() {
    var spaceUsedByDublicateFiles = OC.Util.humanFileSize(groupedResult.totalSize - groupedResult.uniqueTotalSize);

    updateTitle(groupedResult.itemCount + ' files found. Total: ' + OC.Util.humanFileSize(groupedResult.totalSize) + '. ' + spaceUsedByDublicateFiles + ' of space could be freed.')
  }

  function isImage(item) {
    return item.mimetype.substr(0, item.mimetype.indexOf('/')) == 'image';
  }

  function isVideo(item) {
    return item.mimetype.substr(0, item.mimetype.indexOf('/')) == 'video';
  }

  function getPreviewImage(item) {
    if (isImage(item) || isVideo(item)) {
      return OC.generateUrl('/core/preview.png?') + $.param({
        file: normalizeItemPath(item.path),
        fileId: item.nodeId,
        x: 500,
        y: 500,
        forceIcon: 0
      });
    }

    return OC.MimeType.getIconUrl(item.mimetype);
  }

  function normalizeItemPath(path){
    return path.match(/\/([^\/]*)\/files(\/.*)/)[2];
  }

  function deleteItem(item, e) {
    let fileClient = OC.Files.getClient();
    var iconEl;
    if(e.target.className === "icon icon-delete"){
      iconEl = e.target;
    }else{
      iconEl = e.target.getElementsByClassName('icon-delete')[0];
    }
    iconEl.classList.replace('icon-delete', 'icon-loading');
    var isLastItemInGroup = false;


    fileClient.remove(normalizeItemPath(item.path)).then(function() {
      groupedResult.groupedItems.forEach((grp, i) => {
        grp.files.forEach((file, j) => {
          if(file.path === item.path){
            if(document.getElementById(grp.id+"-file-"+file.id) !== null){
              document.getElementById(grp.id+"-file-"+file.id).remove();
            }
            groupedResult.totalSize -= item.size;
            groupedResult.itemCount -= 1;
            grp.files.splice(j, 1);
          }
        });
        if(grp.files.length <= 1){
          if(document.getElementById("grp-"+grp.id) !== null){
            document.getElementById("grp-"+grp.id).remove();
          }
          groupedResult.totalSize -= item.size;
          groupedResult.itemCount -= 1;
          groupedResult.uniqueTotalSize -= item.size;
          groupedResult.groupedItems.splice(i, 1);
        }
      });

      updateTitleWithStats();
    }).fail(function() {
      iconEl.classList.replace('icon-loading', 'icon-delete');
      OC.dialogs.alert('Error deleting the file: ' + normalizeItemPath(item.path), 'Error')
    });
  }


  function getGroupElement(group) {
    let groupItems = group.files;
    let groupContainer = document.createElement("div");
    groupContainer.setAttribute('id', "grp-"+group.id);
    groupContainer.setAttribute('class', 'duplicates');

    var sizeDiv = document.createElement("div");
    sizeDiv.setAttribute('class', 'filesize');
    sizeDiv.innerHTML = OC.Util.humanFileSize(groupItems[0].size);
    groupContainer.appendChild(sizeDiv);

    groupItems.forEach(function(item) {
      var itemDiv = getItemElement(item, group.id);
      groupContainer.appendChild(itemDiv);
    });

    return groupContainer;
  }


  function getItemElement(item, grpId) {
    // Item wrapper container
    var itemDiv = document.createElement("div");
    itemDiv.setAttribute('id', grpId+"-file-"+item.id);
    itemDiv.setAttribute('class', 'element')

    // Delete button
    var deleteButton = document.createElement("button");
    deleteButton.innerHTML = '<span class="icon icon-delete"></span>';
    deleteButton.setAttribute('class', 'button-delete');
    deleteButton.addEventListener("click", function(e) {
      deleteItem(item, e);
    });
    itemDiv.appendChild(deleteButton);

    // Preview image
    var previewImage = document.createElement("div");
    previewImage.setAttribute('class', 'thumbnail');
    previewImage.style.backgroundImage = "url('" + getPreviewImage(item) + "')";
    itemDiv.appendChild(previewImage);

    // Label container on the right
    var labelContainer = document.createElement("div");

    // Item path
    var itemPath = document.createElement("h1");
    itemPath.setAttribute('class', 'path');
    itemPath.innerHTML = normalizeItemPath(item.path);
    labelContainer.appendChild(itemPath);

    var actions = [
      {
        'icon': 'icon-file',
        'url': function(item) {
          let dir = OC.dirname(normalizeItemPath(item.path));

          return OC.generateUrl('/apps/files/?dir=' + dir + '&openfile=' + item.nodeId);
        },
        'description': 'Show file'
      },
      {
        'icon': 'icon-details',
        'url': function(item) {
          return OC.generateUrl('/f/' + item.nodeId);
        },
        'description': 'Show details'
      }
    ];
    var actionsContainer = document.createElement("div");
    actionsContainer.setAttribute('class', 'fileactions');

    actions.forEach(action => {
      var action_link = document.createElement('a');
      action_link.setAttribute('href', action.url.call(null, item));
      action_link.setAttribute('class', 'action permanent');
      action_link.setAttribute('title', action.description)

      var action_icon = document.createElement('span');
      action_icon.setAttribute('class', 'icon ' + action.icon);
      action_link.appendChild(action_icon);

      actionsContainer.appendChild(action_link);
    });

    labelContainer.appendChild(actionsContainer);

    // Item hash
    var hashContainer = document.createElement("h1");
    hashContainer.setAttribute('class', 'hash');
    hashContainer.innerHTML = item.fileHash;
    labelContainer.appendChild(hashContainer);

    itemDiv.appendChild(labelContainer);

    return itemDiv;
  }

  function loadFiles(){
    loader.style.display = 'inherit';
      loaderBtn.style.display = 'none';
    $.getJSON(baseUrl + '/v1/Duplicates?offset='+offset)
    .then(function(result) {
      var items = result;

      // Sort desending by size

      groupedResult = {
        groupedItems: groupedResult.groupedItems.concat(items.data),
        totalSize: 0,
        itemCount: 0,
        uniqueTotalSize: 0
      };

      if(items.data.length > 0){
        offset += items.data.length;
        if(offset % limit === 0 ){
          loaderBtn.style.display = 'inherit';
        }
      }else{
        loaderBtn.removeEventListener("click", loadFiles);
      }

      items.data.forEach((duplicate) => {
        duplicate.files = Object.values(duplicate.files);
        groupedResult.totalSize += duplicate.files[0].size*duplicate.files.length;
        groupedResult.itemCount += duplicate.files.length;
        groupedResult.uniqueTotalSize += duplicate.files[0].size;
      });
      items.data.sort((a, b) => Math.abs((b.files[0].size*b.files.length) - (a.files[0].size*a.files.length)));

      render(items.data);
    });
  }

  loadFiles();
  loaderBtn.addEventListener("click", loadFiles);
})();
