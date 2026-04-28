const setPreviewUrl = (file) => {
  const anchor = file.previewElement.querySelector('.file-name')
  if (anchor) {
    anchor.href = anchor.href + file.name
    return anchor.href
  }
}

const getPreviewTemplate = (element) => {
  if (!element) return
  const template = element.innerHTML
  element.parentNode.removeChild(element)
  return template
}

export const fileUpload = async (formElement) => {
  if (!formElement) return null
  const Dropzone = (await import('dropzone')).default
  await import('./fileUpload.css')

  // if (Dropzone.instances.length > 0)
  //   Dropzone.instances.forEach((dz) => dz.destroy())

  // Get the template HTML and remove it from the doument
  const uploadElement = formElement.querySelector('.form-group-mupload')
  if (!uploadElement) {
    return null
  }
  const input = uploadElement.querySelector('input[type="file"]')
  const apiUrl = '/index.php?rex-api-call=upload_files'
  const maxFileSize = input.dataset.maxFileSize
    ? Math.ceil(input.dataset.maxFileSize / 1000 / 1000)
    : 10485760
  const acceptedFiles =
    input.dataset.acceptedFiles ||
    '.jpg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar'
  Dropzone.autoDiscover = false

  const previewTemplateElement = formElement.querySelector(
    '[data-dropzone-template]'
  )
  const previewTemplate = getPreviewTemplate(previewTemplateElement)
  const previewElement = formElement.querySelector('.dropzone-previews')

  const dropzone = new Dropzone(formElement, {
    // Make the whole body a dropzone
    url: apiUrl,
    parallelUploads: 20,
    maxFiles: 10,
    maxFilesize: maxFileSize,
    paramName: 'files',
    createImageThumbnails: false,
    acceptedFiles: acceptedFiles,
    previewTemplate: previewTemplate,
    addRemoveLinks: false,
    autoQueue: true, // Make sure the files aren't queued until manually added
    previewsContainer: '[data-dropzone-previews]', // Define the container to display the previews
    clickable:
      '.form-group-mupload label, .form-group-mupload .files .clickable', // Define the element that should be used as click trigger to select files.
    dictDefaultMessage:
      'Dateien hier ablegen oder klicken, um Dateien hochzuladen.',
    dictFallbackMessage:
      "Ihr Browser unterstützt Drag'n'Drop Datei-Upload nicht.",
    dictFileTooBig:
      'Datei ist zu groß ({{filesize}}MB). Max: {{maxFilesize}}MB.',
    dictInvalidFileType: 'Dateityp nicht erlaubt.',
    dictResponseError: 'Server hat mit {{statusCode}} Code geantwortet.',
    dictCancelUpload: 'Upload abbrechen',
    dictCancelUploadConfirmation: 'Upload wirklich abbrechen?',
    dictRemoveFile: 'Datei entfernen',
    dictMaxFilesExceeded: 'Maximale Anzahl an Dateien erreicht.',
    init: function () {
      const self = this
      const files = JSON.parse(
        formElement.querySelector('[data-dropzone-files-data]').textContent
      )

      if (!files.length) return

      files.forEach((file) => {
        file.accepted = true
        self.displayExistingFile(
          file,
          previewTemplateElement.dataset.url + file.name
        )
        self.files.push(file)
        setPreviewUrl(file)
      })
    }
  })

  dropzone.on('addedfile', function () {
    previewElement.classList.add('has-files')
  })

  dropzone.on('removedfile', function (file) {
    const hasFiles = dropzone.files.length > 0
    if (!hasFiles) {
      previewElement.classList.remove('has-files')
    }
    fetch(apiUrl + '&file=' + file.name, {
      method: 'DELETE',
      headers: {
        'Content-type': 'application/json; charset=UTF-8'
      }
    })
  })

  dropzone.on('success', function (file) {
    setPreviewUrl(file)
  })

  return dropzone

  // dropzone.on('error', function (file, message) {
  //     alert(message);
  //     this.removeFile(file);
  // });

  // Update the total progress bar
  // dropzone.on('totaluploadprogress', function (progress) {
  //     document.querySelector(
  //         '#total-progress .progress-bar'
  //     ).style.width = progress + '%';
  // });

  // dropzone.on('sending', function (file) {
  //     document.querySelector('#files-header').removeAttribute('style');
  //     // Show the total progress bar when upload starts
  //     // document.querySelector(
  //     //     '#total-progress .progress-bar'
  //     // ).style.display = 'block';
  //     // And disable the start button
  //     // file.previewElement
  //     //     .querySelector('.start')
  //     //     .setAttribute('disabled', 'disabled')
  // });

  // Hide the total progress bar when nothing's uploading anymore
  // dropzone.on('queuecomplete', function (progress) {
  //     document.querySelector(
  //         '#total-progress .progress-bar'
  //     ).style.display = 'none';
  // });

  // Setup the buttons for all transfers
  // The "add files" button doesn't need to be setup because the config
  // `clickable` has already been specified.
  // document.querySelector('#actions .start').onclick = function() {
  //     dropzone.enqueueFiles(dropzone.getFilesWithStatus(Dropzone.ADDED))
  // }
  // document.querySelector('#actions .cancel').onclick = function() {
  //     dropzone.removeAllFiles(true)
  // }
}
