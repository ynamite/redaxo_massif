const addAlert = ({ msg, className = 'alert-danger', appendTo = null }) => {
  const element = document.createElement('div')
  element.classList.add(className)
  element.innerHTML = msg
  if (appendTo) {
    appendTo.appendChild(element)
  }
  return element
}

// displays multiple input error messages
const addAlertsToForm = (obj, form) => {
  if (typeof obj === 'object') {
    obj.forEach((error) => {
      // var $input = $form.find('[name="' + name + '"]');
      // if ($input.length == 0) {
      //     $input = $form.find('[name="' + name + '[]"]');
      // }
      // if ($input.length) {
      //     var $group = $input.closest('.form-group');
      //     $.each(val, function (i, v) {
      //         if ($group.hasClass('file-upload')) {
      //             $group.append($(inputErrorMessage(v)));
      //         } else {
      //             $input.after($(inputErrorMessage(v)));
      //         }
      //     });
      //     $group.addClass('has-error');
      // }
    })
    const alerts = form.querySelectorAll('.has-error')
    return Array.from(alerts)
  }
  return []
}

// remove all alerts
const removeAlerts = (element) => {
  element.classList.remove('has-error')
  element
    .querySelectorAll('.has-error')
    .forEach((el) => el.classList.remove('has-error'))
  element.querySelectorAll('.alert-danger').forEach((el) => el.remove())
  element.parentElement
    .querySelectorAll('.alert-success')
    .forEach((el) => el.remove())
}

// set readonly state on or off
const setFormReadonly = (form, state) => {
  if (!form) return
  var elements = form.elements
  for (var i = 0, len = elements.length; i < len; ++i) {
    elements[i].readOnly = state
  }
  if (state) {
    form.classList.add('submitting')
    form
      .querySelectorAll('button[type=submit]')
      .forEach((el) => el.setAttribute('disabled', 'disabled'))
  } else {
    form.classList.remove('submitting')
    form
      .querySelectorAll('button[type=submit]')
      .forEach((el) => el.setAttribute('disabled', false))
  }
}

export {
  addAlert,
  addAlert as inputErrorMessage,
  addAlertsToForm,
  addAlertsToForm as showFormInputErrors,
  removeAlerts,
  setFormReadonly
}
