document.addEventListener("DOMContentLoaded", () => {
  const wrapper = document.querySelector(".wrapper"),
    form = document.querySelector("form"),
    fileInp = form.querySelector("input[type='file']"),
    submitBtn = document.getElementById("submitBtn"),
    infoText = form.querySelector("p"),
    editBtn = document.querySelector(".edit"),
    saveBtn = document.querySelector(".save"),
    cameraBtn = document.querySelector(".open-camera"),
    readerDiv = document.getElementById("reader"),
    modal = document.getElementById("editModal"),
    modalCloseBtn = document.getElementById("modalClose"),
    modalSaveBtn = document.getElementById("modalSave");

  let html5QrCode;

  cameraBtn.addEventListener("click", () => {
    readerDiv.style.display = "block";
    wrapper.classList.add("scanner-active");

    if (!html5QrCode) {
      html5QrCode = new Html5Qrcode("reader");
    }

    Html5Qrcode.getCameras()
      .then((devices) => {
        if (devices.length > 0) {
          let cameraId = devices[0].id;

          html5QrCode
            .start(
              cameraId,
              {
                fps: 10,
                qrbox: { width: 250, height: 250 },
              },
              (decodedText) => {
                console.log("Scanned QR:", decodedText);

                let lines = decodedText.split("\n");
                let filteredText = lines
                  .filter((line) => {
                    let lowerCaseLine = line.toLowerCase();
                    return (
                      lowerCaseLine.includes("student id") ||
                      lowerCaseLine.includes("name") ||
                      lowerCaseLine.includes("course") ||
                      lowerCaseLine.includes("status") ||
                      lowerCaseLine.includes("year started") ||
                      lowerCaseLine.includes("middle initial") ||
                      lowerCaseLine.includes("suffix")
                    );
                  })
                  .join("\n");

                let textarea = document.querySelector("textarea");
                if (textarea) {
                  textarea.value = filteredText;
                  editBtn.disabled = !textarea.value.trim();
                } else {
                  console.error("Textarea not found!");
                }

                html5QrCode.stop();
                wrapper.classList.add("active");
              },
              (errorMessage) => {
                console.warn("Scanning error:", errorMessage);
              }
            )
            .catch((err) => {
              console.error("Camera error:", err);
              alert("Camera access denied or not available.");
            });
        } else {
          alert("No cameras found.");
        }
      })
      .catch((err) => {
        console.error("No cameras found:", err);
        alert("No cameras found.");
      });
  });

  submitBtn.addEventListener("click", () => {
    let qrData = document.querySelector("textarea").value;
    if (!qrData) {
      alert("No QR Code data found!");
      return;
    }

    console.log("Raw QR Data:", qrData);

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
      console.log("Processing Line:", cleanLine);

      let match = cleanLine.match(/^([\w\s]+):\s*(.+)$/);
      if (match) {
        let key = match[1].trim().toLowerCase();
        let value = match[2].trim();

        console.log("Extracted Key:", key, "| Value:", value);

        if (key.includes("student id")) {
          student.studentid = value;
        }

        if (key.includes("name")) {
          student.name = value;
        }

        if (key.includes("middle initial")) {
          student.middleInitial = value;
        }

        if (key.includes("suffix")) {
          student.suffix = value;
        }

        if (key.includes("course")) student.course = value;
        if (key.includes("status")) {
          let cleanedStatus = value.trim().toLowerCase();
          if (cleanedStatus === "active" || cleanedStatus === "inactive") {
            student.status =
              cleanedStatus.charAt(0).toUpperCase() + cleanedStatus.slice(1);
          } else {
            student.status = "N/A";
          }
          console.log("Parsed Status:", student.status);
        }

        if (key.includes("year started")) {
          let parsedDate = new Date(value);
          if (!isNaN(parsedDate)) {
            student.yearStarted = parsedDate.getFullYear().toString();
          } else {
            alert("Invalid date format for Year Started.");
            student.yearStarted = "";
          }
        }
      }
    });

    console.log("Processed Student Data:", student);

    fetch("/qr/assets/QrScanner/save_student.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(student),
    })
      .then((response) => response.text())
      .then((data) => {
        console.log("Server Response:", data);
        alert(data);
        if (data.includes("success")) {
          alert("Student data saved successfully!");
          window.location.href = "/qr/assets/students/student_file.php";
        } else {
          alert("Error saving student data: " + data);
        }
      })
      .catch((error) => console.error("Error:", error));
  });

  editBtn.addEventListener("click", () => {
    let textarea = document.querySelector("textarea");

    if (!textarea.value.trim()) {
      alert("Scan first the QR Code!");
      return;
    }

    let qrData = encodeURIComponent(textarea.value);
    window.location.href = `edit_profile.php?data=${qrData}`;
  });

  modalCloseBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  modalSaveBtn.addEventListener("click", () => {
    let editedText = document.getElementById("editText").value;
    let textarea = document.querySelector("textarea");
    if (textarea) {
      textarea.value = editedText;
      editBtn.disabled = !textarea.value.trim();
    }
    modal.style.display = "none";
  });
});
