document.addEventListener("DOMContentLoaded", () => {
    const wrapper = document.querySelector(".wrapper"),
        form = document.querySelector("form"),
        fileInp = form.querySelector("input"),
        infoText = form.querySelector("p"),
        editBtn = document.querySelector(".edit"), // Changed from close to edit
        saveBtn = document.querySelector(".save"),
        cameraBtn = document.querySelector(".open-camera"),
        readerDiv = document.getElementById("reader"),
        modal = document.getElementById("editModal"), // Modal for editing
        modalCloseBtn = document.getElementById("modalClose"), // Close button for modal
        modalSaveBtn = document.getElementById("modalSave"); // Save button for modal

    let html5QrCode; // Declare the QR code reader variable

    function fetchRequest(file, formData) {
        infoText.innerText = "Scanning QR CODE...";
        fetch("https://api.qrserver.com/v1/read-qr-code/", {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(result => {
            console.log("API Response:", result); // Debugging log

            if (!result || !result[0] || !result[0].symbol || !result[0].symbol[0].data) {
                infoText.innerText = "Couldn't Scan QR CODE";
                return;
            }

            let qrText = result[0].symbol[0].data; // Get the QR text data
            console.log("Scanned Text:", qrText); // Debugging log

            // Extract only the required fields
            let lines = qrText.split("\n"); // Split text into lines
            let filteredText = lines.filter(line => {
                let lowerCaseLine = line.toLowerCase();
                return (
                    lowerCaseLine.includes("studentid") ||
                    lowerCaseLine.includes("student id") ||
                    lowerCaseLine.includes("name") ||
                    lowerCaseLine.includes("course") ||
                    lowerCaseLine.includes("status") ||
                    lowerCaseLine.includes("yearstarted") ||
                    lowerCaseLine.includes("year started")
                );
            }).join("\n");

            let textarea = document.querySelector("textarea");
            if (textarea) {
                textarea.value = filteredText; // Show only filtered details
            } else {
                console.error("Textarea not found!");
            }

            form.querySelector("img").src = URL.createObjectURL(file);
            wrapper.classList.add("active");
            infoText.innerText = "QR Code Scanned Successfully!";
        })
        .catch(err => {
            console.error("QR Scan Error:", err);
            infoText.innerText = "Couldn't Scan QR CODE";
        });
    }

    fileInp.addEventListener("change", async e => {
        let file = e.target.files[0];  
        if (!file) return;
        console.log("File Selected:", file); // Debugging Log
        let formData = new FormData();
        formData.append('file', file);
        fetchRequest(file, formData);
    });

    saveBtn.addEventListener("click", () => {
        let qrData = document.querySelector("textarea").value;  
        if (!qrData) {
            alert("No QR Code data found!");
            return;
        }
    
        console.log("Raw QR Data:", qrData); // Debugging: See actual scanned data
    
        let student = {
            studentid: "",
            name: "",
            course: "",
            status: "",
            yearStarted: ""
        };
    
        qrData.split("\n").forEach(line => {
            let cleanLine = line.replace(/["{}]/g, '').trim(); // Remove unwanted characters
            console.log("Processing Line:", cleanLine); // Debugging: Log each line
            
            let match = cleanLine.match(/^([\w\s]+):\s*(.+)$/); // Match "Key: Value" with space handling
            
            if (match) {
                let key = match[1].trim().toLowerCase();
                let value = match[2].trim();
                
                console.log("Extracted Key:", key, "| Value:", value); // Debugging log
                
                if (key.includes("studentid") || key.includes("student id")) student.studentid = value;
                if (key.includes("name")) student.name = value;
                if (key.includes("course")) student.course = value;
                if (key.includes("status")) student.status = value;
                if (key.includes("yearstarted") || key.includes("year started")) {
                    let parsedDate = new Date(value);
                    if (!isNaN(parsedDate)) {
                        student.yearStarted = parsedDate.toISOString().split('T')[0]; // Convert to YYYY-MM-DD format
                    } else {
                        student.yearStarted = value; // Keep as-is if it can't be parsed
                    }
                }
            }
        });
    
        console.log("Processed Student Data:", student); // Debugging: Log final student object
    
        // Send clean data to PHP
        fetch("save_student.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(student)
        })
        .then(response => response.text())
        .then(data => {
            console.log("Server Response:", data);
            alert(data);
            if (data.includes("success")) {
                alert("Student data saved successfully!");
                window.location.href = "/qr/assets/students/student_file.php"; // Absolute path to the file
            } else {
                alert("Error saving student data: " + data);
            }
        })
        .catch(error => console.error("Error:", error));
    });

    cameraBtn.addEventListener("click", () => {
        readerDiv.style.display = "block"; // Show camera scanner
        wrapper.classList.add("scanner-active"); // Initialize scanner if not already done
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("reader");
        }

        // Request camera access
        Html5Qrcode.getCameras().then(devices => {
            if (devices.length > 0) {
                let cameraId = devices[0].id; // Select the first available camera

                html5QrCode.start(
                    cameraId,
                    {
                        fps: 10, // Frames per second
                        qrbox: { width: 250, height: 250 } // QR scanning area
                    },
                    (decodedText) => {
                        console.log("Scanned QR:", decodedText);

                        // Process scanned data
                        let lines = decodedText.split("\n");
                        let filteredText = lines.filter(line => {
                            let lowerCaseLine = line.toLowerCase();
                            return (
                                lowerCaseLine.includes("studentid") ||
                                lowerCaseLine.includes("student id") ||
                                lowerCaseLine.includes("name") ||
                                lowerCaseLine.includes("course") ||
                                lowerCaseLine.includes("status") ||
                                lowerCaseLine.includes("yearstarted") || 
                                lowerCaseLine.includes("year started")
                            );
                        }).join("\n");

                        let textarea = document.querySelector("textarea");
                        if (textarea) {
                            textarea.value = filteredText; // Update textarea with filtered text
                        } else {
                            console.error("Textarea not found!");
                        }

                        html5QrCode.stop(); // Stop scanner after successful scan
                        wrapper.classList.add("active");
                    },
                    (errorMessage) => {
                        console.warn("Scanning error:", errorMessage);
                    }
                ).catch(err => {
                    console.error("Camera error:", err);
                    alert("Camera access denied or not available.");
                });
            } else {
                alert("No cameras found.");
            }
        }).catch(err => {
            console.error("No cameras found:", err);
            alert("No cameras found.");
        });
    });

    editBtn.addEventListener("click", () => {
        let textarea = document.querySelector("textarea");
        
        // Check if the textarea is empty
        if (!textarea.value.trim()) {
            alert("Scan first the QR Code!");
            return; // Exit the function if the textarea is empty
        }
        
        // If not empty, proceed to open the modal
        document.getElementById("editText").value = textarea.value; // Populate modal with current text
        modal.style.display = "block"; // Show the modal
    });

    modalCloseBtn.addEventListener("click", () => {
        modal.style.display = "none"; // Hide the modal
    });

    modalSaveBtn.addEventListener("click", () => {
        let editedText = document.getElementById("editText").value; // Get edited text
        let textarea = document.querySelector("textarea");
        if (textarea) {
            textarea.value = editedText; // Update textarea with edited text
        }
        modal.style.display = "none"; // Hide the modal
    });
});