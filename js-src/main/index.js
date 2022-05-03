/* global OC, fetch */
(function () {
  const baseUrl = OC.generateUrl('/apps/duplicatefinder/api')
  const element = document.getElementById('container')
  const loader = document.getElementById('loader-container')
  const loaderBtn = document.getElementById('loader-btn')
  const title = document.getElementById('title')
  const groupedResult = {
    groupedItems: [],
    totalSize: 0,
    itemCount: 0,
    uniqueTotalSize: 0
  }
  let offset = 0

  function render (items) {
    loader.style.display = 'none'

    if (groupedResult.itemCount === 0) {
      updateTitle('0 duplicate file found')
    } else {
      updateTitleWithStats()

      items.forEach((duplicate) => {
        if (duplicate && Array.isArray(duplicate.files) && duplicate.files.length > 0) {
          const groupDOM = getGroupElement(duplicate)
          element.appendChild(groupDOM)
        }
      })
    }
  }

  function updateTitle (sTitle) {
    title.innerHTML = sTitle
  }

  function updateTitleWithStats () {
    const spaceUsedByDublicateFiles = OC.Util.humanFileSize(groupedResult.totalSize - groupedResult.uniqueTotalSize)

    updateTitle(groupedResult.itemCount + ' files found. Total: ' + OC.Util.humanFileSize(groupedResult.totalSize) + '. ' + spaceUsedByDublicateFiles + ' of space could be freed.')
  }

  function isImage (item) {
    return item.mimetype.substr(0, item.mimetype.indexOf('/')) === 'image'
  }

  function isVideo (item) {
    return item.mimetype.substr(0, item.mimetype.indexOf('/')) === 'video'
  }

  function getPreviewImage (item) {
    if (isImage(item) || isVideo(item)) {
      const query = new URLSearchParams({
        file: normalizeItemPath(item.path),
        fileId: item.nodeId,
        x: 500,
        y: 500,
        forceIcon: 0
      })
      return OC.generateUrl('/core/preview.png?') + query.toString()
    }

    return OC.MimeType.getIconUrl(item.mimetype)
  }

  function normalizeItemPath (path) {
    return path.match(/\/([^/]*)\/files(\/.*)/)[2]
  }

  function deleteItem (item, e) {
    const fileClient = OC.Files.getClient()
    let iconEl
    if (e.target.className === 'icon icon-delete') {
      iconEl = e.target
    } else {
      iconEl = e.target.getElementsByClassName('icon-delete')[0]
    }
    iconEl.classList.replace('icon-delete', 'icon-loading')

    fileClient.remove(normalizeItemPath(item.path)).then(function () {
      groupedResult.groupedItems.forEach(cleanGroupItems.bind(undefined, item))
      updateTitleWithStats()
    }).fail(function () {
      iconEl.classList.replace('icon-loading', 'icon-delete')
      OC.dialogs.alert('Error deleting the file: ' + normalizeItemPath(item.path), 'Error')
    })
  }

  function cleanGroupItems (item, grp, i) {
    grp.files.forEach((file, j) => {
      if (file.path === item.path) {
        if (document.getElementById(grp.id + '-file-' + file.id) !== null) {
          document.getElementById(grp.id + '-file-' + file.id).remove()
        }
        groupedResult.totalSize -= item.size
        groupedResult.itemCount -= 1
        grp.files.splice(j, 1)
      }
    })
    if (grp.files.length <= 1) {
      if (document.getElementById('grp-' + grp.id) !== null) {
        document.getElementById('grp-' + grp.id).remove()
      }
      groupedResult.totalSize -= item.size
      groupedResult.itemCount -= 1
      groupedResult.uniqueTotalSize -= item.size
      groupedResult.groupedItems.splice(i, 1)
    }
  }

  function getGroupElement (group) {
    const groupItems = group.files
    const groupContainer = document.createElement('div')
    groupContainer.setAttribute('id', 'grp-' + group.id)
    groupContainer.setAttribute('class', 'duplicates')

    const sizeDiv = document.createElement('div')
    sizeDiv.setAttribute('class', 'filesize')

    groupItems.forEach(function (item) {
      const itemDiv = getItemElement(item, group.id)
      groupContainer.appendChild(itemDiv)
      if (item && item.size) {
        sizeDiv.innerHTML = OC.Util.humanFileSize(item.size)
      }
    })
    groupContainer.appendChild(sizeDiv)
    return groupContainer
  }

  function getItemElement (item, grpId) {
    // Item wrapper container
    const itemDiv = document.createElement('div')
    itemDiv.setAttribute('id', grpId + '-file-' + item.id)
    itemDiv.setAttribute('class', 'element')

    // Delete button
    const deleteButton = document.createElement('button')
    deleteButton.innerHTML = '<span class="icon icon-delete"></span>'
    deleteButton.setAttribute('class', 'button-delete')
    deleteButton.addEventListener('click', function (e) {
      deleteItem(item, e)
    })
    itemDiv.appendChild(deleteButton)

    // Preview image
    const previewImage = document.createElement('div')
    previewImage.setAttribute('class', 'thumbnail')
    previewImage.style.backgroundImage = "url('" + getPreviewImage(item) + "')"
    itemDiv.appendChild(previewImage)

    // Label container on the right
    const labelContainer = document.createElement('div')

    // Item path
    const itemPath = document.createElement('h1')
    itemPath.setAttribute('class', 'path')
    itemPath.innerHTML = normalizeItemPath(item.path)
    labelContainer.appendChild(itemPath)

    const actions = [
      {
        icon: 'icon-file',
        url: function (selectedItem) {
          const dir = OC.dirname(normalizeItemPath(selectedItem.path))

          return OC.generateUrl('/apps/files/?dir=' + dir + '&openfile=' + selectedItem.nodeId)
        },
        description: 'Show file'
      },
      {
        icon: 'icon-details',
        url: function (selectedItem) {
          return OC.generateUrl('/f/' + selectedItem.nodeId)
        },
        description: 'Show details'
      }
    ]
    const actionsContainer = document.createElement('div')
    actionsContainer.setAttribute('class', 'fileactions')

    actions.forEach(action => {
      const actionLink = document.createElement('a')
      actionLink.setAttribute('href', action.url.call(null, item))
      actionLink.setAttribute('class', 'action permanent')
      actionLink.setAttribute('title', action.description)

      const actionIcon = document.createElement('span')
      actionIcon.setAttribute('class', 'icon ' + action.icon)
      actionLink.appendChild(actionIcon)

      actionsContainer.appendChild(actionLink)
    })

    labelContainer.appendChild(actionsContainer)

    // Item hash
    const hashContainer = document.createElement('h1')
    hashContainer.setAttribute('class', 'hash')
    hashContainer.innerHTML = item.fileHash
    labelContainer.appendChild(hashContainer)

    itemDiv.appendChild(labelContainer)

    return itemDiv
  }

  async function loadFiles () {
    loader.style.display = 'inherit'
    loaderBtn.style.display = 'none'
    let response
    try {
      response = await fetch(baseUrl + '/v1/Duplicates?offset=' + offset, {
        redirect: 'error'
      })

      const result = await response.json()
      const items = result.data.entities

      if (items.length > 0) {
        offset = result.data.pageKey
        if (!result.data.isLastFetched) {
          loaderBtn.style.display = 'inherit'
        }
      } else {
        loaderBtn.removeEventListener('click', loadFiles)
      }

      items.forEach((duplicate, i) => {
        duplicate.files = Object.values(duplicate.files)
        if (duplicate.files.length > 0) {
          groupedResult.totalSize += duplicate.files[0].size * duplicate.files.length
          groupedResult.itemCount += duplicate.files.length
          groupedResult.uniqueTotalSize += duplicate.files[0].size
        } else {
          items.splice(i, 1)
        }
      })
      // Sort desending by size
      items.sort((a, b) => {
        if (Array.isArray(b.files) && Array.isArray(a.files) &&
              b.files.length > 0 && a.files.length > 0) {
          return Math.abs((b.files[0].size * b.files.length) - (a.files[0].size * a.files.length))
        } else {
          return -1
        }
      })
      groupedResult.groupedItems = groupedResult.groupedItems.concat(items)

      render(items)
    } catch (e) {
      console.error('duplicatefinder: API Fetching', e, response)
      loader.style.display = 'none'
      const errorElement = document.createElement('div')
      errorElement.innerHTML = 'Failed to load duplicates'
      errorElement.style = 'width: 100%; color: rgb(132, 32, 41); background-color: rgb(248, 215, 218); border-color: rgb(245, 194, 199);height: 4em;line-height: 4em;padding-left: 1em;border: 1px solid rgb(245, 194, 199);border-radius: .25rem;'
      element.appendChild(errorElement)
    }
  }

  loadFiles()
  loaderBtn.addEventListener('click', loadFiles)
})()
