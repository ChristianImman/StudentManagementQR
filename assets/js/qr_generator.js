document.addEventListener("DOMContentLoaded", function() {
    // Get the modal and buttons
    var modal = document.getElementById("qrModal");
    var openModalButton = document.getElementById("qrButton");
    var closeModalSpan = document.getElementsByClassName("close")[0];
    var generateQRButton = document.getElementById("generateQRButton");
    var printQRButton = document.getElementById("printQRButton");

    // Function to open the modal
    function openModal(event) {
        event.preventDefault(); // Prevent default anchor behavior
        modal.style.display = "block"; // Show the modal
    }

    // Function to close the modal and reset contents
    function closeModal() {
        modal.style.display = "none"; // Hide the modal
        
        // Reset input fields
        document.getElementById("studentId").value = '';
        document.getElementById("firstName").value = '';
        document.getElementById("lastName").value = '';
        document.getElementById("course").value = '';
        document.getElementById("yearStarted").value = '';
        document.getElementById("status").value = 'active'; // Reset to default value

        // Clear the QR code display
        var qrcodeContainer = document.getElementById("qrcode");
        qrcodeContainer.innerHTML = ''; // Clear QR code
    }

    // Event listener for opening the modal when the "Get QR Code" link is clicked
    if (openModalButton) {
        openModalButton.addEventListener("click", openModal); // Use the openModal function
    }

    // Event listener for closing the modal when the close button is clicked
    if (closeModalSpan) {
        closeModalSpan.addEventListener("click", closeModal); // Close the modal when "X" is clicked
    }

    // When the user clicks anywhere outside of the modal, close it
    window.addEventListener("click", function(event) {
        if (event.target === modal) {
            closeModal(); // Close the modal if clicked outside
        }
    });

    // Function to generate QR code
    function generateQR() {
        var firstNameElement = document.getElementById("firstName");
        var lastNameElement = document.getElementById("lastName");
        
        var studentId = document.getElementById("studentId").value.trim();
        var firstName = firstNameElement.value.trim();
        var lastName = lastNameElement.value.trim();
        var course = document.getElementById("course").value.trim();
        var yearStarted = document.getElementById("yearStarted").value.trim();
        var status = document.getElementById("status").value.trim();
        
        // Debugging logs
        console.log("Student ID:", studentId);
        console.log("First Name:", firstName);
        console.log("Last Name:", lastName);
        console.log("Course:", course);
        console.log("Year Started:", yearStarted);
        console.log("Status:", status);
        
        if (!studentId || !firstName || !lastName || !course || !yearStarted || !status) {
            alert("Please fill in all fields before generating the QR code.");
            return;
        }
    
        var fullName = firstName + " " + lastName; // Combine first and last name
        console.log("Full Name:", fullName); // Log the full name
    
        var formattedData = "Student ID: " + studentId + "\n" +
                            "Full Name: " + fullName + "\n" +
                            "Course: " + course + "\n" +
                            "Year Started: " + yearStarted + "\n" +
                            "Status: " + status;
    
        console.log("Formatted Data for QR Code:", formattedData); // Log the formatted data
    
        var qrcodeContainer = document.getElementById("qrcode");
        qrcodeContainer.innerHTML = ""; 
        
        new QRCode(qrcodeContainer, {
            text: formattedData,
            width: 200,
            height: 200
        });
    }

    // Function to print QR code
    function printQR() {
        var printWindow = window.open('', '_blank');
 printWindow.document.write('<html><head><title>Print QR Code</title></head><body>');
        printWindow.document.write('<h2>QR Code</h2>');
        printWindow.document.write(document.getElementById("qrcode").innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }

    // Event listeners for QR code generation and printing
    if (generateQRButton) {
        generateQRButton.addEventListener("click", generateQR);
    }

    if (printQRButton) {
        printQRButton.addEventListener("click", printQR);
    }
});