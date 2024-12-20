document.addEventListener("DOMContentLoaded", function () {
  // Function to clear previous results from a form
  function clearResults(form) {
    const resultContainer = form.querySelector(".result-container");
    if (resultContainer) {
      resultContainer.remove();
    }
  }
  // Function to add filename below input file element
  function handleFileInput(fileInput) {
    fileInput.addEventListener("change", function () {
      clearResults(fileInput.closest("form")); // Clear results when input file change
      if (this.files && this.files[0]) {
        let fileName = this.files[0].name;
        // Adds class `file-info` for new style display
        let fileInfo = document.createElement("span");
        fileInfo.classList.add("file-info");
        fileInfo.textContent = `Selected: ${fileName}`;
        // Checks and remove any previous result from same input group
        if (this.parentNode.querySelector(".file-info")) {
          this.parentNode.querySelector(".file-info").remove();
        }

        this.parentNode.appendChild(fileInfo);
      } else {
        if (this.parentNode.querySelector(".file-info")) {
          this.parentNode.querySelector(".file-info").remove();
        }
      }
    });
  }
  // Attaching new file inputs
  const fileInputs = document.querySelectorAll('input[type="file"]');
  fileInputs.forEach(handleFileInput);
  // Animation to all `section` using loop to implement the effects
  const sections = document.querySelectorAll(".section");
  sections.forEach(function (section, index) {
    section.style.animationDelay = `${index * 0.15}s`; // Time interval so all of element dont load at the same time
    section.addEventListener("animationend", function () {
      // enable pointer when animation is done
      section.style.pointerEvents = "auto"; //Enable Click for the users
    });
  });

  // Initial fade-in Animation on container
  const container = document.querySelector(".container");
  container.style.animationDelay = "0.4s";
});
document.addEventListener("DOMContentLoaded", function () {
  const embedForm = document.getElementById("embedForm");
  const extractForm = document.getElementById("extractForm");
  const imagePreview = document.getElementById("imagePreview");
  const logoPreview = document.getElementById("logoPreview");
  const watermarkedImagePreview = document.getElementById(
    "watermarkedImagePreview"
  );
  const extractDropArea = document
    .getElementById("watermarked-image")
    .closest(".drop-area");

  // Function to clear previous results
  function clearResults(form) {
    const resultContainer = form.querySelector(".result-container");
    if (resultContainer) {
      resultContainer.remove();
    }
  }

  // Image preview functionality
  function setupImagePreview(fileInput, previewImg) {
    fileInput.addEventListener("change", function (e) {
      clearResults(fileInput.closest("form")); // Clear results when new file is selected
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
          previewImg.src = event.target.result;
          previewImg.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
      } else {
        previewImg.src = ""; // Clear the image preview if no file is selected
        previewImg.classList.add("hidden"); // Hide the image preview if no file is selected
      }
    });
  }

  // Function to handle dragover event
  function handleDragOver(e) {
    e.preventDefault();
    this.classList.add("bg-gray-100");
  }

  // Function to handle dragleave event
  function handleDragLeave() {
    this.classList.remove("bg-gray-100");
  }

  // Function to handle drop event
  function handleDrop(e, inputElement, previewElement) {
    e.preventDefault();
    this.classList.remove("bg-gray-100");
    const file = e.dataTransfer.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (event) {
        previewElement.src = event.target.result;
        previewElement.classList.remove("hidden");
      };
      inputElement.files = e.dataTransfer.files; // Assign dropped files to input
      reader.readAsDataURL(file);
    }
    this.style.display = "none"; // Hide the drop area
  }

  // Setup drag and drop for extract section
  if (extractDropArea) {
    extractDropArea.addEventListener("dragover", handleDragOver);
    extractDropArea.addEventListener("dragleave", handleDragLeave);
    extractDropArea.addEventListener("drop", (e) =>
      handleDrop(
        e,
        document.getElementById("watermarked-image"),
        watermarkedImagePreview
      )
    );
  }

  // File input change handling for extract section (for non-drag-and-drop)
  const extractInput = document.getElementById("watermarked-image");
  if (extractInput) {
    extractInput.addEventListener("change", function (event) {
      clearResults(extractForm);
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          watermarkedImagePreview.src = e.target.result;
          watermarkedImagePreview.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
      } else {
        watermarkedImagePreview.src = ""; // Clear the image preview if no file is selected
        watermarkedImagePreview.classList.add("hidden"); // Hide the image preview if no file is selected
      }
      extractDropArea.style.display = "none"; // Hide the drag-and-drop area when file input is used
    });
  }

  // Setup image previews
  setupImagePreview(document.getElementById("image"), imagePreview);
  setupImagePreview(document.getElementById("logo"), logoPreview);

  // AJAX form submission
  function setupAjaxFormSubmission(form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      clearResults(form); // Clear previous results before submitting
      const formData = new FormData(form);
      const resultContainer = document.createElement("div");
      resultContainer.classList.add(
        "mt-4",
        "p-4",
        "bg-gray-100",
        "rounded",
        "result-container"
      );

      form.appendChild(resultContainer);
      resultContainer.innerHTML = "<p>Processing...</p>";

      fetch("process.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            resultContainer.classList.add("text-green-600");
            resultContainer.innerHTML = `
                <h3 class="font-bold">Success</h3>
                <p>${data.message}</p>
                ${
                  data.data.watermarked_image
                    ? `<img src="${data.data.watermarked_image}" class="mt-4 max-h-64">`
                    : data.data.message
                    ? `<p>Message: ${data.data.message}</p>`
                    : ""
                }
                `;
          } else {
            resultContainer.classList.add("text-red-600");
            resultContainer.innerHTML = `
                    <h3 class="font-bold">Error</h3>
                    <p>${data.message}</p>
                `;
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          resultContainer.classList.add("text-red-600");
          resultContainer.innerHTML = `
                <h3 class="font-bold">Network Error</h3>
                <p>Unable to process your request. Please try again.</p>
            `;
        });
    });
  }

  // Setup AJAX for both forms
  setupAjaxFormSubmission(embedForm);
  setupAjaxFormSubmission(extractForm);

  // Password toggle (existing functionality)
  const passwordToggles = document.querySelectorAll(".toggle-password");
  passwordToggles.forEach((toggle) => {
    toggle.addEventListener("click", function () {
      const passwordInput = this.closest(".relative").querySelector("input");
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      this.classList.toggle("fa-eye-slash");
      this.classList.toggle("fa-eye");
    });
  });
});
