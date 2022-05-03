/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!******************************!*\
  !*** ./js-src/main/index.js ***!
  \******************************/
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

/* global OC, fetch */
(function () {
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
  var offset = 0;

  function render(items) {
    loader.style.display = 'none';

    if (groupedResult.itemCount === 0) {
      updateTitle('0 duplicate file found');
    } else {
      updateTitleWithStats();
      items.forEach(function (duplicate) {
        if (duplicate && Array.isArray(duplicate.files) && duplicate.files.length > 0) {
          var groupDOM = getGroupElement(duplicate);
          element.appendChild(groupDOM);
        }
      });
    }
  }

  function updateTitle(sTitle) {
    title.innerHTML = sTitle;
  }

  function updateTitleWithStats() {
    var spaceUsedByDublicateFiles = OC.Util.humanFileSize(groupedResult.totalSize - groupedResult.uniqueTotalSize);
    updateTitle(groupedResult.itemCount + ' files found. Total: ' + OC.Util.humanFileSize(groupedResult.totalSize) + '. ' + spaceUsedByDublicateFiles + ' of space could be freed.');
  }

  function isImage(item) {
    return item.mimetype.substr(0, item.mimetype.indexOf('/')) === 'image';
  }

  function isVideo(item) {
    return item.mimetype.substr(0, item.mimetype.indexOf('/')) === 'video';
  }

  function getPreviewImage(item) {
    if (isImage(item) || isVideo(item)) {
      var query = new URLSearchParams({
        file: normalizeItemPath(item.path),
        fileId: item.nodeId,
        x: 500,
        y: 500,
        forceIcon: 0
      });
      return OC.generateUrl('/core/preview.png?') + query.toString();
    }

    return OC.MimeType.getIconUrl(item.mimetype);
  }

  function normalizeItemPath(path) {
    return path.match(/\/([^/]*)\/files(\/.*)/)[2];
  }

  function deleteItem(item, e) {
    var fileClient = OC.Files.getClient();
    var iconEl;

    if (e.target.className === 'icon icon-delete') {
      iconEl = e.target;
    } else {
      iconEl = e.target.getElementsByClassName('icon-delete')[0];
    }

    iconEl.classList.replace('icon-delete', 'icon-loading');
    fileClient.remove(normalizeItemPath(item.path)).then(function () {
      groupedResult.groupedItems.forEach(cleanGroupItems.bind(undefined, item));
      updateTitleWithStats();
    }).fail(function () {
      iconEl.classList.replace('icon-loading', 'icon-delete');
      OC.dialogs.alert('Error deleting the file: ' + normalizeItemPath(item.path), 'Error');
    });
  }

  function cleanGroupItems(item, grp, i) {
    grp.files.forEach(function (file, j) {
      if (file.path === item.path) {
        if (document.getElementById(grp.id + '-file-' + file.id) !== null) {
          document.getElementById(grp.id + '-file-' + file.id).remove();
        }

        groupedResult.totalSize -= item.size;
        groupedResult.itemCount -= 1;
        grp.files.splice(j, 1);
      }
    });

    if (grp.files.length <= 1) {
      if (document.getElementById('grp-' + grp.id) !== null) {
        document.getElementById('grp-' + grp.id).remove();
      }

      groupedResult.totalSize -= item.size;
      groupedResult.itemCount -= 1;
      groupedResult.uniqueTotalSize -= item.size;
      groupedResult.groupedItems.splice(i, 1);
    }
  }

  function getGroupElement(group) {
    var groupItems = group.files;
    var groupContainer = document.createElement('div');
    groupContainer.setAttribute('id', 'grp-' + group.id);
    groupContainer.setAttribute('class', 'duplicates');
    var sizeDiv = document.createElement('div');
    sizeDiv.setAttribute('class', 'filesize');
    groupItems.forEach(function (item) {
      var itemDiv = getItemElement(item, group.id);
      groupContainer.appendChild(itemDiv);

      if (item && item.size) {
        sizeDiv.innerHTML = OC.Util.humanFileSize(item.size);
      }
    });
    groupContainer.appendChild(sizeDiv);
    return groupContainer;
  }

  function getItemElement(item, grpId) {
    // Item wrapper container
    var itemDiv = document.createElement('div');
    itemDiv.setAttribute('id', grpId + '-file-' + item.id);
    itemDiv.setAttribute('class', 'element'); // Delete button

    var deleteButton = document.createElement('button');
    deleteButton.innerHTML = '<span class="icon icon-delete"></span>';
    deleteButton.setAttribute('class', 'button-delete');
    deleteButton.addEventListener('click', function (e) {
      deleteItem(item, e);
    });
    itemDiv.appendChild(deleteButton); // Preview image

    var previewImage = document.createElement('div');
    previewImage.setAttribute('class', 'thumbnail');
    previewImage.style.backgroundImage = "url('" + getPreviewImage(item) + "')";
    itemDiv.appendChild(previewImage); // Label container on the right

    var labelContainer = document.createElement('div'); // Item path

    var itemPath = document.createElement('h1');
    itemPath.setAttribute('class', 'path');
    itemPath.innerHTML = normalizeItemPath(item.path);
    labelContainer.appendChild(itemPath);
    var actions = [{
      icon: 'icon-file',
      url: function url(selectedItem) {
        var dir = OC.dirname(normalizeItemPath(selectedItem.path));
        return OC.generateUrl('/apps/files/?dir=' + dir + '&openfile=' + selectedItem.nodeId);
      },
      description: 'Show file'
    }, {
      icon: 'icon-details',
      url: function url(selectedItem) {
        return OC.generateUrl('/f/' + selectedItem.nodeId);
      },
      description: 'Show details'
    }];
    var actionsContainer = document.createElement('div');
    actionsContainer.setAttribute('class', 'fileactions');
    actions.forEach(function (action) {
      var actionLink = document.createElement('a');
      actionLink.setAttribute('href', action.url.call(null, item));
      actionLink.setAttribute('class', 'action permanent');
      actionLink.setAttribute('title', action.description);
      var actionIcon = document.createElement('span');
      actionIcon.setAttribute('class', 'icon ' + action.icon);
      actionLink.appendChild(actionIcon);
      actionsContainer.appendChild(actionLink);
    });
    labelContainer.appendChild(actionsContainer); // Item hash

    var hashContainer = document.createElement('h1');
    hashContainer.setAttribute('class', 'hash');
    hashContainer.innerHTML = item.fileHash;
    labelContainer.appendChild(hashContainer);
    itemDiv.appendChild(labelContainer);
    return itemDiv;
  }

  function loadFiles() {
    return _loadFiles.apply(this, arguments);
  }

  function _loadFiles() {
    _loadFiles = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
      var response, result, items, errorElement;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              loader.style.display = 'inherit';
              loaderBtn.style.display = 'none';
              _context.prev = 2;
              _context.next = 5;
              return fetch(baseUrl + '/v1/Duplicates?offset=' + offset, {
                redirect: 'error'
              });

            case 5:
              response = _context.sent;
              _context.next = 8;
              return response.json();

            case 8:
              result = _context.sent;
              items = result.data.entities;

              if (items.length > 0) {
                offset = result.data.pageKey;

                if (!result.data.isLastFetched) {
                  loaderBtn.style.display = 'inherit';
                }
              } else {
                loaderBtn.removeEventListener('click', loadFiles);
              }

              items.forEach(function (duplicate, i) {
                duplicate.files = Object.values(duplicate.files);

                if (duplicate.files.length > 0) {
                  groupedResult.totalSize += duplicate.files[0].size * duplicate.files.length;
                  groupedResult.itemCount += duplicate.files.length;
                  groupedResult.uniqueTotalSize += duplicate.files[0].size;
                } else {
                  items.splice(i, 1);
                }
              }); // Sort desending by size

              items.sort(function (a, b) {
                if (Array.isArray(b.files) && Array.isArray(a.files) && b.files.length > 0 && a.files.length > 0) {
                  return Math.abs(b.files[0].size * b.files.length - a.files[0].size * a.files.length);
                } else {
                  return -1;
                }
              });
              groupedResult.groupedItems = groupedResult.groupedItems.concat(items);
              render(items);
              _context.next = 25;
              break;

            case 17:
              _context.prev = 17;
              _context.t0 = _context["catch"](2);
              console.error('duplicatefinder: API Fetching', _context.t0, response);
              loader.style.display = 'none';
              errorElement = document.createElement('div');
              errorElement.innerHTML = 'Failed to load duplicates';
              errorElement.style = 'width: 100%; color: rgb(132, 32, 41); background-color: rgb(248, 215, 218); border-color: rgb(245, 194, 199);height: 4em;line-height: 4em;padding-left: 1em;border: 1px solid rgb(245, 194, 199);border-radius: .25rem;';
              element.appendChild(errorElement);

            case 25:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, null, [[2, 17]]);
    }));
    return _loadFiles.apply(this, arguments);
  }

  loadFiles();
  loaderBtn.addEventListener('click', loadFiles);
})();
/******/ })()
;
//# sourceMappingURL=script.js.map