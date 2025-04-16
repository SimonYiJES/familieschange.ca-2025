document.addEventListener("DOMContentLoaded", function () {
  const printButton = document.getElementById("print-pdf");
  if (printButton) {
    printButton.addEventListener("click", function () {
      var pdfUrl = this.getAttribute("data-pdf-url");
      if (!pdfUrl) {
        alert("PDF not found!");
        return;
      }

      var printWindow = window.open(pdfUrl, "_blank");

      if (printWindow) {
        printWindow.onload = function () {
          printWindow.print();
        };
      } else {
        alert("Popup blocked! Please allow popups and try again.");
      }
    });
  }


});



// document.addEventListener("DOMContentLoaded", function () {
//   let modal = document.getElementById("MayModal");
//   let pdfPreview = document.getElementById("pdf-preview");
//   let printBtn = document.getElementById("print-pdf");

//   modal.addEventListener("show.bs.modal", function (event) {
//       let button = event.relatedTarget;
//       let pdfUrl = button.getAttribute("data-pdf-url");
//       pdfPreview.src = pdfUrl + "#toolbar=0";

//       printBtn.onclick = function () {
//           let printWindow = window.open(pdfUrl);
//           printWindow.print();
//       };
//   });
// });

(function ($, Drupal, once) {
    Drupal.behaviors.movePager = {
      attach: function (context, settings) {
  
        if (!location.pathname.includes("/help")) {
          return;
        }
  
        createFilter();
  
        $(document).ajaxComplete(function (event, xhr, settings) {
          if (settings.url.includes("/views/ajax")) {
            createFilter();
          }
        });
  
        function createFilter() {
          const originalFilter = document.querySelector(
            ".view-help .view-filters .form-item-field-select-province-target-id select"
          );
  
          if (originalFilter) {
            const targetDiv = document.querySelector(
              ".view-help .view-header .sorting"
            );
  
            // Remove any existing cloned element to prevent duplicates
            const existingCustomSorting = document.getElementById("custom-sorting-element");
            if (existingCustomSorting) {
              existingCustomSorting.remove();
            }
  
            // Clone the filter element
            const clonedFilter = originalFilter.cloneNode(true);
            clonedFilter.id = "cloned-sorting-element";
  
            // Create custom dropdown
            const customSorting = document.createElement("div");
            customSorting.id = "custom-sorting-element";
            const selected = document.createElement("a");
            selected.id = "selected-option";
  
            const ul = document.createElement("ul");
            ul.classList.add("options-list");
  
            clonedFilter.querySelectorAll("option").forEach((data) => {
              const li = document.createElement("li");
              li.innerText = data.textContent;
              li.dataset.value = data.value; // Store the option value
              if (data.selected) {
                selected.innerText = data.textContent;
                customSorting.insertBefore(selected, customSorting.firstChild);
                li.classList.add("selected");
              }
              ul.appendChild(li);
            });
  
            customSorting.appendChild(ul);
            targetDiv.appendChild(customSorting);
  
            // Adding click event to toggle the ul
            selected.addEventListener("click", function (e) {
              e.stopPropagation();
              ul.classList.toggle("expanded");
            });
  
            // Handle selection change
            const customOptions = ul.querySelectorAll("li");
            customOptions.forEach((opt) => {
              opt.addEventListener("click", function () {
                customOptions.forEach((item) => item.classList.remove("selected"));
                opt.classList.add("selected");
                selected.innerText = opt.innerText;
                ul.classList.remove("expanded");
  
                // Update original filter value and trigger AJAX filter
                originalFilter.value = opt.dataset.value;
                $(originalFilter).trigger("change"); // Drupal AJAX trigger
              });
            });
  
            // Close dropdown when clicking outside
            document.addEventListener("click", function (e) {
              if (!customSorting.contains(e.target)) {
                ul.classList.remove("expanded");
              }
            });
          }
        }
      },
    };
  })(jQuery, Drupal, once);
  

  document.addEventListener("DOMContentLoaded", function() {
    var watchNowBtn = document.querySelector(".block-banner .cta a"); // Watch Now button
    var modal = document.getElementById("videoModal");
    var iframe = document.getElementById("videoFrame");
    var closeBtn = document.querySelector(".close-btn");

    if (watchNowBtn && modal && iframe) {
        watchNowBtn.addEventListener("click", function(event) {
            event.preventDefault(); // Prevent default navigation
            iframe.src = this.href; // Load video dynamically
            modal.classList.add("show"); // Add 'show' class to trigger transition
        });

        closeBtn.addEventListener("click", function() {
            modal.classList.remove("show"); // Remove 'show' class
            setTimeout(() => {
                iframe.src = ""; // Stop video playback AFTER transition
            }, 400); // Matches transition duration
        });

        window.addEventListener("click", function(event) {
            if (event.target === modal) {
                modal.classList.remove("show"); // Hide modal smoothly
                setTimeout(() => {
                    iframe.src = ""; // Stop video playback AFTER transition
                }, 400);
            }
        });
    }
});


