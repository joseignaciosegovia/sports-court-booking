(() => {
  'use strict'

  function initializeValidation() {
    // Validación de confirmación de contraseña
    const password = document.getElementById('password')
    const passwordConfirmation = document.getElementById('password_confirmation')

    if (password && passwordConfirmation) {
      const checkPasswordsMatch = () => {
        if (passwordConfirmation.value !== password.value) {
          passwordConfirmation.setCustomValidity('Las contraseñas no coinciden.')
        } else {
          passwordConfirmation.setCustomValidity('')
        }
      }

      password.addEventListener('input', checkPasswordsMatch)
      passwordConfirmation.addEventListener('input', checkPasswordsMatch)
    }

    // Validación general de formularios
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
  }

  // Cuando el DOM está disponible
  if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initializeValidation)
  } else {
      initializeValidation()
  }
})()