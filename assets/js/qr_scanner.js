document.addEventListener("DOMContentLoaded", () => {
  const wrapper = document.querySelector(".wrapper"),
    form = document.querySelector("form"),
    fileInp = form.querySelector("input[type='file']"),
    submitBtn = document.getElementById("submitBtn"),
    infoText = form.querySelector("p"),
    editBtn = document.querySelector(".edit"),
    saveBtn = document.querySelector(".save"),
    cameraBtn = document.querySelector(".open-camera"),
    switchCameraBtn = document.querySelector(".switch-camera"),
    scanAgainBtn = document.querySelector(".scan-again"),
    readerDiv = document.getElementById("reader"),
    modal = document.getElementById("editModal"),
    modalCloseBtn = document.getElementById("modalClose"),
    modalSaveBtn = document.getElementById("modalSave");

  let html5QrCode;
  let cameras = [];
  let currentCameraIndex = 0;

  function startCamera(cameraId) {
    html5QrCode
      .start(
        cameraId,
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText) => {
          console.log("Scanned QR:", decodedText);
          processDecodedText(decodedText);
          html5QrCode.stop();
          wrapper.classList.add("active");
        },
        (errorMessage) => {
          console.warn("Scanning error:", errorMessage);
        }
      )
      .catch((err) => {
        console.error("Camera error:", err);
        alert("Unable to access camera.");
      });
  }

  cameraBtn.addEventListener("click", () => {
    readerDiv.style.display = "block";
    switchCameraBtn.style.display = "inline-block";
    wrapper.classList.add("scanner-active");

    if (!html5QrCode) {
      html5QrCode = new Html5Qrcode("reader");
    }

    Html5Qrcode.getCameras()
      .then((devices) => {
        if (devices.length > 0) {
          cameras = devices;
          const frontCam = devices.find((cam) => /front|user/i.test(cam.label));
          currentCameraIndex = frontCam ? devices.indexOf(frontCam) : 0;
          startCamera(cameras[currentCameraIndex].id);
        } else {
          alert("No cameras found.");
        }
      })
      .catch((err) => {
        console.error("No cameras found:", err);
        alert("No cameras found.");
      });
  });

  switchCameraBtn.addEventListener("click", () => {
    if (!cameras.length || !html5QrCode) return;

    html5QrCode
      .stop()
      .then(() => {
        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
        console.log("Switched to camera:", cameras[currentCameraIndex].label);
        startCamera(cameras[currentCameraIndex].id);
      })
      .catch((err) => {
        console.error("Error switching camera:", err);
      });
  });

  fileInp.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (!html5QrCode) {
      html5QrCode = new Html5Qrcode("reader");
    }

    html5QrCode
      .scanFile(file, true)
      .then((decodedText) => {
        processDecodedText(decodedText);
        wrapper.classList.add("active");
      })
      .catch((err) => {
        console.error("File scan error:", err);
        alert("Failed to scan QR code from file.");
      });
  });

  scanAgainBtn.addEventListener("click", () => {
    const textarea = document.querySelector("textarea");
    if (textarea) {
      textarea.value = "";
      editBtn.disabled = true;
    }

    wrapper.classList.remove("active");
    wrapper.classList.add("scanner-active");
    readerDiv.style.display = "block";
    switchCameraBtn.style.display = "inline-block";

    if (!html5QrCode) {
      html5QrCode = new Html5Qrcode("reader");
    }

    Html5Qrcode.getCameras()
      .then((devices) => {
        if (devices.length > 0) {
          cameras = devices;
          startCamera(cameras[currentCameraIndex].id);
        } else {
          alert("No cameras found.");
        }
      })
      .catch((err) => {
        console.error("No cameras found:", err);
        alert("No cameras found.");
      });
  });

  editBtn.addEventListener("click", () => {
    const textarea = document.querySelector("textarea");
    if (!textarea.value.trim()) {
      alert("Scan first the QR Code!");
      return;
    }

    let qrData = encodeURIComponent(textarea.value);
    window.location.href = `edit_profile.php?data=${qrData}`;
  });

  submitBtn.addEventListener("click", () => {
    let qrData = document.querySelector("textarea").value;
    if (!qrData) {
      alert("No QR Code data found!");
      return;
    }

    let student = {
      studentid: "",
      name: "",
      middleInitial: "",
      suffix: "",
      course: "",
      status: "",
      yearStarted: "",
    };

    qrData.split("\n").forEach((line) => {
      let cleanLine = line.replace(/["{}]/g, "").trim();
      let match = cleanLine.match(/^([\w\s]+):\s*(.+)$/);
      if (match) {
        let key = match[1].trim().toLowerCase();
        let value = match[2].trim();

        if (key.includes("student id")) student.studentid = value;
        if (key.includes("name")) student.name = value;
        if (key.includes("middle initial")) student.middleInitial = value;
        if (key.includes("suffix")) student.suffix = value;
        if (key.includes("course")) student.course = value;
        if (key.includes("status")) {
          let cleanedStatus = value.toLowerCase();
          student.status =
            cleanedStatus === "active" || cleanedStatus === "inactive"
              ? cleanedStatus.charAt(0).toUpperCase() + cleanedStatus.slice(1)
              : "N/A";
        }
        if (key.includes("year started")) {
          let parsedDate = new Date(value);
          student.yearStarted = !isNaN(parsedDate)
            ? parsedDate.getFullYear().toString()
            : "";
        }
      }
    });

    fetch("/qr/assets/QrScanner/save_student.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(student),
    })
      .then((response) => response.text())
      .then((data) => {
        alert(data);
        if (data.includes("success")) {
          alert("Student data saved successfully!");
          window.location.href = "/qr/assets/students/student_file.php";
        }
      })
      .catch((error) => console.error("Error:", error));
  });

  function processDecodedText(decodedText) {
    const textarea = document.querySelector("textarea");

    let studentId = "";
    let lines = decodedText.split("\n");

    lines.forEach((line) => {
      if (line.toLowerCase().includes("student id")) {
        const parts = line.split(":");
        if (parts.length > 1) {
          studentId = parts[1].trim();
        }
      }
    });

    if (!studentId) {
      alert("Invalid QR code: Student ID is missing.");
      textarea.value = "";
      return;
    }

    textarea.value = decodedText;
    fetchStudentData(studentId, lines, decodedText);
  }

  function fetchStudentData(studentId, lines, decodedText) {
    const textarea = document.querySelector("textarea");

    fetch("/qr/assets/php/fetch_student.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ qrCode: studentId }),
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.status === "success" && result.data) {
          const student = result.data;
          textarea.value =
            `Student ID: ${student.studentid}\n` +
            `Name: ${student.name}\n` +
            `Course: ${student.course || "Not Provided"}\n` +
            `Year Started: ${student.yearStarted || "Not Provided"}\n` +
            `Status: ${student.status || "Not Provided"}`;
        } else {
          const filteredText = lines
            .filter((line) => {
              const lower = line.toLowerCase();
              return (
                lower.includes("student id") ||
                lower.includes("name") ||
                lower.includes("course") ||
                lower.includes("status") ||
                lower.includes("year started") ||
                lower.includes("middle initial") ||
                lower.includes("suffix")
              );
            })
            .join("\n");

          if (!filteredText.trim()) {
            alert("QR code does not contain valid student data.");
            textarea.value = "";
            return;
          }

          textarea.value = filteredText;
        }
      })
      .catch((error) => {
        console.error("Error fetching student data via POST:", error);
        alert("Error retrieving student data. Please try again.");
        textarea.value = "";
      });
  }
});
