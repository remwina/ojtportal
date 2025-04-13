document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
  const submitButton = form.querySelector('button[type="submit"]');
  const btnText = submitButton.querySelector('.btn-text');
  const btnLoader = submitButton.querySelector('.btn-loader');
  const csrfToken = document.querySelector('#csrf_token');

  fetch("../Backend/Core/MAIN.php?action=getToken")
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        csrfToken.value = data.token;
      }
    });

  function clearErrors() {
    document.querySelectorAll(".error-message").forEach(element => {
      element.remove();
    });
    document.querySelectorAll("input, select").forEach(element => {
      element.classList.remove("error");
    });
  }

  function showError(fieldname, message) {
    const inputField = document.querySelector(`[name="${fieldname}"]`);
    if (inputField) {
      inputField.classList.add("error");
      const errorElement = document.createElement("div");
      errorElement.className = "error-message";
      errorElement.textContent = message;
      inputField.parentNode.insertBefore(errorElement, inputField.nextSibling);
    }
  }

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    clearErrors();

    // Show loading state
    submitButton.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-block';

    try {
      const formData = new FormData(form);
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        await swal({
          title: "Success!",
          text: data.message,
          icon: "success",
          button: "Continue"
        });
        
        if (data.redirect) {
          window.location.href = data.redirect;
        }
      } else {
        if (data.errors && Array.isArray(data.errors)) {
          data.errors.forEach(error => {
            showError(error.field, error.message);
          });
        } else {
          await swal({
            title: "Error!",
            text: data.message || "An unexpected error occurred",
            icon: "error",
            button: "Try Again"
          });
        }
      }
    } catch (error) {
      await swal({
        title: "Error!",
        text: "An unexpected error occurred. Please try again.",
        icon: "error",
        button: "Try Again"
      });
    } finally {
      submitButton.disabled = false;
      btnText.style.display = 'inline-block';
      btnLoader.style.display = 'none';
    }
  });
});