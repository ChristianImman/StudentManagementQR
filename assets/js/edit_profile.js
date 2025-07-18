document.addEventListener("DOMContentLoaded", () => {
  const editProfileForm = document.getElementById("editProfileForm");
  const submitBtn = document.getElementById("submitBtn");
  const modalCloseBtn = document.getElementById("modalClose");
  const modalSaveBtn = document.getElementById("modalSave");
  const cancelBtn = document.getElementById("cancelBtn");

  const modal = document.getElementById("modal");

  if (!submitBtn || !editProfileForm) {
    console.error("One or more required elements are missing!");
    return;
  }

  submitBtn.addEventListener("click", (event) => {
    event.preventDefault();

    const studentId = document.getElementById("studentId").value;
    const name = document.getElementById("name").value;
    const course = document.getElementById("course").value;
    const status = document.getElementById("status").value;
    const yearStarted = document.getElementById("yearStarted").value;

    if (!studentId || !name || !course || !status || !yearStarted) {
      alert("Please fill in all fields.");
      return;
    }

    const formData = new FormData();
    formData.append("studentid", studentId);
    formData.append("name", name);
    formData.append("course", course);
    formData.append("status", status);
    formData.append("yearStarted", yearStarted);

    console.log("Form data being sent:", Object.fromEntries(formData));

    fetch("/qr/assets/QrScanner/save_student.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((data) => {
        console.log("Server Response:", data);
        alert(data);
        if (data.includes("success")) {
          alert("Student data saved successfully!");
          window.location.href = "/assets/students/student_file.php";
        } else {
          alert("Error saving student data: " + data);
        }
      })

      .catch((error) => console.error("Error:", error));
  });

  if (modalCloseBtn) {
    modalCloseBtn.addEventListener("click", () => {
      modal.style.display = "none";
    });
  }

  if (modalSaveBtn) {
    modalSaveBtn.addEventListener("click", () => {
      modal.style.display = "none";
    });
  }

  cancelBtn.addEventListener("click", () => {
    console.log("✅ Cancel button clicked");
    window.location.href = "/qr/assets/qrscanner/qr_scanner.php";
  });
});
