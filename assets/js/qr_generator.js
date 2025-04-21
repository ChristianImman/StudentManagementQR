document.addEventListener("DOMContentLoaded", function () {
  var modal = document.getElementById("qrModal");
  var openModalButton = document.getElementById("qrButton");
  var closeModalSpan = document.getElementsByClassName("close")[0];
  var generateQRButton = document.getElementById("generateQRButton");
  var printQRButton = document.getElementById("printQRButton");

  function openModal(event) {
    event.preventDefault();
    modal.style.display = "block";
  }

  function closeModal() {
    modal.style.display = "none";

    document.getElementById("studentId").value = "";
    document.getElementById("firstName").value = "";
    document.getElementById("middleInitial").value = "";
    document.getElementById("lastName").value = "";
    document.getElementById("suffix").value = "";
    document.getElementById("course").value = "";
    document.getElementById("status").value = "active";

    var qrcodeContainer = document.getElementById("qrcode");
    qrcodeContainer.innerHTML = "";
  }

  function populateYearDropdown() {
    const yearDropdown = document.getElementById("yearStarted");
    const currentYear = new Date().getFullYear();
    const startYear = 1926;

    for (let year = currentYear; year >= startYear; year--) {
      const option = document.createElement("option");
      option.value = year;
      option.textContent = year;
      yearDropdown.appendChild(option);
    }

    yearDropdown.value = currentYear;
  }

  populateYearDropdown();

  function capitalizeFirstLetter(string) {
    return string
      .split(" ")
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");
  }

  function generateQR() {
    var studentId = capitalizeFirstLetter(
      document.getElementById("studentId").value.trim()
    );
    var firstName = capitalizeFirstLetter(
      document.getElementById("firstName").value.trim()
    );
    var middleInitial = capitalizeFirstLetter(
      document.getElementById("middleInitial").value.trim()
    );
    var lastName = capitalizeFirstLetter(
      document.getElementById("lastName").value.trim()
    );
    var course = "";
    var yearStarted = capitalizeFirstLetter(
      document.getElementById("yearStarted").value.trim()
    );
    var status = "";

    if (!studentId || !firstName || !lastName || !yearStarted) {
      alert("Please fill in all fields before generating the QR code.");
      return;
    }

    var fullName =
      firstName +
      (middleInitial ? " " + middleInitial : "") +
      " " +
      lastName +
      (document.getElementById("suffix").value.trim()
        ? ", " + document.getElementById("suffix").value.trim()
        : "");

    var formattedData =
      "Student ID: " +
      studentId +
      "\n" +
      "Name: " +
      fullName +
      "\n" +
      "Course: " +
      course +
      "\n" +
      "Year Started: " +
      yearStarted +
      "\n" +
      "Status: " +
      status;

    var qrcodeContainer = document.getElementById("qrcode");
    qrcodeContainer.innerHTML = "";

    new QRCode(qrcodeContainer, {
      text: formattedData,
      width: 256,
      height: 256,
    });

    openModal();
  }

  function printQR() {
    var qrcodeContainer = document.getElementById("qrcode");
    if (qrcodeContainer.innerHTML.trim() === "") {
      alert("Please generate a QR code before printing.");
      return;
    }

    var printWindow = window.open("", "_blank");
    printWindow.document.write(
      "<html><head><title>Print QR Code</title></head><body>"
    );
    printWindow.document.write("<h2>QR Code</h2>");
    printWindow.document.write(qrcodeContainer.innerHTML);
    printWindow.document.write("</body></html>");
    printWindow.document.close();
    printWindow.print();
  }

  if (generateQRButton) {
    generateQRButton.addEventListener("click", generateQR);
  }

  if (printQRButton) {
    printQRButton.addEventListener("click", printQR);
  }

  if (closeModalSpan) {
    closeModalSpan.addEventListener("click", closeModal);
  }

  window.addEventListener("click", function (event) {
    if (event.target === modal) {
      closeModal();
    }
  });

  window.addEventListener("beforeunload", function () {
    closeModal();
  });
});
