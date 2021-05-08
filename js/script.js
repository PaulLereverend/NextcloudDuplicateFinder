(function() {
  var baseUrl = OC.generateUrl('/apps/duplicatefinder');
  var element = document.getElementById('container');
  var loader = document.getElementById('loader-container');
  var title = document.getElementById('title');
  var groupedResult = {};

  function render() {
    loader.style.display = 'none';

    if (groupedResult.itemCount == 0) {
      updateTitle("0 duplicate file found");
    } else {
      updateTitleWithStats();

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
    var spaceUsedByDublicateFiles = OC.Util.humanFileSize(groupedResult.totalSize - groupedResult.uniqueTotalSize);

    updateTitle(groupedResult.itemCount + ' files found. Total: ' + OC.Util.humanFileSize(groupedResult.totalSize) + '. ' + spaceUsedByDublicateFiles + ' of space could be freed.')
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
    var itemEl = document.getElementById(item.infos.id);
    var iconEl = itemEl.getElementsByClassName('icon-delete')[0];

    iconEl.classList.replace('icon-delete', 'icon-loading');

    fileClient.remove(item.path).then(function() {
      var itemGroup = groupedResult.groupedItems[item.hash];
      var isLastItemInGroup = itemGroup.length === 1;
      var itemIndex = itemGroup.findIndex(function(i) {
        return i.infos.id === item.infos.id;
      });
      itemGroup.splice(itemIndex, 1);

      // If it was the last item, remove the whole group container.
      if (isLastItemInGroup) {
        itemEl.parentElement.remove();
      }
      else {
        itemEl.remove();
      }

      // Update the stats
      groupedResult.totalSize -= item.infos.size;
      groupedResult.itemCount -= 1;
      if (isLastItemInGroup) {
        groupedResult.uniqueTotalSize -= item.infos.size;
      }

      updateTitleWithStats();
    }).fail(function() {
      iconEl.classList.replace('icon-loading', 'icon-delete');
      OC.dialogs.alert('Error deleting the file: ' + item.path, 'Error')
    });
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

  function groupByHashWithStats(items) {
    var hashes = [];
    var groupedItems = {};
    var totalSize = 0;
    var uniqueTotalSize = 0;

    items.forEach(function(item) {
      var key = item.hash;

      totalSize += item.infos.size;

      if (!groupedItems[key]) {
        groupedItems[key] = [];
        hashes.push(key);
        uniqueTotalSize += item.infos.size;
      }

      groupedItems[key].push(item);
    });

    return {
      hashes: hashes,
      groupedItems: groupedItems,
      totalSize: totalSize,
      itemCount: items.length,
      uniqueTotalSize: uniqueTotalSize,
    };
  };

  $.getJSON(baseUrl + '/files')
    .then(function(result) {
      var items = result;

      // Sort desending by size
      items.sort((a, b) => b.infos.size - a.infos.size);

      // Group items with same hash.
      groupedResult = groupByHashWithStats(items);

      render();
    });
})();
