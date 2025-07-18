document.addEventListener("DOMContentLoaded", function () {
  let html5QrCode;
  let cameras = [];
  let currentCameraIndex = 0;

  const generatePrintQRButton = document.getElementById("generatePrintQRButton");
  const fileUploadButton = document.getElementById("fileUploadButton");
  const fileInput = document.getElementById("fileInput");

  function capitalizeFirstLetter(string) {
    return string
      .split(" ")
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
      .join(" ");
  }

  function populateYearDropdown() {
    const yearDropdown = document.getElementById("yearStarted");
    const currentYear = new Date().getFullYear();
    for (let year = currentYear; year >= 1926; year--) {
      const option = document.createElement("option");
      option.value = year;
      option.textContent = year;
      yearDropdown.appendChild(option);
    }
    yearDropdown.value = currentYear;
  }

  populateYearDropdown();

  function transformStudentData(raw) {
    const studentNo = raw["Student No"]?.trim() || "";
    const fullName = raw["Full Name"]?.trim() || "";
    const program = raw["Program Name"]?.trim() || "";

    const yearStarted = studentNo.substring(0, 4);

    const suffixMatch = program.match(/\(([^)]+)\)/);
    const suffix = suffixMatch ? suffixMatch[1] : "";
    const baseProgram = program.split(" (")[0];
    const acronym = baseProgram
      .split(" ")
      .filter(
        (word) =>
          !["in", "of", "and", "the", "for"].includes(word.toLowerCase())
      )
      .map((word) => word[0])
      .join("")
      .toUpperCase();

    const course = suffix ? `${acronym} - ${suffix}` : acronym;

    return {
      studentId: studentNo,
      name: fullName,
      course,
      yearStarted,
      status: "",
      qrCode: "",
    };
  }

  function generateQRCodeForStudent(student) {
    return new Promise((resolve, reject) => {
      const qrText = `Student ID: ${student.studentId}\nName: ${student.name}\nYear Started: ${student.yearStarted}`;

      let qrDataUrl = null;
      let success = false;

      try {
        for (let version = 1; version <= 40; version++) {
          try {
            const qr = qrcode(version, "L");
            qr.addData(qrText);
            qr.make();
            qrDataUrl = qr.createDataURL();
            success = true;
            break;
          } catch (e) {}
        }

        if (!success) {
          return reject(
            "QR code generation failed: content too large for max version 40."
          );
        }

        student.qrCode = qrDataUrl;
        resolve(student);
      } catch (err) {
        reject("QR generation failed: " + err.message);
      }
    });
  }

  function generateAllQRCodesWithProgress(studentList) {
    const progressContainer = document.getElementById("qrProgressContainer");
    const progressCount = document.getElementById("qrProgressCount");
    const progressTotal = document.getElementById("qrProgressTotal");

    progressContainer.style.display = "block";
    progressTotal.textContent = studentList.length;
    progressCount.textContent = "0";

    let completed = 0;

    const qrPromises = studentList.map((student) =>
      generateQRCodeForStudent(student).then((updatedStudent) => {
        completed++;
        progressCount.textContent = completed;
        return updatedStudent;
      })
    );

    Promise.all(qrPromises)
      .then((studentsWithQR) => {
        progressContainer.style.display = "none";
        showUploadModal(studentsWithQR);
      })
      .catch((err) => {
        alert("Error generating QR codes: " + err);
        progressContainer.style.display = "none";
      });
  }

  function showUploadModal(data) {
    const modal = document.createElement("div");
    modal.classList.add("modal");
    modal.innerHTML = `
      <div class="modal-content">
        <h2>Ready to Upload Data</h2>
        <button id="uploadBtn">Upload Data to Database</button>
        <button id="closeModalBtn">Close</button>
      </div>
    `;
    document.body.appendChild(modal);
    modal.style.display = "block";

    document.getElementById("uploadBtn").addEventListener("click", function () {
      sendDataToServer(data, "upload");
      modal.style.display = "none";
    });

    document
      .getElementById("closeModalBtn")
      .addEventListener("click", function () {
        modal.style.display = "none";
      });
  }

  async function sendDataToServer(data, actionType = "upload") {
    try {
      const response = await fetch("/qr/assets/php/process_student_data.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: actionType, payload: data }),
      });

      if (!response.ok)
        throw new Error(`HTTP error! Status: ${response.status}`);

      const rawResponse = await response.text();
      let results;
      try {
        results = JSON.parse(rawResponse);
      } catch (error) {
        throw new Error("Failed to parse JSON response: " + error.message);
      }

      displayResultPrompt(results);
    } catch (error) {
      console.error("Error in sendDataToServer:", error.message);
      alert("Error communicating with server: " + error.message);
    }
  }

  function displayResultPrompt(results) {
    let successMessages = [];
    let errorMessages = [];

    results.forEach((result) => {
      const id = result.studentId || "Unknown ID";
      const message = result.message || "Unknown error";
      if (result.status === "success") {
        successMessages.push(`${id}: ${message}`);
      } else {
        errorMessages.push(`${id}: ${message}`);
      }
    });

    let fullMessage = "";
    if (successMessages.length) {
      fullMessage += ` ✅ Success:\n${successMessages.join("\n")}\n\n`;
    }
    if (errorMessages.length) {
      fullMessage += `❌ Failed:\n${errorMessages.join("\n")}`;
    }

    alert(fullMessage);
  }

  if (generatePrintQRButton) {
    generatePrintQRButton.addEventListener("click", function () {
      const studentId = document.getElementById("studentId").value.trim();
      const firstName = capitalizeFirstLetter(
        document.getElementById("firstName").value.trim()
      );
      const middleInitial = capitalizeFirstLetter(
        document.getElementById("middleInitial").value.trim()
      );
      const lastName = capitalizeFirstLetter 
        (document.getElementById("lastName").value.trim());
      const suffix = capitalizeFirstLetter(
        document.getElementById("suffix").value.trim()
      );
      const yearStarted = document.getElementById("yearStarted").value.trim();

      if (!studentId || !firstName || !lastName || !yearStarted) {
        alert("Please fill in all required fields to Generate QR Code.");
        return;
      }

      const fullName = `${firstName}${middleInitial ? " " + middleInitial : ""} ${lastName}${suffix ? ", " + suffix : ""}`;

      const student = {
        studentId,
        name: fullName,
        course: "",
        yearStarted,
        status: "",
        qrCode: "",
      };

      generateAndShowQR(student);
    });
  }

  function generateAndShowQR(student) {
    const { studentId, name, yearStarted } = student;
    const qrText = `Student ID: ${studentId}\nName: ${name}\nYear Started: ${yearStarted}`;
    const qrcodeContainer = document.getElementById("qrcode");
    qrcodeContainer.innerHTML = "";

    new QRCode(qrcodeContainer, {
      text: qrText,
      width: 256,
      height: 256,
    });

    waitForQRCodeAndSend(student, studentId, name);
  }

  function waitForQRCodeAndSend(student, studentId, name) {
    const maxWait = 3000;
    const interval = 100;
    let waited = 0;

    const checkQRReady = setInterval(() => {
      const qrImg = document.querySelector("#qrcode img");
      const qrCanvas = document.querySelector("#qrcode canvas");

      const qrDataUrl = qrImg?.src || qrCanvas?.toDataURL("image/png") || "";

      if (qrDataUrl && qrDataUrl.startsWith("data:image")) {
        clearInterval(checkQRReady);
        student.qrCode = qrDataUrl;
        sendDataToServer([student], "qr_only");
        showQRPopup(studentId, name);
      }

      waited += interval;
      if (waited >= maxWait) {
        clearInterval(checkQRReady);
        alert("QR Code generation timed out. Please try again.");
      }
    }, interval);
  }

  function showQRPopup(studentId, fullName) {
    const modal = document.getElementById("qrModal");
    const display = document.getElementById("qrDisplayContainer");
    const info = document.getElementById("modalStudentInfo");

    let qrDataUrl = "";
    const qrImg = document.querySelector("#qrcode img");
    const qrCanvas = document.querySelector("#qrcode canvas");

    if (qrImg && qrImg.src.startsWith("data:image")) {
      qrDataUrl = qrImg.src;
    } else if (qrCanvas) {
      try {
        qrDataUrl = qrCanvas.toDataURL("image/png");
      } catch (err) {
        console.warn("Failed to get canvas data URL:", err);
      }
    }

    if (!qrDataUrl) {
      alert("QR code could not be displayed. Please try again.");
      return;
    }

    currentQRInfo = { studentId, fullName, qrDataUrl };

    display.innerHTML = `<img src="${qrDataUrl}" id="popupQR" style="width: 256px; height: 256px;" />`;
    info.innerHTML = `<p>${studentId}</p><p>${fullName}</p>`;
    modal.style.display = "block";
  }

  let currentQRInfo = {
    studentId: "",
    fullName: "",
    qrDataUrl: "",
  };

  document.getElementById("printBtn")?.addEventListener("click", () => {
    const qrImage = document.getElementById("popupQR");
    const studentInfo = document.getElementById("modalStudentInfo");

    if (!qrImage || !studentInfo) {
      alert("QR code or student info not found for printing.");
      return;
    }

    const printWindow = window.open("", "_blank", "width=400,height=500");
    printWindow.document.write(`
      <html>
        <head>
          <title>Print QR Code</title>
          <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
            img { width: 256px; height: 256px; }
            p { margin: 10px 0; font-size: 18px; }
          </style>
        </head>
        <body>
          ${qrImage.outerHTML}
          ${studentInfo.outerHTML}
        </body>
      </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.onload = () => {
      printWindow.print();
      printWindow.close();
    };
  });

  document.getElementById("downloadBtn")?.addEventListener("click", () => {
    const { studentId, fullName, qrDataUrl } = currentQRInfo;
    if (!qrDataUrl) {
      alert("No QR code to download.");
      return;
    }

    const img = new Image();
    img.crossOrigin = "anonymous";

    img.onload = function () {
      const canvas = document.createElement("canvas");
      canvas.width = 800;
      canvas.height = 880;
      const ctx = canvas.getContext("2d");

      ctx.fillStyle = "#fff";
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, 0, 0, 800, 680);

      ctx.fillStyle = "#000";
      ctx.font = "bold 40px Arial";
      ctx.textAlign = "center";
      ctx.fillText(studentId, 400, 720);
      ctx.fillText(fullName, 400, 770);

      canvas.toBlob((blob) => {
        if (!blob) {
          alert("Failed to create image for download.");
          return;
        }

        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `${studentId}.png`;
        document.body.appendChild(link);
        link.click();
        setTimeout(() => {
          URL.revokeObjectURL(link.href);
          document.body.removeChild(link);
        }, 100);
      }, "image/png");
    };

    img.onerror = () => alert("Failed to load QR image for download.");
    img.src = qrDataUrl;
  });

  document.getElementById("closeBtn")?.addEventListener("click", () => {
    document.getElementById("qrModal").style.display = "none";
    document.getElementById("qrcode").innerHTML = "";
  });

  if (fileUploadButton) {
    fileUploadButton.addEventListener("click", () => fileInput.click());
  }

  if (fileInput) {
    fileInput.addEventListener("change", function () {
      const file = fileInput.files[0];
      if (!file) return;

      const fileName = file.name.toLowerCase();
      if (fileName.endsWith(".csv")) {
        processCSV(file);
      } else if (fileName.endsWith(".xlsx")) {
        processExcel(file);
      } else {
        alert("Only .csv and .xlsx formats are supported.");
      }
    });
  }

  function processCSV(file) {
    Papa.parse(file, {
      header: true,
      skipEmptyLines: true,
      complete: function (results) {
        const transformed = results.data.map(transformStudentData);
        generateAllQRCodesWithProgress(transformed);
      },
      error: function (error) {
        console.error("CSV error:", error);
      },
    });
  }

  function processExcel(file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      const data = new Uint8Array(e.target.result);
      const workbook = XLSX.read(data, { type: "array" });
      const worksheet = workbook.Sheets[workbook.SheetNames[0]];
      const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: "" });
      const transformed = jsonData.map(transformStudentData);
      generateAllQRCodesWithProgress(transformed);
    };
    reader.readAsArrayBuffer(file);
  }

  document.getElementById("firstName")?.addEventListener("input", (e) => {
    e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, "");
  });
  document.getElementById("lastName")?.addEventListener("input", (e) => {
    e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, "");
  });
});
