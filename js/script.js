(function() {
  var baseUrl = OC.generateUrl('/apps/duplicatefinder');
  var element = document.getElementById('container');
  var loader = document.getElementById('loader-container');
  var title = document.getElementById('title');
  var items = [];

  function render() {
    loader.style.display = 'none';

    if (items.length == 0) {
      updateTitle("0 duplicate file found");
    } else {
      updateTitleWithStats();

      // Sort desending by size
      items.sort((a, b) => b.infos.size - a.infos.size);

      // Group items with same hash.
      var groupedResult = groupBy(items, function(item) {
        return item.hash;
      });

      groupedResult.hashes.forEach(function(hash) {
        var groupItems = groupedResult.groupedItems[hash];
        var gruopDOM = getGroupElement(groupItems);
        element.appendChild(gruopDOM);
      });
    }
  }

  function updateTitle(s_title) {
    title.innerHTML = s_title;
  }

  function updateTitleWithStats() {
    var stats = getStats();

    updateTitle(stats.count + ' files found. Total: ' + OC.Util.humanFileSize(stats.totalSize))
  }

  function getStats() {
    return {
      count: items.length,
      totalSize: items.map(item => item.infos.size).reduce((a, b) => a + b, 0)
    }
  }

  function isImage(item) {
    return item.infos.mimetype.substr(0, item.infos.mimetype.indexOf('/')) == 'image';
  }

  function isVideo(item) {
    return item.infos.mimetype.substr(0, item.infos.mimetype.indexOf('/')) == 'video';
  }

  function getPreviewImage(item) {
    if (isImage(item) || isVideo(item)) {
      return OC.generateUrl('/core/preview.png?') + $.param({
        file: item.path,
        fileId: item.infos.id,
        x: 500,
        y: 500,
        forceIcon: 0
      });
    }

    return OC.MimeType.getIconUrl(item.infos.mimetype);
  }


  function deleteItem(item) {
    let fileClient = OC.Files.getClient();
    fileClient.remove(item.path);
    document.getElementById(item.infos.id).remove();
    var itemIndex = items.findIndex(function(i) {
      return i.infos.id === item.infos.id;
    });
    items.splice(itemIndex, 1);
    updateTitleWithStats();
  }


  function getGroupElement(groupItems) {
    let groupContainer = document.createElement("div");
    groupContainer.setAttribute('class', 'duplicates');

    var sizeDiv = document.createElement("div");
    sizeDiv.setAttribute('class', 'filesize');
    sizeDiv.innerHTML = OC.Util.humanFileSize(groupItems[0].infos.size);
    groupContainer.appendChild(sizeDiv);

    groupItems.forEach(function(item) {
      var itemDiv = getItemElement(item);
      groupContainer.appendChild(itemDiv);
    });

    return groupContainer;
  }


  function getItemElement(item) {
    // Item wrapper container
    var itemDiv = document.createElement("div");
    itemDiv.setAttribute('id', item.infos.id);
    itemDiv.setAttribute('class', 'element')

    // Delete button
    var deleteButton = document.createElement("button");
    deleteButton.innerHTML = '<span class="icon icon-delete"></span>';
    deleteButton.setAttribute('class', 'button-delete');
    deleteButton.addEventListener("click", function() {
      deleteItem(item);
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
    itemPath.innerHTML = item.path;
    labelContainer.appendChild(itemPath);

    var actions = [
      {
        'icon': 'icon-file',
        'url': function(item) {
          let dir = OC.dirname(item.path);

          return OC.generateUrl('/apps/files/?dir=' + dir + '&openfile=' + item.infos.id);
        },
        'description': 'Show file'
      },
      {
        'icon': 'icon-details',
        'url': function(item) {
          return OC.generateUrl('/f/' + item.infos.id);
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
    hashContainer.innerHTML = item.hash;
    labelContainer.appendChild(hashContainer);

    itemDiv.appendChild(labelContainer);

    return itemDiv;
  }

  function groupBy(items, fn) {
    var hashes = [];
    var groupedItems = {};
    items.forEach(function(item) {
      var key = fn(item);

      if (!groupedItems[key]) {
        groupedItems[key] = [];
        hashes.push(key);
      }

      groupedItems[key].push(item);
    });

    return {
      hashes: hashes,
      groupedItems: groupedItems
    };
  };

  $.getJSON(baseUrl + '/files')
    .then(function(result) {
      items = result;
      render();
    });
})();
